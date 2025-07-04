<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import Datatable from '@/Components/Datatable/index.vue';
import { ColumnDef } from '@tanstack/vue-table'

interface Company {
    id: number
    name: string
    company_name: string
}

interface Concept {
    period: number
    year: number
    concepto: string
    amount: number
    is_exented: boolean
    is_taxed: boolean
    employee: Employee
}

interface Employee {
    id: number
    name: string
    clave: string
    company: Company
}

const props = defineProps<{
    concepts: Concept[]
}>()

const columns: ColumnDef<Concept>[] = [
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
        accessorKey: 'concepto',
        header: 'Concepto',
        cell: ({ row }) => row.getValue('concepto')
    },
    {
        accessorKey: 'amount',
        header: 'Monto',
        cell: ({ row }) => row.getValue('amount')
    },
    {
        accessorKey: 'employee',
        header: 'Empleado',
        cell: ({ row }) => row.original.employee.name
    },
    {
        accessorKey: 'is_exented',
        header: 'Exento/Gravado',
        cell: ({row}) => row.getValue('is_exented') ? 'Exento' : 'Gravado'
    }
]

</script>

<template>

    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Conceptos</h2>
        </template>

        <Datatable :columns="columns" :data="concepts">
        </Datatable>
    </AuthenticatedLayout>
</template>
