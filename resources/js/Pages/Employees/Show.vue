<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

import { ColumnDef } from '@tanstack/vue-table'
import Datatable from '@/Components/Datatable/index.vue';

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


import { useForm,usePage } from '@inertiajs/vue3';

interface Company {
    id: string
    name: string
    rfc: string
    company_name: string
}

interface Year {
    slug: string
    name: string
}

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
    employee: Employee
}

interface Employee {
    id: number
    name: string
    rfc: string
    curp: string
    number: number
    start_date: Date
    social_number: string
    base_salary: number
    company: Company
}

interface Variable {
    concepto: string
    total: number
}

const columns: ColumnDef<EmployeeSalary>[] = [
    {
        accessorKey: 'period',
        header: 'Periodo',
        cell: ({ row }) => row.getValue('period')
    },
    {
        accessorKey: 'year',
        header: 'Año',
        cell: ({ row }) => row.getValue('year')
    },
    {
        accessorKey: 'employee',
        header: 'Empleado',
        cell: ({ row }) => row.original.employee.name
    },
    {
        accessorKey: 'employee',
        header: 'RFC',
        cell: ({ row }) => row.original.employee.rfc
    },
    {
        accessorKey: 'employee',
        header: 'NSS',
        cell: ({ row }) => row.original.employee.social_number
    },
    {
        accessorKey: 'employee',
        header: 'Fecha inicio',
        cell: ({ row }) => row.original.employee.start_date
    },
    {
        accessorKey: 'daily_salary',
        header: 'Salario diario',
        cell: ({ row }) => row.getValue('daily_salary')
    },
    {
        accessorKey: 'daily_bonus',
        header: 'Aguinaldo diario',
        cell: ({ row }) => row.getValue('daily_bonus')
    },
    {
        accessorKey: 'vacations_days',
        header: 'Días de vacaciones',
        cell: ({ row }) => row.getValue('vacations_days')
    },
    {
        accessorKey: 'vacations_import',
        header: 'Importe de vacaciones',
        cell: ({ row }) => row.getValue('vacations_import')
    },
    {
        accessorKey: 'vacation_bonus',
        header: 'Prima vacacional',
        cell: ({ row }) => row.getValue('vacation_bonus')
    },
    {
        accessorKey: 'sdi',
        header: 'SDI',
        cell: ({ row }) => row.getValue('sdi')
    },
    {
        accessorKey: 'sdi_variable',
        header: 'SDI variable',
        cell: ({ row }) => row.getValue('sdi_variable')
    },
    {
        accessorKey: 'total_sdi',
        header: 'SDI Total',
        cell: ({ row }) => row.getValue('total_sdi')
    },
    {
        accessorKey: 'sdi_limit',
        header: 'Tope SDI',
        cell: ({ row }) => row.getValue('sdi_limit')
    },
    {
        accessorKey: 'sdi_aud',
        header: 'SDI Aud.',
        cell: ({ row }) => row.getValue('sdi_aud')
    },
    {
        accessorKey: 'sdi_quoted',
        header: 'IMSS CIA',
        cell: ({ row }) => row.getValue('sdi_quoted')
    },
    {
        accessorKey: 'difference',
        header: 'Diferencia',
        cell: ({ row }) => row.getValue('difference')
    }
]

const months = ["1","2","3","4","5","6","7","8","9","10","11","12"];

const props = defineProps<{
    years: Year[],
    year: string,
    period: string,
    salaries: EmployeeSalary[],
    companies: Company[],
    company: string,
    variables: Variable[]

}>()

const formFilter = useForm({
    year: props.year,
    period: props.period,
    company: props.company,
    variables: [],
});

const handleClick = () => {
    formFilter.get(route('employees.salaries'))
}

const handleClickExport = () => {
    const URL = `${route('employees.exportSalaries')}?period=${formFilter.period}&year=${formFilter.year}`
    window.open(URL)
}


</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="container">
            <div class="flex justify-end items-end mb-8 gap-4">
                <div class="w-1/4">
                    <label for="">
                        Empresa
                        <Select class="w-1/2" v-model="formFilter.company">
                            <SelectTrigger>
                            <SelectValue placeholder="Seleccionar empresa" />
                            </SelectTrigger>
                            <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="company in companies" :key="company.id" :value="company.id.toString()">
                                {{ company.name }}
                                </SelectItem>
                            </SelectGroup>
                            </SelectContent>
                        </Select>
                    </label>
                </div>

                <div class="w-1/4">
                    <label for="">
                        Año
                        <Select class="w-1/2" v-model="formFilter.year">
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

                <div class="w-1/4">
                    <label for="">
                        Periodo
                        <Select v-model="formFilter.period">
                            <SelectTrigger>
                            <SelectValue placeholder="Seleccionar periodo" />
                            </SelectTrigger>
                            <SelectContent>
                            <SelectGroup>
                                <SelectItem v-for="period in months" :value="period">
                                {{ period }}
                                </SelectItem>
                            </SelectGroup>
                            </SelectContent>
                        </Select>
                    </label>
                </div>

                <div class="w-1/4 flex justify-end gap-3">
                    <Button @click="handleClick">
                        Filtrar
                    </Button>

                    <Button v-if="salaries.length > 0"
                        class=" bg-green-400 hover:bg-green-800 hover:text-slate-50"
                        variant="outline"
                        @click="handleClickExport">
                        Exportar

                    </Button>
                </div>
            </div>

            <Datatable :columns="columns" :data="salaries" class></Datatable>
        </div>
    </AuthenticatedLayout>
</template>
