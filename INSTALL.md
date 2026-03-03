# WABA CRM - Panel de Control para WhatsApp Business API 🚀

Un CRM completo, asíncrono y en tiempo real construido con **Laravel 12, Vue.js (Inertia)** y **WebSockets (Reverb)**. Diseñado para recibir, gestionar y responder mensajes multimedia usando la API oficial de Meta (Cloud API), sin depender de servicios de terceros.

## 🌟 Características
* **Tiempo Real:** Notificaciones instantáneas usando Laravel Reverb.

* **Bóveda Multimedia:** Las notas de voz, imágenes y documentos se descargan y protegen en discos locales.

* **Seguridad Criptográfica:** Validación de firmas X-Hub-Signature-256 para evitar ataques al Webhook.

* **WebRTC Preparado:** Interfaz lista para recibir llamadas de voz de WhatsApp.

## 🛠️ Requisitos Previos
* PHP 8.2+

* Composer & NPM

* Base de datos (MariaDB / MySQL)

* Cuenta de desarrollador en Meta con una App de WhatsApp configurada.

## ⚙️ Instalación paso a paso
**1. Clonar el repositorio y dependencias**
```bash
git clone https://github.com/TuUsuario/waba-crm.git
cd waba-crm
composer install
npm install
```

**2. Configurar el entorno (.env)**
Copia el archivo de ejemplo y genera tu llave de aplicación:
```bash
cp .env.example .env
php artisan key:generate
```

**Abre tu archivo .env, configura tu conexión a la base de datos y añade obligatoriamente las claves de cifrado de Meta para que el sistema permita la instalación:**
```bash
ID_APP_META_APIWB="tu_app_id"
SECRET_APP_META_APIWB="tu_app_secret"
```

**3. Base de datos y Compilación**
Ejecuta las migraciones para construir las tablas y compila los assets de Vue:
```bash
php artisan migrate
npm run build
```

**4. Levantar los Servicios (Workers y WebSockets)**
Para que el CRM funcione en tiempo real y procese los Webhooks, necesitas mantener estos tres procesos corriendo en tu servidor (puedes usar Supervisor o pm2):
```bash
php artisan serve
php artisan reverb:start
php artisan queue:work
```

## 🔐 Primeros Pasos (El Búnker)
Entra a http://localhost:8000 (o a tu dominio configurado).

Haz clic en Registrar Administrador. Al hacerlo, la ruta pública de registro se destruirá para proteger el sistema permanentemente.

Ve a /settings e introduce el resto de tus credenciales de WhatsApp (Token EAAl, Phone ID, WABA ID) para conectar el CRM definitivamente.

## Licencia
Licencia: MIT
