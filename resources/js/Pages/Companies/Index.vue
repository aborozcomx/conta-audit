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

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog'
import { useForm } from '@inertiajs/vue3';

interface Company {
    id: number
    name: string
    rfc: string
    company_name: string
}

const form = useForm({});

defineProps<{
    companies: Company[]
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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Empresas</h2>
        </template>

        <Button as-child>
            <Link :href="route('companies.create')">Agregar empresa</Link>
        </Button>
        <Table>
    <TableCaption>Lista de empresas</TableCaption>
    <TableHeader>
      <TableRow>
        <TableHead>
          ID
        </TableHead>
        <TableHead>Nombre</TableHead>
        <TableHead>RFC</TableHead>
        <TableHead class="text-right">
          Razon Social
        </TableHead>
        <TableHead class="text-right">
          Acciones
        </TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow v-for="company in companies" :key="company.id">
        <TableCell class="font-medium">
          {{ company.id }}
        </TableCell>
        <TableCell>{{ company.name }}</TableCell>
        <TableCell>{{ company.rfc }}</TableCell>
        <TableCell class="text-right">
          {{ company.company_name }}
        </TableCell>
        <TableCell class="text-right">
          <Button variant="outline" class="mr-3" as-child>
            <Link :href="route('companies.import', company.id)">Importar CFDIS</Link>
          </Button>
          <Button variant="outline" class="mr-3" as-child>
            <Link :href="route('companies.importPayrolls', company.id)">Importar IMSS</Link>
          </Button>
          <Button variant="outline" class="mr-3" as-child>
            <Link :href="route('companies.importVariables', company.id)">Importar Variables CIA</Link>
          </Button>
          <Button variant="outline" class="mr-3" as-child>
            <Link :href="route('companies.edit', company.id)">Editar</Link>
          </Button>
          <Button variant="destructive" @click="handleDelete(company.id)">Eliminar</Button>
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>
    </AuthenticatedLayout>
</template>
