<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);
const form = useForm({ password: '' });

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    setTimeout(() => passwordInput.value?.focus(), 250);
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => { confirmingUserDeletion.value = false; form.reset(); };
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-bold text-red-600">Eliminar Cuenta</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-[#8696a0]">Esta acción borrará permanentemente tus datos.</p>
        </header>

        <DangerButton @click="confirmUserDeletion">Eliminar Cuenta de Forma Definitiva</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6 dark:bg-[#202c33]">
                <h2 class="text-lg font-bold text-gray-900 dark:text-[#e9edef]">¿Confirmas la eliminación?</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-[#8696a0]">Por favor, ingresa tu contraseña.</p>
                <div class="mt-6">
                    <TextInput id="password" ref="passwordInput" v-model="form.password" type="password" class="mt-1 block w-full dark:bg-[#111b21] dark:border-[#313d45] dark:text-[#e9edef] focus:border-red-500 focus:ring-red-500" placeholder="Contraseña" />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                    <DangerButton class="ms-3" :disabled="form.processing" @click="deleteUser">Eliminar</DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>