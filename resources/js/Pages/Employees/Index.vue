<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Datatable from '@/Components/Datatable/index.vue';
import { useForm } from '@inertiajs/vue3';
import { h } from 'vue';
import { ColumnDef } from '@tanstack/vue-table'

interface Company {
    id: number
    name: string
    company_name: string
}
interface Employee {
    id: number
    name: string
    clave: string
    company: Company
}

const form = useForm({});
const props = defineProps<{
    employees: Employee[]
}>()

const columns: ColumnDef<Employee>[] = [
    {
        accessorKey: 'name',
        header: 'Nombre',
        cell: ({ row }) => row.getValue('name')
    },
    {
        accessorKey: 'clave',
        header: 'Clave',
        cell: ({ row }) => row.getValue('clave')
    },
    {
        accessorKey: 'company',
        header: 'Empresa',
        cell: ({ row }) => row.original.company.name
    },
    {
        accessorKey: 'Acciones',
        header: 'Acciones',
    }
]



import Button from '@/Components/ui/button/Button.vue';
import { Link } from '@inertiajs/vue3';
import {
  TableCell
} from '@/Components/ui/table'

const handleDelete = (id: number) => {
    form.delete(route('employees.destroy', id));
}
</script>

<template>

    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Empleados</h2>
        </template>

        <Button class="mb-5" as-child>
            <Link :href="route('employees.create')">Agregar empleados</Link>
        </Button>

        <Datatable :columns="columns" :data="employees">
            <template #actions="data">
                <TableCell class="text-left">
                    <Button variant="outline" as-child class="mr-3">
                        <Link :href="route('employees.show', data.data.original.id)">Ver</Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="route('employees.concepts', data.data.original.id)">Ver nóminas</Link>
                    </Button>
                </TableCell>
            </template>
        </Datatable>
    </AuthenticatedLayout>
</template>
