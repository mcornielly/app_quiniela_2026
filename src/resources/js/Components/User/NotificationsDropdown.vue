<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { EyeIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    notifications: {
        type: Array,
        default: () => [],
    },
    viewAllHref: {
        type: String,
        default: '#',
    },
    totalCount: {
        type: Number,
        default: 0,
    },
    onRemove: {
        type: Function,
        default: null,
    },
    onClearAll: {
        type: Function,
        default: null,
    },
})

const timeAgo = (isoDate) => {
    if (!isoDate) {
        return 'hace pocos segundos'
    }

    const seconds = Math.max(1, Math.floor((Date.now() - new Date(isoDate).getTime()) / 1000))

    if (seconds < 60) {
        return 'hace pocos segundos'
    }

    const minutes = Math.floor(seconds / 60)
    if (minutes < 60) {
        return `hace ${minutes} min`
    }

    const hours = Math.floor(minutes / 60)
    if (hours < 24) {
        return `hace ${hours} h`
    }

    const days = Math.floor(hours / 24)
    return `hace ${days} d`
}

const timeClass = (isoDate) => {
    const seconds = isoDate ? Math.max(1, Math.floor((Date.now() - new Date(isoDate).getTime()) / 1000)) : 0

    if (seconds < 60) {
        return 'text-xs font-bold text-blue-600 dark:text-blue-400'
    }

    return 'text-xs font-semibold text-primary-700 dark:text-primary-400'
}

const normalizedItems = computed(() => props.notifications.map((item) => {
    const homeScore = Number.isFinite(Number(item?.homeScore)) ? Number(item.homeScore) : 0
    const awayScore = Number.isFinite(Number(item?.awayScore)) ? Number(item.awayScore) : 0

    return {
        ...item,
        homeScore,
        awayScore,
    }
}))

const isDrawScore = (item) => item.homeScore === item.awayScore

const homeScoreClass = (item) => {
    if (item.type === 'result' && (isDrawScore(item) || item.homeScore > item.awayScore)) {
        return 'text-emerald-600 dark:text-emerald-400'
    }

    return 'text-slate-900 dark:text-white'
}

const awayScoreClass = (item) => {
    if (item.type === 'result' && (isDrawScore(item) || item.awayScore > item.homeScore)) {
        return 'text-emerald-600 dark:text-emerald-400'
    }

    return 'text-slate-900 dark:text-white'
}

const isQualification = (item) => item.type === 'qualification'
const isResult = (item) => item.type === 'result'

const itemToneClass = (item) => {
    if (isResult(item)) {
        return 'text-emerald-700 dark:text-emerald-400'
    }

    if (isQualification(item)) {
        return 'text-amber-700 dark:text-amber-400'
    }

    if (item.type === 'update') {
        return 'text-amber-700 dark:text-amber-400'
    }

    return 'text-rose-700 dark:text-rose-400'
}

const itemTypeLabel = (item) => {
    if (isResult(item)) {
        return 'Resultado final'
    }

    if (isQualification(item)) {
        return 'Clasificados confirmados'
    }

    if (item.type === 'update') {
        return 'Clasificacion proxima ronda'
    }

    return 'Partido en vivo'
}

const removeNotification = (id) => {
    props.onRemove?.(id)
}

const clearAll = () => {
    props.onClearAll?.()
}
</script>

