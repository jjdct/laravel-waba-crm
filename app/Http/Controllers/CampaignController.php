<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Jobs\SendMessage;

class CampaignController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function index()
{
    $error = null;
    $templates = [];

    try {
        // El Service ahora busca el token en la BD (MariaDB) gracias al modelo WhatsappConfig
        $response = $this->whatsAppService->loadTemplates();
        $templates = $response['data'] ?? [];
    } catch (\Exception $e) {
        // Capturamos el error para que Vue lo muestre en la barra roja
        $error = "Error de conexión con Meta: " . $e->getMessage();
    }

    return Inertia::render('WhatsApp/Campaigns', [
        'templates' => $templates,
        'error' => $error
    ]);
}

    public function send(Request $request)
    {
        $input = $request->validate([
            'template_name' => 'required',
            'template_language' => 'required',
            'recipients' => 'required',
            'header_placeholder' => 'nullable|string', // Para el {{1}} del Header
            'body_placeholders' => 'nullable|array',   // Para los {{n}} del Body
        ]);

        $template = $this->whatsAppService->loadTemplateByName(
            $input['template_name'], 
            $input['template_language']
        );

        if (!$template) return back()->with('error', 'Plantilla no encontrada.');

        $templateHeader = '';
        $templateBody = '';
        $templateFooter = '';
        $templateButtons = [];

        foreach ($template['components'] as $component) {
            if ($component['type'] == 'HEADER') $templateHeader = $component['text'] ?? '';
            if ($component['type'] == 'BODY') $templateBody = $component['text'];
            if ($component['type'] == 'FOOTER') $templateFooter = $component['text'];
            if ($component['type'] == 'BUTTONS') $templateButtons = $component['buttons'];
        }

        // Estructura para Meta
        $payload = [
            'messaging_product' => 'whatsapp',
            'type' => 'template',
            'template' => [
                'name' => $input['template_name'],
                'language' => ['code' => $input['template_language']],
                'components' => []
            ],
        ];

        // 1. Procesar Header Dinámico
        $finalHeader = $templateHeader;
        if (!empty($input['header_placeholder'])) {
            $payload['template']['components'][] = [
                'type' => 'header',
                'parameters' => [['type' => 'text', 'text' => $input['header_placeholder']]]
            ];
            $finalHeader = str_replace('{{1}}', $input['header_placeholder'], $templateHeader);
        }

        // 2. Procesar Body Dinámico
        $finalBody = $templateBody;
        if (!empty($input['body_placeholders'])) {
            $bodyParams = [];
            foreach ($input['body_placeholders'] as $key => $val) {
                $bodyParams[] = ['type' => 'text', 'text' => $val];
                $finalBody = str_replace('{{'.($key + 1).'}}', $val, $finalBody);
            }
            $payload['template']['components'][] = [
                'type' => 'body',
                'parameters' => $bodyParams
            ];
        }

        $extraData = [
            'header_text' => $finalHeader,
            'footer' => $templateFooter,
            'buttons' => $templateButtons
        ];

        $recipients = explode("\n", str_replace("\r", "", $input['recipients']));
        foreach ($recipients as $recipient) {
            $phone = trim(filter_var($recipient, FILTER_SANITIZE_NUMBER_INT));
            if ($phone) {
                $p = $payload;
                $p['to'] = $phone;
                SendMessage::dispatch($p, $finalBody, $extraData);
            }
        }

        return back()->with('success', 'Mensajes encolados correctamente.');
    }
}