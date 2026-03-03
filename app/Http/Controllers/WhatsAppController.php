<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsAppController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Muestra el Perfil de Empresa (About, Address, Description, etc.)
     */
    public function businessIndex()
{
    $error = null;
    $profile = null;
    $businessName = 'Tu Empresa'; // Valor por defecto

    try {
        // 1. Cargamos el perfil (about, address, etc.)
        $profileResponse = $this->whatsAppService->getBusinessProfile();
        $profile = $profileResponse['data'][0] ?? null;

        // 2. Cargamos el nombre desde el endpoint de la cuenta/número
        $accountResponse = $this->whatsAppService->getAccountInfo();
        $businessName = $accountResponse['info']['verified_name'] ?? 'Tu Empresa';

    } catch (\Exception $e) {
        $error = "No se pudo sincronizar con Meta: " . $e->getMessage();
    }

    return Inertia::render('WhatsApp/BusinessProfile', [
        'profile' => $profile,
        'businessName' => $businessName, // Pasamos el nombre rescatado
        'error' => $error
    ]);
}

    /**
     * Muestra la Configuración técnica de la Cuenta
     */
    /**
     * Muestra la Configuración técnica de la Cuenta
     */
    public function accountIndex()
    {
        try {
            // El Service ya nos devuelve la info de Meta + phone_id + waba_id desde la DB
            $accountData = $this->whatsAppService->getAccountInfo();

            return Inertia::render('WhatsApp/AccountConfig', [
                'account' => $accountData
            ]);
        } catch (\Exception $e) {
            return Inertia::render('WhatsApp/AccountConfig', [
                'account' => null,
                'error' => 'No se pudo obtener la info de Meta: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Cambiar el Nombre Verificado (Verified Name)
     */
    public function updateDisplayName(Request $request)
    {
        $request->validate([
            'new_name' => 'required|string|min:3|max:60'
        ]);

        try {
            $this->whatsAppService->updateDisplayName($request->new_name);
            return back()->with('success', 'Solicitud de cambio de nombre enviada a Meta.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al cambiar nombre: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza los datos del perfil en Meta
     */
    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'about'       => 'nullable|string|max:139',
            'address'     => 'nullable|string|max:256',
            'description' => 'nullable|string|max:512',
            'email'       => 'nullable|email',
            'vertical'    => 'nullable|string',
            'websites'    => 'nullable|array',
            'profile_picture_handle' => 'nullable|string'
        ]);

        // Limpiamos los links vacíos antes de enviar a Meta
        if (isset($data['websites'])) {
            $data['websites'] = array_filter($data['websites']);
        }

        try {
            $this->whatsAppService->updateProfile($data);
            return back()->with('success', 'Perfil actualizado en WhatsApp.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function getAvatarHandle(Request $request)
    {
        $request->validate(['avatar' => 'required|image|max:4096']); // Max 4MB

        // Guardar temporalmente (esto devuelve una ruta relativa como 'temp/nombre.jpg')
        $path = $request->file('avatar')->store('temp', 'public');

        try {
            // Le pasamos solo la ruta relativa ($path) porque el Service ya le agrega el storage_path()
            $handle = $this->whatsAppService->uploadProfilePictureHandle($path);
            
            return response()->json(['success' => true, 'handle' => $handle]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}