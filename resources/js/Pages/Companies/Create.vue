<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import Button from '@/Components/ui/button/Button.vue';
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { toast } from 'vue-sonner'
import { ReloadIcon } from '@radix-icons/vue'
import { ref } from 'vue';

const page = usePage()

const isLoading = ref<boolean>(false);

const form = useForm({
    name: '',
    rfc: '',
    company_name: '',
    vacation_days: 0,
    vacation_bonus: 0
});

const submit = () => {
    isLoading.value = true
    form.post(route('companies.store'), {
        onFinish: () => {
            form.reset('name', 'rfc', 'company_name');
            toast.success(page.props.flash.message);
        },
    });
};
</script>

<template>
<AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Empresas</h2>

        </template>

        <form @submit.prevent="submit">
            <div class="flex gap-3">
                <div class="w-1/3 mb-3">
                    <Label for="name">Nombre</Label>
                    <Input id="name" type="text" v-model="form.name"/>
                </div>

                <div class="w-1/3 mb-3">
                    <Label for="rfc">RFC</Label>
                    <Input id="rfc" type="text"  v-model="form.rfc"/>
                </div>

                <div class="w-1/3 mb-3">
                    <Label for="company_name">Razon social</Label>
                    <Input id="company_name" type="text"  v-model="form.company_name"/>
                </div>
            </div>

            <div class="flex gap-3">
                <div class="w-1/3 mb-3">
                    <Label for="vacation_days">Min. Dias de aguinaldo</Label>
                    <Input id="vacation_days" type="text"  v-model.number="form.vacation_days"/>
                </div>

                <div class="w-1/3 mb-3">
                    <Label for="vacation_bonus">Prima vacacional</Label>
                    <Input id="vacation_bonus" type="text"  v-model.number="form.vacation_bonus"/>
                </div>
            </div>

            <div class="flex justify-end">
                <div>
                    <Button type="submit" :disabled="isLoading">
                        <ReloadIcon v-if="isLoading" class="w-4 h-4 mr-2 animate-spin" />
                        Guardar
                    </Button>
                </div>
            </div>
        </form>
</AuthenticatedLayout>
</template>
