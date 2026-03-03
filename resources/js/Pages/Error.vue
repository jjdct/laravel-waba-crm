<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    status: Number,
});

// Títulos dinámicos según el código de error
const title = computed(() => {
    return {
        403: 'Acceso Denegado',
        404: 'Página no encontrada',
        500: 'Error del servidor',
        503: 'Servicio no disponible',
    }[props.status] || 'Error Desconocido';
});

// Descripciones dinámicas
const description = computed(() => {
    return {
        403: 'No tienes los permisos necesarios para ver este recurso.',
        404: 'El archivo o la página que estás buscando no existe.',
        500: 'Ocurrió un error interno en el servidor.',
        503: 'El servicio no está disponible en este momento.',
    }[props.status] || 'Ha ocurrido un error inesperado al procesar tu solicitud.';
});
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-[#f0f2f5] dark:bg-[#111b21] transition-colors duration-300 flex flex-col items-center justify-center p-4">
        
        <div class="w-full max-w-md bg-white dark:bg-[#202c33] border border-gray-200 dark:border-[#313d45] rounded-[2rem] shadow-lg overflow-hidden flex flex-col items-center justify-center p-10 text-center transition-colors duration-300">
            
            <div class="w-24 h-24 mb-6 rounded-full bg-gray-50 dark:bg-[#111b21] flex items-center justify-center border border-gray-100 dark:border-[#313d45] shadow-inner">
                
                <svg v-if="status === 403" class="w-10 h-10 text-[#00a884]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                
                <svg v-else-if="status === 404" class="w-10 h-10 text-[#00a884]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>

                <svg v-else class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>

            <h1 class="text-6xl font-black text-gray-200 dark:text-[#2a3942] mb-2 tracking-tighter">{{ status }}</h1>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-[#e9edef] mb-3 leading-tight">{{ title }}</h2>
            <p class="text-[15px] text-gray-500 dark:text-[#8696a0] mb-8 leading-relaxed">
                {{ description }}
            </p>

            <Link :href="route('dashboard')" class="bg-[#00a884] hover:bg-[#008f6f] text-white font-bold py-3 px-8 rounded-xl transition-all shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Regresar a un lugar seguro
            </Link>
            
        </div>

    </div>
</template>