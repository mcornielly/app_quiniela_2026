<script setup>
import Breadcrumb from '@/Layouts/Partials/Breadcrumb.vue'

const crumbs = [
  { label: 'Home', href: '/' },
  { label: 'E-commerce', href: '/ecommerce' },
  { label: 'Products' },
]

defineProps({
    title: String,
});

</script>

<template>
    <div class="mb-4">
        <Breadcrumb :items="crumbs" />
        <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">All {{ title }}</h1>
    </div>
</template>
