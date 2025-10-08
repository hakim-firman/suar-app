<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
