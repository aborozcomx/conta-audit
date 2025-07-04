<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Button from '@/Components/ui/button/Button.vue';
import { useForm } from '@inertiajs/vue3';
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'


interface Company {
    id: number
    name: string
    rfc: string
    company_name: string
    vacation_days: number
    vacation_bonus: number
}

const props = defineProps<{
    company: Company
}>()

const form = useForm({...props.company});

const submit = () => {
    form.put(route('companies.update', props.company.id), {
        onFinish: () => {
            form.reset('name', 'rfc', 'company_name');
        },
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
                    <Label for="name">Nombre</Label>
                    <Input id="name" type="text" v-model="form.name"/>
                </div>

                <div class="w-1/3 mb-3">
                    <Label for="rfc">RFC</Label>
                    <Input id="rfc" type="text"  v-model="form.rfc"/>
                </div>

                <div class="w-1/3 mb-3">
                    <Label for="company_name">Razon social</Label>
                    <Input id="company_name" type="text"  v-model="form.company_name"/>
                </div>
            </div>

            <div class="flex gap-3">
                <div class="w-1/3 mb-3">
                    <Label for="vacation_days">Dias de vacaciones</Label>
                    <Input id="vacation_days" type="text"  v-model.number="form.vacation_days"/>
                </div>

                <div class="w-1/3 mb-3">
                    <Label for="vacation_bonus">Prima vacacional</Label>
                    <Input id="vacation_bonus" type="text"  v-model.number="form.vacation_bonus"/>
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
