<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;

// --- VISTA PÚBLICA ---
Route::get('/', function () {
    // Verificamos si ya existe al menos un usuario en la base de datos
    $adminExists = \App\Models\User::exists();

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'hasAdmin' => $adminExists, // ¡La llave mágica para Vue!
    ]);
});

Route::get('/creditos', function () {
    return Inertia::render('Credits');
})->name('credits');

Route::get('/up', [SettingsController::class, 'systemStatus'])->name('SystemStatus');

// --- RUTAS PROTEGIDAS (AUTH) ---
Route::middleware(['auth', 'verified'])->group(function () {

    // 1. ZONA ESTABLE (Dashboard)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/send/{conversation}', [DashboardController::class, 'sendMessage'])->name('dashboard.send');
    Route::post('/dashboard/assign/{conversation}', [DashboardController::class, 'assign'])->name('dashboard.assign');

    // 2. ZONA DE LABORATORIO (Chat Experimental)
    // Aquí es donde probarás WebRTC y las nuevas funciones de voz
    Route::get('/chat', [DashboardController::class, 'chat'])->name('chat');
    Route::post('/chat/send/{conversation}', [DashboardController::class, 'sendMessage'])->name('chat.send');
    Route::post('/chat/assign/{conversation}', [DashboardController::class, 'assign'])->name('chat.assign');
    
    // Ruta maestra para aceptar llamadas de Meta
    Route::post('/chat/call/accept/{conversation}', [CallController::class, 'acceptCall'])->name('chat.call.accept');
    Route::post('/chat/call/reject/{conversation}', [CallController::class, 'rejectCall'])->name('chat.call.reject');

    // 🔒 3. LA BÓVEDA MULTIMEDIA (Archivos Protegidos)
    Route::get('/media/secure/{path}', function ($path) {
        $filePath = 'whatsapp_media/' . $path;

        // Verificamos que el archivo exista en el disco privado 'local'
        if (!Storage::disk('local')->exists($filePath)) {
            abort(404, 'Archivo no encontrado');
        }

        // Devolvemos el archivo directamente al navegador
        return Storage::disk('local')->response($filePath);
    })->where('path', '.*')->name('media.secure');

    // --- ADMINISTRACIÓN (Solo para administradores) ---
    Route::middleware('can:access-admin')->group(function () {
        // Perfil de Empresa
        Route::get('/business', [WhatsAppController::class, 'businessIndex'])->name('business');
        Route::post('/business', [WhatsAppController::class, 'updateProfile'])->name('business.update');
        Route::post('/business/avatar', [WhatsAppController::class, 'updateAvatar'])->name('business.updateAvatar');
        
        // Cuenta de WhatsApp
        Route::get('/whatsapp', [WhatsAppController::class, 'accountIndex'])->name('whatsapp');
        Route::post('/whatsapp/display-name', [WhatsAppController::class, 'updateDisplayName'])->name('whatsapp.updateName');
        Route::post('/whatsapp/get-avatar-handle', [WhatsAppController::class, 'getAvatarHandle'])->name('business.getAvatarHandle');
        
        // Gestión de Usuarios/Agentes
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');

        Route::get('/settings', function () {
            // Bloqueo de seguridad manual (por si acaso xd)
            if (auth()->user()->role !== 'admin') {
                abort(403, 'No tienes acceso al Búnker Maestro.');
            }

            return Inertia::render('Admin/Settings');
        })->name('admin.settings');
        
        Route::post('/settings/webhook-override', [SettingsController::class, 'updateWebhookOverride'])->name('admin.settings.webhook.override');
        
        // Analíticas
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

        // --- Rutas del Búnker de Configuración ---
        
        // 1. Ver la página
        Route::get('/admin/settings', [SettingsController::class, 'index'])->name('admin.settings');

        // 2. Guardar IDs y Token (Cifrado)
        Route::post('/admin/settings/config', [SettingsController::class, 'updateConfig'])->name('admin.settings.update');

        // 3. Acciones de Meta (Register / Deregister)
        Route::post('/admin/settings/action', [SettingsController::class, 'handleAction'])->name('admin.settings.action');
    });

    // --- CAMPAÑAS ---
    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::post('/campaigns/send', [CampaignController::class, 'send'])->name('campaigns.send');

    // --- PERFIL DE AGENTE (Breeze) ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';