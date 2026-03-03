<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $guarded = [];

    // Esto es crucial: le decimos a Laravel que 'data' es un Array/JSON
    protected $casts = [
        'outgoing' => 'boolean',
        'data' => 'array',
        'created_at' => 'datetime', 
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}