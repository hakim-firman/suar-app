<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Ticket;
use App\Models\TicketPackage;

class WhatsappBotController extends Controller
{
    /**
     * Twilio Webhook Entry Point
     */
    public function handleWebhook(Request $request)
    {
        try {
            $from = $request->input('From');
            $body = strtolower(trim($request->input('Body')));

            if ($from == env('TWILIO_WHATSAPP_FROM')) {
                return response()->json(['status' => 'ignored']);
            }

            $userPhone = str_replace('whatsapp:', '', $from);
            Log::info('Incoming WhatsApp message', ['from' => $userPhone, 'body' => $body]);

            $reply = $this->processMessage($userPhone, $body);

            $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
            $twilio->messages->create($from, [
                'from' => env('TWILIO_WHATSAPP_FROM'),
                'body' => $reply,
                'statusCallback' => url('/api/whatsapp/callback'),
            ]);

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error', ['message' => $e->getMessage()]);
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Callback status from Twilio
     */
    public function handleCallback(Request $request)
    {
        Log::info('Twilio Callback', [
            'sid' => $request->input('MessageSid'),
            'status' => $request->input('MessageStatus'),
            'to' => $request->input('To'),
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Main bot logic
     */
    private function processMessage(string $userPhone, string $body): string
    {
        // Reset user session
        if (in_array($body, ['cancel', 'reset', 'stop'])) {
            Cache::forget('event_' . $userPhone);
            return "🔁 Your session has been reset.\nType *help* or *hi* to start again.";
        }

        $currentEvent = Cache::get('event_' . $userPhone);

        if (in_array($body, ['hi', 'hello', 'help', 'menu']) && !$currentEvent) {
            $events = Event::where('status', true)->get(['id', 'title', 'code', 'description']);
            if ($events->isEmpty()) {
                return "❌ No active events available right now.";
            }

            $reply = "👋 *Welcome to the WhatsApp Ticket Assistant!*\n\n"
                . "Here are the currently available events:\n\n";

            foreach ($events as $ev) {
                $reply .= "🎫 *{$ev->title}* — Code: *{$ev->code}*\n";
            }

            $reply .= "\nPlease type the *event code* (e.g. *EV001*) to view ticket options.";
            return $reply;
        }

        if (!$currentEvent) {
            $event = Event::whereRaw('LOWER(code) = ?', [strtolower($body)])
                ->where('status', true)
                ->with(['ticketPackages' => fn($q) => $q->where('status', true)])
                ->first();

            if (!$event) {
                return "❌ Event not found.\nPlease type a valid *event code* or type *help* to see the list.";
            }

            Cache::put('event_' . $userPhone, $event->id, now()->addMinutes(30));

            $reply = "📢 *{$event->title}*\n"
                . "{$event->description}\n\n"
                . "🎟️ *Available Ticket Packages:*\n";

            foreach ($event->ticketPackages as $pkg) {
                $remaining = $pkg->quota - $pkg->tickets()->count();
                $reply .= "- *{$pkg->name}* — Rp " . number_format($pkg->price) . " — Remaining: {$remaining}\n";
            }

            $reply .= "\nTo book, please reply in this format:\n"
                . "*register Name#Gender#Age#Job#PackageName*\n"
                . "Example: *register John Doe#male#25#Engineer#VIP*";

            return $reply;
        }

        if (str_starts_with($body, 'register')) {
            return $this->handleRegistration($userPhone, $body, $currentEvent);
        }

        return "🤖 I didn’t understand that.\nType *help* to restart or *cancel* to reset your session.";
    }

    /**
     * Handle ticket registration
     */
    private function handleRegistration(string $userPhone, string $body, int $eventId): string
    {
        $parts = explode('#', str_replace('register ', '', $body));

        if (count($parts) < 5) {
            return "⚠️ Invalid format.\nUse:\n"
                . "*register Name#Gender#Age#Job#PackageName*";
        }

        [$name, $genderRaw, $age, $job, $packageName] = array_map('trim', $parts);

        $event = Event::with('ticketPackages')->find($eventId);
        if (!$event) {
            Cache::forget('event_' . $userPhone);
            return "❌ Event not found. Please start again.";
        }

        $package = TicketPackage::whereRaw('LOWER(name) = ?', [strtolower($packageName)])
            ->where('event_id', $eventId)
            ->first();

        if (!$package) {
            return "❌ Ticket package *{$packageName}* not found for this event.";
        }

        // Check quota
        $sold = Ticket::where('ticket_package_id', $package->id)->count();
        if ($sold >= $package->quota) {
            return "🚫 Sorry, *{$package->name}* tickets are sold out.";
        }

        $genderMap = [
            'male' => 'male', 'man' => 'male', 'm' => 'male',
            'female' => 'female', 'woman' => 'female', 'f' => 'female',
        ];
        $gender = $genderMap[strtolower($genderRaw)] ?? null;
        if (!$gender) {
            return "⚠️ Invalid gender. Use *male* or *female*.";
        }

        $ticket = null;

        DB::transaction(function () use ($userPhone, $name, $gender, $age, $job, $event, $package, &$ticket) {
            $participant = Participant::firstOrCreate(
                ['name' => ucwords($name), 'gender' => $gender, 'age' => (int)$age],
                ['job' => ucfirst($job), 'address' => '-']
            );

            $ticket = Ticket::create([
                'participant_id' => $participant->id,
                'event_id' => $event->id,
                'ticket_package_id' => $package->id,
                'status' => 'booked',
                'phone' => $userPhone,
            ]);
        });

        Cache::forget('event_' . $userPhone);

        return "✅ *Booking Successful!*\n\n"
            . "👤 Name: {$name}\n"
            . "🎟️ Package: {$package->name}\n"
            . "📅 Event: {$event->title}\n"
            . "📞 Phone: {$userPhone}\n"
            . "📌 Status: *BOOKED*\n\n"
            . "Please proceed with the payment to confirm your ticket.\n"
            . "Type *help* to view other available events.";
    }
}
