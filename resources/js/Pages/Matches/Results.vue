<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { CheckCircleIcon, FunnelIcon } from '@heroicons/vue/24/outline'
import AppDatePicker from '@/Components/UI/AppDatePicker.vue'
import FilterResultsSkeleton from '@/Components/UI/FilterResultsSkeleton.vue'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'

const props = defineProps({
    tournament: {
        type: Object,
        default: null,
    },
    results: {
        type: Array,
        default: () => [],
    },
    totalFinished: {
        type: Number,
        default: 0,
    },
})

const page = usePage()
const favoriteTeamTheme = computed(() => page.props.auth?.user?.favorite_team_theme ?? null)
const tickerThemes = {
    neutral: {
        tickerClass: 'border-t border-slate-300/70 bg-[linear-gradient(to_right,_#cfd6df_0%,_#e4e8ee_45%,_#f4f6f9_100%)] text-slate-900',
    },
}
const activeTickerTheme = computed(() => ({
    ...tickerThemes.neutral,
    ...(favoriteTeamTheme.value ?? {}),
}))

const orderedResults = computed(() => props.results)
const selectedDate = ref('')
const isFiltering = ref(false)
let filteringTimer = null
const filteredResults = computed(() => {
    if (!selectedDate.value) {
        return orderedResults.value
    }

    return orderedResults.value.filter((match) => match.matchDateIso === selectedDate.value)
})
const hasResult = (match) => Number.isInteger(match.homeScore) && Number.isInteger(match.awayScore)
const isDraw = (match) => hasResult(match) && match.homeScore === match.awayScore
const isHomeWinner = (match) => hasResult(match) && match.homeScore > match.awayScore
const isAwayWinner = (match) => hasResult(match) && match.awayScore > match.homeScore

watch(selectedDate, () => {
    if (filteringTimer) {
        window.clearTimeout(filteringTimer)
    }

    isFiltering.value = true
    filteringTimer = window.setTimeout(() => {
        isFiltering.value = false
        filteringTimer = null
    }, 360)
})

onBeforeUnmount(() => {
    if (filteringTimer) {
        window.clearTimeout(filteringTimer)
    }
})
</script>

<template>
    <Head title="Results" />

    <UserDashboardLayout
        title="Results"
        description="Latest finalized matches from World Cup 2026."
        :ticker-class="[activeTickerTheme.tickerClass, activeTickerTheme.decorationClass || ''].join(' ')"
    >
        <template #ticker>
            <div class="h-12 w-full" aria-hidden="true" />
        </template>

        <template #headerContent>
            <div class="hidden" />
        </template>

        <section>
            <div class="-mt-8 flex items-center justify-between gap-4 text-left">
                <div class="inline-flex items-center gap-2 text-3xl font-bold text-slate-900 dark:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="h-8 w-8 fill-current text-amber-400" aria-hidden="true">
                        <path d="M448 96c0-35.3-28.7-64-64-64L64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-320zM256 152c0 13.3-10.7 24-24 24l-112 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l112 0c13.3 0 24 10.7 24 24zm72 80c13.3 0 24 10.7 24 24s-10.7 24-24 24l-208 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l208 0zM192 360c0 13.3-10.7 24-24 24l-48 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l48 0c13.3 0 24 10.7 24 24z"/>
                    </svg>
                    <h1>Resultados</h1>
                </div>
                <Link
                    :href="route('dashboard')"
                    class="inline-flex items-center whitespace-nowrap rounded-md px-2 py-1.5 text-xs font-bold uppercase tracking-wide text-primary-700 transition hover:bg-gray-200 dark:text-primary-500 dark:hover:bg-gray-600"
                >
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Regresar
                </Link>
            </div>

            <div class="mt-10">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <p class="text-sm leading-tight text-slate-500 dark:text-slate-400">{{ totalFinished }} partidos finalizados</p>
                    <div class="inline-flex items-center gap-2">
                        <FunnelIcon class="h-4 w-4 text-slate-400 dark:text-slate-500" />
                        <AppDatePicker v-model="selectedDate" placeholder="Filtrar por fecha" />
                    </div>
                </div>
                <div class="mt-[6px] border-b border-slate-300 dark:border-slate-700" />

                <FilterResultsSkeleton v-if="isFiltering" :rows="4" />

                <div v-else-if="filteredResults.length" class="mt-4 space-y-3">
                    <article
                        v-for="match in filteredResults"
                        :key="match.id"
                        class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/75"
                    >
                        <div class="grid grid-cols-1 items-center gap-4 lg:grid-cols-[170px_1fr_88px]">
                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ match.groupName || match.stageLabel }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ match.matchDate }}</p>
                            </div>

                            <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">
                                <div class="flex items-center justify-end gap-2 text-right">
                                    <span class="truncate text-lg font-semibold text-slate-900 dark:text-white">{{ match.homeTeam }}</span>
                                    <span class="text-base font-semibold uppercase text-slate-500 dark:text-slate-400">{{ match.homeCode }}</span>
                                    <img v-if="match.homeFlagUrl" :src="match.homeFlagUrl" :alt="match.homeTeam" class="h-5 w-7 rounded object-cover">
                                </div>

                                <div class="inline-flex min-w-[92px] items-center justify-center gap-3 rounded-xl bg-slate-100 px-3 py-2 text-2xl font-black dark:bg-slate-800">
                                    <span :class="(isHomeWinner(match) || isDraw(match)) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                        {{ match.homeScore ?? 0 }}
                                    </span>
                                    <span class="text-slate-400 dark:text-slate-500">-</span>
                                    <span :class="(isAwayWinner(match) || isDraw(match)) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                        {{ match.awayScore ?? 0 }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-base font-semibold uppercase text-slate-500 dark:text-slate-400">{{ match.awayCode }}</span>
                                    <span class="truncate text-lg font-semibold text-slate-900 dark:text-white">{{ match.awayTeam }}</span>
                                    <img v-if="match.awayFlagUrl" :src="match.awayFlagUrl" :alt="match.awayTeam" class="h-5 w-7 rounded object-cover">
                                </div>
                            </div>

                            <div class="lg:justify-end">
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-1 text-[11px] font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                    <CheckCircleIcon class="h-3.5 w-3.5" />
                                    <span>Finalizado</span>
                                </span>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    No hay resultados para la fecha seleccionada.
                </div>
            </div>
        </section>
    </UserDashboardLayout>
</template>

