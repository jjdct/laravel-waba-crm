<x-mail::message>
# ¡Bienvenido a {{ config('app.name') }}, {{ $user->name }}!

Tu cuenta de administrador/agente ha sido creada exitosamente. Ahora eres parte del equipo.

Tus credenciales de acceso temporal son:
- **Correo:** {{ $user->email }}
- **Contraseña:** {{ $password }}

Por motivos de seguridad, te recomendamos cambiar esta contraseña una vez que inicies sesión.

<x-mail::button :url="route('login')">
Iniciar Sesión Ahora
</x-mail::button>

Saludos,<br>
El equipo de {{ config('app.name') }}
</x-mail::message>