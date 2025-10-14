<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'price',
        'quota',
        'status',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function scopeSelectTicket($query, $name)
    {
        return $query->where('name', $name);
    }

    public function getRemainingQuotaAttribute(): int
    {
        return $this->quota - $this->tickets()->count();
    }

    public function getSoldTicketsAttribute(): int
    {
        return $this->tickets()->count();
    }
}
