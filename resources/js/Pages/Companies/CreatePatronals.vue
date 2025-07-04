<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import Button from '@/Components/ui/button/Button.vue';
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { toast } from 'vue-sonner'
import { ReloadIcon } from '@radix-icons/vue'
import { ref } from 'vue';

interface Company {
    id: number
    name: string
    rfc: string
    company_name: string
}

const page = usePage()

const isLoading = ref<boolean>(false);

const props = defineProps<{
    company: Company
}>()

const form = useForm({
    name: '',
    risk: '',
    companny_id: props.company.id
});

const submit = () => {
    isLoading.value = true
    form.post(route('companies.store'), {
        onFinish: () => {
            form.reset('name', 'risk');
            toast.success(page.props.flash.message);
        },
    });
};
</script>

<template>
<AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ company.name }}</h2>

        </template>

        <form @submit.prevent="submit">
            <div class="flex gap-3">
                <div class="w-1/3 mb-3">
                    <Label for="name">Nombre</Label>
                    <Input id="name" type="text" v-model="form.name"/>
                </div>

                <div class="w-1/3 mb-3">
                    <Label for="risk">Prima de riesgo</Label>
                    <Input id="risk" type="text"  v-model="form.risk"/>
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
