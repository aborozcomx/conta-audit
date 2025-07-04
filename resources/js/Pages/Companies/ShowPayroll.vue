<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import {Link} from '@inertiajs/vue3';

import { useForm } from '@inertiajs/vue3';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'

import Button from '@/Components/ui/button/Button.vue';
import { toast } from 'vue-sonner'

interface Year {
    slug: string
    name: string
}

interface Company {
    id: number
    name: string
    rfc: string
    company_name: string
}

const page = usePage()

const props = defineProps<{
    years: Year[],
    company: Company
}>()

const form = useForm({});

const formFile = useForm({
    file: null,
    year: '2023'
});


const handleFile = () => {
    formFile.post(route('companies.filesPayrolls', props.company.id), {
        onFinish: () => {
            formFile.reset('file');
            toast.info(page.props.flash.message);
        },
    });
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Empresa: {{ company.name }}</h2>
        </template>

        <div class="grid place-content-center">
            <div class="grid w-full max-w-sm items-center gap-1.5 mb-3">
                <label for="">
                        Año
                        <Select class="w-1/2" v-model="formFile.year">
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
                    </label>

            </div>

            <div class="grid w-full max-w-sm items-center gap-1.5 mb-3">
                <Label for="picture">IMSS</Label>
                <Input id="file" type="file" @input="formFile.file = $event.target.files[0]"/>
            </div>

            <Button :disabled="!formFile.file" @click="handleFile">Subir IMSS</Button>
        </div>
    </AuthenticatedLayout>
</template>
