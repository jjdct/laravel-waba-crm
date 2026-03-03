<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallController extends Controller
{
    /**
     * Acepta una llamada entrante enviando el SDP de respuesta a Meta.
     */
    public function acceptCall(Request $request, \App\Models\Conversation $conversation, \App\Services\WhatsAppService $service)
    {
        $request->validate([
            'sdp' => 'required|string',
            'call_id' => 'required|string',
        ]);

        try {
            // Estructura EXACTA sacada de la documentación oficial
            $response = $service->postToMeta('calls', [
                'messaging_product' => 'whatsapp',
                'call_id' => $request->call_id,
                'action' => 'accept', 
                'session' => [
                    'sdp_type' => 'answer',
                    'sdp' => $request->sdp,
                ],
            ]);

            return response()->json([
                'success' => true,
                'meta_response' => $response
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error al aceptar llamada: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function rejectCall(Request $request, \App\Models\Conversation $conversation, \App\Services\WhatsAppService $service)
    {
        $request->validate([
            'call_id' => 'required|string',
        ]);

        try {
            // Le decimos a Meta: "Corta la llamada, no vamos a contestar"
            $response = $service->postToMeta('calls', [
                'messaging_product' => 'whatsapp',
                'call_id' => $request->call_id,
                'action' => 'reject', 
            ]);

            return response()->json([
                'success' => true,
                'meta_response' => $response
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error al rechazar llamada: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}