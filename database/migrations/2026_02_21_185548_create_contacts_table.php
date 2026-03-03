<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            // El número de teléfono con el código de país (wa_id de Meta)
            $table->string('phone')->unique(); 
            // El nombre de perfil que nos manda WhatsApp
            $table->string('name')->nullable(); 
            $table->timestamps();
        });
    }
};