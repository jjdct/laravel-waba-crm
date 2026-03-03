<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Recuperar Contraseña" />

        <div class="mb-4 text-sm text-gray-600 dark:text-[#8696a0] leading-relaxed">
            ¿Olvidaste tu contraseña? No hay problema. Solo indícanos tu correo electrónico y te enviaremos un enlace para que puedas elegir una nueva.
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-[#00a884] bg-[#E2F5ED] dark:bg-[#00a884]/10 p-3 rounded-lg border border-[#00a884]/20">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Correo Electrónico" class="dark:text-[#e9edef]" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef] focus:border-[#00a884] focus:ring-[#00a884]"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-6 flex items-center justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center px-6 py-2.5 bg-[#00a884] hover:bg-[#008f6f] border border-transparent rounded-xl font-bold text-white transition-all shadow-sm disabled:opacity-50"
                    :class="{ 'opacity-50 cursor-wait': form.processing }"
                    :disabled="form.processing"
                >
                    Enviar Enlace de Recuperación
                </button>
            </div>
        </form>
    </GuestLayout>
</template>