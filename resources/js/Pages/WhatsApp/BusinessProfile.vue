<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    profile: Object,
    businessName: String, // Recibido desde el controlador (endpoint de la cuenta)
    error: String         // Captura fallos de conexión con Meta
});

const verticals = [
    "UNDEFINED", "OTHER", "AUTO", "BEAUTY", "APPAREL", "EDU", "ENTERTAIN", 
    "EVENT_PLAN", "FINANCE", "GROCERY", "GOVT", "HOTEL", "HEALTH", 
    "NONPROFIT", "PROF_SERVICES", "RETAIL", "TRAVEL", "RESTAURANT"
];

const verticalNames = {
    "OTHER": "Otros", "AUTO": "Automotriz", "BEAUTY": "Belleza", "APPAREL": "Ropa y Accesorios",
    "EDU": "Educación", "ENTERTAIN": "Entretenimiento", "EVENT_PLAN": "Planificación de Eventos",
    "FINANCE": "Finanzas", "GROCERY": "Abarrotes", "GOVT": "Gobierno", "HOTEL": "Hotel",
    "HEALTH": "Salud", "NONPROFIT": "Sin Fines de Lucro", "PROF_SERVICES": "Servicios Profesionales",
    "RETAIL": "Comercio Minorista", "TRAVEL": "Viajes", "RESTAURANT": "Restaurante", "UNDEFINED": "No Definido"
};

const form = useForm({
    name: props.businessName || '', // Sincronizado con el nombre oficial
    about: props.profile?.about || '',
    address: props.profile?.address || '',
    description: props.profile?.description || '',
    email: props.profile?.email || '',
    vertical: props.profile?.vertical || 'OTHER',
    websites: [
        props.profile?.websites?.[0] || '',
        props.profile?.websites?.[1] || ''
    ],
    profile_picture_handle: '', 
});

const photoInput = ref(null);
const photoPreview = ref(null); 
const uploadStatus = ref('');   

const selectNewPhoto = () => photoInput.value.click();

