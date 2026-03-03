<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;
    protected $body;
    protected $extraData;

    /**
     * @param array $payload  Datos formateados para la API de Meta
     * @param string $body    Texto renderizado (con variables aplicadas)
     * @param array $extraData Footer, botones y metadatos
     */
    public function __construct($payload, $body, $extraData = [])
    {
        $this->payload = $payload;
        $this->body = $body;
        $this->extraData = $extraData;
    }

    public function handle(WhatsAppService $service): void
    {
        try {
            // CORRECCIÓN AQUÍ: Usamos genericPayload() igual que en tu Service
            $response = $service->genericPayload($this->payload);
            
            $wamId = $response['messages'][0]['id'] ?? null;

            if (!$wamId) {
                throw new \Exception("Meta no devolvió un ID de mensaje válido.");
            }

            // 2. Localizar al contacto por su número (wa_id)
            $waId = $this->payload['to'];
            $contact = Contact::firstOrCreate(
                ['phone' => $waId],
                ['name' => 'Contacto de Campaña']
            );

            // 3. Obtener o crear la conversación (ventana de 24h)
            $conversation = Conversation::firstOrCreate(
                ['contact_id' => $contact->id, 'status' => 'abierto']
            );

            $conversation->update(['last_message_at' => Carbon::now()]);

            // 4. Registrar el mensaje en la DB local
            $nuevoMensaje = Message::create([
                'conversation_id' => $conversation->id,
                'wa_id'           => $waId,
                'wam_id'          => $wamId,
                'type'            => $this->payload['type'],
                'outgoing'        => true,
                'body'            => $this->body,
                'status'          => 'sent',
                'data'            => $this->extraData, // Coincide con tu columna JSON
                'created_at'      => Carbon::now(),
            ]);

            // OPCIONAL PERO RECOMENDADO: 
            // Si quieres que el mensaje de campaña aparezca mágicamente en tu 
            // Dashboard sin recargar la página, puedes descomentar esta línea:
            // broadcast(new \App\Events\MessageReceived($nuevoMensaje));

        } catch (\Exception $e) {
            Log::error("Error en Job SendMessage [{$this->payload['to']}]: " . $e->getMessage());
        }
    }
}