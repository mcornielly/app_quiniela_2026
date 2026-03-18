<script setup>
import { computed } from 'vue'

const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    shieldSrc: {
        type: String,
        required: true,
    },
    imageVariant: {
        type: String,
        default: 'shield',
    },
    buttonLabel: {
        type: String,
        default: 'Ver juegos',
    },
    gamesAvailable: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['action'])

const posterVariant = computed(() => props.imageVariant === 'poster')
const mediaWrapperClass = computed(() => posterVariant.value
    ? 'relative h-[13.6rem] overflow-hidden border-b border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-950'
    : 'flex h-[11.5rem] items-center justify-center overflow-hidden border-b border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-950')
const imageClass = computed(() => posterVariant.value
    ? 'absolute left-1/2 top-1/2 h-auto w-[98%] max-w-none -translate-x-1/2 -translate-y-[43%] object-contain'
    : 'h-full w-full object-contain p-4')
</script>

<template>
    <div class="flex h-full min-h-[19.25rem] flex-col overflow-hidden rounded-[1.5rem] border border-white/80 bg-white/90 shadow-xl shadow-slate-200/70 backdrop-blur dark:border-slate-800 dark:bg-slate-900/82 dark:shadow-none">
        <div :class="mediaWrapperClass">
            <img
                :src="props.shieldSrc"
                :alt="props.name"
                :class="imageClass"
            >
        </div>

        <div class="flex flex-1 flex-col items-center bg-[linear-gradient(180deg,rgba(245,247,250,0.98)_0%,rgba(228,232,238,0.96)_100%)] px-5 py-2 text-center dark:bg-[#141c2f]">
            <div class="-mx-5 w-[calc(100%+2.5rem)] border-y border-slate-200/80 bg-white px-4 py-2 dark:border-slate-700/80 dark:bg-white">
                <h3 class="text-[1.4rem] font-semibold leading-none tracking-tight text-slate-950">
                    {{ props.name }}
                </h3>
            </div>

            <p class="mt-2 text-sm font-bold text-slate-800 dark:text-slate-100">
                Mundial de Futbol 2026
            </p>
            <p class="mt-1 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                USA | CANADA | MEXICO
            </p>

            <button
                type="button"
                class="mt-2.5 inline-flex items-center justify-center rounded-2xl border border-transparent bg-transparent px-4 py-2 text-sm font-medium leading-5 text-primary-700 transition hover:bg-primary-50 focus:outline-none focus:ring-4 focus:ring-primary-100 dark:text-primary-300 dark:hover:bg-primary-500/10 dark:focus:ring-primary-500/20"
                @click="$emit('action')"
            >
                {{ props.buttonLabel }}
                <svg class="ms-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"/>
                </svg>
            </button>
        </div>
    </div>
</template>
