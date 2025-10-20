<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'event_id',
        'ticket_package_id',
        'participant_id',
        'status',
        'phone',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketPackage()
    {
        return $this->belongsTo(TicketPackage::class, 'ticket_package_id');
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    protected static function booted()
    {
        static::created(function ($ticket) {
            Log::info('Ticket created', ['code' => $ticket->code, 'status' => $ticket->status]);
        });

        static::updated(function ($ticket) {
            if ($ticket->isDirty('status')) {
                Log::info('Ticket status changed', [
                    'code' => $ticket->code,
                    'old_status' => $ticket->getOriginal('status'),
                    'new_status' => $ticket->status,
                ]);
            }
        });
    }
}
