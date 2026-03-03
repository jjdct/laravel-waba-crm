<x-mail::message>
# Hola, {{ $user->name }}

Te enviamos este correo para confirmar que la contraseña de tu cuenta en **{{ config('app.name') }}** ha sido actualizada exitosamente.

Si fuiste tú quien realizó este cambio, no necesitas hacer nada más. Puedes seguir usando el sistema con total normalidad.

<x-mail::button :url="route('dashboard')">
Ir al CRM
</x-mail::button>

**⚠️ Aviso de Seguridad:**
Si no solicitaste este cambio ni actualizaste tu contraseña recientemente, por favor contacta a un administrador inmediatamente, ya que tu cuenta podría estar comprometida.

Saludos,<br>
El equipo de {{ config('app.name') }}
</x-mail::message>