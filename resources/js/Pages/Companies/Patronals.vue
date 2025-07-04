<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/Components/ui/table'
import { useForm } from '@inertiajs/vue3';

interface Company {
    id: number
    name: string
    rfc: string
    company_name: string
}

interface CompanyPatronal {
    id: number
    name: string
    risk: number
    company: Company
}

const form = useForm({});

defineProps<{
    patronals: CompanyPatronal[]
    company: Company
}>()

import Button from '@/Components/ui/button/Button.vue';
import {Link} from '@inertiajs/vue3';

const handleDelete = (id: number) => {
    form.delete(route('companies.destroy', id));
}

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Registros patronales</h2>
        </template>

        <Button as-child>
            <Link :href="route('companies.createPatronal')">Agregar regisitro patronal</Link>
        </Button>
        <Table>
    <TableCaption>Lista de registros patronales</TableCaption>
    <TableHeader>
      <TableRow>
        <TableHead>
          ID
        </TableHead>
        <TableHead>Nombre</TableHead>
        <TableHead>Prima de riesgo</TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow v-for="patronal in patronals" :key="patronal.id">
        <TableCell class="font-medium">
          {{ patronal.id }}
        </TableCell>
        <TableCell>{{ patronal.name }}</TableCell>
        <TableCell>{{ patronal.risk }}</TableCell>
      </TableRow>
    </TableBody>
  </Table>
    </AuthenticatedLayout>
</template>
