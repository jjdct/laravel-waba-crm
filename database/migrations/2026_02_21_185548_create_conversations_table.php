<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            // El agente asignado al chat. Si es NULL, el chat está "Sin Asignar"
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            // Para saber si el chat requiere atención o ya lo cerraste
            $table->enum('status', ['abierto', 'resuelto'])->default('abierto'); 
            // VITAL para calcular la ventana de 24 horas de Meta
            $table->timestamp('last_message_at')->nullable(); 
            $table->timestamps();
        });
    }
};