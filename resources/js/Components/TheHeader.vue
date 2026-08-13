<script setup lang="ts">
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  navigationMenuTriggerStyle,
} from '@/Components/ui/navigation-menu'

import { Link } from '@inertiajs/vue3'
</script>

<template>
  <NavigationMenu class="max-w-full p-4">
    <NavigationMenuList>
        <NavigationMenuItem>
            <NavigationMenuLink :href="route('users.index')" :class="navigationMenuTriggerStyle()" :active="$page.url.startsWith('/users')">
                Usuarios
            </NavigationMenuLink>
        </NavigationMenuItem>
        <NavigationMenuItem>
            <NavigationMenuLink :href="route('umas.index')" :class="navigationMenuTriggerStyle()" :active="$page.url.startsWith('/umas')">
                UMAS
            </NavigationMenuLink>
        </NavigationMenuItem>
        <NavigationMenuItem>
            <NavigationMenuLink :href="route('categories.index')" :class="navigationMenuTriggerStyle()" :active="$page.url.startsWith('/categories')">
                Categorías
            </NavigationMenuLink>
        </NavigationMenuItem>
        <NavigationMenuItem>
            <NavigationMenuLink :href="route('vacations.index')" :class="navigationMenuTriggerStyle()" :active="$page.url.startsWith('/vacations')">
                Vacaciones
            </NavigationMenuLink>
        </NavigationMenuItem>
        <NavigationMenuItem>
            <NavigationMenuLink :href="route('companies.index')" :class="navigationMenuTriggerStyle()" :active="$page.url.startsWith('/companies')">
                Empresas
            </NavigationMenuLink>
        </NavigationMenuItem>
        <NavigationMenuItem>
            <NavigationMenuLink :href="route('employees.salaries')" :class="navigationMenuTriggerStyle()" :active="$page.url.startsWith('/salaries')">
                Salarios
            </NavigationMenuLink>
        </NavigationMenuItem>
        <NavigationMenuItem>
            <NavigationMenuLink :href="route('employees.showQuotas')" :class="navigationMenuTriggerStyle()" :active="$page.url.startsWith('/quotas')">
                IMSS
            </NavigationMenuLink>
        </NavigationMenuItem>
    </NavigationMenuList>

    <NavigationMenuList>
        <Link :href="route('logout')" :class="navigationMenuTriggerStyle()" method="post" class="bg-red-700 text-white hover:bg-red-500" :active="$page.url.startsWith('/quotas')" as="button">
                Cerrar Sesión
            </Link>
    </NavigationMenuList>
  </NavigationMenu>
</template>
<style scoped>
.data-\[active\]\:bg-accent\/50[data-active] {
    background-color: hsl(var(--primary));
    color: #fff
}
</style>
