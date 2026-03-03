<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-[#f0f2f5] dark:bg-[#111b21] transition-colors duration-300">
        
        <nav class="sticky top-0 z-50 bg-white dark:bg-[#202c33] border-b border-gray-200 dark:border-[#313d45] shadow-sm transition-colors duration-300">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('dashboard')">
                                <ApplicationLogo class="block h-9 w-auto fill-current text-[#00a884]" />
                            </Link>
                        </div>

                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')" class="text-gray-600 dark:text-[#e9edef] hover:text-gray-900 dark:hover:text-white">
                                Chat
                            </NavLink>
                            <NavLink :href="route('campaigns.index')" :active="route().current('campaigns.index')" class="text-gray-600 dark:text-[#e9edef] hover:text-gray-900 dark:hover:text-white">
                                Campañas
                            </NavLink>

                            <template v-if="$page.props.auth.user.role === 'admin'">
                                <NavLink :href="route('business')" :active="route().current('business')" class="text-gray-600 dark:text-[#e9edef] hover:text-gray-900 dark:hover:text-white">
                                    Perfil de Empresa
                                </NavLink>
                                <NavLink :href="route('whatsapp')" :active="route().current('whatsapp')" class="text-gray-600 dark:text-[#e9edef] hover:text-gray-900 dark:hover:text-white">
                                    Cuenta WhatsApp
                                </NavLink>
                                <NavLink :href="route('analytics')" :active="route().current('analytics')" class="text-gray-700 dark:text-[#e9edef]">
                                    Estadísticas
                                </NavLink>
                                <NavLink :href="route('users.index')" :active="route().current('users.index')" class="text-gray-600 dark:text-[#e9edef] hover:text-gray-900 dark:hover:text-white"> 
                                    Usuarios 
                                </NavLink>
                            </template>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <div class="relative ms-3">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button type="button" class="inline-flex items-center rounded-md border border-transparent bg-transparent px-3 py-2 text-sm font-semibold leading-4 text-gray-700 dark:text-[#e9edef] transition duration-150 ease-in-out hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-[#2a3942] focus:outline-none">
                                            {{ $page.props.auth.user.name }}
                                            <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                                
                                <template #content>
                                    <DropdownLink :href="route('profile.edit')" class="dark:bg-[#202c33] dark:text-[#e9edef] dark:hover:bg-[#2a3942]"> 
                                        Mi Perfil 
                                    </DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button" class="dark:bg-[#202c33] dark:text-[#e9edef] dark:hover:bg-[#2a3942]"> 
                                        Cerrar Sesión 
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 dark:text-[#8696a0] hover:bg-gray-100 dark:hover:bg-[#2a3942] hover:text-gray-700 dark:hover:text-[#e9edef] focus:outline-none transition-colors duration-200">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden bg-white dark:bg-[#202c33] border-t border-gray-200 dark:border-[#313d45] absolute w-full shadow-lg">
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')" class="text-gray-700 dark:text-[#e9edef]"> 
                        Chat 
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('campaigns.index')" :active="route().current('campaigns.index')" class="text-gray-700 dark:text-[#e9edef]"> 
                        Campañas 
                    </ResponsiveNavLink>
                    
                    <template v-if="$page.props.auth.user.role === 'admin'">
                        <ResponsiveNavLink :href="route('business')" :active="route().current('business')" class="text-gray-700 dark:text-[#e9edef]"> 
                            Perfil de Empresa 
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('whatsapp')" :active="route().current('whatsapp')" class="text-gray-700 dark:text-[#e9edef]"> 
                            Cuenta WhatsApp 
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('analytics')" :active="route().current('analytics')" class="text-gray-700 dark:text-[#e9edef]">
                            Estadísticas
                        </ResponsiveNavLink>
                    </template>
                </div>

                <div class="border-t border-gray-200 dark:border-[#313d45] pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium text-gray-800 dark:text-[#e9edef]">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm font-medium text-gray-500 dark:text-[#8696a0]">{{ $page.props.auth.user.email }}</div>
                    </div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')" class="text-gray-700 dark:text-[#e9edef]">Mi Perfil</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button" class="text-gray-700 dark:text-[#e9edef]">Cerrar Sesión</ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header class="bg-white dark:bg-[#202c33] shadow dark:shadow-none dark:border-b dark:border-[#313d45]" v-if="$slots.header">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>