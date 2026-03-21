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
</script>

<template>
    <div class="max-w-sm overflow-hidden">
        <div class="block border-b border-gray-200 bg-gray-50 px-4 py-1.5 text-center text-sm font-semibold text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
            Notificaciones
        </div>

        <div v-if="normalizedItems.length" class="max-h-[24rem] overflow-y-auto divide-y divide-gray-100 bg-white dark:divide-gray-600 dark:bg-gray-700">
            <div
                v-for="item in normalizedItems"
                :key="item.id"
                class="px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-600"
            >
                <div class="flex items-center justify-between gap-3">
                    <p
                        :class="item.type === 'result' ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400'"
                        class="text-[11px] font-semibold uppercase tracking-[0.14em]"
                    >
                        {{ item.type === 'result' ? 'Resultado final' : 'Partido en vivo' }}
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
                        <span :class="homeScoreClass(item)">{{ item.homeScore }}</span>
                        <span class="text-slate-400 dark:text-slate-500">-</span>
                        <span :class="awayScoreClass(item)">{{ item.awayScore }}</span>
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

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                    {{ item.matchDate || '--/--/----' }} - {{ item.matchTime || '--:--' }}
                    <span v-if="item.venue"> | {{ item.venue }}</span>
                </p>
                <p :class="timeClass(item.occurredAt)" class="mt-1">
                    {{ timeAgo(item.occurredAt) }}
                </p>
            </div>
        </div>

        <div v-else class="bg-white px-4 py-6 text-sm text-gray-500 dark:bg-gray-700 dark:text-gray-300">
            Aun no tienes notificaciones.
        </div>

        <Link
            :href="viewAllHref"
            class="block border-t border-gray-200 bg-gray-50 py-2 text-center text-base font-semibold text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
        >
            <span class="inline-flex items-center gap-2">
                <EyeIcon class="h-5 w-5" />
                View all
            </span>
        </Link>
    </div>
</template>
