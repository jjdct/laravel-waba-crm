<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    config: Object,
    live_webhook: Object, // <-- Recibimos la info en vivo de Meta
    error: String,
    success: String
});

const showToken = ref(false);

// 1. Formulario principal (Ahora incluye el Verify Token)
const form = useForm({
    meta_token: '', 
    waba_id: props.config?.waba_id || '',
    phone_id: props.config?.phone_id || '',
    webhook_verify_token: props.config?.webhook_verify_token || 'miku_peluche_secreto',
    storage_mode: props.config?.storage_mode || 'normal',
    region: props.config?.data_region || 'CH',
    pin: '',
});

// 2. Formulario exclusivo para Webhook (Solo necesita la URL)
const webhookForm = useForm({
    callback_url: '', 
});

// URL de Webhook esperada (la de tu aplicación)
const expectedWebhookUrl = window.location.origin + '/api/webhook';

// 1. Guardar Configuración Técnica en DB
const saveConfig = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.meta_token = ''; 
            alert('¡Configuración guardada y cifrada con éxito!');
        }
    });
};

// 2. Ejecutar Acciones de Meta (Register / Deregister)
const handleMetaAction = (action) => {
    const messages = {
        'deregister': '¿ESTÁS SEGURO? Esto desconectará el CRM de Meta y detendrá todos los mensajes.',
        'register': 'Se procederá a registrar el número con el PIN proporcionado.'
    };

    if (confirm(messages[action])) {
        router.post(route('admin.settings.action'), { 
            action: action,
            pin: form.pin,
            region: form.storage_mode === 'local' ? form.region : null
        }, {
            preserveScroll: true,
            onSuccess: () => form.pin = ''
        });
    }
};

// 3. Sincronizar Webhook Override con Meta
const updateWebhookOverride = () => {
    if (!webhookForm.callback_url) {
        alert('Por favor, ingresa la URL de Callback.');
        return;
    }
    
    // Disparamos la petición a nuestro controlador
    webhookForm.post(route('admin.settings.webhook.override'), {
        preserveScroll: true,
        onSuccess: () => {
            webhookForm.reset('callback_url');
        }
    });
};

</script>

