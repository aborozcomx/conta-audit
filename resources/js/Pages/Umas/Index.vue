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

interface Uma {
    id: number
    year: string
    balance: number
}

const form = useForm({});

defineProps<{
    umas: Uma[]
}>()

import Button from '@/Components/ui/button/Button.vue';
import {Link} from '@inertiajs/vue3';

const handleDelete = (id: number) => {
    form.delete(route('umas.destroy', id));
}

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">UMAS</h2>
        </template>

        <Button as-child>
            <Link :href="route('umas.create')">Agregar UMA</Link>
        </Button>
        <Table>
    <TableCaption>Lista de UMAS</TableCaption>
    <TableHeader>
      <TableRow>
        <TableHead>
          ID
        </TableHead>
        <TableHead>Año</TableHead>
        <TableHead>Saldo</TableHead>
        <TableHead class="text-right">
          Acciones
        </TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow v-for="uma in umas" :key="uma.id">
        <TableCell class="font-medium">
          {{ uma.id }}
        </TableCell>
        <TableCell>{{ uma.year }}</TableCell>
        <TableCell>{{ uma.balance }}</TableCell>
        <TableCell class="text-right">
          <Button variant="outline" class="mr-3" as-child>
            <Link :href="route('umas.edit', uma.id)">Editar</Link>
          </Button>
          <Button variant="destructive" @click="handleDelete(uma.id)">Eliminar</Button>
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>
    </AuthenticatedLayout>
</template>
