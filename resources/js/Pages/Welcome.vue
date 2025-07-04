<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button'


defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    laravelVersion: string;
    phpVersion: string;
}>();

const handleClick = () => {
    alert('Holamundo desde Shadcn vueaz')
}
</script>

<template>
    <Head title="Welcome" />
    <div class="bg-gray-50 text-black dark:bg-black dark:text-white">
        <div
        class="relative min-h-screen flex selection:bg-[#FF2D20] selection:text-white"
        >

            <nav class="w-[250px] bg-slate-300 border-r-2 p-4">
                <ul class="flex flex-col gap-3">
                    <li>
                        <Button as-child class="dark:bg-muted dark:text-white dark:hover:bg-muted dark:hover:text-white justify-start w-full">
                            <Link href="dashboard">Dashboard</Link>
                        </Button>
                    </li>
                    <li>
                        <Button as-child class="dark:bg-muted dark:text-white dark:hover:bg-muted dark:hover:text-white justify-start w-full" variant="outline">
                            <Link href="companies">Empresas</Link>
                        </Button>
                    </li>
                    <li>
                        <Button as-child class="dark:bg-muted dark:text-white dark:hover:bg-muted dark:hover:text-white justify-start w-full" variant="outline">
                            <Link href="employees">Empleados</Link>
                        </Button>
                    </li>
                </ul>
            </nav>

            <main class="flex-1 p-4">

            </main>
        </div>
    </div>
</template>
