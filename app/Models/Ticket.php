<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
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
}
