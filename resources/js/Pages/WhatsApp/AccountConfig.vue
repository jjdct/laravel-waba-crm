<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    account: Object,
    error: String
});

const nameForm = useForm({
    new_name: '',
});

const updateName = () => {
    if (!nameForm.new_name) return;
    nameForm.post(route('whatsapp.updateName'), {
        onSuccess: () => {
            alert('Solicitud de cambio de nombre enviada a Meta. Recuerda que está sujeta a revisión.');
            nameForm.reset();
        }
    });
};
</script>

<template>
    <Head title="Cuenta WhatsApp" />

    <AuthenticatedLayout>
        
        <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 min-h-[calc(100vh-100px)]">
            
            <div class="mb-6">
                <h2 class="font-bold text-gray-800 dark:text-[#e9edef] text-2xl tracking-tight">Configuración de la Cuenta</h2>
                <p class="text-sm text-gray-500 dark:text-[#8696a0] mt-1">Gestión técnica del número y conexión con Meta Cloud API.</p>
            </div>

            <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-xl border border-red-200 dark:border-red-800/30 text-sm shadow-sm flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                {{ error }}
            </div>

            <div v-if="account && account.info" class="bg-white dark:bg-[#111b21] rounded-2xl shadow-sm border border-gray-200 dark:border-[#313d45] overflow-hidden transition-colors duration-300">
                
                <div class="p-6 border-b border-gray-100 dark:border-[#313d45] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/50 dark:bg-[#202c33]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#00a884]/10 flex items-center justify-center text-[#00a884]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-[#e9edef]">Estado del Número</h3>
                            <p class="text-xs text-gray-500 dark:text-[#8696a0]">Sincronizado en tiempo real</p>
                        </div>
                    </div>
                    
                    <span :class="[
                        'px-4 py-1.5 rounded-lg text-[11px] font-extrabold uppercase tracking-wider shadow-sm border',
                        account.info.quality_rating === 'GREEN' 
                            ? 'bg-[#E2F5ED] text-[#00a884] border-[#00a884]/20 dark:bg-[#00a884]/10 dark:border-[#00a884]/30' 
                            : 'bg-yellow-50 text-yellow-700 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-500 dark:border-yellow-700/30'
                    ]">
                        Calidad: {{ account.info.quality_rating }}
                    </span>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-1.5">
                        <label class="flex items-center gap-1.5 text-[11px] font-bold text-gray-400 dark:text-[#8696a0] uppercase tracking-wider">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Nombre Verificado
                        </label>
                        <p class="text-xl font-bold text-[#00a884]">{{ account.info.verified_name }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="flex items-center gap-1.5 text-[11px] font-bold text-gray-400 dark:text-[#8696a0] uppercase tracking-wider">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            Número de Teléfono
                        </label>
                        <p class="text-xl font-mono font-bold text-gray-800 dark:text-[#e9edef]">{{ account.info.display_phone_number }}</p>
                    </div>

                    <div class="md:col-span-2 space-y-2">
                        <label class="flex items-center justify-between text-[11px] font-bold text-gray-400 dark:text-[#8696a0] uppercase tracking-wider">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                Webhook Activo (URL de Destino)
                            </span>
                            <span v-if="account.info.webhook_configuration" class="text-[9px] px-2 py-0.5 rounded-full border" 
                                :class="account.info.webhook_configuration.phone_number ? 'bg-purple-50 text-purple-600 border-purple-200 dark:bg-purple-900/20 dark:border-purple-800/30' : 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800/30'">
                                {{ account.info.webhook_configuration.phone_number ? 'OVERRIDE (Nivel Número)' : 'GLOBAL (Nivel App)' }}
                            </span>
                        </label>
                        <div class="bg-[#1c2c35] p-3.5 rounded-lg border border-[#2a3942] shadow-inner">
                            <p class="font-mono text-[12px] text-[#53bdeb] break-all leading-relaxed">
                                {{ 
                                    account.info.webhook_configuration?.phone_number || 
                                    account.info.webhook_configuration?.application || 
                                    'No configurado en Meta Dashboard' 
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-[#f0f2f5] dark:bg-[#202c33] border-t border-gray-100 dark:border-[#313d45]">
                    <h4 class="text-sm font-bold text-gray-800 dark:text-[#e9edef] mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#00a884]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        Actualizar Nombre en Pantalla
                    </h4>
                    
                    <form @submit.prevent="updateName" class="flex flex-col sm:flex-row gap-3">
                        <input 
                            v-model="nameForm.new_name"
                            type="text" 
                            placeholder="Escribe el nuevo nombre verificado"
                            class="flex-1 rounded-xl border-gray-300 dark:border-[#313d45] bg-white dark:bg-[#111b21] text-gray-800 dark:text-[#e9edef] text-sm focus:border-[#00a884] focus:ring-[#00a884] shadow-sm transition-colors"
                        />
                        <button 
                            type="submit" 
                            :disabled="nameForm.processing || !nameForm.new_name"
                            class="bg-[#00a884] text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-[#008f6f] transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
                        >
                            {{ nameForm.processing ? 'Procesando...' : 'Solicitar Cambio' }}
                        </button>
                    </form>
                    <p class="text-[11px] text-gray-500 dark:text-[#8696a0] mt-2.5 flex items-start gap-1">
                        <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        El cambio de nombre requiere revisión manual por parte del equipo de Meta y puede tardar varias horas en reflejarse.
                    </p>
                </div>
            </div>

            <div v-if="account" class="p-5 bg-white dark:bg-[#111b21] rounded-xl border border-dashed border-gray-300 dark:border-[#4a5a64] flex flex-wrap gap-x-12 gap-y-4 shadow-sm transition-colors duration-300">
                <div class="flex flex-col space-y-1">
                    <span class="text-[10px] font-bold text-gray-400 dark:text-[#8696a0] uppercase tracking-wider flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                        Phone ID
                    </span>
                    <span class="text-[13px] font-mono font-medium text-gray-600 dark:text-[#e9edef]">{{ account.phone_id }}</span>
                </div>
                
                <div class="flex flex-col space-y-1">
                    <span class="text-[10px] font-bold text-gray-400 dark:text-[#8696a0] uppercase tracking-wider flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        WABA ID
                    </span>
                    <span class="text-[13px] font-mono font-medium text-gray-600 dark:text-[#e9edef]">{{ account.waba_id }}</span>
                </div>
                
                <div class="flex flex-col space-y-1">
                    <span class="text-[10px] font-bold text-gray-400 dark:text-[#8696a0] uppercase tracking-wider flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                        Plataforma
                    </span>
                    <span class="text-[13px] font-mono font-medium text-[#00a884]">{{ account.info?.platform_type || 'Cloud API' }}</span>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>