<script setup>
import { computed } from 'vue'

/**
 * items:
 * [
 *   { label: 'Home', href: '/' },
 *   { label: 'E-commerce', href: '/ecommerce' },
 *   { label: 'Products' } // current page
 * ]
 */
const props = defineProps({
  items: {
    type: Array,
    default: () => [{ label: 'Home', href: '/' }],
  },
})

const tail = computed(() => props.items.slice(1))
const isLast = (idxInTail) => idxInTail === tail.value.length - 1
</script>

<template>
  <nav class="flex mb-5" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 text-sm font-medium md:space-x-2">
      <!-- Home -->
      <li class="inline-flex items-center">
        <component
          :is="items[0]?.href ? 'a' : 'span'"
          :href="items[0]?.href || undefined"
          class="inline-flex items-center text-gray-700 hover:text-primary-600 dark:text-gray-300 dark:hover:text-white"
          :class="!items[0]?.href ? 'cursor-default hover:text-gray-700 dark:hover:text-gray-300' : ''"
        >
          <svg
            class="w-5 h-5 mr-2.5"
            fill="currentColor"
            viewBox="0 0 20 20"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"
            />
          </svg>
          {{ items[0]?.label ?? 'Home' }}
        </component>
      </li>

      <!-- Rest -->
      <li v-for="(item, idx) in tail" :key="idx">
        <div class="flex items-center">
          <svg
            class="w-6 h-6 text-gray-400"
            fill="currentColor"
            viewBox="0 0 20 20"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              fill-rule="evenodd"
              d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
              clip-rule="evenodd"
            />
          </svg>

          <!-- Last item = current page -->
          <span
            v-if="isLast(idx)"
            class="ml-1 text-gray-400 md:ml-2 dark:text-gray-500"
            aria-current="page"
          >
            {{ item.label }}
          </span>

          <!-- Link item -->
          <a
            v-else
            :href="item.href || '#'"
            class="ml-1 text-gray-700 hover:text-primary-600 md:ml-2 dark:text-gray-300 dark:hover:text-white"
          >
            {{ item.label }}
          </a>
        </div>
      </li>
    </ol>
  </nav>
</template>


