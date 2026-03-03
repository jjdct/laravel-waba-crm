<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

// Recibimos la variable desde Laravel para saber si ya hay un admin
defineProps({
    adminExists: {
        type: Boolean,
        default: false
    }
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'admin' // Forzamos que el primer registro sea administrador
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Instalación del Sistema" />

        <div class="flex md:hidden flex-col items-center justify-center p-4 text-center">
            <div class="w-20 h-20 bg-[#f0f2f5] dark:bg-[#2a3942] rounded-full flex items-center justify-center mb-4 shadow-inner border border-gray-200 dark:border-[#313d45]">
                <svg class="w-10 h-10 text-gray-400 dark:text-[#8696a0]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-[#e9edef] mb-2">Instalación Restringida</h2>
            <p class="text-sm text-gray-500 dark:text-[#8696a0]">
                Por motivos de seguridad, la configuración inicial y creación de la cuenta maestra solo puede realizarse desde una computadora.
            </p>
            <Link :href="route('login')" class="mt-6 inline-block text-sm font-bold text-[#00a884] hover:underline">
                Ir a Iniciar Sesión
            </Link>
        </div>

        <div class="hidden md:block">
            
            <div v-if="adminExists" class="text-center py-6">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 bg-green-50 dark:bg-[#00a884]/10 rounded-full flex items-center justify-center text-[#00a884]">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                </div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-[#e9edef] mb-2">Sistema Protegido</h2>
                <p class="text-sm text-gray-500 dark:text-[#8696a0] mb-6">
                    El sistema ya cuenta con un Administrador registrado. Por seguridad, el registro público ha sido deshabilitado automáticamente.
                </p>
                <Link :href="route('login')" class="inline-flex items-center px-6 py-2.5 bg-[#00a884] hover:bg-[#008f6f] border border-transparent rounded-xl font-bold text-white transition-all shadow-sm">
                    Ir al Panel de Control
                </Link>
            </div>

            <div v-else>
                <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 rounded-r-xl shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-0.5">
                            <svg class="h-6 w-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-[13px] font-extrabold text-yellow-800 dark:text-yellow-400 uppercase tracking-wide">Paso Único de Instalación</h3>
                            <p class="text-[13px] text-yellow-700 dark:text-yellow-500 mt-1.5 leading-relaxed">
                                Estás a punto de crear la cuenta maestra. Una vez registrado, este formulario se bloqueará automáticamente para proteger el CRM.
                            </p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="name" value="Nombre Completo (Admin)" class="dark:text-[#e9edef] font-bold" />
                        <TextInput
                            id="name"
                            type="text"
                            class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef] focus:border-[#00a884] focus:ring-[#00a884] rounded-xl"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="email" value="Correo Electrónico" class="dark:text-[#e9edef] font-bold" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef] focus:border-[#00a884] focus:ring-[#00a884] rounded-xl"
                            v-model="form.email"
                            required
                            autocomplete="username"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="password" value="Contraseña Maestra" class="dark:text-[#e9edef] font-bold" />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef] focus:border-[#00a884] focus:ring-[#00a884] rounded-xl"
                            v-model="form.password"
                            required
                            autocomplete="new-password"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="mt-4">
                        <InputLabel
                            for="password_confirmation"
                            value="Confirmar Contraseña"
                            class="dark:text-[#e9edef] font-bold"
                        />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef] focus:border-[#00a884] focus:ring-[#00a884] rounded-xl"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                        />
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <Link
                            :href="route('login')"
                            class="rounded-md text-sm text-gray-600 dark:text-[#8696a0] underline hover:text-gray-900 dark:hover:text-[#e9edef] transition-colors"
                        >
                            ¿Ya instalaste el sistema?
                        </Link>

                        <button
                            type="submit"
                            class="inline-flex items-center px-6 py-2.5 bg-[#00a884] hover:bg-[#008f6f] border border-transparent rounded-xl font-bold text-white transition-all shadow-sm disabled:opacity-50"
                            :class="{ 'opacity-50 cursor-wait': form.processing }"
                            :disabled="form.processing"
                        >
                            Crear Admin
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </GuestLayout>
</template>