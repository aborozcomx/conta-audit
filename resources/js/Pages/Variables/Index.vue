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

interface Variable {
    id: number
    code: string
    name: string
}

const form = useForm({});

defineProps<{
    variables: Variable[]
}>()

import Button from '@/Components/ui/button/Button.vue';
import {Link} from '@inertiajs/vue3';

const handleDelete = (id: number) => {
    form.delete(route('variables.destroy', id));
}

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Variables</h2>
        </template>

        <Button as-child>
            <Link :href="route('variables.create')">Agregar variable</Link>
        </Button>
        <Table>
    <TableCaption>Lista de variables</TableCaption>
    <TableHeader>
      <TableRow>
        <TableHead>Clave</TableHead>
        <TableHead>Nombre</TableHead>
        <TableHead class="text-right">
          Acciones
        </TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow v-for="variable in variables" :key="variable.id">
        <TableCell>{{ variable.code }}</TableCell>
        <TableCell>{{ variable.name }}</TableCell>
        <TableCell class="text-right">
          <Button variant="outline" class="mr-3" as-child>
            <Link :href="route('variables.edit', variable.id)">Editar</Link>
          </Button>
          <Button variant="destructive" @click="handleDelete(variable.id)">Eliminar</Button>
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>
    </AuthenticatedLayout>
</template>
