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

interface Year {
    slug: string
    name: string
}

interface Uma {
    id: number
    year: string
    balance: number
}

const props = defineProps<{
    years: Year[]
    uma: Uma
}>()

const form = useForm({...props.uma});

const submit = () => {
    form.put(route('umas.update', props.uma.id), {
        onFinish: () => {
            form.reset('year', 'balance');
        },
    });
};
</script>

<template>
<AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">UMAS</h2>
        </template>

        <form @submit.prevent="submit">
            <div class="flex gap-3">
                <div class="w-1/3 mb-3">
                    <Label for="name">Año</Label>
                    <Select class="w-1/2" v-model="form.year">
                        <SelectTrigger>
                            <SelectValue placeholder="Selecciona año" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="year in years" :key="year.slug" :value="year.slug">
                                {{ year.name }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>

                <div class="w-1/3 mb-3">
                    <Label for="rfc">Saldo</Label>
                    <Input id="rfc" type="text"  v-model="form.balance"/>
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
