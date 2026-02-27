<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
  <AppLayout>
    <section class="space-y-4">
      <header class="flex items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">Mis Pools</h1>
          <p class="text-sm text-gray-600">Gestión base de pools del usuario.</p>
        </div>
        <Link
          :href="route('pools.create')"
          class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
          Crear pool
        </Link>
      </header>

      <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-gray-700">No tienes pools creados por ahora.</p>
      </div>
    </section>
  </AppLayout>
</template>
