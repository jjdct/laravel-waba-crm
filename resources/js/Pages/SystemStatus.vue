<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    systemStatus: Object,
});

// Estado para controlar el auto-refresco
const isAutoRefreshActive = ref(true);
let refreshInterval = null;

// Función para recargar solo los datos (sin recargar la página completa)
const refreshData = () => {
    router.reload({ 
        only: ['systemStatus'], 
        preserveScroll: true 
    });
};

// Configurar el intervalo al montar el componente
onMounted(() => {
    refreshInterval = setInterval(() => {
        if (isAutoRefreshActive.value) {
            refreshData();
        }
    }, 10000); // Cada 10 segundos
});

// Limpiar el intervalo al salir de la página para no gastar recursos del Celeron xd
onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});

const getStatusColor = (status) => {
    if (status === 'ok') return 'bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.6)]';
    if (status === 'warning') return 'bg-yellow-500 shadow-[0_0_10px_rgba(234,179,8,0.6)]';
    return 'bg-red-500 shadow-[0_0_10px_rgba(239,68,68,0.6)]';
};

const getStatusText = (status) => {
    if (status === 'ok') return 'Operativo';
    if (status === 'warning') return 'Advertencia / Faltan Datos';
    return 'Caído / Error Crítico';
};
</script>

<template>
    <Head title="Estado del Sistema" />

    <div class="min-h-screen bg-[#f0f2f5] dark:bg-[#111b21] py-12 px-4 flex flex-col items-center font-sans transition-colors duration-300">
        
        <div class="w-full max-w-3xl mb-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <svg class="w-7 h-7 text-[#00a884]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Monitor del Sistema
            </h1>
            <div class="flex items-center gap-4">
                <button @click="isAutoRefreshActive = !isAutoRefreshActive" 
                    :class="['text-[10px] font-bold uppercase px-3 py-1 rounded-full border transition-all', 
                    isAutoRefreshActive ? 'bg-[#00a884]/10 text-[#00a884] border-[#00a884]/20' : 'bg-gray-200 dark:bg-[#2a3942] text-gray-500 border-transparent']">
                    {{ isAutoRefreshActive ? 'Auto-refresco: ON' : 'Auto-refresco: OFF' }}
                </button>
                <Link href="/" class="text-sm font-semibold text-[#00a884] hover:underline">Volver al CRM</Link>
            </div>
        </div>

        <div class="w-full max-w-3xl bg-white dark:bg-[#202c33] rounded-2xl shadow-sm border border-gray-200 dark:border-[#313d45] overflow-hidden transition-colors duration-300">
            <div class="divide-y divide-gray-100 dark:divide-[#313d45]">
                
                <div class="p-6 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900 dark:text-[#e9edef]">Base de Datos (MariaDB)</h3>
                        <p class="text-sm text-gray-500 dark:text-[#8696a0]">Conexión principal para usuarios y mensajes.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ getStatusText(systemStatus.database) }}</span>
                        <div class="w-4 h-4 rounded-full" :class="getStatusColor(systemStatus.database)"></div>
                    </div>
                </div>

                <div class="p-6 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900 dark:text-[#e9edef]">Meta API (WhatsApp)</h3>
                        <p class="text-sm text-gray-500 dark:text-[#8696a0]">{{ systemStatus.whatsapp.message }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ getStatusText(systemStatus.whatsapp.status) }}</span>
                        <div class="w-4 h-4 rounded-full" :class="getStatusColor(systemStatus.whatsapp.status)"></div>
                    </div>
                </div>

                <div class="p-6 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900 dark:text-[#e9edef]">Servidor Reverb (Real-time)</h3>
                        <p class="text-sm text-gray-500 dark:text-[#8696a0]">Encargado de la mensajería instantánea.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ getStatusText(systemStatus.reverb) }}</span>
                        <div class="w-4 h-4 rounded-full" :class="getStatusColor(systemStatus.reverb)"></div>
                    </div>
                </div>

                <div class="p-6 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900 dark:text-[#e9edef]">Disco de Almacenamiento</h3>
                        <p class="text-sm text-gray-500 dark:text-[#8696a0]">Permisos de escritura para multimedia.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ getStatusText(systemStatus.storage) }}</span>
                        <div class="w-4 h-4 rounded-full" :class="getStatusColor(systemStatus.storage)"></div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 dark:bg-[#111b21] p-4 flex justify-between items-center border-t border-gray-200 dark:border-[#313d45]">
                <p class="text-xs text-gray-400 dark:text-[#8696a0] font-mono italic">
                    Actualizando automáticamente cada 10s...
                </p>
                <p class="text-xs text-gray-400 dark:text-[#8696a0] font-mono">
                    Última comprobación: {{ new Date(systemStatus.timestamp).toLocaleTimeString() }}
                </p>
            </div>
        </div>
    </div>
</template>