<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ 
    templates: { type: Array, default: () => [] },
    error: String // <--- Recibimos el error del controlador
});

const selectedTemplate = ref(null);

const form = useForm({
    template_name: '',
    template_language: '',
    recipients: '',
    header_placeholder: '',
    body_placeholders: [],
});

const selectTemplate = (tmp) => {
    selectedTemplate.value = tmp;
    form.template_name = tmp.name;
    form.template_language = tmp.language;
    form.header_placeholder = '';
    
    const body = tmp.components.find(c => c.type === 'BODY');
    const matches = body?.text.match(/{{(\d+)}}/g) || [];
    form.body_placeholders = new Array(matches.length).fill('');
};

const previewHeader = computed(() => {
    const header = selectedTemplate.value?.components.find(c => c.type === 'HEADER');
    if (!header || header.format !== 'TEXT') return '';
    return header.text.replace('{{1}}', form.header_placeholder || '[Variable]');
});

const previewBody = computed(() => {
    if (!selectedTemplate.value) return '';
    let text = selectedTemplate.value.components.find(c => c.type === 'BODY')?.text || '';
    form.body_placeholders.forEach((val, i) => {
        text = text.replace(`{{${i + 1}}}`, val || `[Var ${i + 1}]`);
    });
    return text;
});

const submit = () => {
    form.post(route('campaigns.send'), { 
        preserveScroll: true,
        onSuccess: () => { 
            alert('¡Campaña encolada y lista para enviarse!'); 
            form.reset(); 
            selectedTemplate.value = null; 
        } 
    });
};
</script>

