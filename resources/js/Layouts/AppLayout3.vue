<script setup>
import { Link, usePage, Head } from '@inertiajs/vue3'
import { onMounted, watch } from 'vue'
import { initFlowbite } from 'flowbite'
import { router } from '@inertiajs/vue3'
// Inicializar Flowbite para dropdowns, sidebar, etc.
import 'flowbite'
import Navbar from './Partials/Navbar.vue'
import Sidebar from './Partials/Sidebar.vue'

const page = usePage()
const { props } = usePage()

defineProps({
    title: String,
});

// initialize components based on data attribute selectors
onMounted(() => {
    initFlowbite();
})


// 👇 Re-inicializa Flowbite después de cada visita Inertia
router.on('navigate', () => {
    initFlowbite()
})


watch(
    () => page.props.flash,
    (flash) => {

        if(flash.success){
            notifySuccess(flash.success)
        }

        if(flash.error){
            notifyError(flash.error)
        }

    },
    { immediate: true }
)
</script>

<template>
    <!-- Head -->
    <Head :title="title" />
    <!-- Navbar -->
    <Navbar />

    <!-- Aside -->
    <Sidebar />

    <!-- Container -->
    <div class="mt-14 sm:ml-32 md:ml-64  bg-gray-50 lg:ml-64 dark:bg-gray-900">
        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>

</template>
