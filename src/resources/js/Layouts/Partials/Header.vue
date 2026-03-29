<script setup>
import { computed } from 'vue'
import { route } from 'ziggy-js'
import Breadcrumb from '@/Layouts/Partials/Breadcrumb.vue'
const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
    showAllPrefix: {
        type: Boolean,
        default: true,
    },
});

const crumbs = computed(() => {
    if (props.breadcrumbs.length > 0) {
        return props.breadcrumbs;
    }

    return [
        { label: 'Dashboard', href: route('admin.dashboard') },
        { label: props.title },
    ];
});

const heading = computed(() => (props.showAllPrefix ? `All ${props.title}` : props.title));

</script>

<template>
    <div class="mb-4">
        <Breadcrumb :items="crumbs" />
        <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">{{ heading }}</h1>
    </div>
</template>