const processPhoto = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    photoPreview.value = URL.createObjectURL(file);
    uploadStatus.value = '⌛ Obteniendo ID...';

    const formData = new FormData();
    formData.append('avatar', file);

    try {
        const response = await axios.post(route('business.getAvatarHandle'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (response.data.success) {
            form.profile_picture_handle = response.data.handle; 
            uploadStatus.value = '✅ ID Listo.';
        }
    } catch (error) {
        uploadStatus.value = '❌ Error de Meta';
    }
};

const submit = () => {
    form.post(route('business.update'), {
        preserveScroll: true,
        onSuccess: () => {
            alert('¡Perfil actualizado en Meta!');
            uploadStatus.value = '';
            form.profile_picture_handle = ''; 
        },
    });
};
</script>

<template>
    <Head title="Perfil de Empresa" />

    <AuthenticatedLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row gap-8 min-h-[calc(100vh-100px)]">
            
            <div class="flex-1 bg-white dark:bg-[#111b21] overflow-hidden shadow-sm rounded-2xl border border-gray-200 dark:border-[#313d45]">
                
                <div v-if="error" class="m-5 p-4 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-xl border border-red-200 dark:border-red-800/30 text-sm shadow-sm flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <span class="font-bold">Error de conexión con Meta:</span> {{ error }}
                    </div>
                </div>

                <div class="p-5 border-b border-gray-100 dark:border-[#313d45] bg-gray-50/50 dark:bg-[#202c33]">
                    <h2 class="font-bold text-gray-800 dark:text-[#e9edef] text-xl tracking-tight">Información Pública</h2>
                    <p class="text-xs text-gray-500 dark:text-[#8696a0] mt-1">Configura los datos que tus clientes verán al consultar tu perfil oficial.</p>
                </div>

                <div class="p-6 space-y-6">
                    <div class="flex items-center gap-6 pb-6 border-b border-gray-100 dark:border-[#313d45]">
                        <div class="relative cursor-pointer flex-shrink-0" @click="selectNewPhoto">
                            <img class="h-24 w-24 object-cover rounded-full border-2 border-[#00a884] shadow-md transition-transform hover:scale-105" :src="photoPreview || profile?.profile_picture_url || 'https://via.placeholder.com/150'">
                            <div class="absolute bottom-0 right-0 bg-[#00a884] p-1.5 rounded-full text-white shadow-sm border-2 border-white dark:border-[#111b21]">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                            </div>
                            <input type="file" class="hidden" ref="photoInput" @change="processPhoto" accept="image/png, image/jpeg">
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-[#e9edef]">Logotipo de la Empresa</h3>
                            <p class="text-xs" :class="uploadStatus.includes('❌') ? 'text-red-500' : 'text-gray-500 dark:text-[#8696a0]'">
                                {{ uploadStatus || 'PNG o JPG. Recomendado: 640x640px.' }}
                            </p>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="about" value="Estado (About)" class="dark:text-[#e9edef]" />
                            <TextInput v-model="form.about" class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef]" maxlength="139" placeholder="Disponible / En una reunión" />
                            <p class="text-[10px] text-right mt-1 dark:text-[#8696a0]">{{ form.about.length }}/139</p>
                        </div>
                        <div>
                            <InputLabel for="email" value="Correo de Contacto" class="dark:text-[#e9edef]" />
                            <TextInput v-model="form.email" type="email" class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef]" placeholder="contacto@empresa.com" />
                        </div>

                        <div>
                            <InputLabel for="vertical" value="Sector / Categoría" class="dark:text-[#e9edef]" />
                            <select v-model="form.vertical" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-[#313d45] dark:bg-[#111b21] dark:text-[#e9edef] focus:border-[#00a884] focus:ring-[#00a884] text-sm shadow-sm transition-colors">
                                <option v-for="v in verticals" :key="v" :value="v">{{ verticalNames[v] || v }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="address" value="Dirección Física" class="dark:text-[#e9edef]" />
                            <TextInput v-model="form.address" class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef]" maxlength="256" placeholder="Calle, Ciudad, Estado" />
                        </div>

                        <div>
                            <InputLabel for="web1" value="Sitio Web 1" class="dark:text-[#e9edef]" />
                            <TextInput v-model="form.websites[0]" type="url" class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef]" placeholder="https://www.tusitio.com" />
                        </div>
                        <div>
                            <InputLabel for="web2" value="Sitio Web 2" class="dark:text-[#e9edef]" />
                            <TextInput v-model="form.websites[1]" type="url" class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef]" placeholder="https://blog.tusitio.com" />
                        </div>

                        <div class="md:col-span-2">
                            <InputLabel for="description" value="Descripción del Negocio" class="dark:text-[#e9edef]" />
                            <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-[#313d45] dark:bg-[#111b21] dark:text-[#e9edef] focus:border-[#00a884] focus:ring-[#00a884] resize-none text-sm transition-colors" maxlength="512" placeholder="Describe brevemente a qué se dedica tu empresa..."></textarea>
                            <p class="text-[10px] text-right mt-1 dark:text-[#8696a0]">{{ form.description.length }}/512</p>
                        </div>

                        <div class="md:col-span-2 flex justify-end pt-4 border-t border-gray-100 dark:border-[#313d45]">
                            <PrimaryButton :disabled="form.processing" class="shadow-sm">Guardar Perfil en Meta</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>

            <div class="hidden lg:flex w-[380px] flex-shrink-0 justify-center sticky top-6">
                <div class="w-full max-w-[360px] h-[740px] bg-black border-[12px] border-[#1f1f1f] rounded-[3rem] flex flex-col relative overflow-hidden shadow-2xl">
                    <div class="pt-8 pb-3 px-4 bg-[#0b141a] flex items-center gap-4 border-b border-[#222d34] z-40">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        <h2 class="font-medium text-white text-lg">Info. de la empresa</h2>
                    </div>

                    <div class="flex-1 overflow-y-auto bg-[#0b141a] scrollbar-hide flex flex-col">
                        <div class="flex flex-col items-center py-8 text-center border-b border-[#222d34]">
                            <img :src="photoPreview || profile?.profile_picture_url || 'https://via.placeholder.com/150'" class="w-32 h-32 rounded-full object-cover mb-4 ring-2 ring-[#222d34] shadow-lg">
                            <h2 class="text-2xl text-white font-normal">{{ form.name || 'Tu Empresa' }}</h2>
                            <p class="text-[#00a884] text-xs font-bold mt-1 uppercase tracking-widest">Cuenta de empresa oficial</p>
                        </div>

                        <div class="px-5 py-6 space-y-7">
                            <div v-if="form.description" class="flex gap-6 items-start">
                                <svg class="w-6 h-6 text-[#8696a0] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h7"></path></svg>
                                <span class="text-white text-sm whitespace-pre-wrap leading-relaxed">{{ form.description }}</span>
                            </div>
                            
                            <div v-if="form.vertical" class="flex gap-6 items-center">
                                <svg class="w-6 h-6 text-[#8696a0] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <span class="text-white text-sm">{{ verticalNames[form.vertical] }}</span>
                            </div>

                            <div v-if="form.address" class="flex gap-6 items-center text-[#53bdeb]">
                                <svg class="w-6 h-6 text-[#8696a0] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                <span class="text-sm truncate">{{ form.address }}</span>
                            </div>

                            <div v-if="form.websites[0]" class="flex gap-6 items-center text-[#53bdeb]">
                                <svg class="w-6 h-6 text-[#8696a0] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                                <span class="text-sm truncate">{{ form.websites[0] }}</span>
                            </div>

                            <div v-if="form.email" class="flex gap-6 items-center text-[#53bdeb]">
                                <svg class="w-6 h-6 text-[#8696a0] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <span class="text-sm truncate">{{ form.email }}</span>
                            </div>
                        </div>

                        <div v-if="form.about" class="px-5 py-6 space-y-7 border-t border-[#222d34] mt-2 mb-8 mx-4">
                            <div class="flex gap-6 items-start">
                                <svg class="w-6 h-6 text-[#8696a0] mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div>
                                    <p class="text-white text-base leading-tight">{{ form.about }}</p>
                                    <p class="text-[#8696a0] text-[11px] mt-1 uppercase font-bold tracking-wider">Estado de la cuenta</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>