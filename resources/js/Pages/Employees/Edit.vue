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

interface Employee {
    id: number
    name: string
    lastname: string
    email: string
    phone: string
    rfc: string
    company: Company
    company_id: number
}

interface Company {
    id: number
    name: string
    rfc: string
    company_name: string
}

const props = defineProps<{
    employee: Employee
    companies: Company[]
}>()

const form = useForm({...props.employee});

const submit = () => {
    form.put(route('employees.update', props.employee.id), {
        onFinish: () => {
            form.reset('name', 'rfc');
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
                    <Label for="lastname">Apellido</Label>
                    <Input id="lastname" type="text" v-model="form.lastname"/>
                </div>

                <div class="w-1/3 mb-3">
                    <Label for="email">Correo electronico</Label>
                    <Input id="email" type="email" v-model="form.email"/>
                </div>
            </div>

            <div class="flex gap-3">
                <div class="w-1/3 mb-3">
                    <Label for="phone">Telefono</Label>
                    <Input id="phone" type="text" v-model="form.phone"/>
                </div>

                <div class="w-1/3 mb-3">
                    <Label for="rfc">RFC</Label>
                    <Input id="rfc" type="text"  v-model="form.rfc"/>
                </div>

                <div class="w-1/3 mb-3">
                    <Label for="company">Empresa</Label>
                    <Select v-model="form.company_id.toString">
                        <SelectTrigger>
                        <SelectValue placeholder="Selecciona la empresa" />
                        </SelectTrigger>
                        <SelectContent>
                        <SelectGroup>
                            <SelectItem v-for="company in companies" :key="company.id" :value="company.id.toString()">
                            {{ company.name }}
                            </SelectItem>
                        </SelectGroup>
                        </SelectContent>
                    </Select>
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
