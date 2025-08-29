<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ColumnDef } from '@tanstack/vue-table'
import Datatable from '@/Components/Datatable/index.vue';

import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table'

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

import { useForm } from '@inertiajs/vue3';

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

interface EmployeeQuota {
    id: number
    period: number
    year: number
    employee_id: number
    base_salary: number
    days: number
    absence: number
    incapacity: number
    total_days: number
    difference_days: number
    base_price_em: number
    base_price_rt: number
    base_price_iv: number
    fixed_price: number
    sdmg: number
    in_cash: number
    disability_health: number
    pensioners: number
    risk_price: number
    nurseries: number
    total_audit: number
    total_company: number
    difference: number
    name: string
    rfc: string
    social_number: string
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

const columns: ColumnDef<EmployeeQuota>[] = [
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
        accessorKey: 'name',
        header: 'Empleado',
        cell: ({ row }) => row.getValue('name')
    },
    {
        accessorKey: 'rfc',
        header: 'RFC',
        cell: ({ row }) => row.getValue('rfc')
    },
    {
        accessorKey: 'social_number',
        header: 'NSS',
        cell: ({ row }) => row.getValue('social_number')
    },
    {
        accessorKey: 'base_salary',
        header: 'SDC',
        cell: ({ row }) => row.getValue('base_salary')
    },
    {
        accessorKey: 'days',
        header: 'Días',
        cell: ({ row }) => row.getValue('days')
    },
    {
        accessorKey: 'absence',
        header: 'Ausencias',
        cell: ({ row }) => row.getValue('absence')
    },
    {
        accessorKey: 'incapacity',
        header: 'Incapacidad',
        cell: ({ row }) => row.getValue('incapacity')
    },
    {
        accessorKey: 'total_days',
        header: 'Días totales',
        cell: ({ row }) => row.getValue('total_days')
    },
    {
        accessorKey: 'difference_days',
        header: 'Diferencia días',
        cell: ({ row }) => row.getValue('difference_days')
    },
    {
        accessorKey: 'base_price_em',
        header: 'Cotización E y M',
        cell: ({ row }) => row.getValue('base_price_em')
    },
    {
        accessorKey: 'base_price_rt',
        header: 'Cotización RT y GPS',
        cell: ({ row }) => row.getValue('base_price_rt')
    },
    {
        accessorKey: 'base_price_iv',
        header: 'Cotización I y V',
        cell: ({ row }) => row.getValue('base_price_iv')
    },
    {
        accessorKey: 'fixed_price',
        header: 'Cuota fija',
        cell: ({ row }) => row.getValue('fixed_price')
    },
    {
        accessorKey: 'sdmg',
        header: 'SDMG',
        cell: ({ row }) => row.getValue('sdmg')
    },
    {
        accessorKey: 'in_cash',
        header: 'En dinero',
        cell: ({ row }) => row.getValue('in_cash')
    },
    {
        accessorKey: 'disability_health',
        header: 'Invalidez y vida',
        cell: ({ row }) => row.getValue('disability_health')
    },
    {
        accessorKey: 'pensioners',
        header: 'Pensionados',
        cell: ({ row }) => row.getValue('pensioners')
    },
    {
        accessorKey: 'risk_price',
        header: 'Prima de riesgo',
        cell: ({ row }) => row.getValue('risk_price')
    },
    {
        accessorKey: 'nurseries',
        header: 'Guarderías',
        cell: ({ row }) => row.getValue('nurseries')
    },
    {
        accessorKey: 'total_audit',
        header: 'Total auditoría',
        cell: ({ row }) => row.getValue('total_audit')
    },
    {
        accessorKey: 'total_company',
        header: 'Total Compañía',
        cell: ({ row }) => row.getValue('total_company')
    },
    {
        accessorKey: 'difference',
        header: 'Diferencia',
        cell: ({ row }) => {
            return row.getValue('difference')
        }
    },
    {
        accessorKey: 'Total',
        header: 'Total Neto',
        cell: ({ row }) => {
            const total = props.grouped[row.original.social_number] as number;
            return total.toFixed(2)

        }
    }
]

const months = ["1","2","3","4","5","6","7","8","9","10","11","12"];

const props = defineProps<{
    years: Year[],
    year: string,
    period: string,
    quotas: EmployeeQuota[],
    companies: Company[],
    company: string,
    grouped: Object,
}>()

const formFilter = useForm({
    year: props.year,
    period: props.period,
    company: props.company
});


const handleClick = () => {
    formFilter.get(route('employees.showQuotas'))
}

const handleClickExport = () => {
    const URL = `${route('employees.exportQuotas')}?period=${formFilter.period}&year=${formFilter.year}&company_id=${formFilter.company}`
    window.open(URL)
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="container">
            <div class="flex justify-end items-start mb-8 gap-4">
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

                    <Button v-if="quotas.length > 0"
                        class=" bg-green-400 hover:bg-green-800 hover:text-slate-50"
                        variant="outline"
                        @click="handleClickExport">
                        Exportar

                    </Button>
                </div>
            </div>

            <Datatable :columns="columns" :data="quotas"></Datatable>
        </div>
    </AuthenticatedLayout>
</template>
