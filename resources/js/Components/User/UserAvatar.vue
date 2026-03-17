<script setup>
import { computed } from 'vue'

const props = defineProps({
    name: {
        type: String,
        default: '',
    },
    sizeClass: {
        type: String,
        default: 'h-10 w-10',
    },
})

const initials = computed(() => {
    const parts = String(props.name || '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)

    if (parts.length === 0) {
        return 'U'
    }

    return parts.map((part) => part[0]?.toUpperCase() ?? '').join('')
})
</script>

<template>
    <div
        class="relative inline-flex items-center justify-center overflow-hidden rounded-full bg-slate-200 text-slate-600 ring-1 ring-slate-200/80 transition-colors dark:bg-slate-900 dark:text-sky-200 dark:ring-slate-800/90"
        :class="sizeClass"
    >
        <span class="text-[1.05rem] font-semibold tracking-[-0.01em]">
            {{ initials }}
        </span>
    </div>
</template>


