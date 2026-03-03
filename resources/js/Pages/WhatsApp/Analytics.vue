<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    conversations: {
        type: Array,
        default: () => []
    },
    calls: {
        type: Array,
        default: () => []
    },
    error: {
        type: String,
        default: null
    }
});

// ==========================================
// 🧮 CÁLCULOS BLINDADOS
// ==========================================

const totalConversations = computed(() => {
    if (!props.conversations?.length) return 0;
    return props.conversations.reduce((acc, point) => acc + (point?.conversation || 0), 0);
});

const totalCost = computed(() => {
    if (!props.conversations?.length) return '$0.00';
    const rawCost = props.conversations.reduce((acc, point) => acc + (point?.cost || 0), 0);
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(rawCost);
});

const categoryBreakdown = computed(() => {
    const breakdown = { marketing: 0, service: 0, utility: 0, authentication: 0 };
    if (!props.conversations?.length) return breakdown;

    props.conversations.forEach(point => {
        let type = 'UNKNOWN';
        if (point?.conversation_type) type = String(point.conversation_type).toUpperCase();
        else if (point?.dimensions?.conversation_type) type = String(point.dimensions.conversation_type).toUpperCase();
        
        if (type.includes('MARKETING')) breakdown.marketing += (point?.conversation || 1);
        else if (type.includes('SERVICE')) breakdown.service += (point?.conversation || 1);
        else if (type.includes('UTILITY')) breakdown.utility += (point?.conversation || 1);
        else if (type.includes('AUTHENTICATION')) breakdown.authentication += (point?.conversation || 1);
    });
    
    return breakdown;
});

const totalCalls = computed(() => {
    if (!props.calls?.length) return 0;
    return props.calls.reduce((acc, point) => acc + (point?.count || 0), 0);
});

const avgCallDuration = computed(() => {
    if (!props.calls?.length || totalCalls.value === 0) return '0s';
    const totalDuration = props.calls.reduce((acc, point) => acc + ((point?.average_duration || 0) * (point?.count || 0)), 0);
    const avgSeconds = Math.round(totalDuration / totalCalls.value);
    
    const m = Math.floor(avgSeconds / 60);
    const s = avgSeconds % 60;
    return `${m}m ${s}s`;
});
</script>

