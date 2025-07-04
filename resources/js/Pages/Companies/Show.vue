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


const encabezados = ref<string[]>([]);

const encabezadosFiltrados = computed<string[]>(() => {
  const result: string[] = [];
  for (const header of encabezados.value) {
    if (header.includes("IMSS")) {
      break; // Detiene la iteración en "IMSS" sin agregarlo
    }
    if (header.includes("0")) {
        const variable = header.split("/");
      result.push(variable[variable.length - 1]); // Solo agrega si contiene "0"
    }
  }
  return result;
});

const procesarArchivo = (event: Event): void => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  const reader = new FileReader();

  reader.onload = (e: ProgressEvent<FileReader>) => {
    if (!e.target?.result) return;
    const data = new Uint8Array(e.target.result as ArrayBuffer);
    const workbook: XLSX.WorkBook = XLSX.read(data, { type: "array" });

    // Obtener la primera hoja
    const sheetName: string = workbook.SheetNames[0];
    const sheet: XLSX.WorkSheet = workbook.Sheets[sheetName];

    // Convertir la hoja en JSON
    const jsonData: (string | undefined)[][] = XLSX.utils.sheet_to_json(sheet, { header: 1 });

    // Guardar encabezados en el estado (verificamos que sean strings)
    encabezados.value = jsonData[0]?.map(String) || [];
  };

  reader.readAsArrayBuffer(file);
};

const selectedItems = ref<string[]>([]);

// Función para manejar la selección manualmente
const toggleSelection = (concepto: string, checked: boolean) => {
  if (checked) {
    selectedItems.value.push(concepto);
  } else {
    selectedItems.value = selectedItems.value.filter((item) => item !== concepto);
  }
};
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
                <Input id="file" type="file" @input="formFile.file = $event.target.files[0]" @change="procesarArchivo"/>
                <Button :disabled="!formFile.file" class=" self-end" @click="handleFile">Subir archivo</Button>
            </div>


        </div>
          <!-- <div v-if="encabezadosFiltrados.length">
            <div class="mx-auto w-full">
                <h3 class="text-center">Encabezados del archivo:</h3>
                <div class="p-4 pb-0">
                    <div class="flex items-center justify-center flex-wrap gap-5">
                        <div class="flex items-center space-x-2 w-1/6" v-for="(item, index) in encabezadosFiltrados" :key="index">
                            <Checkbox
                                :checked="selectedItems.includes(item)"
                                @update:checked="(checked) => toggleSelection(item, checked)"
                            />
                            <label
                                for="terms"
                                class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                {{ item }}
                            </label>
                        </div>
                    </div>
                    <Button class=" self-end" :disabled="selectedItems.length <= 0" @click="handleFile">Subir archivo</Button>
                </div>
            </div>
        </div> -->
    </AuthenticatedLayout>
</template>
