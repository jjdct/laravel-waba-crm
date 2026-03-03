<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center pt-10 sm:justify-center sm:pt-0 bg-[#f0f2f5] dark:bg-[#111b21] px-4 transition-colors duration-300">
        
        <div>
            <Link href="/">
                <ApplicationLogo class="h-16 w-16 sm:h-20 sm:w-20 fill-current text-[#00a884] transition-all" />
            </Link>
        </div>

        <div class="mt-8 mb-10 w-full overflow-hidden bg-white dark:bg-[#202c33] px-6 py-8 shadow-lg dark:shadow-2xl sm:max-w-md rounded-2xl sm:rounded-3xl border border-gray-100 dark:border-[#313d45] transition-colors duration-300">
            <slot />
        </div>
        
    </div>
</template>
