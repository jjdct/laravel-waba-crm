<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verificar Correo Electrónico" />

        <div class="mb-6 text-[15px] text-gray-500 dark:text-[#8696a0] leading-relaxed text-center">
            ¡Gracias por ser parte del equipo! Antes de comenzar, ¿podrías verificar tu dirección de correo electrónico haciendo clic en el enlace que te acabamos de enviar? Si no lo recibiste, con gusto te enviaremos otro.
        </div>

        <div
            class="mb-6 p-4 rounded-xl text-sm font-bold bg-green-50 text-[#00a884] dark:bg-[#00a884]/10 border border-green-100 dark:border-[#00a884]/20 text-center"
            v-if="verificationLinkSent"
        >
            Se ha enviado un nuevo enlace de verificación a la dirección de correo que proporcionaste.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <button
                    type="submit"
                    class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-2.5 bg-[#00a884] hover:bg-[#008f6f] border border-transparent rounded-xl font-bold text-white transition-all shadow-sm disabled:opacity-50"
                    :class="{ 'opacity-50 cursor-wait': form.processing }"
                    :disabled="form.processing"
                >
                    Reenviar Correo
                </button>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm font-bold text-gray-500 dark:text-[#8696a0] hover:text-gray-900 dark:hover:text-[#e9edef] underline transition-colors"
                >
                    Cerrar Sesión
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>