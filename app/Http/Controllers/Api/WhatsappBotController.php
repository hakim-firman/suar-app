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

    public function handleCallback(Request $request)
    {
        Log::info('Twilio Callback', [
            'sid' => $request->input('MessageSid'),
            'status' => $request->input('MessageStatus'),
            'to' => $request->input('To'),
        ]);

        return response()->json(['status' => 'ok']);
    }

    private function processMessage(string $userPhone, string $body): string
    {
        // Reset sesi
        if (in_array($body, ['cancel', 'reset', 'stop'])) {
            Cache::forget('bot_stage_' . $userPhone);
            Cache::forget('event_' . $userPhone);
            return "🔁 Your session has been reset.\nType *hi* or *help* to start again.";
        }

        $stage = Cache::get('bot_stage_' . $userPhone);
        $currentEvent = Cache::get('event_' . $userPhone);

        // Menu utama
        if (in_array($body, ['hi', 'hello', 'help', 'menu', 'back'])) {
            Cache::put('bot_stage_' . $userPhone, 'main_menu', now()->addMinutes(30));

            return "👋 *Welcome to the WhatsApp Ticket Assistant!*\n\n"
                . "Please choose an option by typing the number:\n\n"
                . "1️⃣ View Event Details\n"
                . "2️⃣ Register for an Event\n"
                . "3️⃣ Check My Current Ticket\n"
                . "4️⃣ Cancel / Reset\n\n"
                . "Type *1*, *2*, *3*, or *4* to continue.";
        }

        if ($stage === 'main_menu') {
            if ($body === '1') {
                Cache::put('bot_stage_' . $userPhone, 'view_events', now()->addMinutes(30));
                $events = Event::where('status', true)->get(['title', 'code', 'description']);

                if ($events->isEmpty()) {
                    return "❌ There are no active events right now.\n\nType *back* to return to the main menu.";
                }

                $reply = "🎫 *Active Events:*\n\n";
                foreach ($events as $ev) {
                    $reply .= "• *{$ev->title}* — Code: *{$ev->code}*\n";
                }

                return $reply . "\nType an *event code* (e.g. *EV001*) to view details, or *back* to return.";
            }

            if ($body === '2') {
                Cache::put('bot_stage_' . $userPhone, 'register_event', now()->addMinutes(30));
                return "📝 Please type the *event code* you want to register for.\nExample: *EV001*\n\nType *back* to return.";
            }

            if ($body === '3') {
                Cache::put('bot_stage_' . $userPhone, 'check_ticket_code', now()->addMinutes(30));
                return "🎟️ Please type your *ticket code* to manage your ticket.\nExample: *FEAST1234*\n\nType *back* to return.";
            }

            if ($body === '4') {
                Cache::forget('bot_stage_' . $userPhone);
                Cache::forget('event_' . $userPhone);
                return "✅ Session cleared.\nType *hi* to start again.";
            }

            return "⚠️ Invalid choice. Please type *1*, *2*, *3*, or *4*.";
        }

        if ($stage === 'view_events') {
            if ($body === 'back') {
                Cache::put('bot_stage_' . $userPhone, 'main_menu', now()->addMinutes(30));
                return "🔙 Back to main menu.\n\n1️⃣ View Event Details\n2️⃣ Register for an Event\n3️⃣ Check My Current Ticket";
            }

            $event = Event::whereRaw('LOWER(code) = ?', [strtolower($body)])
                ->where('status', true)
                ->with(['ticketPackages' => fn($q) => $q->where('status', true)])
                ->first();

            if (!$event) {
                return "❌ Event not found.\nPlease type a valid *event code* or *back* to return.";
            }

            $reply = "📢 *{$event->title}*\n{$event->description}\n\n🎟️ *Available Ticket Packages:*\n";
            foreach ($event->ticketPackages as $pkg) {
                $remaining = $pkg->quota - $pkg->tickets()->count();
                $reply .= "- *{$pkg->name}* — Rp " . number_format($pkg->price) . " — Remaining: {$remaining}\n";
            }

            return $reply . "\nType *back* to return to the event list.";
        }

        if ($stage === 'register_event' && !$currentEvent) {
            if ($body === 'back') {
                Cache::put('bot_stage_' . $userPhone, 'main_menu', now()->addMinutes(30));
                return "🔙 Back to main menu.";
            }

            $event = Event::whereRaw('LOWER(code) = ?', [strtolower($body)])
                ->where('status', true)
                ->first();

            if (!$event) {
                return "❌ Event not found.\nPlease type a valid *event code* or *back*.";
            }

            Cache::put('event_' . $userPhone, $event->id, now()->addMinutes(30));
            Cache::put('bot_stage_' . $userPhone, 'register_form', now()->addMinutes(30));

            return "✅ *{$event->title}* selected.\nNow please type:\n*register Name#Gender#Age#Job#PackageName*\nExample: *register John Doe#male#25#Engineer#VIP*\n\nType *back* to choose another event.";
        }

        if ($stage === 'register_form' && str_starts_with($body, 'register')) {
            return $this->handleRegistration($userPhone, $body, $currentEvent);
        }

        if ($stage === 'check_ticket_code') {
            if ($body === 'back') {
                Cache::put('bot_stage_' . $userPhone, 'main_menu', now()->addMinutes(30));
                return "🔙 Back to main menu.";
            }

            $ticket = Ticket::with(['event', 'ticketPackage', 'participant'])
                ->whereRaw('LOWER(code) = ?', [strtolower($body)])
                ->first();

            if (!$ticket) {
                return "❌ Ticket with code *{$body}* not found.\nPlease check again or type *back* to return.";
            }

            Cache::put('bot_stage_' . $userPhone, 'ticket_action', now()->addMinutes(30));
            Cache::put('ticket_code_' . $userPhone, $ticket->code, now()->addMinutes(30));

            $reply = "🎟️ *Ticket Found!*\n\n"
                . "👤 Name: {$ticket->participant->name}\n"
                . "Event: {$ticket->event->title}\n"
                . "Package: {$ticket->ticketPackage->name}\n"
                . "Status: *{$ticket->status}*\n\n"
                . "Please choose an action:\n"
                . "1️⃣ Cancel Ticket\n"
                . "2️⃣ View Event Details\n"
                . "3️⃣ Back to Main Menu";

            return $reply;
        }

        if ($stage === 'ticket_action') {
            $ticketCode = Cache::get('ticket_code_' . $userPhone);
            $ticket = Ticket::with(['event', 'ticketPackage', 'participant'])
                ->where('code', $ticketCode)
                ->first();

            if (!$ticket) {
                return "⚠️ Session expired or ticket not found.\nType *hi* to start again.";
            }

            if ($body === '1') {
                if ($ticket->status === 'cancelled') {
                    return "⚠️ Ticket *{$ticket->code}* is already cancelled.";
                }

                $ticket->update(['status' => 'cancelled']);
                Cache::forget('ticket_code_' . $userPhone);
                Cache::forget('bot_stage_' . $userPhone);

                return "✅ Ticket *{$ticket->code}* has been successfully cancelled.\nType *hi* to go back to the main menu.";
            }

            if ($body === '2') {
                $event = $ticket->event;
                $type = is_array($event->type) ? implode(', ', $event->type) : $event->type;
                $reply = "📢 *{$event->title}*\n"
                    . "{$event->description}\n\n"
                    . "📅 Type: {$type}\n"
                    . "🎫 Code: {$event->code}\n\n"
                    . "Type *hi* to return to the main menu.";

                return $reply;
            }

            if ($body === '3' || $body === 'back') {
                Cache::forget('ticket_code_' . $userPhone);
                Cache::put('bot_stage_' . $userPhone, 'main_menu', now()->addMinutes(30));
                return "🔙 Back to main menu.";
            }

            return "⚠️ Invalid option. Type *1*, *2*, or *3*.";
        }

        return "🤖 I didn’t understand that.\nType *hi* to restart or *back* to go one step back.";
    }

    private function handleRegistration(string $userPhone, string $body, ?int $eventId): string
    {
        if (!$eventId) {
            return "⚠️ You haven’t selected an event yet.\nType *hi* to start again.";
        }

        $parts = explode('#', str_replace('register ', '', $body));
        if (count($parts) < 5) {
            return "⚠️ Invalid format.\nUse:\n*register Name#Gender#Age#Job#PackageName*";
        }

        [$name, $genderRaw, $age, $job, $packageName] = array_map('trim', $parts);

        $event = Event::with('ticketPackages.tickets')->find($eventId);
        if (!$event) {
            Cache::forget('event_' . $userPhone);
            return "❌ Event not found.";
        }

        $package = TicketPackage::whereRaw('LOWER(name) = ?', [strtolower($packageName)])
            ->where('event_id', $eventId)
            ->first();

        if (!$package) {
            return "❌ Ticket package *{$packageName}* not found.";
        }

        $soldTicketsForPackage = $package->tickets()->count();
        if ($soldTicketsForPackage >= $package->quota) {
            return "🚫 Sorry, *{$package->name}* tickets are sold out.";
        }

        $totalEventTickets = $event->ticketPackages->sum(fn($p) => $p->tickets->count());
        if ($totalEventTickets >= $event->capacity) {
            if ($event->status !== false) {
                $event->update(['status' => false]);
            }
            return "🚫 Sorry, *{$event->title}* has reached full capacity and is now closed for registration.";
        }

        $genderMap = ['male' => 'male', 'm' => 'male', 'female' => 'female', 'f' => 'female'];
        $gender = $genderMap[strtolower($genderRaw)] ?? null;
        if (!$gender) {
            return "⚠️ Invalid gender. Use *male* or *female*.";
        }

        $existingTicket = Ticket::whereHas('participant', function ($q) use ($name) {
            $q->whereRaw('LOWER(name) = ?', [strtolower($name)]);
        })
            ->where('event_id', $event->id)
            ->exists();

        if ($existingTicket) {
            return "⚠️ Participant *{$name}* already has a ticket for *{$event->title}*.";
        }

        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $event->title), 0, 6));
        $random = rand(1000, 9999);
        $ticketCode = "{$prefix}{$random}";

        DB::transaction(function () use ($userPhone, $name, $gender, $age, $job, $event, $package, $ticketCode) {
            $participant = Participant::firstOrCreate(
                ['name' => ucwords($name), 'gender' => $gender, 'age' => (int)$age],
                ['job' => ucfirst($job), 'address' => '-']
            );

            Ticket::create([
                'code' => $ticketCode,
                'participant_id' => $participant->id,
                'event_id' => $event->id,
                'ticket_package_id' => $package->id,
                'status' => 'booked',
                'phone' => $userPhone,
            ]);
        });

        $totalNow = $event->ticketPackages->sum(fn($p) => $p->tickets()->count());
        if ($totalNow >= $event->capacity && $event->status !== false) {
            $event->update(['status' => false]);
        }

        Cache::forget('event_' . $userPhone);
        Cache::forget('bot_stage_' . $userPhone);

        return "✅ *Booking Successful!*\n\n"
            . "👤 Name: {$name}\n"
            . "🎟️ Ticket Code: *{$ticketCode}*\n"
            . "Package: {$package->name}\n"
            . "Event: {$event->title}\n"
            . "📌 Status: *BOOKED*\n\n"
            . "Please proceed with payment to confirm your ticket.\n"
            . "Type *hi* to return to the main menu.";
    }
}
