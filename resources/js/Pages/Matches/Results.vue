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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="h-7 w-7 fill-current text-amber-400" aria-hidden="true">
                        <path d="M144.3 0l224 0c26.5 0 48.1 21.8 47.1 48.2-.2 5.3-.4 10.6-.7 15.8l49.6 0c26.1 0 49.1 21.6 47.1 49.8-7.5 103.7-60.5 160.7-118 190.5-15.8 8.2-31.9 14.3-47.2 18.8-20.2 28.6-41.2 43.7-57.9 51.8l0 73.1 64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-192 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l64 0 0-73.1c-16-7.7-35.9-22-55.3-48.3-18.4-4.8-38.4-12.1-57.9-23.1-54.1-30.3-102.9-87.4-109.9-189.9-1.9-28.1 21-49.7 47.1-49.7l49.6 0c-.3-5.2-.5-10.4-.7-15.8-1-26.5 20.6-48.2 47.1-48.2zM101.5 112l-52.4 0c6.2 84.7 45.1 127.1 85.2 149.6-14.4-37.3-26.3-86-32.8-149.6zM380 256.8c40.5-23.8 77.1-66.1 83.3-144.8L411 112c-6.2 60.9-17.4 108.2-31 144.8z"/>
                    </svg>
                    <h1>Resultados</h1>
                </div>
                <Link
                    :href="route('dashboard')"
                    class="inline-flex items-center whitespace-nowrap rounded-md px-2 py-1.5 text-xs font-bold uppercase tracking-wide text-primary-700 transition hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700"
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
