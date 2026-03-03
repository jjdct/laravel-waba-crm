<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    users: Array
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'agente', // Por defecto
});

const submit = () => {
    form.post(route('users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            alert('¡Usuario creado correctamente!');
            form.reset('name', 'email', 'password');
        },
    });
};
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AuthenticatedLayout>
        
        <div class="flex md:hidden h-[calc(100vh-100px)] flex-col items-center justify-center p-6 text-center bg-white dark:bg-[#111b21] transition-colors duration-300">
            <div class="w-24 h-24 bg-gray-100 dark:bg-[#202c33] rounded-full flex items-center justify-center mb-6 shadow-inner border border-gray-200 dark:border-[#313d45]">
                <svg class="w-12 h-12 text-gray-400 dark:text-[#8696a0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-[#e9edef] mb-2">Acceso Restringido</h2>
            <p class="text-[15px] text-gray-500 dark:text-[#8696a0] max-w-xs">
                La gestión de usuarios y permisos del equipo requiere una vista amplia. Por seguridad, accede a esta sección desde una computadora.
            </p>
        </div>

        <div class="hidden md:flex py-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex-col md:flex-row gap-8 min-h-[calc(100vh-100px)]">
            
            <div class="w-full md:w-1/3">
                <div class="bg-white dark:bg-[#202c33] px-6 py-8 shadow-sm sm:rounded-2xl border border-gray-100 dark:border-[#313d45] transition-colors duration-300">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-[#e9edef] mb-6">Registrar Nuevo Usuario</h2>
                    
                    <form @submit.prevent="submit">
                        <div>
                            <InputLabel for="name" value="Nombre Completo" class="dark:text-[#e9edef]" />
                            <TextInput id="name" type="text" class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef] focus:border-[#00a884] focus:ring-[#00a884]" v-model="form.name" required autofocus />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="email" value="Correo Electrónico" class="dark:text-[#e9edef]" />
                            <TextInput id="email" type="email" class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef] focus:border-[#00a884] focus:ring-[#00a884]" v-model="form.email" required />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="password" value="Contraseña Temporal" class="dark:text-[#e9edef]" />
                            <TextInput id="password" type="password" class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef] focus:border-[#00a884] focus:ring-[#00a884]" v-model="form.password" required />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="role" value="Nivel de Acceso" class="dark:text-[#e9edef]" />
                            <select id="role" v-model="form.role" class="mt-1 block w-full rounded-md border-gray-300 dark:border-[#313d45] dark:bg-[#111b21] dark:text-[#e9edef] focus:border-[#00a884] focus:ring-[#00a884] shadow-sm">
                                <option value="agente">Agente (Solo Chat)</option>
                                <option value="admin">Administrador</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.role" />
                        </div>

                        <div class="mt-6 flex items-center justify-end">
                            <button type="submit" class="inline-flex items-center px-6 py-2.5 bg-[#00a884] hover:bg-[#008f6f] border border-transparent rounded-xl font-bold text-white transition-all shadow-sm disabled:opacity-50" :disabled="form.processing">
                                Crear Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="flex-1">
                <div class="bg-white dark:bg-[#202c33] shadow-sm sm:rounded-2xl border border-gray-100 dark:border-[#313d45] overflow-hidden transition-colors duration-300">
                    <div class="p-6 border-b border-gray-100 dark:border-[#313d45]">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-[#e9edef]">Directorio del Equipo</h2>
                    </div>
                    <ul class="divide-y divide-gray-100 dark:divide-[#313d45]">
                        <li v-for="user in users" :key="user.id" class="p-6 flex justify-between items-center hover:bg-gray-50 dark:hover:bg-[#111b21] transition-colors">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-[#e9edef]">{{ user.name }}</p>
                                <p class="text-sm text-gray-500 dark:text-[#8696a0]">{{ user.email }}</p>
                            </div>
                            <span :class="['px-3 py-1 rounded-full text-xs font-bold uppercase', user.role === 'admin' ? 'bg-[#E2F5ED] text-[#00a884] dark:bg-[#00a884]/10 border dark:border-[#00a884]/20' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400']">
                                {{ user.role }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>