<script setup lang="ts">
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  navigationMenuTriggerStyle,
} from '@/Components/ui/navigation-menu'
</script>

<template>
  <NavigationMenu class="max-w-full p-4">
    <NavigationMenuList>
        <NavigationMenuItem>
            <NavigationMenuLink :href="route('umas.index')" :class="navigationMenuTriggerStyle()">
                UMAS
            </NavigationMenuLink>
        </NavigationMenuItem>
        <NavigationMenuItem>
            <NavigationMenuLink :href="route('categories.index')" :class="navigationMenuTriggerStyle()">
                Categorías
            </NavigationMenuLink>
        </NavigationMenuItem>
        <NavigationMenuItem>
            <NavigationMenuLink :href="route('vacations.index')" :class="navigationMenuTriggerStyle()">
                Vacaciones
            </NavigationMenuLink>
        </NavigationMenuItem>
        <NavigationMenuItem>
            <NavigationMenuLink :href="route('companies.index')" :class="navigationMenuTriggerStyle()">
                Empresas
            </NavigationMenuLink>
        </NavigationMenuItem>
        <NavigationMenuItem>
            <NavigationMenuLink :href="route('employees.salaries')" :class="navigationMenuTriggerStyle()">
                Salarios
            </NavigationMenuLink>
        </NavigationMenuItem>
        <NavigationMenuItem>
            <NavigationMenuLink :href="route('employees.showQuotas')" :class="navigationMenuTriggerStyle()">
                IMSS
            </NavigationMenuLink>
        </NavigationMenuItem>
    </NavigationMenuList>
  </NavigationMenu>
</template>
