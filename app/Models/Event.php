<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'description',
        'type',
        'capaticy',
        'status',
    ];

    public function ticketPackages()
    {
        return $this->hasMany(TicketPackage::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
