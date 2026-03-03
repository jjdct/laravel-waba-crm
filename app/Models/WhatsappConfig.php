<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappConfig extends Model
{
    // Laravel buscará la tabla "whatsapp_configs" por defecto
    protected $fillable = [
        'access_token',
        'waba_id',
        'phone_id',
        'webhook_verify_token',
        'storage_mode',
        'data_region',
    ];

    // Esto oculta el token cifrado si alguna vez haces un return del modelo directo a Vue
    protected $hidden = [
        'access_token',
    ];
}