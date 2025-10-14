<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'code',
        'type',
        'description',
        'capacity',
        'status',
    ];

    public function setTypeAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['type'] = implode(',', $value);
        } else {
            $this->attributes['type'] = $value;
        }
    }

    public function getTypeAttribute($value)
    {
        if (empty($value)) {
            return [];
        }
        return array_map('trim', explode(',', $value));
    }

    public function ticketPackages()
    {
        return $this->hasMany(TicketPackage::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function getTotalTicketsSoldAttribute(): int
    {
        return $this->ticketPackages()
            ->withCount('tickets')
            ->get()
            ->sum('tickets_count');
    }

    public function participants()
    {
        return $this->belongsToMany(
            Participant::class,
            'tickets',
            'event_id',
            'participant_id'
        );
    }
}
