<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'code',
        'type',
        'description',
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
        return $this->tickets()
            ->where('status', 'booked')
            ->get()
            ->Count();
    }

    public function getCapacityAttribute(): int
    {
        return $this->ticketPackages()->sum('quota');
    }

    public function getIsFullAttribute(): bool
    {
        return $this->ticketPackages()->sum('remaining_quota') <= 0;
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
