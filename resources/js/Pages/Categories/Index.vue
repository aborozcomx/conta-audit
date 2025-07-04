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

interface Category {
    id: number
    name: string
}

const form = useForm({});

defineProps<{
    categories: Category[]
}>()

import Button from '@/Components/ui/button/Button.vue';
import {Link} from '@inertiajs/vue3';

const handleDelete = (id: number) => {
    form.delete(route('categories.destroy', id));
}

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Categorias</h2>
        </template>

        <Button as-child>
            <Link :href="route('categories.create')">Agregar categoria</Link>
        </Button>
        <Table>
    <TableCaption>Lista de categorias</TableCaption>
    <TableHeader>
      <TableRow>
        <TableHead>
          ID
        </TableHead>
        <TableHead>Nombre</TableHead>
        <TableHead class="text-right">
          Acciones
        </TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow v-for="category in categories" :key="category.id">
        <TableCell class="font-medium">
          {{ category.id }}
        </TableCell>
        <TableCell>{{ category.name }}</TableCell>
        <TableCell class="text-right">
          <Button variant="outline" class="mr-3" as-child>
            <Link :href="route('categories.edit', category.id)">Editar</Link>
          </Button>
          <Button variant="destructive" @click="handleDelete(category.id)">Eliminar</Button>
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>
    </AuthenticatedLayout>
</template>
