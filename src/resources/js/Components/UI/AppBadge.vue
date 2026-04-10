<script setup>
import { computed } from 'vue'

const props = defineProps({
    tone: {
        type: String,
        default: 'blue',
    },
    size: {
        type: String,
        default: 'sm',
    },
})

const toneClasses = {
    blue: 'border-cyan-200 bg-cyan-100 text-cyan-800 dark:border-cyan-500/40 dark:bg-slate-800 dark:text-cyan-300',
    slate: 'border-slate-300 bg-white text-slate-600 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-300',
    green: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300',
    red: 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300',
    amber: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300',
}

const sizeClasses = {
    sm: 'text-[11px] px-2 py-1',
    md: 'text-xs px-2.5 py-1.5',
}

const resolvedTone = computed(() => toneClasses[props.tone] || toneClasses.blue)
const resolvedSize = computed(() => sizeClasses[props.size] || sizeClasses.sm)
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full border font-semibold leading-none shadow-sm"
        :class="[resolvedTone, resolvedSize]"
    >
        <slot name="icon" />
        <slot />
    </span>
</template>
