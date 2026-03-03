<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="rounded border-gray-300 shadow-sm transition-all duration-200
               text-[#00a884] focus:ring-[#00a884] 
               dark:border-[#313d45] dark:bg-[#111b21] 
               dark:focus:ring-offset-[#202c33]"
    />
</template>