<template>
    <Head title="Estadísticas de WhatsApp" />

    <AuthenticatedLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 min-h-[calc(100vh-100px)]">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-bold text-gray-800 dark:text-[#e9edef] text-2xl tracking-tight">Rendimiento (Últimos 30 días)</h2>
                    <p class="text-sm text-gray-500 dark:text-[#8696a0] mt-1">Métricas obtenidas directamente desde la API de Meta Business.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="relative flex h-3 w-3">
                      <span v-if="!error" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                      <span :class="['relative inline-flex rounded-full h-3 w-3', error ? 'bg-red-500' : 'bg-green-500']"></span>
                    </span>
                    <span class="text-xs font-medium text-gray-600 dark:text-[#8696a0] uppercase tracking-widest">
                        {{ error ? 'Sincronización fallida' : 'Sincronizado en tiempo real' }}
                    </span>
                </div>
            </div>

            <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-xl border border-red-200 dark:border-red-800/30 text-sm shadow-sm flex items-center gap-3">
                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div class="flex-1">
                    <strong class="block font-bold">Problema de conexión con Meta</strong>
                    <span class="font-mono text-xs mt-1 block">{{ error }}</span>
                </div>
            </div>

            <div v-if="!error" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-[#111b21] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-[#313d45] flex flex-col justify-between transition-colors duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-gray-400 dark:text-[#8696a0] uppercase tracking-wider mb-1">Gasto Acumulado</p>
                            <h3 class="text-3xl font-black text-gray-900 dark:text-[#e9edef]">{{ totalCost }}</h3>
                        </div>
                        <div class="p-3 bg-green-50 dark:bg-[#00a884]/10 rounded-xl text-[#00a884]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-4">Cobros aproximados de la API de Meta</p>
                </div>

                <div class="bg-white dark:bg-[#111b21] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-[#313d45] flex flex-col justify-between transition-colors duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-gray-400 dark:text-[#8696a0] uppercase tracking-wider mb-1">Conversaciones</p>
                            <h3 class="text-3xl font-black text-gray-900 dark:text-[#e9edef]">{{ totalConversations }}</h3>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl text-blue-500 dark:text-blue-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-4">Sesiones de 24 horas facturables</p>
                </div>

                <div class="bg-white dark:bg-[#111b21] p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-[#313d45] flex flex-col justify-between transition-colors duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-gray-400 dark:text-[#8696a0] uppercase tracking-wider mb-1">Llamadas Recibidas</p>
                            <div class="flex items-baseline gap-2">
                                <h3 class="text-3xl font-black text-gray-900 dark:text-[#e9edef]">{{ totalCalls }}</h3>
                                <span class="text-sm font-medium text-gray-500">({{ avgCallDuration }} prom.)</span>
                            </div>
                        </div>
                        <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl text-purple-500 dark:text-purple-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-4">Tráfico procesado vía WebRTC</p>
                </div>
            </div>

            <div v-if="!error && totalConversations > 0" class="bg-white dark:bg-[#111b21] rounded-2xl shadow-sm border border-gray-100 dark:border-[#313d45] overflow-hidden transition-colors duration-300">
                <div class="p-5 border-b border-gray-100 dark:border-[#313d45] bg-gray-50/50 dark:bg-[#202c33]">
                    <h3 class="font-bold text-gray-800 dark:text-[#e9edef] text-lg">Distribución por Categoría</h3>
                </div>
                
                <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="flex flex-col items-center p-4 rounded-xl bg-gray-50 dark:bg-[#182229] border border-gray-100 dark:border-[#2a3942]">
                        <span class="text-xs font-bold text-gray-500 dark:text-[#8696a0] uppercase mb-2 text-center">Servicio</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-[#e9edef]">{{ categoryBreakdown.service }}</span>
                        <span class="text-[10px] text-gray-400 mt-1 text-center">Respuesta al cliente</span>
                    </div>

                    <div class="flex flex-col items-center p-4 rounded-xl bg-gray-50 dark:bg-[#182229] border border-gray-100 dark:border-[#2a3942]">
                        <span class="text-xs font-bold text-gray-500 dark:text-[#8696a0] uppercase mb-2 text-center">Marketing</span>
                        <span class="text-2xl font-bold text-[#00a884]">{{ categoryBreakdown.marketing }}</span>
                        <span class="text-[10px] text-gray-400 mt-1 text-center">Promociones/Ofertas</span>
                    </div>

                    <div class="flex flex-col items-center p-4 rounded-xl bg-gray-50 dark:bg-[#182229] border border-gray-100 dark:border-[#2a3942]">
                        <span class="text-xs font-bold text-gray-500 dark:text-[#8696a0] uppercase mb-2 text-center">Utilidad</span>
                        <span class="text-2xl font-bold text-blue-500">{{ categoryBreakdown.utility }}</span>
                        <span class="text-[10px] text-gray-400 mt-1 text-center">Avisos/Recibos</span>
                    </div>

                    <div class="flex flex-col items-center p-4 rounded-xl bg-gray-50 dark:bg-[#182229] border border-gray-100 dark:border-[#2a3942]">
                        <span class="text-xs font-bold text-gray-500 dark:text-[#8696a0] uppercase mb-2 text-center">Autenticación</span>
                        <span class="text-2xl font-bold text-purple-500">{{ categoryBreakdown.authentication }}</span>
                        <span class="text-[10px] text-gray-400 mt-1 text-center">Códigos OTP</span>
                    </div>
                </div>
            </div>

            <div v-if="!error && totalConversations === 0" class="flex flex-col items-center justify-center p-12 bg-white dark:bg-[#111b21] rounded-2xl border border-gray-100 dark:border-[#313d45] border-dashed">
                <svg class="w-16 h-16 text-gray-300 dark:text-[#313d45] mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-[#e9edef]">No hay datos recientes</h3>
                <p class="text-sm text-gray-500 dark:text-[#8696a0] mt-1 text-center max-w-md">No se han registrado conversaciones facturables ni tráfico de mensajes en los últimos 30 días.</p>
            </div>

        </div>
    </AuthenticatedLayout>
</template>