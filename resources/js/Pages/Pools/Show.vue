<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
    BoltIcon,
    CheckCircleIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline'
import FilterSelect from '@/Components/UI/FilterSelect.vue'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'
import { formatRegistrationNumber } from '@/Utils/format'

const props = defineProps({
    poolEntry: {
        type: Object,
        required: true,
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

const stats = computed(() => props.poolEntry?.stats ?? {
    totalPoints: 0,
    matchesTotal: 0,
    matchesPlayed: 0,
    matchesPending: 0,
    exactHits: 0,
    correctResults: 0,
})

const statusClass = (status) => {
    if (status === 'finished') {
        return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
    }

    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
}

const statusIcon = (status) => {
    if (status === 'finished') {
        return CheckCircleIcon
    }

    if (status === 'draft') {
        return ClockIcon
    }

    return BoltIcon
}

const rowStatusClass = (prediction) => {
    if (prediction.hasOfficialResult) {
        return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
    }

    if (prediction.status === 'in_progress') {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
    }

    return 'bg-slate-200 text-slate-700 dark:bg-slate-700/70 dark:text-slate-200'
}

const isPredictedDraw = (prediction) => prediction.predictedHomeScore === prediction.predictedAwayScore
const isPredictedHomeWinner = (prediction) => prediction.predictedHomeScore > prediction.predictedAwayScore
const isPredictedAwayWinner = (prediction) => prediction.predictedAwayScore > prediction.predictedHomeScore
const isOfficialDraw = (prediction) => prediction.actualHomeScore === prediction.actualAwayScore
const isOfficialHomeWinner = (prediction) => prediction.actualHomeScore > prediction.actualAwayScore
const isOfficialAwayWinner = (prediction) => prediction.actualAwayScore > prediction.actualHomeScore

const stageLabelFromKey = (stage) => {
    const labels = {
        group: 'Fase de grupos',
        round_32: 'Ronda de 32',
        round_16: 'Octavos',
        quarter: 'Cuartos',
        semi: 'Semifinal',
        third_place: 'Tercer lugar',
        final: 'Final',
    }

    return labels[stage] ?? String(stage || '')
}

const stageHeaderLabel = (prediction) => {
    if (prediction.stage === 'group') {
        return 'Fase de grupos'
    }

    if (prediction.stage === 'round_32') {
        return 'Fase ronda 32'
    }

    if (prediction.stage === 'round_16') {
        return 'Fase octavos'
    }

    if (prediction.stage === 'quarter') {
        return 'Fase cuartos'
    }

    if (prediction.stage === 'semi') {
        return 'Fase semifinal'
    }

    if (prediction.stage === 'third_place') {
        return 'Fase tercer lugar'
    }

    if (prediction.stage === 'final') {
        return 'Fase final'
    }

    return `Fase ${stageLabelFromKey(prediction.stage)}`
}

const stageHeaderSuffix = (prediction) => {
    if (prediction.stage === 'group') {
        return String(prediction.groupName || '').trim().split(' ').pop()?.toUpperCase() ?? ''
    }

    const stageStartMatch = {
        round_32: 73,
        round_16: 89,
        quarter: 97,
        semi: 101,
        third_place: 103,
        final: 104,
    }

    if (prediction.stage in stageStartMatch) {
        const startMatch = stageStartMatch[prediction.stage]
        const stageGameNumber = Math.max(1, Number(prediction.matchNumber ?? startMatch) - (startMatch - 1))
        return `J-#${stageGameNumber}`
    }

    return ''
}

const activeTab = ref('played')
const sortBy = ref('date_asc')
const stageFilter = ref('all')
const sortOptions = [
    { value: 'date_asc', label: 'Ordenar por: Fecha (asc)' },
    { value: 'date_desc', label: 'Ordenar por: Fecha (desc)' },
    { value: 'stage_group', label: 'Ordenar por: Etapa / grupo' },
]
const stageOrder = {
    group: 10,
    round_32: 20,
    round_16: 30,
    quarter: 40,
    semi: 50,
    third_place: 60,
    final: 70,
}
const tabItems = computed(() => ([
    {
        key: 'played',
        label: 'Con resultado',
        count: props.poolEntry?.playedPredictions?.length ?? 0,
    },
    {
        key: 'pending',
        label: 'Pendientes',
        count: props.poolEntry?.pendingPredictions?.length ?? 0,
    },
    {
        key: 'all',
        label: 'Todos',
        count: props.poolEntry?.allPredictions?.length ?? 0,
    },
]))

const visiblePredictions = computed(() => {
    if (activeTab.value === 'pending') {
        return props.poolEntry?.pendingPredictions ?? []
    }

    if (activeTab.value === 'all') {
        return props.poolEntry?.allPredictions ?? []
    }

    return props.poolEntry?.playedPredictions ?? []
})

const stageFilterOptions = computed(() => {
    const sourcePredictions = props.poolEntry?.allPredictions ?? []
    const uniqueStages = [...new Set(sourcePredictions.map((prediction) => prediction.stage).filter(Boolean))]
    const sortedStages = uniqueStages.sort((a, b) => (stageOrder[a] ?? 999) - (stageOrder[b] ?? 999))

    return sortedStages.map((stage) => {
        return {
            value: stage,
            label: stageLabelFromKey(stage),
        }
    })
})

const normalizedGroupLetter = (prediction) => String(stageHeaderSuffix(prediction) || '').trim().toUpperCase()
const predictionDateTimeKey = (prediction) => {
    const dateKey = prediction.matchDateIso ?? '9999-12-31'
    const timeKey = prediction.matchTime ?? '23:59'

    return `${dateKey} ${timeKey}`
}

const sortedVisiblePredictions = computed(() => {
    let list = [...(visiblePredictions.value ?? [])]

    if (stageFilter.value !== 'all') {
        list = list.filter((prediction) => prediction.stage === stageFilter.value)
    }

    if (sortBy.value === 'stage_group') {
        return list.sort((a, b) => {
            const stageDelta = (stageOrder[a.stage] ?? 999) - (stageOrder[b.stage] ?? 999)
            if (stageDelta !== 0) {
                return stageDelta
            }

            const groupDelta = normalizedGroupLetter(a).localeCompare(normalizedGroupLetter(b), 'es', { sensitivity: 'base' })
            if (groupDelta !== 0) {
                return groupDelta
            }

            const matchDelta = Number(a.matchNumber ?? 999) - Number(b.matchNumber ?? 999)
            if (matchDelta !== 0) {
                return matchDelta
            }

            return predictionDateTimeKey(a).localeCompare(predictionDateTimeKey(b))
        })
    }

    if (sortBy.value === 'date_desc') {
        return list.sort((a, b) => predictionDateTimeKey(b).localeCompare(predictionDateTimeKey(a)))
    }

    return list.sort((a, b) => predictionDateTimeKey(a).localeCompare(predictionDateTimeKey(b)))
})

const emptyTabMessage = computed(() => {
    if (activeTab.value === 'pending') {
        return 'No quedan partidos pendientes en esta quiniela.'
    }

    if (activeTab.value === 'all') {
        return 'No hay partidos disponibles para mostrar.'
    }

    return 'Aun no hay partidos con resultado oficial para esta quiniela.'
})

const revealCycle = ref(0)
const isTabLoading = ref(false)
let tabLoadingTimer = null
const triggerListLoading = () => {
    revealCycle.value += 1

    if (tabLoadingTimer) {
        window.clearTimeout(tabLoadingTimer)
    }

    isTabLoading.value = true
    tabLoadingTimer = window.setTimeout(() => {
        isTabLoading.value = false
        tabLoadingTimer = null
    }, 260)
}

const onBeforeEnterCard = (el) => {
    el.style.opacity = '0'
    el.style.transform = 'translateY(12px)'
}

const onEnterCard = (el, done) => {
    const delay = Number(el.dataset.index || 0) * 70

    window.setTimeout(() => {
        el.style.transition = 'opacity 260ms ease-out, transform 260ms ease-out'
        el.style.opacity = '1'
        el.style.transform = 'translateY(0)'
        window.setTimeout(done, 270)
    }, delay)
}

const onLeaveCard = (el, done) => {
    el.style.transition = 'opacity 140ms ease-in, transform 140ms ease-in'
    el.style.opacity = '0'
    el.style.transform = 'translateY(6px)'
    window.setTimeout(done, 150)
}

watch(activeTab, () => {
    stageFilter.value = 'all'
    triggerListLoading()
})

watch([sortBy, stageFilter], () => {
    triggerListLoading()
})

onBeforeUnmount(() => {
    if (tabLoadingTimer) {
        window.clearTimeout(tabLoadingTimer)
    }
})
</script>

<template>
    <Head :title="`Detalle | ${poolEntry.name}`" />

    <UserDashboardLayout
        :title="poolEntry.name"
        description="Detalle completo de tu quiniela, con el rendimiento partido por partido."
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
                        <path d="M96 96c0-35.3 28.7-64 64-64H416c35.3 0 64 28.7 64 64V352c0 35.3-28.7 64-64 64H160c-35.3 0-64-28.7-64-64V96zM0 128c0-17.7 14.3-32 32-32s32 14.3 32 32V384c0 53 43 96 96 96H384c17.7 0 32 14.3 32 32s-14.3 32-32 32H160C71.6 544 0 472.4 0 384V128z"/>
                    </svg>
                    <h1>{{ poolEntry.name }}</h1>
                </div>
                <Link
                    :href="route('pools.index')"
                    class="inline-flex items-center whitespace-nowrap rounded-md px-2 py-1.5 text-xs font-bold uppercase tracking-wide text-primary-700 transition hover:bg-gray-200 dark:text-primary-500 dark:hover:bg-gray-600"
                >
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Volver a mis quinielas
                </Link>
            </div>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/75">
                <div class="flex flex-wrap items-center gap-3">
                    <span :class="statusClass(poolEntry.status)" class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-bold">
                        <component :is="statusIcon(poolEntry.status)" class="h-3.5 w-3.5" />
                        {{ poolEntry.statusLabel }}
                    </span>
                    <div class="ml-auto flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                        <p>Creada: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ poolEntry.createdDate || '-' }}</span></p>
                        <p>Registro: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ formatRegistrationNumber(poolEntry.registrationNumber) }}</span></p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                    <div class="rounded-xl bg-slate-100 px-3 py-2 dark:bg-slate-800/70">
                        <p class="text-left text-xs text-slate-500 dark:text-slate-400">Puntos</p>
                        <p class="mt-1 text-right text-2xl font-black text-cyan-500 dark:text-cyan-400">{{ stats.totalPoints }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-100 px-3 py-2 dark:bg-slate-800/70">
                        <p class="text-left text-xs text-slate-500 dark:text-slate-400">Partidos</p>
                        <p class="mt-1 text-right text-2xl font-black text-slate-900 dark:text-white">{{ stats.matchesTotal }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-100 px-3 py-2 dark:bg-slate-800/70">
                        <p class="text-left text-xs text-slate-500 dark:text-slate-400">Con resultado</p>
                        <p class="mt-1 text-right text-2xl font-black text-emerald-500 dark:text-emerald-400">{{ stats.matchesPlayed }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-100 px-3 py-2 dark:bg-slate-800/70">
                        <p class="text-left text-xs text-slate-500 dark:text-slate-400">Pendientes</p>
                        <p class="mt-1 text-right text-2xl font-black text-amber-500 dark:text-amber-400">{{ stats.matchesPending }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-100 px-3 py-2 dark:bg-slate-800/70">
                        <p class="text-left text-xs text-slate-500 dark:text-slate-400">Aciertos exactos</p>
                        <p class="mt-1 text-right text-2xl font-black text-slate-900 dark:text-white">{{ stats.exactHits }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-100 px-3 py-2 dark:bg-slate-800/70">
                        <p class="text-left text-xs text-slate-500 dark:text-slate-400">Resultado correcto</p>
                        <p class="mt-1 text-right text-2xl font-black text-slate-900 dark:text-white">{{ stats.correctResults }}</p>
                    </div>
                </div>
            </div>

            <article class="mt-6">
                <div class="border-b border-slate-300 px-1 pb-2 dark:border-slate-700">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <ul class="-mb-px flex flex-wrap text-sm font-semibold text-slate-500 dark:text-slate-400">
                        <li v-for="tab in tabItems" :key="tab.key" class="me-2">
                            <button
                                type="button"
                                @click="activeTab = tab.key"
                                class="inline-flex items-center justify-center rounded-t-lg border-b-2 px-4 py-3 transition"
                                :class="activeTab === tab.key
                                    ? 'border-primary-500 text-primary-700 dark:border-primary-400 dark:text-primary-300'
                                    : 'border-transparent hover:border-primary-300 hover:text-primary-700 dark:hover:border-primary-500 dark:hover:text-primary-300'"
                            >
                                <CheckCircleIcon v-if="tab.key === 'played'" class="me-2 h-4 w-4" />
                                <ClockIcon v-else-if="tab.key === 'pending'" class="me-2 h-4 w-4" />
                                <BoltIcon v-else class="me-2 h-4 w-4" />
                                <span>{{ tab.label }}</span>
                                <span
                                    class="ms-2 rounded-full px-2 py-0.5 text-[11px] font-bold"
                                    :class="activeTab === tab.key
                                        ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300'
                                        : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
                                >
                                    {{ tab.count }}
                                </span>
                            </button>
                        </li>
                    </ul>

                        <div class="flex flex-wrap items-center justify-end gap-2 pb-1 text-xs">
                            <FilterSelect
                                v-model="sortBy"
                                :options="sortOptions"
                                icon="sort"
                            />

                            <FilterSelect
                                v-model="stageFilter"
                                :options="[{ value: 'all', label: 'Seleccionar etapa: Todas' }, ...stageFilterOptions]"
                            />
                        </div>
                    </div>
                </div>

                <div v-if="isTabLoading" class="mt-4 space-y-4">
                    <div
                        v-for="i in 3"
                        :key="`skeleton-${i}`"
                        class="animate-pulse rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/75"
                    >
                        <div class="grid grid-cols-1 items-center gap-4 lg:grid-cols-[170px_1fr_170px]">
                            <div>
                                <div class="h-3 w-24 rounded bg-slate-200 dark:bg-slate-700" />
                                <div class="mt-2 h-8 w-20 rounded bg-slate-200 dark:bg-slate-700" />
                                <div class="mt-2 h-3 w-28 rounded bg-slate-200 dark:bg-slate-700" />
                            </div>
                            <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">
                                <div class="flex justify-end">
                                    <div class="h-5 w-36 rounded bg-slate-200 dark:bg-slate-700" />
                                </div>
                                <div class="h-12 w-24 rounded-xl bg-slate-200 dark:bg-slate-700" />
                                <div>
                                    <div class="h-5 w-36 rounded bg-slate-200 dark:bg-slate-700" />
                                </div>
                            </div>
                            <div class="flex justify-start lg:justify-end">
                                <div class="h-6 w-24 rounded-full bg-slate-200 dark:bg-slate-700" />
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="h-4 w-32 rounded bg-slate-200 dark:bg-slate-700" />
                            <div class="h-7 w-20 rounded-full bg-slate-200 dark:bg-slate-700" />
                        </div>
                    </div>
                </div>

                <TransitionGroup
                    v-else-if="sortedVisiblePredictions.length"
                    :key="`${activeTab}-${sortBy}-${stageFilter}-${revealCycle}`"
                    tag="div"
                    class="mt-4 space-y-4"
                    :css="false"
                    @before-enter="onBeforeEnterCard"
                    @enter="onEnterCard"
                    @leave="onLeaveCard"
                >
                    <article
                        v-for="(prediction, index) in sortedVisiblePredictions"
                        :key="`${activeTab}-${prediction.id}`"
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900/75"
                        :data-index="index"
                    >
                        <div class="grid gap-3">
                            <div class="grid grid-cols-[11.5rem_1fr_8.5rem] items-center gap-3 text-xs">
                                <div>
                                    <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                        <template v-if="stageHeaderSuffix(prediction)">
                                            {{ stageHeaderLabel(prediction) }}:
                                            <span class="font-bold text-slate-600 dark:text-slate-300">{{ stageHeaderSuffix(prediction) }}</span>
                                        </template>
                                        <template v-else>
                                            {{ stageHeaderLabel(prediction) }}
                                        </template>
                                    </span>
                                </div>

                                <div class="flex min-w-0 items-center justify-center gap-3 text-slate-500 dark:text-slate-400">
                                    <span>{{ prediction.matchDate }} - <span class="font-semibold">{{ prediction.matchTime }}</span></span>
                                    <span class="inline-flex items-center gap-1.5 truncate" :title="prediction.venue || 'Sede por confirmar'">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="h-3.5 w-3.5 shrink-0 fill-current text-cyan-500 dark:text-cyan-400" aria-hidden="true">
                                            <path d="M0 188.6C0 84.4 86 0 192 0S384 84.4 384 188.6c0 119.3-120.2 262.3-170.4 316.8-11.8 12.8-31.5 12.8-43.3 0-50.2-54.5-170.4-197.5-170.4-316.8zM192 256a64 64 0 1 0 0-128 64 64 0 1 0 0 128z"/>
                                        </svg>
                                        <span class="truncate transition-colors hover:text-cyan-500 dark:hover:text-cyan-400">{{ prediction.venue || 'Sede por confirmar' }}</span>
                                    </span>
                                </div>

                                <div class="flex justify-end">
                                    <span class="inline-flex items-center rounded-full bg-cyan-100 px-3 py-1 text-sm font-black text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                                        +{{ prediction.awardedPoints ?? 0 }} pts
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-[11.5rem_1fr_8.5rem] items-center gap-3">
                                <div />

                                <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">
                                    <div class="flex items-center justify-end gap-2 text-right">
                                        <span class="truncate text-base font-semibold text-slate-900 dark:text-white">{{ prediction.homeTeamName }}</span>
                                        <img v-if="prediction.homeFlagUrl" :src="prediction.homeFlagUrl" :alt="prediction.homeTeamName" class="h-5 w-7 rounded object-cover">
                                    </div>

                                    <div class="inline-flex min-w-[96px] items-center justify-center gap-3 rounded-xl bg-slate-100 px-3 py-2 text-2xl font-black dark:bg-slate-800">
                                        <span :class="isPredictedHomeWinner(prediction) || isPredictedDraw(prediction) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                            {{ prediction.predictedHomeScore }}
                                        </span>
                                        <span class="text-slate-400 dark:text-slate-500">-</span>
                                        <span :class="isPredictedAwayWinner(prediction) || isPredictedDraw(prediction) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                            {{ prediction.predictedAwayScore }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span class="truncate text-base font-semibold text-slate-900 dark:text-white">{{ prediction.awayTeamName }}</span>
                                        <img v-if="prediction.awayFlagUrl" :src="prediction.awayFlagUrl" :alt="prediction.awayTeamName" class="h-5 w-7 rounded object-cover">
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <span :class="rowStatusClass(prediction)" class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-bold">
                                        <CheckCircleIcon v-if="prediction.hasOfficialResult" class="h-3.5 w-3.5" />
                                        <ClockIcon v-else class="h-3.5 w-3.5" />
                                        {{ prediction.statusLabel }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-sm text-slate-500 dark:text-slate-400">
                                <p class="inline-flex items-center gap-2">
                                    <span class="font-semibold">Resultado oficial:</span>
                                    <span
                                        v-if="prediction.hasOfficialResult"
                                        class="inline-flex min-w-[62px] items-center justify-center gap-1 rounded-md bg-slate-100 px-1.5 py-0.5 text-sm font-black dark:bg-slate-800"
                                    >
                                        <span :class="isOfficialHomeWinner(prediction) || isOfficialDraw(prediction) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                            {{ prediction.actualHomeScore }}
                                        </span>
                                        <span class="text-slate-400 dark:text-slate-500">-</span>
                                        <span :class="isOfficialAwayWinner(prediction) || isOfficialDraw(prediction) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                            {{ prediction.actualAwayScore }}
                                        </span>
                                    </span>
                                    <span v-else class="ml-1 font-black text-slate-700 dark:text-slate-200">-- - --</span>
                                </p>
                                <span
                                    v-if="prediction.hasOfficialResult && prediction.isExactHit"
                                    class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                                >
                                    Exacto
                                </span>
                                <span
                                    v-else-if="prediction.hasOfficialResult && prediction.isCorrectResult"
                                    class="rounded-full bg-sky-100 px-2 py-1 text-xs font-bold text-sky-700 dark:bg-sky-900/30 dark:text-sky-300"
                                >
                                    Resultado
                                </span>
                            </div>
                        </div>
                    </article>
                </TransitionGroup>

                <div v-else class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    {{ emptyTabMessage }}
                </div>
            </article>
        </section>
    </UserDashboardLayout>
</template>

<style scoped>
.stagger-list-move {
    transition: transform 240ms ease;
}
</style>
