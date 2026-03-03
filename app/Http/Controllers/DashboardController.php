<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Muestra la interfaz ESTABLE (Dashboard)
     */
    public function index(Request $request)
    {
        $data = $this->getChatData($request);
        return Inertia::render('Dashboard', $data);
    }

    /**
     * Muestra la interfaz EXPERIMENTAL (Chat con WebRTC)
     */
    public function chat(Request $request)
    {
        $data = $this->getChatData($request);
        return Inertia::render('Chat', $data);
    }

    /**
     * Lógica compartida para obtener conversaciones y mensajes.
     * Centralizamos esto para que ambos mundos reciban la misma info.
     */
    private function getChatData(Request $request)
    {
        $user = auth()->user();

        // 1. Cargamos las conversaciones con su contacto y su usuario (agente)
        $conversationsQuery = Conversation::with(['contact', 'user'])
            ->orderBy('last_message_at', 'desc');

        // 2. FILTRO DE SEGURIDAD MULTI-AGENTE
        if (!$user->can('access-admin')) {
            $conversationsQuery->where(function($q) use ($user) {
                $q->where('user_id', $user->id)    // Mis chats
                  ->orWhereNull('user_id');        // Chats nuevos/sin asignar
            });
        }

        $activeConversation = null;
        $messages = [];

        if ($request->has('chat')) {
            $activeConversation = Conversation::with(['contact', 'user'])->find($request->chat);
        
            if ($activeConversation) {
                $messages = Message::where('conversation_id', $activeConversation->id)
                    ->orderBy('created_at', 'asc')
                    ->get();
            }
        }

        return [
            'conversations' => $conversationsQuery->get(),
            'activeConversation' => $activeConversation,
            'messages' => $messages,
        ];
    }

    /**
     * Procesa el envío manual (Texto, Multimedia, Ubicación, Botones, Reacciones)
     */
    public function sendMessage(Request $request, Conversation $conversation, WhatsAppService $whatsAppService)
    {
        // 1. Bloqueo de las 24 horas
        $lastMessageTime = Carbon::parse($conversation->last_message_at);
        if ($lastMessageTime->diffInHours(now()) >= 24) {
            return back()->with('error', 'La ventana de 24 horas se ha cerrado.');
        }

        // 2. Validación Expandida
        $request->validate([
            'type'       => 'required|string|in:text,image,document,audio,video,sticker,location,interactive,reaction',
            'body'       => 'nullable|string',
            'media'      => 'nullable|file|max:16384', 
            'lat'        => 'nullable|numeric', // Para ubicación
            'lng'        => 'nullable|numeric', // Para ubicación
            'buttons'    => 'nullable|array',   // Para botones
            'message_id' => 'nullable|string',  // Para reacciones
            'emoji'      => 'nullable|string',  // Para reacciones
        ]);

        $contact = $conversation->contact;
        $type = $request->type;
        $bodyOrCaption = $request->body;
        $messageData = null; // Aquí guardaremos la metadata extra en la DB

        // 🛡️ EL ESCUDO ANTI-ERRORES DE META 🛡️
        if (in_array($type, ['sticker', 'audio'])) {
            $bodyOrCaption = null;
        }

        try {
            $wamId = null;
            $localMediaPath = null;

            // 3. Enrutador de lógicas según el tipo
            if ($type === 'text') {
                if (empty($bodyOrCaption)) return back()->with('error', 'El mensaje está vacío.');
                $response = $whatsAppService->sendText($contact->phone, $bodyOrCaption);
                $wamId = $response['messages'][0]['id'] ?? null;
            
            } elseif ($type === 'location') {
                // Enviar Ubicación
                $response = $whatsAppService->sendLocation($contact->phone, (string)$request->lat, (string)$request->lng, "Ubicación", "Punto enviado por el agente");
                $wamId = $response['messages'][0]['id'] ?? null;
                $bodyOrCaption = "📍 Ubicación enviada"; // Lo que querías ver visualmente
                $messageData = ['location_data' => ['latitude' => $request->lat, 'longitude' => $request->lng]];
            
            } elseif ($type === 'interactive') {
                // Enviar Botones
                $response = $whatsAppService->sendButtons($contact->phone, $bodyOrCaption, $request->buttons);
                $wamId = $response['messages'][0]['id'] ?? null;
                $messageData = ['interactive' => ['type' => 'button']];
            
            } elseif ($type === 'reaction') {
                // Enviar Reacción
                $response = $whatsAppService->sendReaction($contact->phone, $request->message_id, $request->emoji);
                $wamId = $response['messages'][0]['id'] ?? null;
                $bodyOrCaption = $request->emoji;
                $messageData = ['reaction_to_message_id' => $request->message_id];

            } else {
                // Multimedia (Imágenes, audios, documentos)
                if (!$request->hasFile('media')) return back()->with('error', 'Falta el archivo adjunto.');

                $path = $request->file('media')->store('whatsapp_media', 'public');
                $localMediaPath = $path;
                
                $mediaId = $whatsAppService->uploadMedia($path);
                if (!$mediaId) throw new \Exception("Meta no generó el ID del archivo.");

                $response = $whatsAppService->sendMedia($contact->phone, $type, $mediaId, true, $bodyOrCaption);
                $wamId = $response['messages'][0]['id'] ?? null;
            }

            // 4. Guardar en nuestra DB local
            if ($wamId) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'wa_id'           => $contact->phone,
                    'wam_id'          => $wamId,
                    'type'            => $type,
                    'outgoing'        => true,
                    'body'            => $bodyOrCaption,
                    'media_url'       => $localMediaPath,
                    'data'            => $messageData, // Guardamos los datos técnicos aquí
                    'status'          => 'sent',
                    'created_at'      => now(),
                ]);
            }

            return back(); 

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error enviando mensaje a {$contact->phone}: " . $e->getMessage());
            return back()->with('error', 'Fallo al enviar el mensaje: ' . $e->getMessage());
        }
    }

    /**
     * Asigna el chat al usuario actual
     */
    public function assign(Conversation $conversation)
    {
        $conversation->update(['user_id' => auth()->id()]);
        return back();
    }
}