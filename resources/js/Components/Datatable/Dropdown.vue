<script setup lang="ts">
import { MoreHorizontal } from 'lucide-vue-next'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu'
import { Button } from '@/Components/ui/button'
import {Link} from '@inertiajs/vue3';
import {
  TableCell
} from '@/Components/ui/table'

defineProps<{
    showLink: string
}>()

defineEmits(['onEdit'])
</script>

<template>
    <TableCell class="text-right">
          <Button variant="outline" class="mr-3" as-child>
            <Link :href="showLink">Ver</Link>
          </Button>
    </TableCell>
</template>
