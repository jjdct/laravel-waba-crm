<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    hasAdmin: {
        type: Boolean,
        default: false // Si es true, muestra el logo gigante y oculta el registro
    }
});
</script>

<template>
    <Head title="Guía de Instalación" />

    <div class="min-h-screen bg-[#f0f2f5] dark:bg-[#111b21] text-gray-800 dark:text-[#e9edef] transition-colors duration-300 font-sans selection:bg-[#00a884] selection:text-white pb-12">
        
        <nav class="bg-white dark:bg-[#202c33] border-b border-gray-200 dark:border-[#313d45] px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 flex items-center justify-center">
                    <svg class="w-full h-full text-[#00a884] fill-current" viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg">
                        <path d="M305.8 81.125C305.77 80.995 305.69 80.885 305.65 80.755C305.56 80.525 305.49 80.285 305.37 80.075C305.29 79.935 305.17 79.815 305.07 79.685C304.94 79.515 304.83 79.325 304.68 79.175C304.55 79.045 304.39 78.955 304.25 78.845C304.09 78.715 303.95 78.575 303.77 78.475L251.32 48.275C249.97 47.495 248.31 47.495 246.96 48.275L194.51 78.475C194.33 78.575 194.19 78.725 194.03 78.845C193.89 78.955 193.73 79.045 193.6 79.175C193.45 79.325 193.34 79.515 193.21 79.685C193.11 79.815 192.99 79.935 192.91 80.075C192.79 80.285 192.71 80.525 192.63 80.755C192.58 80.875 192.51 80.995 192.48 81.125C192.38 81.495 192.33 81.875 192.33 82.265V139.625L148.62 164.795V52.575C148.62 52.185 148.57 51.805 148.47 51.435C148.44 51.305 148.36 51.195 148.32 51.065C148.23 50.835 148.16 50.595 148.04 50.385C147.96 50.245 147.84 50.125 147.74 49.995C147.61 49.825 147.5 49.635 147.35 49.485C147.22 49.355 147.06 49.265 146.92 49.155C146.76 49.025 146.62 48.885 146.44 48.785L93.99 18.585C92.64 17.805 90.98 17.805 89.63 18.585L37.18 48.785C37 48.885 36.86 49.035 36.7 49.155C36.56 49.265 36.4 49.355 36.27 49.485C36.12 49.635 36.01 49.825 35.88 49.995C35.78 50.125 35.66 50.245 35.58 50.385C35.46 50.595 35.38 50.835 35.3 51.065C35.25 51.185 35.18 51.305 35.15 51.435C35.05 51.805 35 52.185 35 52.575V232.235C35 233.795 35.84 235.245 37.19 236.025L142.1 296.425C142.33 296.555 142.58 296.635 142.82 296.725C142.93 296.765 143.04 296.835 143.16 296.865C143.53 296.965 143.9 297.015 144.28 297.015C144.66 297.015 145.03 296.965 145.4 296.865C145.5 296.835 145.59 296.775 145.69 296.745C145.95 296.655 146.21 296.565 146.45 296.435L251.36 236.035C252.72 235.255 253.55 233.815 253.55 232.245V174.885L303.81 145.945C305.17 145.165 306 143.725 306 142.155V82.265C305.95 81.875 305.89 81.495 305.8 81.125ZM144.2 227.205L100.57 202.515L146.39 176.135L196.66 147.195L240.33 172.335L208.29 190.625L144.2 227.205ZM244.75 114.995V164.795L226.39 154.225L201.03 139.625V89.825L219.39 100.395L244.75 114.995ZM249.12 57.105L292.81 82.265L249.12 107.425L205.43 82.265L249.12 57.105ZM114.49 184.425L96.13 194.995V85.305L121.49 70.705L139.85 60.135V169.815L114.49 184.425ZM91.76 27.425L135.45 52.585L91.76 77.745L48.07 52.585L91.76 27.425ZM43.67 60.135L62.03 70.705L87.39 85.305V202.545V202.555V202.565C87.39 202.735 87.44 202.895 87.46 203.055C87.49 203.265 87.49 203.485 87.55 203.695V203.705C87.6 203.875 87.69 204.035 87.76 204.195C87.84 204.375 87.89 204.575 87.99 204.745C87.99 204.745 87.99 204.755 88 204.755C88.09 204.905 88.22 205.035 88.33 205.175C88.45 205.335 88.55 205.495 88.69 205.635L88.7 205.645C88.82 205.765 88.98 205.855 89.12 205.965C89.28 206.085 89.42 206.225 89.59 206.325C89.6 206.325 89.6 206.325 89.61 206.335C89.62 206.335 89.62 206.345 89.63 206.345L139.87 234.775V285.065L43.67 229.705V60.135ZM244.75 229.705L148.58 285.075V234.775L219.8 194.115L244.75 179.875V229.705ZM297.2 139.625L253.49 164.795V114.995L278.85 100.395L297.21 89.825V139.625H297.2Z" />
                    </svg>
                </div>
                <h1 class="font-bold text-xl tracking-tight">WABA <span class="text-[#00a884]">CRM</span></h1>
            </div>
            
            <div class="flex items-center gap-3">
                <Link :href="route('SystemStatus')" class="text-xs font-semibold text-gray-500 hover:text-gray-900 dark:hover:text-white px-3 py-2 border border-transparent hover:border-gray-200 dark:hover:border-[#313d45] rounded-lg transition">
                    Status (/up)
                </Link>
                <Link :href="route('credits')" class="text-xs font-semibold text-gray-500 hover:text-gray-900 dark:hover:text-white px-3 py-2 border border-transparent hover:border-gray-200 dark:hover:border-[#313d45] rounded-lg transition">
                    Créditos
                </Link>

                <div v-if="canLogin" class="flex gap-2 ml-2">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="text-sm font-semibold bg-[#00a884] hover:bg-[#008f6f] text-white px-5 py-2.5 rounded-lg transition shadow-sm">
                        Ir al Panel
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="text-sm font-semibold bg-white dark:bg-[#111b21] hover:bg-gray-50 dark:hover:bg-[#202c33] border border-gray-200 dark:border-[#313d45] text-gray-700 dark:text-[#e9edef] px-5 py-2.5 rounded-lg transition shadow-sm">
                            Iniciar Sesión
                        </Link>
                        <Link v-if="canRegister && !hasAdmin" :href="route('register')" class="text-sm font-semibold bg-[#00a884] hover:bg-[#008f6f] text-white px-5 py-2.5 rounded-lg transition shadow-sm">
                            Registrar Administrador
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <main v-if="hasAdmin" class="max-w-3xl mx-auto px-6 py-20 flex flex-col items-center justify-center text-center animate-fade-in">
            <svg class="w-full h-full text-[#00a884] fill-current" viewBox="0 0 316 316" xmlns="http://www.w3.org/2000/svg">
                <path d="M305.8 81.125C305.77 80.995 305.69 80.885 305.65 80.755C305.56 80.525 305.49 80.285 305.37 80.075C305.29 79.935 305.17 79.815 305.07 79.685C304.94 79.515 304.83 79.325 304.68 79.175C304.55 79.045 304.39 78.955 304.25 78.845C304.09 78.715 303.95 78.575 303.77 78.475L251.32 48.275C249.97 47.495 248.31 47.495 246.96 48.275L194.51 78.475C194.33 78.575 194.19 78.725 194.03 78.845C193.89 78.955 193.73 79.045 193.6 79.175C193.45 79.325 193.34 79.515 193.21 79.685C193.11 79.815 192.99 79.935 192.91 80.075C192.79 80.285 192.71 80.525 192.63 80.755C192.58 80.875 192.51 80.995 192.48 81.125C192.38 81.495 192.33 81.875 192.33 82.265V139.625L148.62 164.795V52.575C148.62 52.185 148.57 51.805 148.47 51.435C148.44 51.305 148.36 51.195 148.32 51.065C148.23 50.835 148.16 50.595 148.04 50.385C147.96 50.245 147.84 50.125 147.74 49.995C147.61 49.825 147.5 49.635 147.35 49.485C147.22 49.355 147.06 49.265 146.92 49.155C146.76 49.025 146.62 48.885 146.44 48.785L93.99 18.585C92.64 17.805 90.98 17.805 89.63 18.585L37.18 48.785C37 48.885 36.86 49.035 36.7 49.155C36.56 49.265 36.4 49.355 36.27 49.485C36.12 49.635 36.01 49.825 35.88 49.995C35.78 50.125 35.66 50.245 35.58 50.385C35.46 50.595 35.38 50.835 35.3 51.065C35.25 51.185 35.18 51.305 35.15 51.435C35.05 51.805 35 52.185 35 52.575V232.235C35 233.795 35.84 235.245 37.19 236.025L142.1 296.425C142.33 296.555 142.58 296.635 142.82 296.725C142.93 296.765 143.04 296.835 143.16 296.865C143.53 296.965 143.9 297.015 144.28 297.015C144.66 297.015 145.03 296.965 145.4 296.865C145.5 296.835 145.59 296.775 145.69 296.745C145.95 296.655 146.21 296.565 146.45 296.435L251.36 236.035C252.72 235.255 253.55 233.815 253.55 232.245V174.885L303.81 145.945C305.17 145.165 306 143.725 306 142.155V82.265C305.95 81.875 305.89 81.495 305.8 81.125ZM144.2 227.205L100.57 202.515L146.39 176.135L196.66 147.195L240.33 172.335L208.29 190.625L144.2 227.205ZM244.75 114.995V164.795L226.39 154.225L201.03 139.625V89.825L219.39 100.395L244.75 114.995ZM249.12 57.105L292.81 82.265L249.12 107.425L205.43 82.265L249.12 57.105ZM114.49 184.425L96.13 194.995V85.305L121.49 70.705L139.85 60.135V169.815L114.49 184.425ZM91.76 27.425L135.45 52.585L91.76 77.745L48.07 52.585L91.76 27.425ZM43.67 60.135L62.03 70.705L87.39 85.305V202.545V202.555V202.565C87.39 202.735 87.44 202.895 87.46 203.055C87.49 203.265 87.49 203.485 87.55 203.695V203.705C87.6 203.875 87.69 204.035 87.76 204.195C87.84 204.375 87.89 204.575 87.99 204.745C87.99 204.745 87.99 204.755 88 204.755C88.09 204.905 88.22 205.035 88.33 205.175C88.45 205.335 88.55 205.495 88.69 205.635L88.7 205.645C88.82 205.765 88.98 205.855 89.12 205.965C89.28 206.085 89.42 206.225 89.59 206.325C89.6 206.325 89.6 206.325 89.61 206.335C89.62 206.335 89.62 206.345 89.63 206.345L139.87 234.775V285.065L43.67 229.705V60.135ZM244.75 229.705L148.58 285.075V234.775L219.8 194.115L244.75 179.875V229.705ZM297.2 139.625L253.49 164.795V114.995L278.85 100.395L297.21 89.825V139.625H297.2Z" />
            </svg>
            <h2 class="text-4xl font-black mb-3 text-gray-900 dark:text-white tracking-tight">WABA CRM</h2>
            <p class="text-gray-600 dark:text-[#8696a0] text-lg max-w-lg">
                Un hermoso CRM de WhatsApp. Inicia sesión para administrar las conversaciones y campañas.
            </p>
        </main>

        <main v-else class="max-w-5xl mx-auto px-6 py-12">
            <div class="mb-10 text-center md:text-left">
                <h2 class="text-3xl font-bold mb-3 text-gray-900 dark:text-white">Bienvenido a tu CRM</h2>
                <p class="text-gray-600 dark:text-[#8696a0] text-lg">El sistema está casi listo. Verifica estos pasos antes de crear al Administrador Maestro.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div class="bg-white dark:bg-[#202c33] border border-gray-200 dark:border-[#313d45] rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="w-6 h-6 text-[#00a884]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <h3 class="font-bold text-lg">1. Cifrado de Meta</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-[#8696a0] mb-4">Asegúrate de que tu archivo <code class="font-mono bg-gray-100 dark:bg-black px-1.5 py-0.5 rounded text-[#00a884]">.env</code> tenga la firma criptográfica para rechazar ataques:</p>
                    <div class="bg-gray-900 rounded-lg p-4 font-mono text-[11px] text-[#53bdeb] overflow-x-auto border border-gray-800 shadow-inner leading-relaxed space-y-1">
                        <p>ID_APP_META_APIWB="1361811..."</p>
                        <p>SECRET_APP_META_APIWB="b7abdfdb..."</p>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-[#8696a0] mt-3 italic">
                        *El resto de IDs (Teléfono, Token) se configurarán de forma segura desde el panel <span class="font-semibold text-gray-700 dark:text-gray-300">/settings</span> una vez que inicies sesión.
                    </p>
                </div>

                <div class="bg-white dark:bg-[#202c33] border border-gray-200 dark:border-[#313d45] rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="w-6 h-6 text-[#00a884]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                        <h3 class="font-bold text-lg">2. Base de Datos (MariaDB / MySQL)</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-[#8696a0] mb-3">Si tu servicio de base de datos en el servidor no está activo, el sistema colapsará. Verifícalo corriendo:</p>
                    <div class="bg-gray-900 rounded-lg p-3 font-mono text-[12px] text-blue-400 border border-gray-800">
                        php artisan migrate:status
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-[#8696a0] mt-2">
                        Si falla, inicia el servicio en tu distribución Linux (ej. <code class="bg-gray-100 dark:bg-black px-1 py-0.5 rounded">sudo systemctl start mariadb</code>) y ejecuta las migraciones correspondientes.
                    </p>
                </div>

                <div class="bg-white dark:bg-[#202c33] border border-gray-200 dark:border-[#313d45] rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="w-6 h-6 text-[#00a884]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                        <h3 class="font-bold text-lg">3. Workers & WebSockets</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-[#8696a0] mb-4">En tu entorno de servidor (VPS o local), mantén vivos estos procesos usando <span class="text-purple-500 font-mono">Supervisor</span>, <span class="text-purple-500 font-mono">pm2</span> o pestañas separadas:</p>
                    <div class="space-y-2">
                        <div class="bg-gray-900 rounded-lg p-3 font-mono text-[11px] text-yellow-400 border border-gray-800 flex justify-between">
                            <span>php artisan reverb:start</span>
                            <span class="text-gray-500">&lt;-- Tiempo real</span>
                        </div>
                        <div class="bg-gray-900 rounded-lg p-3 font-mono text-[11px] text-purple-400 border border-gray-800 flex justify-between">
                            <span>php artisan queue:work</span>
                            <span class="text-gray-500">&lt;-- Asíncrono</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#202c33] border border-gray-200 dark:border-[#313d45] rounded-2xl p-6 shadow-sm relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-green-50 dark:bg-green-900/10 rounded-full blur-2xl"></div>
                    <div class="flex items-center gap-3 mb-4 relative z-10">
                        <div class="w-8 h-8 rounded-full bg-green-50 dark:bg-[#00a884]/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#00a884]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <h3 class="font-bold text-lg">4. Cierre del Búnker</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-[#8696a0] mb-2 leading-relaxed relative z-10">
                        Al hacer clic en <strong class="text-gray-800 dark:text-gray-200">Registrar Administrador</strong>, crearás la cuenta maestra. 
                    </p>
                    <p class="text-sm text-gray-500 dark:text-[#8696a0] leading-relaxed relative z-10">
                        Una vez completado el registro, la puerta pública se cerrará para siempre y <strong>esta pantalla de instalación desaparecerá</strong>. Inmediatamente después, dirígete a <strong class="text-gray-800 dark:text-gray-200">/settings</strong> en tu panel para enlazar los IDs y el Token de WhatsApp.
                    </p>
                </div>

            </div>
        </main>

    </div>
</template>