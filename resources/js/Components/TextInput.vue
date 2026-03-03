<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-md border-gray-300 shadow-sm 
               focus:border-[#00a884] focus:ring-[#00a884] 
               dark:border-[#313d45] dark:bg-[#111b21] dark:text-gray-300 
               dark:focus:border-[#00a884] dark:focus:ring-[#00a884]"
        v-model="model"
        ref="input"
    />
</template>
