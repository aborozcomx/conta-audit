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

interface User {
    id: number
    name: string
    email: string
}

const form = useForm({});

defineProps<{
    users: User[]
}>()

import Button from '@/Components/ui/button/Button.vue';
import {Link} from '@inertiajs/vue3';

const handleDelete = (id: number) => {
    form.delete(route('umas.destroy', id));
}

</script>

<template>
    <Head title="Usuarios" />

    <AuthenticatedLayout>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Usuarios</h2>
        </template>
        <Table>
    <TableCaption>Lista de usuarios</TableCaption>
    <TableHeader>
      <TableRow>
        <TableHead>
          ID
        </TableHead>
        <TableHead>Nombre</TableHead>
        <TableHead>Correo</TableHead>
      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow v-for="user in users" :key="user.id">
        <TableCell class="font-medium">
          {{ user.id }}
        </TableCell>
        <TableCell>{{ user.name }}</TableCell>
        <TableCell>{{ user.email }}</TableCell>
      </TableRow>
    </TableBody>
  </Table>
    </AuthenticatedLayout>
</template>