<template>
    <div class="max-w-sm overflow-hidden rounded-xl bg-white shadow-lg dark:bg-gray-700">
        <div class="relative block border-b border-gray-200 bg-gray-50 px-4 py-2 text-center text-sm font-semibold text-gray-700 dark:border-gray-600 dark:bg-gray-600 dark:text-gray-300">
            Notificaciones
            <button
                v-if="normalizedItems.length > 0"
                type="button"
                @click.stop="clearAll"
                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md bg-transparent p-1.5 text-slate-600 transition hover:bg-slate-300 hover:text-red-600 dark:text-slate-200 dark:hover:bg-slate-500/40 dark:hover:text-red-400"
                title="Limpiar todo"
                aria-label="Limpiar todo"
            >
                <svg class="h-4 w-4" viewBox="0 0 448 512" fill="currentColor" aria-hidden="true">
                    <path d="M136.7 5.9C141.1-7.2 153.3-16 167.1-16l113.9 0c13.8 0 26 8.8 30.4 21.9L320 32 416 32c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 96C14.3 96 0 81.7 0 64S14.3 32 32 32l96 0 8.7-26.1zM32 144l384 0 0 304c0 35.3-28.7 64-64 64L96 512c-35.3 0-64-28.7-64-64l0-304zm88 64c-13.3 0-24 10.7-24 24l0 192c0 13.3 10.7 24 24 24s24-10.7 24-24l0-192c0-13.3-10.7-24-24-24zm104 0c-13.3 0-24 10.7-24 24l0 192c0 13.3 10.7 24 24 24s24-10.7 24-24l0-192c0-13.3-10.7-24-24-24zm104 0c-13.3 0-24 10.7-24 24l0 192c0 13.3 10.7 24 24 24s24-10.7 24-24l0-192c0-13.3-10.7-24-24-24z"/>
                </svg>
            </button>
        </div>

        <div v-if="normalizedItems.length" class="max-h-[24rem] overflow-y-auto divide-y divide-gray-100 bg-white dark:divide-gray-600 dark:bg-gray-700">
            <div
                v-for="item in normalizedItems"
                :key="item.id"
                class="relative px-4 py-3 pr-12 hover:bg-gray-100 dark:hover:bg-gray-600"
            >
                <div class="flex items-center justify-between gap-3">
                    <p
                        :class="itemToneClass(item)"
                        class="text-[11px] font-semibold uppercase tracking-[0.14em]"
                    >
                        {{ itemTypeLabel(item) }}
                    </p>
                    <p class="whitespace-nowrap text-[11px] text-gray-500 dark:text-gray-300">
                        {{ item.stageLabel || 'Mundial 2026' }}
                    </p>
                </div>

                <div class="mt-1.5 grid grid-cols-[1fr_auto_1fr] items-center gap-2 text-sm text-gray-700 dark:text-gray-100">
                    <span class="inline-flex min-w-0 items-center justify-end gap-2">
                        <img
                            v-if="item.homeFlagUrl"
                            :src="item.homeFlagUrl"
                            :alt="item.homeTeam"
                            class="h-4 w-6 rounded object-cover"
                        >
                        <span class="truncate font-medium">{{ item.homeTeam }}</span>
                    </span>

                    <span class="inline-flex min-w-[72px] items-center justify-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-center text-sm font-bold dark:bg-slate-800">
                        <template v-if="isResult(item)">
                            <span :class="homeScoreClass(item)">{{ item.homeScore }}</span>
                            <span class="text-slate-400 dark:text-slate-500">-</span>
                            <span :class="awayScoreClass(item)">{{ item.awayScore }}</span>
                        </template>
                        <template v-else>
                            <span class="text-slate-500 dark:text-slate-300">vs</span>
                        </template>
                    </span>

                    <span class="inline-flex min-w-0 items-center justify-start gap-2">
                        <img
                            v-if="item.awayFlagUrl"
                            :src="item.awayFlagUrl"
                            :alt="item.awayTeam"
                            class="h-4 w-6 rounded object-cover"
                        >
                        <span class="truncate font-medium">{{ item.awayTeam }}</span>
                    </span>
                </div>

                <p class="mt-1 text-center text-xs text-gray-500 dark:text-gray-300">
                    {{ item.matchDate || '--/--/----' }} - {{ item.matchTime || '--:--' }}
                    <span v-if="item.venue"> | {{ item.venue }}</span>
                </p>
                <p :class="timeClass(item.occurredAt)" class="mt-1">
                    {{ timeAgo(item.occurredAt) }}
                </p>

                <button
                    type="button"
                    @click.stop="removeNotification(item.id)"
                    class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full border border-slate-300 bg-transparent text-slate-400 transition hover:border-red-300 hover:bg-red-100 hover:text-red-600 dark:border-slate-500 dark:text-slate-300 dark:hover:border-red-500/40 dark:hover:bg-red-900/30 dark:hover:text-red-400"
                    title="Eliminar notificacion"
                    aria-label="Eliminar notificacion"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 0 1 1.06 0L10 8.94l4.72-4.72a.75.75 0 1 1 1.06 1.06L11.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 1 1-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>

        <div v-else class="bg-white px-4 py-6 text-sm text-gray-500 dark:bg-gray-700 dark:text-gray-300">
            Aun no tienes notificaciones.
        </div>

        <div class="bg-gray-50 dark:bg-gray-600">
            <Link
                :href="viewAllHref"
                class="group relative flex items-center justify-center px-4 py-2 text-base font-semibold text-gray-900 transition hover:bg-blue-50 hover:text-blue-700 dark:text-white dark:hover:bg-blue-900/20 dark:hover:text-blue-300"
            >
                <span class="inline-flex items-center gap-2">
                    <EyeIcon class="h-5 w-5" />
                    View all
                </span>
                <span class="absolute right-4 inline-flex min-w-6 items-center justify-center rounded-full bg-slate-200 px-2 py-0.5 text-xs font-bold text-slate-700 transition group-hover:bg-green-200 group-hover:text-green-900 dark:bg-slate-500 dark:text-white dark:group-hover:bg-green-700/30 dark:group-hover:text-green-200">
                    {{ totalCount }}
                </span>
            </Link>
        </div>
    </div>
</template>
