<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncMetaWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $callbackUrl;
    public $verifyToken;
    public $phoneId;
    public $accessToken;

    /**
     * Le pasamos al Job todo lo que necesita para trabajar solo.
     */
    public function __construct($callbackUrl, $verifyToken, $phoneId, $accessToken)
    {
        $this->callbackUrl = $callbackUrl;
        $this->verifyToken = $verifyToken;
        $this->phoneId = $phoneId;
        $this->accessToken = $accessToken;
    }

    /**
     * Aquí ocurre la magia en segundo plano.
     */
    public function handle(): void
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->asJson()
                ->post("https://graph.facebook.com/v25.0/{$this->phoneId}", [
                    'webhook_configuration' => [
                        'override_callback_uri' => $this->callbackUrl,
                        'verify_token' => $this->verifyToken
                    ]
                ]);

            if ($response->successful()) {
                Log::info("¡Webhook Override sincronizado con éxito desde el Job en segundo plano!");
            } else {
                Log::error("Meta rechazó el Webhook en el Job: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Error crítico en SyncMetaWebhook: " . $e->getMessage());
        }
    }
}