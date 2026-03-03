<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('whatsapp_configs', function (Blueprint $table) {
        $table->id();
        $table->text('access_token'); // Lo guardaremos cifrado
        $table->string('waba_id');
        $table->string('phone_id');
        $table->string('webhook_verify_token');
        $table->string('storage_mode')->default('normal'); // normal, local, none
        $table->string('data_region')->nullable(); // CH, EU, BR
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_configs');
    }
};
