<x-mail::message>
# ¡Felicidades por instalar {{ config('app.name') }}!

¡Hola, **{{ $user->name }}**! El sistema ya está desplegado y listo para operar. 

{{-- Tu frase personalizada --}}
Estamos muy emocionados de que uses nuestro software. Antes de comenzar a enviar mensajes masivos o gestionar chats, te recomendamos realizar las configuraciones iniciales.

<x-mail::button :url="route('business')">
Configurar Perfil de Empresa
</x-mail::button>

### 🛠️ Lista de verificación inicial:
* **Conectar WhatsApp:** Ingresa los IDs de tu aplicación de Meta en la sección de configuración.
* **Crear tu equipo:** Invita a tus agentes desde el Directorio del Equipo.
* **Verificar Webhooks:** Asegúrate de que Meta pueda enviar mensajes a tu CRM.

Si tienes alguna duda durante la configuración, recuerda que siempre puedes consultar el monitor del sistema en la ruta `/up`.

Saludos,<br>
El equipo de **{{ config('app.name') }}**
</x-mail::message>