<template>
    <Head title="Campañas" />
    
    <AuthenticatedLayout>
        <div class="py-4 md:py-6 max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row gap-6 lg:h-[calc(100vh-100px)] min-h-screen lg:min-h-0 min-w-0">
            
            <div :class="['w-full lg:w-[300px] flex-shrink-0 flex-col bg-white dark:bg-[#111b21] border border-gray-200 dark:border-[#313d45] rounded-xl shadow-sm overflow-hidden transition-colors duration-300', selectedTemplate ? 'hidden lg:flex' : 'flex']">
                <div class="p-4 bg-gray-50/50 dark:bg-[#202c33] border-b border-gray-100 dark:border-[#313d45]">
                    <h2 class="font-bold text-gray-800 dark:text-[#e9edef] text-lg tracking-tight">Mis Plantillas</h2>
                </div>
                
                <div v-if="error" class="p-4 m-3 bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/20 rounded-xl">
                    <p class="text-[11px] font-bold text-red-600 dark:text-red-400 uppercase mb-1">Error de Conexión</p>
                    <p class="text-xs text-red-500 dark:text-red-300 leading-tight">{{ error }}</p>
                </div>

                <div class="flex-1 overflow-y-auto p-3 space-y-2">
                    <div v-if="!error && templates?.length === 0" class="p-4 text-center text-gray-500 dark:text-[#8696a0] text-sm italic">
                        No hay plantillas autorizadas.
                    </div>
                    
                    <div v-for="tmp in templates" :key="tmp.id" @click="selectTemplate(tmp)"
                        :class="[
                            'p-4 rounded-xl cursor-pointer transition-all duration-200 border',
                            form.template_name === tmp.name
                                ? 'bg-[#f0f2f5] dark:bg-[#202c33] border-[#00a884] shadow-sm'
                                : 'bg-white dark:bg-[#111b21] border-gray-100 dark:border-[#313d45] hover:border-gray-300 dark:hover:border-gray-600'
                        ]">
                        <p class="font-semibold text-gray-800 dark:text-[#e9edef] text-[14px] truncate">{{ tmp.name }}</p>
                        <p class="text-[10px] text-gray-500 dark:text-[#8696a0] uppercase font-bold tracking-wider mt-1">{{ tmp.language }}</p>
                    </div>
                </div>
            </div>

            <div v-if="selectedTemplate" class="flex-1 flex flex-col-reverse xl:flex-row gap-6 min-w-0 pb-10 xl:pb-0">
                <div class="flex-1 bg-white dark:bg-[#111b21] border border-gray-200 dark:border-[#313d45] rounded-xl shadow-sm flex flex-col overflow-hidden transition-colors duration-300 min-w-0 h-auto xl:h-full">
                    
                    <div class="p-4 bg-gray-50/50 dark:bg-[#202c33] border-b border-gray-100 dark:border-[#313d45] flex items-center gap-3">
                        <button @click="selectedTemplate = null" class="lg:hidden p-2 -ml-2 text-[#54656f] dark:text-[#8696a0] hover:bg-gray-200 dark:hover:bg-[#2a3942] rounded-full transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </button>
                        <h2 class="font-bold text-gray-800 dark:text-[#e9edef] text-lg tracking-tight">Configuración de Envío</h2>
                    </div>

                    <div class="flex-1 p-6 overflow-y-auto space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-[#8696a0] uppercase mb-2">Destinatarios (Uno por línea)</label>
                            <textarea v-model="form.recipients" rows="4" class="w-full rounded-lg border-gray-300 dark:border-[#313d45] bg-white dark:bg-[#202c33] text-gray-800 dark:text-[#e9edef] focus:ring-[#00a884] focus:border-[#00a884] text-[14px] transition-colors shadow-sm resize-none" placeholder="Ej: 5215551234567"></textarea>
                        </div>

                        <div class="space-y-4 bg-gray-50/50 dark:bg-[#182229] p-5 rounded-xl border border-gray-100 dark:border-[#2a3942]">
                            <h3 class="text-sm font-bold text-gray-800 dark:text-[#e9edef] flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#00a884]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                                Variables de Mensaje
                            </h3>

                            <div v-if="selectedTemplate.components.find(c => c.type === 'HEADER' && c.text?.includes('{{1}}'))">
                                <label class="block text-[11px] font-bold text-[#00a884] uppercase mb-1.5">Encabezado Dinámico</label>
                                <input v-model="form.header_placeholder" type="text" class="w-full rounded-md border-gray-300 dark:border-[#313d45] bg-white dark:bg-[#202c33] text-gray-800 dark:text-[#e9edef] focus:ring-[#00a884] focus:border-[#00a884] text-sm shadow-sm" placeholder="Texto para {{1}}...">
                            </div>

                            <div v-for="(v, i) in form.body_placeholders" :key="i">
                                <label class="block text-[11px] font-bold text-gray-500 dark:text-[#8696a0] uppercase mb-1.5">Variable {{i+1}} del Cuerpo</label>
                                <input v-model="form.body_placeholders[i]" type="text" class="w-full rounded-md border-gray-300 dark:border-[#313d45] bg-white dark:bg-[#202c33] text-gray-800 dark:text-[#e9edef] focus:ring-[#00a884] focus:border-[#00a884] text-sm shadow-sm" placeholder="Valor de variable...">
                            </div>
                        </div>
                    </div>

                    <div class="p-4 border-t border-gray-100 dark:border-[#313d45] bg-gray-50/50 dark:bg-[#202c33]">
                        <button @click="submit" :disabled="form.processing || !form.recipients" class="w-full bg-[#00a884] hover:bg-[#008f6f] text-white font-bold py-3 px-4 rounded-xl transition-all disabled:opacity-50 shadow-sm flex justify-center items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            Lanzar Campaña
                        </button>
                    </div>
                </div>

                <div class="w-full xl:w-[350px] flex-shrink-0 flex justify-center xl:items-center">
                    <div class="w-full max-w-[320px] h-[550px] xl:h-[600px] bg-[#efeae2] dark:bg-[#0b141a] border-[8px] border-gray-800 dark:border-[#1e2a30] rounded-[2.5rem] flex flex-col relative overflow-hidden shadow-2xl">
                        <div class="absolute inset-0 opacity-40 dark:opacity-10" style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-size: cover;"></div>
                        
                        <div class="px-4 py-3 bg-[#f0f2f5] dark:bg-[#202c33] flex items-center gap-3 shadow-sm z-10 border-b dark:border-[#313d45]">
                            <div class="w-8 h-8 rounded-full bg-slate-400 dark:bg-[#6b7c85] flex items-center justify-center text-white text-[10px] font-bold uppercase tracking-tighter">WA</div>
                            <div class="flex-1"><h2 class="font-semibold text-gray-900 dark:text-[#e9edef] text-[13px]">Vista Previa</h2></div>
                        </div>

                        <div class="flex-1 p-3 z-10 overflow-y-auto flex flex-col">
                            <div class="mt-auto"></div>
                            <div class="bg-white dark:bg-[#202c33] rounded-xl shadow-sm text-[13.5px] text-gray-800 dark:text-[#e9edef] mb-2 rounded-tl-none relative w-full overflow-hidden border border-gray-100 dark:border-transparent">
                                <div class="p-2.5">
                                    <div v-if="previewHeader" class="font-bold pb-2 mb-2 text-[14px] border-b border-gray-100 dark:border-[#313d45] leading-tight">{{ previewHeader }}</div>
                                    <p class="whitespace-pre-wrap leading-relaxed">{{ previewBody }}</p>
                                    <p v-if="selectedTemplate.components.find(c => c.type === 'FOOTER')" class="text-[10px] text-gray-400 dark:text-[#8696a0] mt-2 pt-1.5 border-t border-gray-100 dark:border-[#313d45]">
                                        {{ selectedTemplate.components.find(c => c.type === 'FOOTER').text }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="selectedTemplate.components.find(c => c.type === 'BUTTONS')" class="space-y-1.5 w-full mt-1">
                                <div v-for="b in selectedTemplate.components.find(c => c.type === 'BUTTONS').buttons" :key="b.text" class="bg-white dark:bg-[#202c33] text-[#00a884] dark:text-[#53bdeb] border border-gray-100 dark:border-[#313d45] text-center py-2 rounded-lg text-sm font-medium shadow-sm flex items-center justify-center gap-2">
                                    {{ b.text }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="flex-1 bg-white dark:bg-[#111b21] border border-gray-200 dark:border-[#313d45] rounded-xl shadow-sm hidden lg:flex flex-col items-center justify-center text-gray-500 dark:text-[#8696a0] p-6 transition-colors duration-300">
                <svg class="w-16 h-16 mb-4 opacity-20 text-[#00a884]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                <h3 class="text-lg font-bold text-gray-800 dark:text-[#e9edef] mb-2 uppercase italic tracking-tighter">Selecciona una plantilla</h3>
                <p class="text-sm text-center max-w-sm">Escoge una plantilla autorizada para empezar a configurar tu campaña de mensajes masivos.</p>
            </div>

        </div>
    </AuthenticatedLayout>
</template>