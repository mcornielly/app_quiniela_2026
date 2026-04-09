<script setup>
import { computed } from 'vue'

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    badge: {
        type: String,
        default: '',
    },
    icon: {
        type: Object,
        required: true,
    },
    tone: {
        type: String,
        default: 'primary',
    },
    signal: {
        type: Boolean,
        default: false,
    },
    valueTone: {
        type: String,
        default: '',
    },
    badgeVariant: {
        type: String,
        default: '',
    },
    iconTone: {
        type: String,
        default: '',
    },
})

const toneClasses = {
    primary: {
        card: 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900',
        icon: 'text-primary-500 dark:text-primary-300',
        badge: 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300',
    },
    sky: {
        card: 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900',
        icon: 'text-sky-500 dark:text-sky-300',
        badge: 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300',
    },
    emerald: {
        card: 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900',
        icon: 'text-emerald-500 dark:text-emerald-300',
        badge: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
    },
    amber: {
        card: 'border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900',
        icon: 'text-amber-500 dark:text-amber-300',
        badge: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    },
}

const activeTone = computed(() => toneClasses[props.tone] ?? toneClasses.primary)
const iconClass = computed(() => {
    if (props.iconTone === 'rose') {
        return 'text-rose-500 dark:text-rose-400'
    }
    if (props.iconTone === 'emerald') {
        return 'text-emerald-500 dark:text-emerald-400'
    }
    if (props.iconTone === 'amber') {
        return 'text-amber-500 dark:text-amber-400'
    }
    if (props.iconTone === 'sky') {
        return 'text-sky-500 dark:text-sky-400'
    }

    return activeTone.value.icon
})
const titleClass = computed(() => {
    const toneKey = props.iconTone || props.tone

    if (toneKey === 'rose') {
        return 'text-slate-400 transition-colors duration-200 group-hover:text-rose-400 dark:text-slate-500 dark:group-hover:text-rose-300'
    }
    if (toneKey === 'sky') {
        return 'text-slate-400 transition-colors duration-200 group-hover:text-sky-400 dark:text-slate-500 dark:group-hover:text-sky-300'
    }
    if (toneKey === 'amber') {
        return 'text-slate-400 transition-colors duration-200 group-hover:text-amber-400 dark:text-slate-500 dark:group-hover:text-amber-300'
    }
    if (toneKey === 'emerald') {
        return 'text-slate-400 transition-colors duration-200 group-hover:text-emerald-400 dark:text-slate-500 dark:group-hover:text-emerald-300'
    }

    return 'text-slate-400 transition-colors duration-200 group-hover:text-primary-400 dark:text-slate-500 dark:group-hover:text-primary-300'
})
const valueClass = computed(() => {
    if (props.valueTone === 'amberMuted') {
        return 'text-amber-500/45 dark:text-amber-400/45'
    }
    if (props.valueTone === 'amber') {
        return 'text-amber-500 dark:text-amber-400'
    }
    if (props.valueTone === 'emeraldMuted') {
        return 'text-emerald-500/45 dark:text-emerald-400/45'
    }
    if (props.valueTone === 'emerald') {
        return 'text-emerald-500 dark:text-emerald-400'
    }
    if (props.valueTone === 'skyMuted') {
        return 'text-sky-500/45 dark:text-sky-400/45'
    }
    if (props.valueTone === 'sky') {
        return 'text-sky-500 dark:text-sky-400'
    }
    if (props.valueTone === 'roseMuted') {
        return 'text-rose-500/45 dark:text-rose-400/45'
    }
    if (props.valueTone === 'rose') {
        return 'text-rose-500 dark:text-rose-400'
    }

    return 'text-slate-500 dark:text-slate-500'
})
const isProgressBadge = computed(() => props.badgeVariant === 'progress')
const isLiveBadge = computed(() => props.badgeVariant === 'live')
const isLivePlainBadge = computed(() => props.badgeVariant === 'livePlain')
const isClockBadge = computed(() => props.badgeVariant === 'clock')
const isProgressSkyBadge = computed(() => props.badgeVariant === 'progressSky')
const isClockSkyBadge = computed(() => props.badgeVariant === 'clockSky')
const isFlameAmberBadge = computed(() => props.badgeVariant === 'flameAmber')
const isClockAmberBadge = computed(() => props.badgeVariant === 'clockAmber')
const isClockRoseBadge = computed(() => props.badgeVariant === 'clockRose')
const isSignalGreen = computed(() => isLiveBadge.value)
const isSignalSearching = computed(() => isClockRoseBadge.value)
const signalPingClass = computed(() => (
    isSignalGreen.value
        ? 'bg-emerald-500/45'
        : 'bg-rose-500/45'
))
const signalDotClass = computed(() => (
    isSignalGreen.value
        ? 'bg-emerald-500'
        : 'bg-rose-500'
))
const signalPingAnimationClass = computed(() => (
    isSignalSearching.value
        ? 'animate-[ping_1.1s_cubic-bezier(0,0,0.2,1)_infinite]'
        : 'animate-ping'
))
const signalDotAnimationClass = computed(() => (
    isSignalSearching.value
        ? 'animate-[pulse_0.95s_ease-in-out_infinite]'
        : 'animate-pulse'
))
const badgeClass = computed(() => {
    if (isFlameAmberBadge.value) {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
    }
    if (isClockAmberBadge.value) {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
    }
    if (isClockRoseBadge.value) {
        return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
    }
    if (isLiveBadge.value) {
        return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
    }
    if (isLivePlainBadge.value) {
        return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
    }
    if (isProgressSkyBadge.value) {
        return 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'
    }
    if (isProgressBadge.value) {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
    }
    if (isClockSkyBadge.value) {
        return 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'
    }
    if (isClockBadge.value) {
        return 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
    }

    return activeTone.value.badge
})
</script>

