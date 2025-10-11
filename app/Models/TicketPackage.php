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
}
