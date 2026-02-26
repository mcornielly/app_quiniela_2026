<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    items: { type: Array, default: () => [] }
});

// compute last index
const lastIndex = computed(() => props.items.length - 1);
</script>

<template>
  <nav class="text-sm mb-2" aria-label="Breadcrumb">
    <ol class="list-none p-0 inline-flex">
      <li v-for="(item, index) in items" :key="index" class="flex items-center">
        <template v-if="index !== lastIndex">
          <Link :href="item.href" class="text-blue-600 hover:underline">{{ item.title }}</Link>
          <span class="mx-2">/</span>
        </template>
        <template v-else>
          <span class="text-gray-700">{{ item.title }}</span>
        </template>
      </li>
    </ol>
  </nav>
</template>
