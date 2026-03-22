<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
    CalendarDaysIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline'
import AppTooltip from '@/Components/UI/AppTooltip.vue'
import FilterCalendarSkeleton from '@/Components/UI/FilterCalendarSkeleton.vue'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
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
const groupSelectOptions = computed(() => ([
    { value: 'all', label: 'Grupos' },
    ...props.groupOptions.map((groupName) => ({ value: groupName, label: groupName })),
]))
const stageSelectOptions = computed(() => ([
    { value: 'all', label: 'Etapas' },
    ...props.stageOptions.map((stageName) => ({ value: stageName, label: stageName })),
]))

const normalizeStageValue = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase()

const isGroupStageSelected = computed(() => {
    const normalized = normalizeStageValue(selectedStage.value)

    return normalized === 'group'
        || normalized === 'group stage'
        || normalized === 'fase de grupo'
        || normalized === 'fase de grupos'
})

const isGroupFilterEnabled = computed(() => selectedStage.value === 'all' || isGroupStageSelected.value)

const filteredMatches = computed(() => props.calendarMatches.filter((match) => {
    const groupOk = !isGroupFilterEnabled.value
        || selectedGroup.value === 'all'
        || match.groupName === selectedGroup.value
    const stageOk = selectedStage.value === 'all' || match.stageLabel === selectedStage.value

    return groupOk && stageOk
}))

const filteredMatchesCount = computed(() => filteredMatches.value.length)

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

const groupLabelEs = (label) => {
    if (!label) {
        return '-'
    }

    const normalized = String(label)
        .replace(/^group\s+/i, 'Grupo ')
        .trim()

    return normalized
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

watch(selectedStage, () => {
    if (!isGroupFilterEnabled.value && selectedGroup.value !== 'all') {
        selectedGroup.value = 'all'
    }
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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="h-8 w-8 fill-current text-cyan-500" aria-hidden="true">
                        <path d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 32 0c35.3 0 64 28.7 64 64l0 288c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 128C0 92.7 28.7 64 64 64l32 0 0-32c0-17.7 14.3-32 32-32zM64 240l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 368l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z"/>
                    </svg>
                    <h1>Calendario</h1>
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
                    <p class="text-sm leading-tight text-slate-500 dark:text-slate-400">Todos los partidos del Mundial {{ tournament?.year ?? '' }}</p>

                    <div class="grid gap-2 sm:grid-cols-[190px_210px]">
                        <FilterSelect
                            v-model="selectedGroup"
                            :options="groupSelectOptions"
                            container-class="w-full"
                            select-class="w-full"
                            :disabled="!isGroupFilterEnabled"
                        />

                        <FilterSelect
                            v-model="selectedStage"
                            :options="stageSelectOptions"
                            container-class="w-full"
                            select-class="w-full"
                        />
                    </div>
                </div>
                <div class="mt-[6px] border-b border-slate-300 dark:border-slate-700" />
                <div class="mt-2 grid grid-cols-3 items-center">
                    <div />
                    <p class="text-center text-xs font-semibold text-slate-500 dark:text-slate-400">
                        Total: {{ filteredMatchesCount }} partidos
                    </p>
                    <div class="flex justify-end">
                        <button
                            type="button"
                            class="text-[10px] font-semibold uppercase tracking-wide text-rose-600 transition hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300"
                            @click="resetFilters"
                        >
                            Reiniciar Filtro
                        </button>
                    </div>
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
                        <div class="space-y-2">
                            <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3 text-xs">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                    {{ groupLabelEs(match.groupName) }}
                                </p>
                                <div class="inline-flex items-center justify-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="h-3.5 w-3.5 shrink-0 fill-current text-cyan-500 dark:text-cyan-400" aria-hidden="true">
                                        <path d="M0 188.6C0 84.4 86 0 192 0S384 84.4 384 188.6c0 119.3-120.2 262.3-170.4 316.8-11.8 12.8-31.5 12.8-43.3 0-50.2-54.5-170.4-197.5-170.4-316.8zM192 256a64 64 0 1 0 0-128 64 64 0 1 0 0 128z"/>
                                    </svg>
                                    <span class="truncate transition-colors hover:text-cyan-500 dark:hover:text-cyan-400">{{ match.venue || 'Venue TBD' }}</span>
                                </div>
                                <p class="text-right text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                    {{ stageLabelEs(match.stageLabel) }}
                                </p>
                            </div>

                            <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                            <div class="justify-self-start">
                                <p class="text-xl font-black text-cyan-500 dark:text-cyan-400 sm:text-2xl">{{ match.matchTime }}</p>
                            </div>

                            <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 justify-self-center">
                                    <div class="flex items-center justify-end gap-2 text-right">
                                        <span class="hidden truncate text-lg font-semibold text-slate-900 dark:text-white sm:inline">{{ match.homeTeam }}</span>
                                        <AppTooltip :text="match.homeTeam" placement="top">
                                            <img
                                                v-if="match.homeFlagUrl"
                                                :src="match.homeFlagUrl"
                                                :alt="match.homeTeam"
                                                class="h-5 w-7 rounded object-cover"
                                            >
                                            <span
                                                v-else
                                                class="inline-flex h-5 min-w-7 items-center justify-center rounded bg-slate-300 px-1 text-[10px] font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200"
                                            >
                                                {{ match.homeCode }}
                                            </span>
                                        </AppTooltip>
                                    </div>

                                    <div
                                        v-if="hasResult(match)"
                                        class="inline-flex min-w-[92px] items-center justify-center gap-3 rounded-xl bg-slate-100 px-3 py-1.5 text-2xl font-black dark:bg-slate-800"
                                    >
                                        <span :class="(isHomeWinner(match) || isDraw(match)) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                            {{ match.homeScore }}
                                        </span>
                                        <span class="text-slate-400 dark:text-slate-500">-</span>
                                        <span :class="(isAwayWinner(match) || isDraw(match)) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                            {{ match.awayScore }}
                                        </span>
                                    </div>
                                    <div v-else class="inline-flex min-w-[92px] items-center justify-center rounded-xl bg-slate-100 px-3 py-1.5 text-sm font-bold uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                        vs
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <AppTooltip :text="match.awayTeam" placement="top">
                                            <img
                                                v-if="match.awayFlagUrl"
                                                :src="match.awayFlagUrl"
                                                :alt="match.awayTeam"
                                                class="h-5 w-7 rounded object-cover"
                                            >
                                            <span
                                                v-else
                                                class="inline-flex h-5 min-w-7 items-center justify-center rounded bg-slate-300 px-1 text-[10px] font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200"
                                            >
                                                {{ match.awayCode }}
                                            </span>
                                        </AppTooltip>
                                        <span class="hidden truncate text-lg font-semibold text-slate-900 dark:text-white sm:inline">{{ match.awayTeam }}</span>
                                    </div>
                            </div>

                            <div class="flex justify-end justify-self-end text-sm text-slate-500 dark:text-slate-400">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-bold tracking-wide"
                                    :class="match.status === 'finished'
                                        ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                                        : (match.status === 'in_progress'
                                            ? 'bg-rose-500/15 text-rose-500 dark:text-rose-400'
                                            : 'bg-slate-500/15 text-slate-500 dark:text-slate-300')"
                                >
                                    <CheckCircleIcon v-if="match.status === 'finished'" class="h-3.5 w-3.5" />
                                    <span>{{ match.status === 'finished' ? 'Finalizado' : match.statusLabel }}</span>
                                </span>
                            </div>
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
