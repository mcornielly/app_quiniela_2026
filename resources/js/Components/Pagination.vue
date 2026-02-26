<template>
  <nav aria-label="Page navigation example" class="flex items-center space-x-4">
    <ul class="flex -space-x-px text-sm">
      <li>
        <button
          @click.prevent="goTo(meta.current_page - 1)"
          :disabled="meta.current_page <= 1"
          class="flex items-center justify-center text-body bg-neutral-secondary-medium border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading shadow-xs font-medium leading-5 rounded-s-base text-sm px-3 h-9 focus:outline-none disabled:opacity-50"
        >
          Previous
        </button>
      </li>

      <li v-for="p in pages" :key="p">
        <button
          @click.prevent="goTo(p)"
          :aria-current="p === meta.current_page ? 'page' : null"
          :class="p === meta.current_page ?
            'flex items-center justify-center text-fg-brand bg-neutral-tertiary-medium box-border border border-default-medium hover:text-fg-brand font-medium text-sm w-9 h-9 focus:outline-none' :
            'flex items-center justify-center text-body bg-neutral-secondary-medium border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading shadow-xs font-medium leading-5 text-sm w-9 h-9 focus:outline-none'"
        >
          {{ p }}
        </button>
      </li>

      <li>
        <button
          @click.prevent="goTo(meta.current_page + 1)"
          :disabled="meta.current_page >= meta.last_page"
          class="flex items-center justify-center text-body bg-neutral-secondary-medium border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading shadow-xs font-medium leading-5 rounded-e-base text-sm px-3 h-9 focus:outline-none disabled:opacity-50"
        >
          Next
        </button>
      </li>
    </ul>
    <!-- per-page slot (if parent wants to inject control) -->
    <slot name="per-page" />
  </nav>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps({
  meta: { type: Object, required: true },
  perPage: { type: Number, required: true },
  baseRoute: { type: String, required: true },
});

// compute a simple page list from 1..last_page so the total number of pages
// is always visible. we intentionally avoid ellipses since the number of pages
// in our app should be relatively small (14 in the example). If the range
// grows very large in the future we can reintroduce a sliding window.
const pages = computed(() => {
  const last = props.meta.last_page || 1;
  const result = [];
  for (let i = 1; i <= last; i++) {
    result.push(i);
  }
  return result;
});

function goTo(page) {
  if (page < 1) return;
  if (page > (props.meta.last_page || 1)) return;
  router.get(route(props.baseRoute), { page, per_page: props.perPage });
}
</script>
