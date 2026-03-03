<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar Sesión" />

        <div v-if="status" class="mb-4 text-sm font-medium text-[#00a884]">
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

            <div class="mt-4">
                <InputLabel for="password" value="Contraseña" class="dark:text-[#e9edef]" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef] focus:border-[#00a884] focus:ring-[#00a884]"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" class="text-[#00a884] focus:ring-[#00a884] dark:bg-[#111b21] dark:border-[#313d45]" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-[#8696a0]">Recordarme</span>
                </label>
            </div>

            <div class="mt-6 flex items-center justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-gray-600 dark:text-[#8696a0] underline hover:text-gray-900 dark:hover:text-[#e9edef] focus:outline-none focus:ring-2 focus:ring-[#00a884] focus:ring-offset-2 dark:focus:ring-offset-[#202c33] transition-colors"
                >
                    ¿Olvidaste tu contraseña?
                </Link>

                <button
                    type="submit"
                    class="ms-4 inline-flex items-center px-6 py-2.5 bg-[#00a884] hover:bg-[#008f6f] border border-transparent rounded-xl font-bold text-white transition-all shadow-sm disabled:opacity-50"
                    :class="{ 'opacity-50 cursor-wait': form.processing }"
                    :disabled="form.processing"
                >
                    Iniciar Sesión
                </button>
            </div>
        </form>
    </GuestLayout>
</template>