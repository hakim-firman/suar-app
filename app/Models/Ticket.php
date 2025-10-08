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
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketPackages()
    {
        return $this->belongsTo(TicketPackage::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }
}
