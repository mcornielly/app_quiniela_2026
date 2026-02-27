<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
  <AppLayout>
    <section class="space-y-4">
      <header>
        <h1 class="text-2xl font-semibold text-gray-900">Rankings</h1>
        <p class="text-sm text-gray-600">Tabla inicial para la clasificación de usuarios.</p>
      </header>

      <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-gray-700">Aún no hay resultados para mostrar en el ranking.</p>
      </div>
    </section>
  </AppLayout>
</template>
