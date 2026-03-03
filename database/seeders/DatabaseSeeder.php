<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Tu cuenta principal (Admin)
        User::factory()->create([
            'name' => 'Miku Peluche',
            'email' => 'admin@whatsapp.local',
            'password' => Hash::make('admin123'), 
            'role' => 'admin',
        ]);

        // Cuenta del Agente
        User::factory()->create([
            'name' => 'Agente Soporte',
            'email' => 'agente@whatsapp.local',
            'password' => Hash::make('agente123'),
            'role' => 'agente',
        ]);
    }
}
