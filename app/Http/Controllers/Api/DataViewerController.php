<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Contact;
use Illuminate\Http\Request;

class DataViewerController extends Controller
{
    // Ver todos los mensajes con su JSON 'data'
    public function getMessages()
    {
        $messages = \App\Models\Message::with('conversation.contact')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();

        // Esto le dice a Laravel: "No busques vistas, no busques a Inertia, solo escupe JSON"
        return response()->json($messages, 200, [], JSON_PRETTY_PRINT);
    }

    // Ver un mensaje específico para inspeccionar el JSON a fondo
    public function showMessage($id)
    {
        $message = Message::find($id);
        
        if (!$message) {
            return response()->json(['error' => 'Mensaje no encontrado'], 404);
        }

        return response()->json([
            'id' => $message->id,
            'wam_id' => $message->wam_id,
            'tipo' => $message->type,
            'contenido_extra' => $message->data, // Aquí verás las reacciones, coordenadas, etc.
            'crudo' => $message // El objeto completo
        ]);
    }
}