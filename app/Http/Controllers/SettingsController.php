<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use App\Models\WhatsappConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function __construct(protected WhatsAppService $whatsAppService) {}

    /**
     * Carga la vista del Búnker con los datos actuales
     */
    /**
     * Carga la vista del Búnker con los datos actuales y el estado en vivo de Meta
     */
    public function index()
    {
        $config = WhatsappConfig::first();
        $webhookInfo = null;

        // Solo intentamos consultar a Meta si ya tenemos credenciales guardadas
        if ($config && $config->access_token) {
            try {
                $numberInfo = $this->whatsAppService->getNumberInfo();
                
                // Si Meta nos devuelve la configuración del webhook, la extraemos
                if (isset($numberInfo['webhook_configuration'])) {
                    $webhookInfo = [
                        // phone_number es el override específico de este número (El que nos interesa)
                        'phone_number' => $numberInfo['webhook_configuration']['phone_number'] ?? null,
                        // application es el global de la App (por si no hay override)
                        'application' => $numberInfo['webhook_configuration']['application'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Búnker: No se pudo obtener la info del webhook en vivo. " . $e->getMessage());
                // Si falla, webhookInfo se queda en null y la vista sigue cargando perfectamente
            }
        }

        return Inertia::render('Admin/Settings', [
            'config' => $config ? [
                'waba_id' => $config->waba_id,
                'phone_id' => $config->phone_id,
                'webhook_verify_token' => $config->webhook_verify_token,
                'storage_mode' => $config->storage_mode,
                'data_region' => $config->data_region,
                // No enviamos el token real a la vista por seguridad
                'meta_token' => $config->access_token ? '••••••••••••••••' : '',
            ] : null,
            // Enviamos la info en vivo a Vue
            'live_webhook' => $webhookInfo
        ]);
    }

    /**
     * Guarda la configuración técnica (IDs y Token cifrado)
     */
    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'meta_token' => 'required|string',
            'waba_id' => 'required|string',
            'phone_id' => 'required|string',
            'webhook_verify_token' => 'required|string',
        ]);

        $config = WhatsappConfig::updateOrCreate(
            ['id' => 1],
            [
                'access_token' => Crypt::encryptString($validated['meta_token']),
                'waba_id' => $validated['waba_id'],
                'phone_id' => $validated['phone_id'],
                'webhook_verify_token' => $validated['webhook_verify_token'],
            ]
        );

        return back()->with('success', 'Configuración maestra actualizada.');
    }

    /**
     * Ejecuta el registro o desconexión (Deregister) del número
     */
    public function handleAction(Request $request)
    {
        try {
            if ($request->action === 'deregister') {
                $this->whatsAppService->deregisterNumber(); //
                return back()->with('success', 'Número desconectado de Meta.');
            }
            
            if ($request->action === 'register') {
                $this->whatsAppService->registerNumber($request->pin, $request->region); //
                return back()->with('success', 'Número registrado exitosamente.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error en Meta: ' . $e->getMessage());
        }
    }

    public function systemStatus()
{
    // 1. Valores por defecto (Estado: Desconocido/Error)
    $dbStatus = 'error';
    $waStatus = 'error';
    $waMessage = 'Error crítico: No se pudo conectar a la base de datos.';
    
    // 2. Test de Base de Datos (SIN modelos, directo al PDO)
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $dbStatus = 'ok';
    } catch (\Exception $e) {
        $dbStatus = 'error';
        // Aquí no morimos, solo guardamos el estado
    }

    // 3. Test de Meta API (Solo si la DB está OK)
    if ($dbStatus === 'ok') {
        try {
            $config = \App\Models\WhatsappConfig::first();
            
            if (!$config || !$config->access_token) {
                $waStatus = 'warning';
                $waMessage = 'Faltan credenciales en el Búnker.';
            } else {
                $token = \Illuminate\Support\Facades\Crypt::decryptString($config->access_token);
                $response = \Illuminate\Support\Facades\Http::withToken($token)
                    ->get("https://graph.facebook.com/v25.0/{$config->waba_id}");

                if ($response->successful()) {
                    $waStatus = 'ok';
                    $waMessage = 'Conectado correctamente.';
                } else {
                    $waStatus = 'error';
                    $waMessage = 'Token inválido o expirado.';
                }
            }
        } catch (\Exception $e) {
            $waStatus = 'error';
            $waMessage = 'Error al leer configuración: ' . $e->getMessage();
        }
    }

    // 4. Test de Reverb y Storage (Independientes de la DB)
    $reverbStatus = 'error';
    try {
        $connection = @fsockopen(config('broadcasting.connections.reverb.options.host', '0.0.0.0'), 8080, $errno, $errstr, 1);
        if ($connection) {
            $reverbStatus = 'ok';
            fclose($connection);
        } else {
            $reverbStatus = 'warning';
        }
    } catch (\Exception $e) { $reverbStatus = 'error'; }

    $storageStatus = is_writable(storage_path('app/public')) ? 'ok' : 'error';

    // 5. Renderizar SIEMPRE
    return \Inertia\Inertia::render('SystemStatus', [
        'systemStatus' => [
            'database' => $dbStatus,
            'whatsapp' => [
                'status' => $waStatus,
                'message' => $waMessage,
            ],
            'reverb' => $reverbStatus,
            'storage' => $storageStatus,
            'timestamp' => now()->toIso8601String(),
        ]
    ]);
}

/**
     * Sincroniza el Webhook a nivel de Phone ID (Override) con Meta
     */
    public function updateWebhookOverride(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'callback_url' => 'required|url',
        ]);

        try {
            $config = \App\Models\WhatsappConfig::first();
            
            if (!$config || empty($config->webhook_verify_token) || empty($config->access_token)) {
                return back()->with('error', 'Faltan credenciales maestras o el Verify Token.');
            }

            // Desencriptamos el token para pasárselo al Job
            $realToken = \Illuminate\Support\Facades\Crypt::decryptString($config->access_token);

            // ¡AQUÍ ESTÁ LA MAGIA! Despachamos el trabajo a la cola y no esperamos respuesta.
            \App\Jobs\SyncMetaWebhook::dispatch(
                $request->callback_url,
                $config->webhook_verify_token,
                $config->phone_id,
                $realToken
            );

            // Respondemos al usuario AL INSTANTE, liberando el servidor para Meta
            return back()->with('success', '¡Solicitud enviada a la cola! Revisa los logs en unos segundos.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }
}