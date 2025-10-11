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
     * Webhook utama - pesan masuk dari Twilio
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
            Log::info('Incoming message', ['from' => $userPhone, 'body' => $body]);

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
     * Callback status dari Twilio
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
     * Logic utama chatbot
     */
    private function processMessage(string $userPhone, string $body): string
    {
        // Reset sesi
        if (in_array($body, ['batal', 'cancel'])) {
            Cache::forget('event_' . $userPhone);
            return "Sesi Anda dibatalkan.\nKetik kode event untuk memulai kembali.";
        }

        // Cek apakah user sedang dalam sesi event
        $currentEvent = Cache::get('event_' . $userPhone);

        // 1️⃣ Pesan pertama atau belum pilih event
        if (!$currentEvent) {
            // Jika user mengetik kode event
            $event = Event::where('code', strtoupper($body))
                ->where('status', true)
                ->with(['ticketPackages' => fn($q) => $q->where('status', true)])
                ->first();

            if (!$event) {
                return "👋 Halo! Selamat datang di *Sistem Tiket WhatsApp*.\n\n"
                    . "Silakan masukkan *kode event* untuk melihat detailnya.\n\n"
                    . "Contoh: *EV001*";
            }

            // Simpan sesi event
            Cache::put('event_' . $userPhone, $event->id, now()->addMinutes(30));

            $reply = "📢 *{$event->title}*\n"
                . "{$event->description}\n\n"
                . "🎟️ *Daftar Paket Tiket:*\n";

            foreach ($event->ticketPackages as $pkg) {
                $remaining = $pkg->quota - $pkg->tickets()->count();
                $reply .= "- *{$pkg->name}* (Rp " . number_format($pkg->price) . ") — Sisa: {$remaining}\n";
            }

            $reply .= "\nUntuk memesan, kirim dengan format:\n"
                . "*daftar [Nama]#[Gender]#[Umur]#[Pekerjaan]#[Nama Paket]*\n"
                . "Contoh: *daftar Budi#laki#25#Programmer#VIP*";

            return $reply;
        }

        // 2️⃣ Sudah memilih event → cek apakah format daftar
        if (str_starts_with($body, 'daftar')) {
            return $this->handleRegistration($userPhone, $body, $currentEvent);
        }

        return "Perintah tidak dikenali.\nKetik *batal* untuk membatalkan sesi atau masukkan *kode event* lain.";
    }

    /**
     * Handle registrasi peserta dan pembuatan tiket
     */
    private function handleRegistration(string $userPhone, string $body, int $eventId): string
    {
        $parts = explode('#', str_replace('daftar ', '', $body));

        if (count($parts) < 5) {
            return "❗ Format salah.\nGunakan format:\n"
                . "*daftar Nama#Gender#Umur#Pekerjaan#Nama Paket*";
        }

        [$name, $genderRaw, $age, $job, $packageName] = array_map('trim', $parts);

        $event = Event::with('ticketPackages')->find($eventId);
        if (!$event) {
            Cache::forget('event_' . $userPhone);
            return "Event tidak ditemukan. Silakan ketik ulang *kode event*.";
        }

        $package = $event->ticketPackages
            ->firstWhere('name', 'ilike', $packageName); // support case-insensitive

        if (!$package) {
            return "Paket *{$packageName}* tidak ditemukan untuk event ini.\n"
                . "Silakan periksa kembali nama paketnya.";
        }

        // Kuota
        $sold = Ticket::where('ticket_package_id', $package->id)->count();
        if ($sold >= $package->quota) {
            return "Maaf, kuota paket *{$package->name}* sudah habis.";
        }

        $genderMap = [
            'laki' => 'male', 'pria' => 'male', 'laki-laki' => 'male',
            'wanita' => 'female', 'perempuan' => 'female',
        ];
        $gender = $genderMap[strtolower($genderRaw)] ?? null;
        if (!$gender) return "Gender tidak valid. Gunakan 'laki' atau 'perempuan'.";

        // Buat tiket dalam transaksi
        $ticketResult = null;

        DB::transaction(function () use ($userPhone, $name, $gender, $age, $job, $event, $package, &$ticketResult) {
            $participant = Participant::firstOrCreate(
                ['name' => ucwords($name), 'age' => (int)$age, 'gender' => $gender],
                ['job' => ucfirst($job), 'address' => '-']
            );

            $ticketResult = Ticket::create([
                'participant_id' => $participant->id,
                'event_id' => $event->id,
                'ticket_package_id' => $package->id,
                'status' => 'booked',
                'phone' => $userPhone,
            ]);
        });

        // Clear session
        Cache::forget('event_' . $userPhone);

        return "✅ *Pemesanan Berhasil!*\n\n"
            . "Nama: {$name}\n"
            . "Event: {$event->title}\n"
            . "Paket: {$package->name}\n"
            . "Status: *BOOKED*\n\n"
            . "Silakan lanjutkan pembayaran untuk mengubah status menjadi *PAID*.\n"
            . "Ketik *kode event* lain untuk memesan tiket baru.";
    }
}
