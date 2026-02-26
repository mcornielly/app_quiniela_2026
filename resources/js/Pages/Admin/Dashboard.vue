<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout title="Admin Dashboard">
        <div class="text-heading font-semibold text-xl mb-4">Admin Dashboard</div>
        <div class="text-body">Bienvenido al panel admin.</div>
    </AdminLayout>
</template>
