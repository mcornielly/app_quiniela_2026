<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
</script>

<template>
  <AppLayout>
    <section class="space-y-4">
      <header>
        <h1 class="text-2xl font-semibold text-gray-900">Crear Pool</h1>
        <p class="text-sm text-gray-600">Formulario inicial para crear pools en próximas fases.</p>
      </header>

      <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm space-y-3">
        <p class="text-gray-700">Este módulo se completará en una fase posterior.</p>
        <Link :href="route('pools.index')" class="text-sm font-medium text-blue-600 hover:text-blue-700">
          Volver a mis pools
        </Link>
      </div>
    </section>
  </AppLayout>
</template>
