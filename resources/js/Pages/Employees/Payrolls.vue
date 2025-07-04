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
} from '@/Components/ui/table';

interface EmployeePayroll {
    id: number
    total: number
    descuento: string
    subtotal: string
    moneda: string
    folio: string
    fecha_inicial: string
    fecha_final: string
    dias_pagados: string
}

defineProps<{
    payrolls: EmployeePayroll[]
}>()

import Button from '@/Components/ui/button/Button.vue';
import {Link} from '@inertiajs/vue3';

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nominas</h2>
        </template>

        <Table>
    <TableCaption>Nominas</TableCaption>
    <TableHeader>
      <TableRow>
        <TableHead>Folio</TableHead>
        <TableHead>Total</TableHead>
        <TableHead>Fecha inicial de pago</TableHead>
        <TableHead>Fecha final de pago</TableHead>
        <TableHead>Dias pagados</TableHead>

      </TableRow>
    </TableHeader>
    <TableBody>
      <TableRow v-for="payroll in payrolls" :key="payroll.id">
        <TableCell>{{ payroll.folio }}</TableCell>
        <TableCell>$ {{ payroll.total }}</TableCell>
        <TableCell>{{ payroll.fecha_inicial }}</TableCell>
        <TableCell>{{ payroll.fecha_final }}</TableCell>
        <TableCell>{{ payroll.dias_pagados }}</TableCell>
        <TableCell>
            <Button variant="outline" class="mr-3" as-child>
                <Link :href="route('employees.payrollConcepts', payroll.id)">Ver Conceptos</Link>
              </Button>
        </TableCell>
      </TableRow>
    </TableBody>
  </Table>
    </AuthenticatedLayout>
</template>
