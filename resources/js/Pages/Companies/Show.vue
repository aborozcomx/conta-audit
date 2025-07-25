<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head , usePage} from '@inertiajs/vue3';
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { computed, ref } from 'vue';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'
import { Checkbox } from '@/Components/ui/checkbox'

import { useForm } from '@inertiajs/vue3';
import Button from '@/Components/ui/button/Button.vue';
import { toast } from 'vue-sonner'
import * as XLSX from 'xlsx';

interface Company {
    id: number
    name: string
    rfc: string
    company_name: string
}

interface Year {
    slug: string
    name: string
}

const page = usePage()

const props = defineProps<{
    years: Year[],
    company: Company
}>()

const formFile = useForm({
    file: null,
    year: '2023',
    variables: []
});


const handleFile = () => {
    //formFile.variables = selectedItems.value;
    formFile.post(route('companies.files', props.company.id), {
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
                <Label for="picture">Seleccionar CFDIS</Label>
                <Input id="file" type="file" @input="formFile.file = $event.target.files[0]"/>
                <Button :disabled="!formFile.file" class=" self-end" @click="handleFile">Subir archivo</Button>
            </div>


        </div>
    </AuthenticatedLayout>
</template>
