<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsAppService;
use App\Events\MessageReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Responde al "Challenge" de Meta (Método GET)
     */
    public function verify(Request $request)
    {
        // Buscamos el token de verificación en la base de datos
        $config = \App\Models\WhatsappConfig::first();
        $verifyToken = $config ? $config->webhook_verify_token : null;
    
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken && $verifyToken !== null) {
            // Meta exige texto plano crudo
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning("Intento de validación de Webhook fallido. Token recibido: " . $token);
        return response()->json(['error' => 'Invalid token'], 403);
    }

    /**
     * Procesa los mensajes entrantes de Meta (Método POST)
     */
    public function process(Request $request, WhatsAppService $whatsAppService)
    {
        // 🛡️ 1. BLINDAJE CRIPTOGRÁFICO: Verificar que el mensaje viene de Meta
        $signature = $request->header('X-Hub-Signature-256');
        $appSecret = env('SECRET_APP_META_APIWB'); // Necesitamos este dato en tu archivo .env
        
        if (!$signature || !$appSecret) {
            Log::warning("Webhook rechazado: Falta la firma de Meta o el META_APP_SECRET no está en el .env.");
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Calculamos la firma matemáticamente usando todo el JSON crudo y tu secreto
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning("Webhook rechazado: Firma criptográfica inválida. Posible ataque interceptado.");
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // 🚀 2. SI PASA LA SEGURIDAD, PROCESAMOS EL MENSAJE
        try {
            $payload = $request->all();

            if (empty($payload['entry'][0]['changes'][0]['value'])) {
                return response()->json(['status' => 'ignored'], 200);
            }

            $value = $payload['entry'][0]['changes'][0]['value'];

            // A. Actualización de estado (Lectura, Entrega, etc.)
            if (!empty($value['statuses'])) {
                $statusData = $value['statuses'][0];
                Message::where('wam_id', $statusData['id'])->update([
                    'status' => $statusData['status']
                ]);
            } 
            
            // B. Errores de Meta
            elseif (!empty($value['errors'])) {
                $errorData = $value['errors'][0];
                Log::error("Meta API Error [{$errorData['code']}]: {$errorData['message']}");
            } 

            // C. 📞 LLAMADAS ENTRANTES (Receptivo)
            elseif (!empty($value['calls'])) {
                $callData = $value['calls'][0];
                $waId = $callData['from'];
                $callId = $callData['id'];
                $event = $callData['event']; // 'connect' o 'terminate'

                $contact = Contact::firstOrCreate(
                    ['phone' => $waId],
                    ['name' => 'Desconocido (Llamada)']
                );

                $conversation = Conversation::firstOrCreate(
                    ['contact_id' => $contact->id, 'status' => 'abierto']
                );

                if (!Message::where('wam_id', $callId . '_' . $event)->exists()) {
                    $body = $event === 'terminate' 
                        ? "📞 Llamada finalizada (Duración: " . ($callData['duration'] ?? 0) . " seg)" 
                        : "📞 Llamada entrante...";

                    $nuevoMensaje = Message::create([
                        'conversation_id' => $conversation->id,
                        'wa_id'           => $waId,
                        'wam_id'          => $callId . '_' . $event, 
                        'type'            => 'call',
                        'outgoing'        => false,
                        'body'            => $body,
                        'status'          => 'received',
                        'data'            => $callData, 
                        'created_at'      => Carbon::createFromTimestamp($callData['timestamp']),
                    ]);

                    broadcast(new \App\Events\MessageReceived($nuevoMensaje));
                }
            }
            
            // D. Mensajes de Chat (Texto, Multimedia, Órdenes, Botones)
            elseif (!empty($value['messages'])) {
                $messageData = $value['messages'][0];
                $waId = $messageData['from'];
                $wamId = $messageData['id'];
                $type = $messageData['type'];

                $contactName = $value['contacts'][0]['profile']['name'] ?? 'Desconocido';
                $contact = Contact::updateOrCreate(
                    ['phone' => $waId],
                    ['name' => $contactName]
                );

                $conversation = Conversation::firstOrCreate(
                    ['contact_id' => $contact->id, 'status' => 'abierto']
                );

                $conversation->update([
                    'last_message_at' => Carbon::createFromTimestamp($messageData['timestamp'])
                ]);

                if (!Message::where('wam_id', $wamId)->exists()) {
                    
                    $body = '';
                    $caption = null;
                    $mediaUrl = null;
                    $extraData = [];

                    if (isset($messageData['context'])) $extraData['context'] = $messageData['context'];
                    if (isset($messageData['referral'])) $extraData['referral'] = $messageData['referral'];

                    switch ($type) {
                        case 'text':
                            $body = $messageData['text']['body'];
                            break;

                        case 'audio':
                        case 'document':
                        case 'image':
                        case 'video':
                        case 'sticker':
                            $mediaId = $messageData[$type]['id'];
                            $mediaUrl = $whatsAppService->downloadMedia($mediaId);
                            $caption = $messageData[$type]['caption'] ?? null;
                            
                            if ($type === 'audio' && isset($messageData['audio']['voice'])) {
                                $extraData['is_voice_note'] = $messageData['audio']['voice'];
                            }
                            break;

                        case 'location':
                            $locName = $messageData['location']['name'] ?? 'Ubicación enviada';
                            $body = "📍 " . $locName;
                            $extraData['location_data'] = $messageData['location']; 
                            break;

                        case 'reaction':
                            $body = $messageData['reaction']['emoji'] ?? ''; 
                            $extraData['reaction_to_message_id'] = $messageData['reaction']['message_id'];
                            break;

                        case 'interactive':
                            $interactiveType = $messageData['interactive']['type'];
                            if ($interactiveType === 'button_reply') {
                                $body = $messageData['interactive']['button_reply']['title'];
                                $extraData['interactive_id'] = $messageData['interactive']['button_reply']['id'];
                            } elseif ($interactiveType === 'list_reply') {
                                $body = $messageData['interactive']['list_reply']['title'];
                                $extraData['interactive_id'] = $messageData['interactive']['list_reply']['id'];
                            }
                            break;

                        case 'contacts':
                            $contactNameData = $messageData['contacts'][0]['name']['formatted_name'] ?? 'Contacto compartido';
                            $body = "👤 " . $contactNameData;
                            $extraData['contacts_data'] = $messageData['contacts'];
                            break;

                        case 'button':
                            $body = $messageData['button']['text'];
                            $extraData['button_payload'] = $messageData['button']['payload'];
                            break;

                        case 'order':
                            $body = "🛒 Nuevo Pedido: " . ($messageData['order']['text'] ?? 'Sin descripción');
                            $extraData['order_data'] = $messageData['order'];
                            break;

                        default:
                            $body = "[Tipo no mapeado: {$type}]";
                            break;
                    }

                    $nuevoMensaje = Message::create([
                        'conversation_id' => $conversation->id,
                        'wa_id'           => $waId,
                        'wam_id'          => $wamId,
                        'type'            => $type,
                        'outgoing'        => false,
                        'body'            => $body,
                        'media_url'       => $mediaUrl,
                        'caption'         => $caption,
                        'status'          => 'received',
                        'data'            => $extraData,
                        'created_at'      => Carbon::createFromTimestamp($messageData['timestamp']),
                    ]);

                    broadcast(new \App\Events\MessageReceived($nuevoMensaje));
                }
            }

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::error('Error en Webhook: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Internal logic error'], 200);
        }
    }
}