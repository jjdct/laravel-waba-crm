<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirmar Contraseña" />

        <div class="mb-6 text-[15px] text-gray-500 dark:text-[#8696a0] leading-relaxed text-center">
            Esta es un área segura de la aplicación. Por favor, confirma tu contraseña maestra antes de continuar con esta acción.
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Contraseña" class="dark:text-[#e9edef] font-bold" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef] focus:border-[#00a884] focus:ring-[#00a884] rounded-xl shadow-sm"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center px-6 py-2.5 bg-[#00a884] hover:bg-[#008f6f] border border-transparent rounded-xl font-bold text-white transition-all shadow-sm disabled:opacity-50"
                    :class="{ 'opacity-50 cursor-wait': form.processing }"
                    :disabled="form.processing"
                >
                    Confirmar Identidad
                </button>
            </div>
        </form>
    </GuestLayout>
</template>