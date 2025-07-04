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

interface EmployeeSalary {
    id: number
    period: number
    year: number
    employee_id: number
    category_id: number
    daily_salary: number
    daily_bonus: number
    vacations_days: number
    vacations_import: number
    vacation_bonus: number
    sdi: number
    sdi_variable: number
    total_sdi: number
    sdi_limit: number
    sdi_aud: number
    sdi_quoted: number
    difference: number
}

const props = defineProps<{
    salary: EmployeeSalary
}>()

const form = useForm({...props.salary});

const submit = () => {
    form.patch(route('employee.salaryUpdate', props.salary.id), {
        onFinish: () => {
            form.reset('sdi_variable', 'sdi_quoted');
        },
    });
};
</script>

<template>
<AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar</h2>
        </template>

        <form @submit.prevent="submit">
            <div class="flex gap-3">
                <div class="w-1/3 mb-3">
                    <Label for="sdi_variable">SDI variable</Label>
                    <Input id="sdi_variable" type="text" v-model.number="form.sdi_variable"/>
                </div>

                <div class="w-1/3 mb-3">
                    <Label for="sdi_quoted">SDI Cotizado</Label>
                    <Input id="sdi_quoted" type="text" v-model.number="form.sdi_quoted"/>
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
