<script setup lang="ts">
import { ref, onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button'
import { Toaster } from '@/Components/ui/sonner'
import TheHeader from '@/Components/TheHeader.vue';


</script>

<template>
    <div>
        <Toaster position="top-right" rich-colors/>
        <TheHeader />
        <div class="min-h-screen bg-gray-100 flex">


            <!-- Page Content -->
            <main class="flex-1">
                <!-- Page Heading -->
                <!-- <header class="bg-white shadow" v-if="$slots.header">
                    <div class="w-full mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header> -->

                <div class="p-16">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
