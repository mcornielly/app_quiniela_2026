<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  paginator: { type: Object, required: true }, // users completo
});

const pages = computed(() => {
  const last = props.paginator.last_page || 1;
  const result = [];
  for (let i = 1; i <= last; i++) result.push(i);
  return result;
});

function goTo(page) {
  if (page < 1 || page > (props.paginator.last_page || 1)) return;

  router.get(props.paginator.path, {
    page,
    per_page: props.paginator.per_page
  }, {
    preserveScroll: true,
    preserveState: true,
    replace: true,
  });
}
</script>

<template>
  <nav class="flex items-center space-x-4" aria-label="Pagination">
    <ul class="flex -space-x-px text-sm">
      <li>
        <button
          @click.prevent="goTo(paginator.current_page - 1)"
          :disabled="paginator.current_page <= 1"
          class="flex items-center justify-center text-body bg-neutral-secondary-medium border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading shadow-xs font-medium leading-5 rounded-s-base text-sm px-3 h-9 focus:outline-none disabled:opacity-50"
        >
          Previous
        </button>
      </li>

      <li v-for="p in pages" :key="p">
        <button
          @click.prevent="goTo(p)"
          :aria-current="p === paginator.current_page ? 'page' : null"
          :class="p === paginator.current_page
            ? 'flex items-center justify-center text-fg-brand bg-neutral-tertiary-medium box-border border border-default-medium hover:text-fg-brand font-medium text-sm w-9 h-9 focus:outline-none'
            : 'flex items-center justify-center text-body bg-neutral-secondary-medium border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading shadow-xs font-medium leading-5 text-sm w-9 h-9 focus:outline-none'"
        >
          {{ p }}
        </button>
      </li>

      <li>
        <button
          @click.prevent="goTo(paginator.current_page + 1)"
          :disabled="paginator.current_page >= paginator.last_page"
          class="flex items-center justify-center text-body bg-neutral-secondary-medium border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading shadow-xs font-medium leading-5 rounded-e-base text-sm px-3 h-9 focus:outline-none disabled:opacity-50"
        >
          Next
        </button>
      </li>
    </ul>
  </nav>
</template>
