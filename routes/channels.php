<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('waba.chat', function ($user) {
    return true; // Todos los admins autenticados pueden escuchar
});