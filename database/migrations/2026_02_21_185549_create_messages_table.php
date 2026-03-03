<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            
            $table->string('wa_id'); // Número del cliente
            $table->string('wam_id')->unique(); // ID único del mensaje de Meta
            $table->string('type'); // text, image, interactive, location, etc.
            
            $table->boolean('outgoing')->default(false); // ¿Lo enviamos nosotros o el cliente?
            $table->text('body')->nullable(); // Texto principal o resumen
            
            $table->string('media_url')->nullable(); // Ruta del archivo local en tu Chromebook
            $table->text('caption')->nullable(); // Pie de foto
            
            $table->string('status')->default('received'); // received, sent, delivered, read
            
            // ¡La columna mágica! Aquí guardamos coordenadas, IDs de botones, items de pedidos...
            $table->json('data')->nullable(); 
            
            $table->timestamps();
        });
    }
};