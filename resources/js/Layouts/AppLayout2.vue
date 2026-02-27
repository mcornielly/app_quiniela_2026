<script setup>
import { Head } from '@inertiajs/vue3'
import { onMounted } from 'vue'
import { initFlowbite } from 'flowbite'
import { router } from '@inertiajs/vue3'
import 'flowbite'
import Navbar from './Partials/Navbar.vue'
import Sidebar from './Partials/Sidebar.vue'

defineProps({
    title: { type: String, default: '' }
})

onMounted(() => initFlowbite())

// Re-initialize Flowbite after each Inertia navigation
router.on('navigate', () => initFlowbite())
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen antialiased bg-gray-50 dark:bg-gray-900">
        <!-- Fixed navbar at top -->
        <Navbar />

        <div class="flex pt-16">
            <!-- Sidebar (hidden on small screens, handled inside Sidebar component) -->
            <aside class="hidden md:block md:fixed md:inset-y-0 md:w-64">
                <Sidebar />
            </aside>

            <!-- Main content area; on md+ screens it will have left padding to make room for sidebar -->
            <main class="flex-1 w-full p-4 md:pl-64">
                <div class="max-w-7xl mx-auto">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