<template>
    <article
        :class="activeTone.card"
        class="group relative h-48 overflow-hidden rounded-2xl border p-4 shadow-sm shadow-slate-200/60 transition hover:-translate-y-0.5 hover:shadow-md dark:shadow-none"
    >
        <div class="absolute right-4 top-3">
            <div class="relative">
                <svg
                    :viewBox="icon.viewBox"
                    :class="iconClass"
                    class="h-6 w-6 opacity-95 transition-transform duration-300 group-hover:scale-105"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path :d="icon.path" />
                </svg>
            </div>
        </div>

        <div class="pr-12">
            <p :class="titleClass" class="text-xs font-semibold uppercase tracking-[0.22em]">
                {{ title }}
                <span
                    v-if="signal"
                    class="relative ml-1 inline-flex h-2.5 w-2.5 -translate-y-px align-middle"
                >
                    <span :class="[signalPingClass, signalPingAnimationClass]" class="absolute inline-flex h-full w-full rounded-full" />
                    <span :class="[signalDotClass, signalDotAnimationClass]" class="relative inline-flex h-2.5 w-2.5 rounded-full" />
                </span>
            </p>
        </div>

        <div class="absolute inset-x-4 top-1/2 -translate-y-1/2 pr-12">
            <p class="text-left text-sm leading-6 text-slate-600 dark:text-slate-300">
                {{ description }}
            </p>
        </div>

        <div class="absolute bottom-4 left-4 right-4 flex items-end justify-between gap-3">
            <span
                v-if="badge"
                :class="badgeClass"
                class="inline-flex shrink-0 items-center justify-center gap-1 whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold leading-none"
            >
                <svg
                    v-if="isProgressBadge || isLiveBadge || isProgressSkyBadge || isFlameAmberBadge"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    class="h-3.5 w-3.5 shrink-0"
                    fill="none"
                    aria-hidden="true"
                >
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.122 17.645a7.185 7.185 0 0 1-2.656 2.495 7.06 7.06 0 0 1-3.52.853 6.617 6.617 0 0 1-3.306-.718 6.73 6.73 0 0 1-2.54-2.266c-2.672-4.57.287-8.846.887-9.668A4.448 4.448 0 0 0 8.07 6.31 4.49 4.49 0 0 0 7.997 4c1.284.965 6.43 3.258 5.525 10.631 1.496-1.136 2.7-3.046 2.846-6.216 1.43 1.061 3.985 5.462 1.754 9.23Z"/>
                </svg>
                <svg
                    v-else-if="isClockBadge || isClockSkyBadge || isClockAmberBadge || isClockRoseBadge"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 512 512"
                    class="h-3.5 w-3.5 shrink-0 fill-current"
                    aria-hidden="true"
                >
                    <path d="M464 256a208 208 0 1 1-416 0 208 208 0 1 1 416 0zM0 256a256 256 0 1 0 512 0 256 256 0 1 0-512 0zM232 120l0 136c0 8 4 15.5 10.7 20l96 64c11 7.4 25.9 4.4 33.3-6.7s4.4-25.9-6.7-33.3L280 243.2 280 120c0-13.3-10.7-24-24-24s-24 10.7-24 24z" />
                </svg>
                {{ badge }}
            </span>
            <p :class="valueClass" class="text-5xl font-black leading-none tracking-tight">
                {{ value }}
            </p>
        </div>
    </article>
</template>
