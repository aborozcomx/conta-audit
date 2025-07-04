<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Button from '@/Components/ui/button/Button.vue';
import { useForm } from '@inertiajs/vue3';
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'


interface Category {
    id: number
    name: string
}

const props = defineProps<{
    category: Category
}>()

const form = useForm({...props.category});

const submit = () => {
    form.put(route('categories.update', props.category.id), {
        onFinish: () => {
            form.reset('name');
        },
    });
};
</script>

<template>
<AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Categorias</h2>
        </template>

        <form @submit.prevent="submit">
            <div class="flex gap-3">
                <div class="w-1/3 mb-3">
                    <Label for="name">Nombre</Label>
                    <Input id="name" type="text" v-model="form.name"/>
                </div>
            </div>

            <div class="flex justify-end">
                <div>
                    <Button type="submit">Guardar</Button>
                </div>
            </div>
        </form>
</AuthenticatedLayout>
</template>
