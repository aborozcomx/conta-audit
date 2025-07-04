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

interface Category {
    id: number
    name: string
}
interface Vacation {
    id: number
    years: string
    days: string
    category: Category
}

const form = useForm({});

defineProps<{
    vacations: Vacation[]
}>()

import Button from '@/Components/ui/button/Button.vue';
import {Link} from '@inertiajs/vue3';

const handleDelete = (id: number) => {
    form.delete(route('vacations.destroy', id));
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Vacaciones</h2>
        </template>

        <Button as-child>
            <Link :href="route('vacations.create')">Agregar vacaciones</Link>
        </Button>
        <Table>
    <TableCaption>Lista de vacaciones</TableCaption>
    <TableHeader>
      <TableRow>
        <TableHead>Años</TableHead>
        <TableHead>
          Dias
        </TableHead>
        <TableHead >
          Categoria
        </TableHead>
        <TableHead >
          Acciones
        </TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow v-for="vacation in vacations" :key="vacation.id">
        <TableCell>{{ vacation.years }}</TableCell>
        <TableCell>{{ vacation.days }}</TableCell>
        <TableCell>
          {{ vacation.category?.name }}
        </TableCell>
        <TableCell>
          <Button variant="outline" class="mr-3" as-child>
            <Link :href="route('vacations.edit', vacation.id)">Editar</Link>
          </Button>
          <Button variant="destructive" @click="handleDelete(vacation.id)">Eliminar</Button>
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>
    </AuthenticatedLayout>
</template>
