<?php

namespace App\Models;

use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'gender',
        'age',
        'job',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function events()
    {
        return $this->belongsToMany(
            Event::class,
            'tickets',       
            'participant_id',
            'event_id'       
        );
    }
}
