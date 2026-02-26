<template>
  <div class="min-h-screen bg-gray-100">
    <Topbar />
    <div class="flex">
      <SidebarNav />
      <main class="flex-1 p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import Topbar from '@/Components/Topbar.vue';
import SidebarNav from '@/Components/SidebarNav.vue';
</script>
