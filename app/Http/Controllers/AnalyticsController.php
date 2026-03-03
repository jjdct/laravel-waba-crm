<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    /**
     * Muestra la pantalla principal de Estadísticas
     */
    public function index(WhatsAppService $whatsAppService)
    {
        // Arrays vacíos por defecto en caso de que Meta no responda
        $conversationStats = [];
        $callStats = [];
        $errorMessage = null;

        try {
            // 1. Obtener analíticas de los últimos 30 días
            $convData = $whatsAppService->getConversationAnalytics(30);
            $callData = $whatsAppService->getCallAnalytics(30);

            // 2. Extraemos solo los arreglos de datos puros ('data_points')
            // Ojo: Meta mete la info dentro de un nodo con el mismo nombre que el endpoint
            $conversationStats = $convData['conversation_analytics']['data_points'] ?? [];
            $callStats = $callData['call_analytics']['data_points'] ?? [];

        } catch (\Exception $e) {
            // Si el token expira o Meta está caído, no rompemos la app.
            Log::error("Error cargando Analytics desde Meta: " . $e->getMessage());
            $errorMessage = "Meta dice: " . $e->getMessage();
        }

        // 3. Enviamos a Vue
        return Inertia::render('WhatsApp/Analytics', [
            'conversations' => $conversationStats,
            'calls'         => $callStats,
            'error'         => $errorMessage
        ]);
    }
}