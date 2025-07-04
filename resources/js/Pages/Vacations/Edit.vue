<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Button from '@/Components/ui/button/Button.vue';
import { useForm } from '@inertiajs/vue3';
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'

interface Category {
    id: number
    name: string
}

interface Vacation {
    id: number
    years: string
    days: string
    category_id: string
}

const props = defineProps<{
    vacation: Vacation
    categories: Category[]
}>()

const form = useForm({...props.vacation});

const submit = () => {
    form.put(route('vacations.update', props.vacation.id), {
        onFinish: () => {},
    });
};
</script>

<template>
<AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Empresas</h2>
        </template>

        <form @submit.prevent="submit">
            <div class="flex gap-3">
                <div class="w-1/3 mb-3">
                    <Label for="name">Años</Label>
                    <Input id="name" type="text" v-model="form.years"/>
                </div>

                <div class="w-1/3 mb-3">
                    <Label for="lastname">Días</Label>
                    <Input id="lastname" type="text" v-model="form.days"/>
                </div>

                <div class="w-1/3 mb-3">
                    <Label for="company">Categoría</Label>
                    <Select v-model="form.category_id">
                        <SelectTrigger>
                        <SelectValue placeholder="Selecciona la categoria" />
                        </SelectTrigger>
                        <SelectContent>
                        <SelectGroup>
                            <SelectItem v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                            </SelectItem>
                        </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div class="flex justify-end">
                <div>
                    <Button type="submit">Editar</Button>
                </div>
            </div>
        </form>
</AuthenticatedLayout>
</template>
