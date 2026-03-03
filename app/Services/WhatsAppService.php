<?php

namespace App\Services;

use App\Models\WhatsappConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsAppService
{
    private string $baseUrl = 'https://graph.facebook.com/v25.0';
    private ?string $accessToken = null;
    private ?string $phoneId = null;
    private ?string $wabaId = null;
    private ?string $appId = null;
    private ?string $appSecret = null;

    public function __construct()
    {
        try {
            // Intentamos obtener la configuración de la DB
            $config = \App\Models\WhatsappConfig::first();

            if ($config && $config->access_token) {
                $this->accessToken = \Illuminate\Support\Facades\Crypt::decryptString($config->access_token);
                $this->phoneId = $config->phone_id;
                $this->wabaId = $config->waba_id;
                
                // Estos pueden seguir en el .env o también a la DB después
                $this->appId = env('ID_APP_META_APIWB', ''); 
                $this->appSecret = env('SECRET_APP_META_APIWB', '');
            }
        } catch (\Exception $e) {
            // Si la DB está caída, el servicio simplemente no carga las credenciales
            // pero NO lanza un error 500. Se queda en silencio.
            \Illuminate\Support\Facades\Log::warning("WhatsAppService: No se pudo cargar la configuración (DB fuera de línea).");
            
            // Opcional: Fallback al .env para emergencias
            $this->accessToken = env('TOKEN_ACCESO_APP_META_APIWB');
            $this->phoneId = env('ID_NUMERO_WHATSAPP_BUSINESS');
        }
    }

    // ==========================================
    // 1. ENVÍO DE MENSAJES
    // ==========================================

    public function sendText(string $to, string $text): array
    {
        return $this->genericPayload([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text,
            ],
        ]);
    }

    // Tipos soportados: 'audio', 'document', 'image', 'video', 'sticker'
    public function sendMedia(string $to, string $type, string $mediaIdOrUrl, bool $isId = true, ?string $caption = null): array
    {
        $mediaData = [
            $isId ? 'id' : 'link' => $mediaIdOrUrl
        ];

        if ($caption && in_array($type, ['image', 'video', 'document'])) {
            $mediaData['caption'] = $caption;
        }

        return $this->genericPayload([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => $type,
            $type => $mediaData,
        ]);
    }

    public function sendButtons(string $to, string $bodyText, array $buttons, string $headerText = '', string $footerText = ''): array
    {
        $formattedButtons = [];
        foreach ($buttons as $id => $title) {
            $formattedButtons[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => (string) $id,
                    'title' => substr($title, 0, 20) // Meta limita a 20 caracteres
                ]
            ];
        }

        $interactive = [
            'type' => 'button',
            'body' => ['text' => $bodyText],
            'action' => ['buttons' => $formattedButtons]
        ];

        if ($headerText) $interactive['header'] = ['type' => 'text', 'text' => $headerText];
        if ($footerText) $interactive['footer'] = ['text' => $footerText];

        return $this->genericPayload([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => $interactive,
        ]);
    }

    public function sendReaction(string $to, string $messageId, string $emoji): array
    {
        return $this->genericPayload([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'reaction',
            'reaction' => [
                'message_id' => $messageId,
                'emoji' => $emoji, // Para quitarla, se manda vacío ''
            ],
        ]);
    }

    public function sendLocation(string $to, string $lat, string $lng, string $name = '', string $address = ''): array
    {
        $locationData = [
            'latitude' => $lat,
            'longitude' => $lng,
        ];

        if ($name) $locationData['name'] = $name;
        if ($address) $locationData['address'] = $address;

        return $this->genericPayload([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'location',
            'location' => $locationData,
        ]);
    }

    // ==========================================
    // 2. GESTIÓN DE MULTIMEDIA (Archivos)
    // ==========================================

    public function downloadMedia(string $mediaId): ?string
    {
        try {
            // 1. Pedir la URL del archivo
            $media = Http::withToken($this->accessToken)
                ->get("{$this->baseUrl}/{$mediaId}")
                ->throw()->json();

            if (empty($media['url'])) return null;

            // 2. Descargar los bytes (¡AQUÍ CORREGIMOS EL HEADER!)
            $fileResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->accessToken
                ])
                ->get($media['url']);

            // Validar que la descarga fue exitosa (a veces Meta tarda en procesar el archivo)
            if (!$fileResponse->successful()) {
                \Illuminate\Support\Facades\Log::error("Error descargando archivo de Meta: HTTP " . $fileResponse->status());
                return null;
            }

            $content = $fileResponse->body();

            // 3. Obtener la extensión
            $mimeType = $media['mime_type'] ?? 'application/octet-stream';
            $ext = $this->mimeToExt($mimeType);
            
            // 4. Guardar en Storage
            $fileName = \Illuminate\Support\Str::uuid()->toString() . '.' . $ext;
            
            // 🛡️ MAGIA APLICADA: Guardamos en el disco privado ('local') en lugar del público ('public')
            \Illuminate\Support\Facades\Storage::disk('local')->put("whatsapp_media/{$fileName}", $content);

            return "whatsapp_media/{$fileName}"; // Devuelve la ruta relativa para el <img src="">
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Excepción en downloadMedia: " . $e->getMessage());
            return null;
        }
    }

    public function uploadMedia(string $localFilePath): ?string
    {
        // 🔒 Ajuste para archivos subidos desde el CRM (asumimos que ahora también irán al disco local privado)
        $fullPath = storage_path('app/whatsapp_media/' . basename($localFilePath));

        if (!file_exists($fullPath)) {
            // Si no está en privado, intentamos buscar en público por retrocompatibilidad
            $fullPath = storage_path('app/public/' . ltrim($localFilePath, '/'));
            if (!file_exists($fullPath)) {
                throw new \Exception("El archivo no existe: {$fullPath}");
            }
        }

        $response = Http::withToken($this->accessToken)
            ->attach('file', file_get_contents($fullPath), basename($fullPath))
            ->post("{$this->baseUrl}/{$this->phoneId}/media", [
                'messaging_product' => 'whatsapp'
            ])
            ->throw()->json();

        return $response['id'] ?? null;
    }

    // ==========================================
    // 3. GESTIÓN DEL PERFIL DE EMPRESA
    // ==========================================

    public function getProfile(): array
    {
        return Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/{$this->phoneId}/whatsapp_business_profile", [
                'fields' => 'about,address,description,email,profile_picture_url,websites,vertical'
            ])
            ->throw()->json();
    }

    public function updateProfile(array $data): array
    {
        $data['messaging_product'] = 'whatsapp';
        
        return Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/{$this->phoneId}/whatsapp_business_profile", $data)
            ->throw()->json();
    }

    // La función que vence la burocracia mentirosa de Meta xd
    public function uploadProfilePictureHandle(string $localFilePath): string
    {
        // 🔒 Ajuste de retrocompatibilidad
        $fullPath = storage_path('app/whatsapp_media/' . basename($localFilePath));
        if (!file_exists($fullPath)) {
            $fullPath = storage_path('app/public/' . ltrim($localFilePath, '/'));
            if (!file_exists($fullPath)) {
                throw new \Exception("La imagen de perfil no existe: {$fullPath}");
            }
        }

        $fileSize = filesize($fullPath);
        $mimeType = mime_content_type($fullPath) ?: 'image/jpeg';
        $fileContent = file_get_contents($fullPath);

        // ¡TU GRAN DESCUBRIMIENTO! Creamos el App Access Token (ID|Secreto)
        $appAccessToken = "{$this->appId}|{$this->appSecret}";

        // PASO 1: Crear la sesión (Mandando parámetros por URL y usando el Token de App)
        $sessionUrl = "{$this->baseUrl}/{$this->appId}/uploads?file_length={$fileSize}&file_type={$mimeType}";
        
        $session = Http::withToken($appAccessToken)
            ->post($sessionUrl)
            ->throw()->json();

        $sessionId = $session['id'];

        // PASO 2: Subir los bytes (Usando el Token de App y el header maldito)
        $upload = Http::withHeaders([
                'Authorization' => 'OAuth ' . $appAccessToken, // Meta es quisquilloso con esto
                'file_offset'   => '0' 
            ])
            ->withBody($fileContent, $mimeType)
            ->post("{$this->baseUrl}/{$sessionId}")
            ->throw()->json();

        return $upload['h']; // ¡El ID invisible que tanto nos costó!
    }

    /**
    * Obtiene la información técnica del número (Calidad, Webhook, etc.)
    */
    public function getNumberInfo()
    {
        return Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/{$this->phoneId}")
            ->throw()
            ->json();
    }

    /**
    * Cambia el nombre visible (Verified Name)
    * Nota: Esto suele requerir revisión de Meta
    */
    public function updateDisplayName(string $newName)
    {
        return Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/{$this->phoneId}", [
                'new_display_name' => $newName
            ])
            ->throw()
            ->json();
    }

    // ==========================================
    // 4. MÉTODOS DE APOYO (Plantillas, Utilidades)
    // ==========================================

    public function loadTemplates(): array
    {
        return Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/{$this->wabaId}/message_templates", [
                'limit' => 250
            ])->throw()->json();
    }

    public function loadTemplateByName(string $name, string $language): ?array
    {
        $templates = $this->loadTemplates();
        return collect($templates['data'] ?? [])
            ->firstWhere(fn ($template) => $template['name'] === $name && $template['language'] === $language);
    }

    public function genericPayload(array $payload): array
    {
        return Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/{$this->phoneId}/messages", $payload)
            ->throw()->json();
    }

    private function mimeToExt(string $mime): string
    {
        $mime_map = [
            'video/3gpp2' => '3g2',
            'video/3gp' => '3gp',
            'video/3gpp' => '3gp',
            'audio/x-acc' => 'aac',
            'image/bmp' => 'bmp',
            'text/css' => 'css',
            'text/csv' => 'csv',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'image/gif' => 'gif',
            'text/html' => 'html',
            'image/jpeg' => 'jpeg',
            'image/pjpeg' => 'jpeg',
            'application/json' => 'json',
            'audio/mp4' => 'm4a',
            'audio/mpeg' => 'mp3',
            'audio/mp3' => 'mp3',
            'video/mp4' => 'mp4',
            'audio/ogg' => 'ogg',
            'video/ogg' => 'ogg',
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/rtf' => 'rtf',
            'image/svg+xml' => 'svg',
            'text/plain' => 'txt',
            'audio/wav' => 'wav',
            'video/webm' => 'webm',
            'image/webp' => 'webp',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/xml' => 'xml',
            'application/zip' => 'zip',
        ];

        return $mime_map[$mime] ?? 'bin';
    }

    /**
     * Envía peticiones genéricas a la API de Meta (especialmente para llamadas).
     */
    public function postToMeta(string $endpoint, array $data): array
    {
        return Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/{$this->phoneId}/{$endpoint}", $data)
            ->throw()
            ->json();
    }

    // ==========================================
    // 5. ESTADÍSTICAS Y ANALÍTICAS (NUEVO)
    // ==========================================

    /**
     * Obtiene el volumen y costo de las conversaciones
     */
    public function getConversationAnalytics(int $days = 30): array
    {
        $start = now()->subDays($days)->startOfDay()->timestamp;
        $end = now()->endOfDay()->timestamp;

        // SOLUCIÓN: Usamos 'CONVERSATION_TYPE' tal como lo exigió el error de Meta
        $fields = "conversation_analytics.start({$start}).end({$end}).granularity(DAILY).dimensions(['CONVERSATION_TYPE'])";

        return Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/{$this->wabaId}", [
                'fields' => $fields
            ])
            ->throw()
            ->json();
    }

    /**
     * Obtiene métricas de éxito de un arreglo de Plantillas
     */
    public function getTemplateAnalytics(array $templateIds, int $days = 30): array
    {
        if (empty($templateIds)) {
            return [];
        }

        $start = now()->subDays($days)->startOfDay()->timestamp;
        $end = now()->endOfDay()->timestamp;

        $jsonIds = '[' . implode(',', $templateIds) . ']';

        return Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/{$this->wabaId}/template_analytics", [
                'start' => $start,
                'end' => $end,
                'granularity' => 'DAILY',
                'metric_types' => 'COST,CLICKED,DELIVERED,READ,SENT',
                'template_ids' => $jsonIds
            ])
            ->throw()
            ->json();
    }

    /**
     * Obtiene estadísticas de llamadas
     */
    public function getCallAnalytics(int $days = 30): array
    {
        $start = now()->subDays($days)->startOfDay()->timestamp;
        $end = now()->endOfDay()->timestamp;

        // Para evitar otro berrinche de Meta, simplificamos esta llamada. 
        // Al omitir las dimensiones, Meta devolverá TODO por defecto (que es lo que queremos).
        $fields = "call_analytics.start({$start}).end({$end}).granularity(DAILY)";

        return Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/{$this->wabaId}", [
                'fields' => $fields
            ])
            ->throw()
            ->json();
    }

    // ==========================================
    // PUENTES PARA EL CONTROLADOR (RESILIENCIA)
    // ==========================================

    /**
     * Alias de getProfile para mantener compatibilidad con el controlador
     */
    public function getBusinessProfile(): array
    {
        return $this->getProfile();
    }

    /**
     * Obtiene info técnica y añade los IDs que el controlador necesita para la vista
     */
    public function getAccountInfo(): array
    {
        $info = $this->getNumberInfo();

        return [
            'info' => $info,
            'phone_id' => $this->phoneId,
            'waba_id' => $this->wabaId
        ];
    }

    // ==========================================
    // CONFIGURACIÓN AVANZADA Y REGISTRO
    // ==========================================

    public function registerNumber(string $pin, ?string $region = null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'pin' => $pin,
        ];

        if ($region) {
            $payload['data_localization_region'] = $region;
        }

        return Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/{$this->phoneId}/register", $payload)
            ->throw()->json();
    }

    public function setNoStorage(int $retention = 60): array
    {
        return Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/{$this->phoneId}/settings", [
                'storage_configuration' => [
                    'status' => 'NO_STORAGE_ENABLED',
                    'retention_minutes' => $retention
                ]
            ])->throw()->json();
    }

    // MÉTODOS DE ESTADO Y DESCONEXIÓN
    // ==========================================

    /**
     * Consulta la configuración actual de almacenamiento en Meta
     */
    public function getStorageStatus(): array
    {
        return Http::withToken($this->accessToken)
            ->get("{$this->baseUrl}/{$this->phoneId}/settings")
            ->throw()
            ->json();
    }

    /**
     * Anula el registro del número (Lo desconecta del CRM)
     */
    public function deregisterNumber(): array
    {
        return Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/{$this->phoneId}/deregister")
            ->throw()
            ->json();
    }

    /**
     * Desactiva el almacenamiento local (Requiere estar desconectado)
     */
    public function disableLocalStorage(): array
    {
        return Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/{$this->phoneId}/settings", [
                'storage_configuration' => [
                    'status' => 'IN_COUNTRY_STORAGE_DISABLED'
                ]
            ])
            ->throw()
            ->json();
    }

    // ==========================================
    // 6. GESTIÓN DE WEBHOOKS (OVERRIDE A NIVEL DE PHONE ID)
    // ==========================================

    /**
     * Configura el Webhook a nivel de Número de Teléfono (Override).
     * Esto hace que Meta envíe los mensajes de este número a la URL indicada,
     * ignorando la configuración global de la cuenta (WABA).
     */
    public function updateWebhook(string $callbackUrl, string $verifyToken): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withToken($this->accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->asJson() // Obligamos a Laravel a enviar el cuerpo como JSON crudo
                ->post("{$this->baseUrl}/{$this->phoneId}", [
                    'webhook_configuration' => [
                        'override_callback_uri' => $callbackUrl,
                        'verify_token' => $verifyToken
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return isset($data['success']) && $data['success'] === true;
            }

            // Si falla, ahora sí veremos exactamente por qué se queja Meta
            \Illuminate\Support\Facades\Log::error("Error al actualizar Webhook Override: " . $response->body());
            return false;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Excepción en updateWebhook: " . $e->getMessage());
            return false;
        }
    }
}