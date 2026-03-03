<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Mi Perfil" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg md:text-xl font-bold leading-tight text-gray-800 dark:text-[#e9edef]">
                Configuración del Perfil
            </h2>
        </template>

        <div class="py-6 md:py-12 bg-[#f0f2f5] dark:bg-[#111b21] min-h-[calc(100vh-65px)] transition-colors duration-300">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                
                <div class="bg-white dark:bg-[#202c33] p-5 shadow-sm border border-gray-200 dark:border-[#313d45] rounded-xl sm:rounded-2xl sm:p-8 transition-colors duration-300">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl text-gray-900 dark:text-[#e9edef]"
                    />
                </div>

                <div class="bg-white dark:bg-[#202c33] p-5 shadow-sm border border-gray-200 dark:border-[#313d45] rounded-xl sm:rounded-2xl sm:p-8 transition-colors duration-300">
                    <UpdatePasswordForm class="max-w-xl text-gray-900 dark:text-[#e9edef]" />
                </div>

                <div class="bg-white dark:bg-[#202c33] p-5 shadow-sm border border-gray-200 dark:border-[#313d45] rounded-xl sm:rounded-2xl sm:p-8 border-t-4 border-t-red-500 transition-colors duration-300">
                    <DeleteUserForm class="max-w-xl text-gray-900 dark:text-[#e9edef]" />
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>