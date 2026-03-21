<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
    CalendarDaysIcon,
    CheckCircleIcon,
    FunnelIcon,
    MapPinIcon,
} from '@heroicons/vue/24/outline'
import FilterCalendarSkeleton from '@/Components/UI/FilterCalendarSkeleton.vue'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'

const props = defineProps({
    tournament: {
        type: Object,
        default: null,
    },
    calendarMatches: {
        type: Array,
        default: () => [],
    },
    groupOptions: {
        type: Array,
        default: () => [],
    },
    stageOptions: {
        type: Array,
        default: () => [],
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

const selectedGroup = ref('all')
const selectedStage = ref('all')
const isFiltering = ref(false)
let filteringTimer = null

const filteredMatches = computed(() => props.calendarMatches.filter((match) => {
    const groupOk = selectedGroup.value === 'all' || match.groupName === selectedGroup.value
    const stageOk = selectedStage.value === 'all' || match.stageLabel === selectedStage.value

    return groupOk && stageOk
}))

const groupedByDate = computed(() => {
    const grouped = new Map()

    filteredMatches.value.forEach((match) => {
        const key = match.matchDateIso ?? 'no-date'

        if (!grouped.has(key)) {
            grouped.set(key, {
                dateIso: key,
                dateLabel: match.calendarDateLabel,
                matches: [],
            })
        }

        grouped.get(key).matches.push(match)
    })

    return Array.from(grouped.values())
})

const hasResult = (match) => Number.isInteger(match.homeScore) && Number.isInteger(match.awayScore)
const isDraw = (match) => hasResult(match) && match.homeScore === match.awayScore
const isHomeWinner = (match) => hasResult(match) && match.homeScore > match.awayScore
const isAwayWinner = (match) => hasResult(match) && match.awayScore > match.homeScore
const stageLabelEs = (label) => {
    const map = {
        'Group stage': 'Fase de Grupo',
        'Round of 32': 'Ronda de 32',
        'Round of 16': 'Octavos',
        'Quarter final': 'Cuartos',
        'Semi-final': 'Semifinal',
        'Third place': 'Tercer lugar',
        'Final': 'Final',
        Stage: 'Etapa',
    }

    return map[label] ?? label
}
const resetFilters = () => {
    selectedGroup.value = 'all'
    selectedStage.value = 'all'
}

watch([selectedGroup, selectedStage], () => {
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
    <Head title="Calendar" />

    <UserDashboardLayout
        title="Calendar"
        description="All World Cup 2026 matches by date."
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
                    <CalendarDaysIcon class="h-7 w-7 text-cyan-500" />
                    <h1>Calendario</h1>
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
                    <p class="text-sm leading-tight text-slate-500 dark:text-slate-400">Todos los partidos del Mundial {{ tournament?.year ?? '' }}</p>

                    <div class="grid gap-2 sm:grid-cols-[190px_210px]">
                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-100">
                            <FunnelIcon class="h-4 w-4 text-slate-400" />
                            <select v-model="selectedGroup" class="w-full border-0 bg-transparent p-0 text-sm font-medium focus:ring-0">
                                <option value="all">Grupos</option>
                                <option v-for="groupName in groupOptions" :key="groupName" :value="groupName">
                                    {{ groupName }}
                                </option>
                            </select>
                        </label>

                        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-100">
                            <FunnelIcon class="h-4 w-4 text-slate-400" />
                            <select v-model="selectedStage" class="w-full border-0 bg-transparent p-0 text-sm font-medium focus:ring-0">
                                <option value="all">Etapas</option>
                                <option v-for="stageName in stageOptions" :key="stageName" :value="stageName">
                                    {{ stageName }}
                                </option>
                            </select>
                        </label>
                    </div>
                </div>
                <div class="mt-[6px] border-b border-slate-300 dark:border-slate-700" />
                <div class="mt-2 flex justify-end">
                    <button
                        type="button"
                        class="text-xs font-semibold uppercase tracking-wide text-rose-600 transition hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300"
                        @click="resetFilters"
                    >
                        Reiniciar Filtro
                    </button>
                </div>
            </div>

            <FilterCalendarSkeleton v-if="isFiltering" :rows="3" />

            <div v-else-if="groupedByDate.length" class="mt-6 space-y-6">
                <section v-for="group in groupedByDate" :key="group.dateIso" class="space-y-3">
                    <div class="flex items-center gap-3 border-b border-slate-200 pb-2 dark:border-slate-800">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-500">
                            <CalendarDaysIcon class="h-5 w-5" />
                        </span>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ group.dateLabel }}</h3>
                    </div>

                    <article
                        v-for="match in group.matches"
                        :key="match.id"
                        class="rounded-2xl border border-slate-200 bg-white px-5 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900/75"
                    >
                        <div class="grid grid-cols-1 items-center gap-3 xl:grid-cols-[170px_1fr_180px]">
                            <div>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ match.groupName || '-' }}</p>
                                <p class="text-2xl font-black text-cyan-500 dark:text-cyan-400">{{ match.matchTime }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <div class="inline-flex w-full items-center justify-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                                    <MapPinIcon class="h-4 w-4 text-cyan-500 dark:text-cyan-400" />
                                    <span class="truncate transition-colors hover:text-cyan-500 dark:hover:text-cyan-400">{{ match.venue || 'Venue TBD' }}</span>
                                </div>
                                <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">
                                    <div class="flex items-center justify-end gap-2 text-right">
                                        <span class="truncate text-lg font-semibold text-slate-900 dark:text-white">{{ match.homeTeam }}</span>
                                        <span class="text-base font-semibold uppercase text-slate-500 dark:text-slate-400">{{ match.homeCode }}</span>
                                    </div>

                                    <div
                                        v-if="hasResult(match)"
                                        class="inline-flex min-w-[92px] items-center justify-center gap-3 rounded-xl bg-slate-100 px-3 py-2 text-2xl font-black dark:bg-slate-800"
                                    >
                                        <span :class="(isHomeWinner(match) || isDraw(match)) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                            {{ match.homeScore }}
                                        </span>
                                        <span class="text-slate-400 dark:text-slate-500">-</span>
                                        <span :class="(isAwayWinner(match) || isDraw(match)) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                            {{ match.awayScore }}
                                        </span>
                                    </div>
                                    <div v-else class="inline-flex min-w-[92px] items-center justify-center rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                        vs
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span class="text-base font-semibold uppercase text-slate-500 dark:text-slate-400">{{ match.awayCode }}</span>
                                        <span class="truncate text-lg font-semibold text-slate-900 dark:text-white">{{ match.awayTeam }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col items-start gap-2 text-sm text-slate-500 dark:text-slate-400 xl:items-end">
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ stageLabelEs(match.stageLabel) }}</p>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-bold tracking-wide"
                                    :class="match.status === 'finished'
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                        : (match.status === 'in_progress'
                                            ? 'bg-rose-500/15 text-rose-500 dark:text-rose-400'
                                            : 'bg-slate-500/15 text-slate-500 dark:text-slate-300')"
                                >
                                    <CheckCircleIcon v-if="match.status === 'finished'" class="h-3.5 w-3.5" />
                                    <span>{{ match.status === 'finished' ? 'Finalizado' : match.statusLabel }}</span>
                                </span>
                            </div>
                        </div>
                    </article>
                </section>
            </div>

            <div v-else class="mt-6 rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                No matches available with the selected filters.
            </div>
        </section>
    </UserDashboardLayout>
</template>
