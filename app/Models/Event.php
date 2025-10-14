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
        'description',
        'capacity',
        'status',
    ];

    protected $casts = [
        'type' => 'array',
    ];

    public function setTypeAttribute($value)
    {
        $this->attributes['type'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function getTypeAttribute($value)
    {
        return $value ? explode(',', $value) : [];
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
}
