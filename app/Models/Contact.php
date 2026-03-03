<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $guarded = []; // Permitimos asignación masiva para todo

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}