<template>
    <Head title="Configuración" />

    <AuthenticatedLayout>
        <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 min-h-[calc(100vh-100px)] pb-20">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-gray-200 dark:border-[#313d45] pb-6 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-[#e9edef] tracking-tight">Configuración del Sistema</h2>
                    <p class="text-sm text-gray-500 dark:text-[#8696a0] mt-1">Gestiona las credenciales de la API y la conectividad global del CRM.</p>
                </div>
                <div class="text-right">
                    <div v-if="success || $page.props.flash?.success" class="inline-flex items-center gap-2 text-sm font-medium text-[#00a884] bg-[#00a884]/10 px-4 py-2 rounded-lg border border-[#00a884]/20">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        Cambios guardados
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                
                <div class="xl:col-span-2 space-y-8">
                    
                    <div class="bg-white dark:bg-[#111b21] rounded-2xl shadow-sm border border-gray-200 dark:border-[#313d45] overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100 dark:border-[#313d45] flex items-center gap-3 bg-gray-50/50 dark:bg-[#202c33]/50">
                            <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                            </div>
                            <h3 class="font-semibold text-lg text-gray-800 dark:text-[#e9edef]">Credenciales de Meta</h3>
                        </div>
                        <div class="p-6 space-y-6">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-[#8696a0] mb-2">Token de Acceso (System User)</label>
                                <div class="relative flex items-center">
                                    <input 
                                        :type="showToken ? 'text' : 'password'" 
                                        v-model="form.meta_token" 
                                        class="w-full rounded-xl border-gray-300 dark:border-[#313d45] bg-white dark:bg-[#202c33] text-gray-900 dark:text-[#e9edef] text-sm focus:ring-[#00a884] focus:border-[#00a884] pr-12 transition-shadow"
                                        :placeholder="config?.meta_token ? '••••••••••••••••••••' : 'Pega el token EAAl...'"
                                    >
                                    <button @click="showToken = !showToken" type="button" class="absolute right-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                        <svg v-if="!showToken" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7 1.274-4.057-5.064-7-9.542-7 4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-[#8696a0] mb-2">WhatsApp Business Account ID (WABA)</label>
                                    <input v-model="form.waba_id" type="text" class="w-full rounded-xl border-gray-300 dark:border-[#313d45] bg-white dark:bg-[#202c33] text-gray-900 dark:text-[#e9edef] text-sm focus:ring-[#00a884] focus:border-[#00a884] shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-[#8696a0] mb-2">Phone Number ID</label>
                                    <input v-model="form.phone_id" type="text" class="w-full rounded-xl border-gray-300 dark:border-[#313d45] bg-white dark:bg-[#202c33] text-gray-900 dark:text-[#e9edef] text-sm focus:ring-[#00a884] focus:border-[#00a884] shadow-sm">
                                </div>
                            </div>

                            <div class="bg-gray-50 dark:bg-[#202c33]/50 p-5 rounded-xl border border-gray-200 dark:border-[#313d45]">
                                <label class="block text-sm font-medium text-gray-700 dark:text-[#e9edef] mb-2">Token de Verificación (Webhooks)</label>
                                <input v-model="form.webhook_verify_token" type="text" class="w-full rounded-xl border-gray-300 dark:border-[#313d45] bg-white dark:bg-[#111b21] text-gray-900 dark:text-[#e9edef] font-mono text-sm focus:ring-[#00a884] focus:border-[#00a884] shadow-sm">
                                <p class="text-xs text-gray-500 dark:text-[#8696a0] mt-2">Esta frase de seguridad debe coincidir exactamente con la configurada en el panel de Meta.</p>
                            </div>

                            <div class="pt-4 border-t border-gray-100 dark:border-[#313d45] flex justify-end">
                                <button @click="saveConfig" :disabled="form.processing" class="bg-[#00a884] hover:bg-[#008f6f] text-white font-semibold py-2.5 px-6 rounded-lg shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                    Guardar Credenciales
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-[#111b21] rounded-2xl shadow-sm border border-gray-200 dark:border-[#313d45] overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100 dark:border-[#313d45] flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gray-50/50 dark:bg-[#202c33]/50">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <h3 class="font-semibold text-lg text-gray-800 dark:text-[#e9edef]">Conexión de Webhook (Override)</h3>
                            </div>
                            
                            <div v-if="live_webhook" class="flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold border"
                                :class="live_webhook.phone_number ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:border-green-800/50 dark:text-green-400' : 'bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800/50 dark:text-yellow-400'">
                                <span class="flex h-2 w-2 rounded-full" :class="live_webhook.phone_number ? 'bg-green-500 animate-pulse' : 'bg-yellow-500'"></span>
                                {{ live_webhook.phone_number ? 'Conectado a Meta' : 'Sin Configurar' }}
                            </div>
                        </div>
                        
                        <div class="p-6 space-y-6">
                            
                            <div v-if="live_webhook?.phone_number" class="bg-gray-50 dark:bg-[#202c33]/50 p-4 rounded-xl border border-gray-200 dark:border-[#313d45] mb-2">
                                <label class="text-xs font-semibold text-gray-500 dark:text-[#8696a0] block mb-1">Destino actual del tráfico:</label>
                                <p class="text-sm text-gray-800 dark:text-[#53bdeb] font-mono truncate">{{ live_webhook.phone_number }}</p>
                            </div>

                            <form @submit.prevent="updateWebhookOverride" class="space-y-5">
                                <div>
                                    <div class="flex justify-between items-end mb-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-[#8696a0]">URL de Recepción (Endpoint)</label>
                                        <button type="button" @click="webhookForm.callback_url = expectedWebhookUrl" class="text-xs font-medium text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300">Insertar URL Local</button>
                                    </div>
                                    <input 
                                        v-model="webhookForm.callback_url" 
                                        type="url" 
                                        placeholder="https://tu-dominio.com/api/webhook"
                                        required
                                        class="w-full rounded-xl border-gray-300 dark:border-[#313d45] bg-white dark:bg-[#202c33] text-gray-900 dark:text-[#e9edef] text-sm focus:ring-purple-500 focus:border-purple-500 shadow-sm"
                                    >
                                    <p class="text-xs text-gray-500 dark:text-[#8696a0] mt-2">
                                        Asegúrate de haber guardado el Token de Verificación en la sección superior antes de sincronizar.
                                    </p>
                                </div>

                                <button type="submit" :disabled="webhookForm.processing" class="w-full bg-gray-900 hover:bg-black dark:bg-[#2a3942] dark:hover:bg-white dark:hover:text-black text-white font-semibold py-3 px-6 rounded-xl shadow-sm transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Sincronizar Webhook con Meta
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white dark:bg-[#111b21] rounded-2xl shadow-sm border border-gray-200 dark:border-[#313d45] overflow-hidden p-6">
                        <h3 class="font-semibold text-lg text-gray-800 dark:text-[#e9edef] mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Registro del Número
                        </h3>
                        
                        <div class="space-y-3 mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-[#8696a0] mb-2">Preferencia de Almacenamiento Meta</label>
                            <label v-for="mode in ['normal', 'local', 'none']" :key="mode" class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-[#313d45] cursor-pointer hover:bg-gray-50 dark:hover:bg-[#202c33] transition-colors">
                                <input type="radio" v-model="form.storage_mode" :value="mode" class="text-[#00a884] focus:ring-[#00a884] h-4 w-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-[#e9edef]">
                                        {{ mode === 'normal' ? 'Estándar (Cloud)' : mode === 'local' ? 'Localizado' : 'Sin Almacenamiento' }}
                                    </p>
                                </div>
                            </label>
                        </div>

                        <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-[#313d45]">
                            <div v-if="form.storage_mode === 'local'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-[#8696a0] mb-2">Región de Localización</label>
                                <select v-model="form.region" class="w-full rounded-lg border-gray-300 dark:border-[#313d45] dark:bg-[#202c33] dark:text-[#e9edef] text-sm focus:ring-[#00a884] focus:border-[#00a884]">
                                    <option value="CH">Suiza (CH)</option>
                                    <option value="BR">Brasil (BR)</option>
                                    <option value="EU">Europa (EU)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-[#8696a0] mb-2">PIN de Registro de Meta</label>
                                <input v-model="form.pin" type="password" maxlength="6" class="w-full rounded-lg border-gray-300 dark:border-[#313d45] bg-white dark:bg-[#202c33] text-gray-900 dark:text-[#e9edef] text-center font-mono text-xl tracking-[0.5em] focus:ring-[#00a884] focus:border-[#00a884]" placeholder="000000">
                            </div>

                            <button 
                                @click="handleMetaAction('register')" 
                                :disabled="!form.pin || form.pin.length < 6"
                                class="w-full bg-[#00a884] hover:bg-[#008f6f] text-white py-2.5 rounded-lg font-semibold transition-colors disabled:opacity-50 shadow-sm"
                            >
                                Registrar Número
                            </button>
                        </div>
                    </div>

                    <div class="border border-red-200 dark:border-red-900/30 rounded-2xl p-6 bg-red-50/50 dark:bg-red-900/10">
                        <h3 class="text-red-700 dark:text-red-400 font-bold text-sm mb-3">Zona de Peligro</h3>
                        <p class="text-xs text-red-600/80 dark:text-red-400/80 mb-4 leading-relaxed">
                            Anular el registro detendrá inmediatamente la recepción y envío de mensajes. Úsalo solo para migrar el número.
                        </p>
                        <button @click="handleMetaAction('deregister')" class="w-full py-2.5 bg-white dark:bg-[#111b21] border border-red-300 dark:border-red-800 text-red-600 dark:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm font-semibold transition-colors">
                            Anular Registro de Meta
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
input[type="password"] {
    -webkit-text-security: disc;
}
</style>