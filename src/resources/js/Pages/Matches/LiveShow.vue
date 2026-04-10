<script setup>
import axios from 'axios'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { ClockIcon } from '@heroicons/vue/24/outline'
import LiveMatchCard from '@/Components/Live/LiveMatchCard.vue'
import MatchEventTimeline from '@/Components/Live/MatchEventTimeline.vue'
import MatchFormationField from '@/Components/Live/MatchFormationField.vue'
import AppBadge from '@/Components/UI/AppBadge.vue'
import { resolveMatchTeamColors } from '@/Components/Live/teamColors'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'

const props = defineProps({
    tournament: {
        type: Object,
        default: null,
    },
    match: {
        type: Object,
        default: null,
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

const loading = ref(false)
const loadError = ref('')
const feed = ref({
    fixture: null,
    headToHead: [],
    events: [],
    lineups: [],
    statistics: [],
    players: [],
    errors: [],
})
const pollIntervalMs = 20000
let pollTimer = null

const fixtureStatusShort = computed(() => feed.value?.fixture?.fixture?.status?.short || '')
const fixtureStatusLong = computed(() => feed.value?.fixture?.fixture?.status?.long || '')
const fixtureElapsed = computed(() => feed.value?.fixture?.fixture?.status?.elapsed)

const displayMatch = computed(() => {
    const base = props.match || {}
    const fixture = feed.value?.fixture

    if (!fixture) {
        return base
    }

    return {
        ...base,
        homeScore: fixture?.goals?.home ?? base.homeScore,
        awayScore: fixture?.goals?.away ?? base.awayScore,
        venue: fixture?.fixture?.venue?.name || base.venue,
        matchTime: fixtureElapsed.value ? `${fixtureElapsed.value}'` : base.matchTime,
    }
})

const statusBadgeLabel = computed(() => fixtureStatusLong.value || 'En Directo')
const teamColors = computed(() => resolveMatchTeamColors({
    home: {
        name: displayMatch.value?.homeTeam,
        code: displayMatch.value?.homeCode,
    },
    away: {
        name: displayMatch.value?.awayTeam,
        code: displayMatch.value?.awayCode,
    },
}))

const statisticsRows = computed(() => {
    const stats = Array.isArray(feed.value.statistics) ? feed.value.statistics : []

    if (stats.length < 2) {
        return []
    }

    const home = stats[0]
    const away = stats[1]
    const awayMap = new Map((away.statistics || []).map((item) => [item.type, item.value]))

    return (home.statistics || []).map((item) => ({
        type: item.type,
        homeValue: item.value,
        awayValue: awayMap.get(item.type) ?? null,
    }))
})

const toNumber = (value) => {
    if (value === null || value === undefined) return 0
    if (typeof value === 'number') return Number.isFinite(value) ? value : 0
    const raw = String(value).replace('%', '').trim()
    const parsed = Number.parseFloat(raw)
    return Number.isFinite(parsed) ? parsed : 0
}

const statPercent = (value, other) => {
    const a = toNumber(value)
    const b = toNumber(other)
    const total = a + b
    if (total <= 0) return 50
    return Math.max(5, Math.min(95, (a / total) * 100))
}

const h2hRows = computed(() => Array.isArray(feed.value.headToHead) ? feed.value.headToHead : [])
const h2hRowsVisible = computed(() => h2hRows.value.slice(1))
const lineupRows = computed(() => Array.isArray(feed.value.lineups) ? feed.value.lineups : [])
const eventRows = computed(() => Array.isArray(feed.value.events) ? feed.value.events : [])
const playerRows = computed(() => Array.isArray(feed.value.players) ? feed.value.players : [])

const currentHomeApiId = computed(() => Number(feed.value?.fixture?.teams?.home?.id || props.match?.homeApiTeamId || 0))
const currentAwayApiId = computed(() => Number(feed.value?.fixture?.teams?.away?.id || props.match?.awayApiTeamId || 0))

const h2hSummary = computed(() => {
    const homeApiId = currentHomeApiId.value
    const awayApiId = currentAwayApiId.value

    if (!homeApiId || !awayApiId || !h2hRows.value.length) {
        return { homeWins: 0, draws: 0, awayWins: 0 }
    }

    return h2hRows.value.reduce((acc, item) => {
        const homeTeamId = Number(item?.teams?.home?.id || 0)
        const awayTeamId = Number(item?.teams?.away?.id || 0)
        const homeGoals = Number(item?.goals?.home ?? 0)
        const awayGoals = Number(item?.goals?.away ?? 0)

        const currentHomeGoals = homeTeamId === homeApiId ? homeGoals : awayGoals
        const currentAwayGoals = awayTeamId === awayApiId ? awayGoals : homeGoals

        if (currentHomeGoals > currentAwayGoals) {
            acc.homeWins++
        } else if (currentHomeGoals < currentAwayGoals) {
            acc.awayWins++
        } else {
            acc.draws++
        }

        return acc
    }, { homeWins: 0, draws: 0, awayWins: 0 })
})

const mapPlayersForCard = (teamPack, fallbackId = 'x') => ({
    teamId: teamPack?.team?.id || `team-${fallbackId}`,
    players: (teamPack?.players || []).slice(0, 14).map((entry) => ({
        id: entry?.player?.id || `${teamPack?.team?.id || fallbackId}-${entry?.player?.name || 'p'}`,
        name: entry?.player?.name || '-',
        number: entry?.statistics?.[0]?.games?.number ?? '-',
        position: entry?.statistics?.[0]?.games?.position || '-',
        rating: entry?.statistics?.[0]?.games?.rating || '-',
    })),
})

const playerTeamCards = computed(() => {
    const packs = playerRows.value
    if (!packs.length) return []

    const byTeamId = new Map(packs.map((pack) => [Number(pack?.team?.id || 0), pack]))
    const homePack = byTeamId.get(currentHomeApiId.value) || packs[0] || null
    const awayPack = byTeamId.get(currentAwayApiId.value) || packs.find((pack) => pack !== homePack) || packs[1] || null

    const cards = []

    if (homePack) {
        const data = mapPlayersForCard(homePack, 'home')
        cards.push({
            teamName: displayMatch.value?.homeTeam || homePack?.team?.name || 'Local',
            teamCode: displayMatch.value?.homeCode || '',
            teamId: data.teamId,
            players: data.players,
        })
    }

    if (awayPack) {
        const data = mapPlayersForCard(awayPack, 'away')
        cards.push({
            teamName: displayMatch.value?.awayTeam || awayPack?.team?.name || 'Visitante',
            teamCode: displayMatch.value?.awayCode || '',
            teamId: data.teamId,
            players: data.players,
        })
    }

    return cards
})

const getStatPair = (type) => {
    const row = statisticsRows.value.find((item) => item.type === type)
    if (!row) return '-'
    return `${row.homeValue ?? '-'} / ${row.awayValue ?? '-'}`
}

const stripPercent = (value) => String(value ?? '-').replaceAll('%', '').trim()

const flagFromCode = (code) => {
    const normalized = String(code || '').trim().toUpperCase()
    if (!/^[A-Z]{2}$/.test(normalized)) return '🏳️'
    const chars = [...normalized].map((ch) => 127397 + ch.charCodeAt(0))
    return String.fromCodePoint(...chars)
}

const widgetCards = computed(() => ([
    {
        label: 'Eventos',
        value: eventRows.value.length,
        accent: 'text-rose-600 dark:text-rose-400',
        iconViewBox: '0 0 576 512',
        iconPath: 'M160 169.3c28.3-12.3 48-40.5 48-73.3 0-44.2-35.8-80-80-80S48 51.8 48 96c0 32.8 19.7 61 48 73.3l0 54.7-64 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l224 0 0 54.7c-28.3 12.3-48 40.5-48 73.3 0 44.2 35.8 80 80 80s80-35.8 80-80c0-32.8-19.7-61-48-73.3l0-54.7 224 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-64 0 0-54.7c28.3-12.3 48-40.5 48-73.3 0-44.2-35.8-80-80-80s-80 35.8-80 80c0 32.8 19.7 61 48 73.3l0 54.7-256 0 0-54.7z',
    },
    {
        label: 'Titulares',
        value: lineupRows.value.reduce((total, lineup) => total + (lineup.startXI?.length || 0), 0),
        accent: 'text-emerald-600 dark:text-emerald-400',
        iconViewBox: '0 0 640 512',
        iconPath: 'M320.2 112c44.2 0 80-35.8 80-80l53.5 0c17 0 33.3 6.7 45.3 18.7L617.6 169.4c12.5 12.5 12.5 32.8 0 45.3l-50.7 50.7c-12.5 12.5-32.8 12.5-45.3 0l-41.4-41.4 0 224c0 35.3-28.7 64-64 64l-192 0c-35.3 0-64-28.7-64-64l0-224-41.4 41.4c-12.5 12.5-32.8 12.5-45.3 0L22.9 214.6c-12.5-12.5-12.5-32.8 0-45.3L141.5 50.7c12-12 28.3-18.7 45.3-18.7l53.5 0c0 44.2 35.8 80 80 80z',
    },
    {
        label: 'Tiros al Arco',
        value: getStatPair('Shots on Goal'),
        accent: 'text-cyan-600 dark:text-cyan-400',
        iconViewBox: '0 0 512 512',
        iconPath: 'M417.3 360.1l-71.6-4.8c-5.2-.3-10.3 1.1-14.5 4.2s-7.2 7.4-8.4 12.5l-17.6 69.6C289.5 445.8 273 448 256 448s-33.5-2.2-49.2-6.4L189.2 372c-1.3-5-4.3-9.4-8.4-12.5s-9.3-4.5-14.5-4.2l-71.6 4.8c-17.6-27.2-28.5-59.2-30.4-93.6L125 228.3c4.4-2.8 7.6-7 9.2-11.9s1.4-10.2-.5-15l-26.7-66.6C128 109.2 155.3 89 186.7 76.9l55.2 46c4 3.3 9 5.1 14.1 5.1s10.2-1.8 14.1-5.1l55.2-46c31.3 12.1 58.7 32.3 79.6 57.9l-26.7 66.6c-1.9 4.8-2.1 10.1-.5 15s4.9 9.1 9.2 11.9l60.7 38.2c-1.9 34.4-12.8 66.4-30.4 93.6zM256 512a256 256 0 1 0 0-512 256 256 0 1 0 0 512zm14.1-325.7c-8.4-6.1-19.8-6.1-28.2 0L194 221c-8.4 6.1-11.9 16.9-8.7 26.8l18.3 56.3c3.2 9.9 12.4 16.6 22.8 16.6l59.2 0c10.4 0 19.6-6.7 22.8-16.6l18.3-56.3c3.2-9.9-.3-20.7-8.7-26.8l-47.9-34.8z',
    },
    {
        label: 'Posesion',
        value: stripPercent(getStatPair('Ball Possession')),
        accent: 'text-indigo-600 dark:text-indigo-400',
        iconViewBox: '0 0 448 512',
        iconPath: 'M192 128a96 96 0 1 0 -192 0 96 96 0 1 0 192 0zM448 384a96 96 0 1 0 -192 0 96 96 0 1 0 192 0zM438.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-384 384c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l384-384z',
    },
]))

const fetchFeed = async () => {
    if (!props.match?.id) return

    loading.value = true
    loadError.value = ''

    try {
        const { data } = await axios.get(route('live.feed', props.match.id))
        feed.value = {
            fixture: data?.fixture ?? null,
            headToHead: data?.headToHead ?? [],
            events: data?.events ?? [],
            lineups: data?.lineups ?? [],
            statistics: data?.statistics ?? [],
            players: data?.players ?? [],
            errors: data?.errors ?? [],
        }
    } catch (error) {
        loadError.value = error?.response?.data?.message || 'No se pudo cargar el detalle en vivo.'
    } finally {
        loading.value = false
    }
}

const startPolling = () => {
    if (pollTimer) clearInterval(pollTimer)
    pollTimer = setInterval(() => { fetchFeed() }, pollIntervalMs)
}

onMounted(async () => {
    await fetchFeed()
    startPolling()
})

onBeforeUnmount(() => {
    if (pollTimer) clearInterval(pollTimer)
})
</script>

<template>
    <Head title="Detalle En Directo" />

    <UserDashboardLayout
        title="Detalle En Directo"
        description="Resumen ampliado del encuentro en vivo."
        :ticker-class="[activeTickerTheme.tickerClass, activeTickerTheme.decorationClass || ''].join(' ')"
    >
        <template #ticker>
            <div class="h-12 w-full" aria-hidden="true" />
        </template>

        <template #headerContent>
            <div class="hidden" />
        </template>

        <section v-if="match" class="space-y-6">
            <!-- ── Header ── -->
            <div class="-mt-8 flex items-center justify-between gap-4 text-left">
                <div class="inline-flex items-center gap-2 text-3xl font-bold text-slate-900 dark:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" class="h-7 w-7 fill-current text-rose-500 dark:text-rose-400" aria-hidden="true">
                        <path d="M87.9 11.5c-11.3-6.9-26.1-3.2-33 8.1-24.8 41-39 89.1-39 140.4s14.2 99.4 39 140.4c6.9 11.3 21.6 15 33 8.1s15-21.6 8.1-33C75.7 241.9 64 202.3 64 160S75.7 78.1 96.1 44.4c6.9-11.3 3.2-26.1-8.1-33zm400.1 0c-11.3 6.9-15 21.6-8.1 33 20.4 33.7 32.1 73.3 32.1 115.6s-11.7 81.9-32.1 115.6c-6.9 11.3-3.2 26.1 8.1 33s26.1 3.2 33-8.1c24.8-41 39-89.1 39-140.4S545.8 60.6 521 19.6c-6.9-11.3-21.6-15-33-8.1zM320 215.4c19.1-11.1 32-31.7 32-55.4 0-35.3-28.7-64-64-64s-64 28.7-64 64c0 23.7 12.9 44.4 32 55.4L256 480c0 17.7 14.3 32 32 32s32-14.3 32-32l0-264.6zM180.2 91c7.2-11.2 3.9-26-7.2-33.2s-26-3.9-33.2 7.2c-17.6 27.4-27.8 60-27.8 95s10.2 67.6 27.8 95c7.2 11.2 22 14.4 33.2 7.2s14.4-22 7.2-33.2c-12.8-19.9-20.2-43.6-20.2-69s7.4-49.1 20.2-69zM436.2 65c-7.2-11.2-22-14.4-33.2-7.2s-14.4 22-7.2 33.2c12.8 19.9 20.2 43.6 20.2 69s-7.4 49.1-20.2 69c-7.2 11.2-3.9 26 7.2 33.2s26 3.9 33.2-7.2c17.6-27.4 27.8-60 27.8-95s-10.2-67.6-27.8-95z"/>
                    </svg>
                    <h1>Detalle Juego Directo</h1>
                </div>
                <Link
                    :href="route('live.index')"
                    class="inline-flex items-center whitespace-nowrap rounded-md px-2 py-1.5 text-xs font-bold uppercase tracking-wide text-primary-700 transition hover:bg-gray-200 dark:text-primary-500 dark:hover:bg-gray-600"
                >
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Regresar
                </Link>
            </div>

            <div class="mt-2 space-y-2">
                <div class="flex justify-end">
                    <AppBadge tone="blue" size="sm">
                        <template #icon>
                            <ClockIcon class="h-3.5 w-3.5" />
                        </template>
                        {{ loading ? 'Actualizando...' : 'Auto refresh 20 s' }}
                    </AppBadge>
                </div>
                <div class="border-b border-slate-300 dark:border-slate-700" />
            </div>

            <!-- ── Score card ── -->
            <LiveMatchCard
                :match="displayMatch"
                :status-label="statusBadgeLabel"
                :status-short="fixtureStatusShort"
                :show-status-icon="false"
            />

            <!-- ── Detail section ── -->
            <section class="detail-stack space-y-8 md:space-y-10">
                <!-- Alerts -->
                <p v-if="loadError" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300">
                    {{ loadError }}
                </p>
                <p v-if="feed.errors.includes('missing_fixture_id')" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-300">
                    Falta <code>api_fixture_id</code> en este juego; sincroniza para activar estadisticas, alineacion, eventos y jugadores.
                </p>
                <p v-if="feed.errors.includes('missing_team_api_ids')" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-300">
                    Falta <code>api_team_id</code> en uno de los equipos; por eso no hay Head to Head.
                </p>

                <!-- Widget cards -->
                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="card in widgetCards"
                        :key="card.label"
                        class="kpi-card relative overflow-hidden rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                    >
                        <p class="text-xs uppercase tracking-[0.12em] text-gray-500 dark:text-gray-300">{{ card.label }}</p>
                        <span class="absolute right-2 top-2 inline-flex" :class="card.accent">
                            <svg :viewBox="card.iconViewBox" class="h-6 w-6 fill-current" aria-hidden="true">
                                <path :d="card.iconPath" />
                            </svg>
                        </span>
                        <div class="mt-2 flex min-h-[42px] items-center justify-center">
                            <p class="text-center text-[1.7rem] font-black leading-none" :class="card.accent">{{ card.value }}</p>
                        </div>
                    </article>
                </section>

                <!-- Head to Head -->
                <section class="space-y-3">
                    <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-600 dark:text-slate-300">Head to Head</h3>
                    <div class="grid gap-3 md:grid-cols-3">
                        <article class="h2h-card rounded-xl border border-gray-200 bg-white p-3 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-xs text-gray-500 dark:text-gray-300">Victorias {{ displayMatch.homeTeam }}</p>
                            <p class="text-2xl font-black text-gray-900 dark:text-gray-100">{{ h2hSummary.homeWins }}</p>
                        </article>
                        <article class="h2h-card rounded-xl border border-gray-200 bg-white p-3 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-xs text-gray-500 dark:text-gray-300">Empates</p>
                            <p class="text-2xl font-black text-gray-900 dark:text-gray-100">{{ h2hSummary.draws }}</p>
                        </article>
                        <article class="h2h-card rounded-xl border border-gray-200 bg-white p-3 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-xs text-gray-500 dark:text-gray-300">Victorias {{ displayMatch.awayTeam }}</p>
                            <p class="text-2xl font-black text-gray-900 dark:text-gray-100">{{ h2hSummary.awayWins }}</p>
                        </article>
                    </div>
                    <div v-if="h2hRowsVisible.length" class="space-y-2">
                        <article v-for="item in h2hRowsVisible" :key="item.fixture?.id" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-xs text-gray-500 dark:text-gray-300">{{ item.league?.name }} - {{ item.fixture?.date?.slice(0, 10) || 'Fecha' }}</p>
                            <p class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ item.teams?.home?.name || 'Local' }} {{ item.goals?.home ?? 0 }} - {{ item.goals?.away ?? 0 }} {{ item.teams?.away?.name || 'Visitante' }}
                            </p>
                        </article>
                    </div>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-300">Sin datos de head to head para este cruce.</p>
                </section>

                <section class="space-y-3">
                    <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-600 dark:text-slate-300">Línea de Tiempo</h3>
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900/75">
                        <MatchEventTimeline
                            :events="feed.events"
                            :home-team-id="currentHomeApiId"
                            :home-team="displayMatch.homeTeam"
                            :away-team="displayMatch.awayTeam"
                            :home-code="displayMatch.homeCode"
                            :away-code="displayMatch.awayCode"
                            :home-color="teamColors.homeColor"
                            :away-color="teamColors.awayColor"
                        />
                    </article>
                </section>

                <!-- ── Formación + Estadísticas lado a lado ── -->
                <section class="grid grid-cols-1 items-stretch gap-6 lg:grid-cols-2">
                    <div class="flex h-full flex-col gap-3">
                        <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-600 dark:text-slate-300">Alineaciones</h3>
                        <MatchFormationField
                            :lineups="feed.lineups"
                            :home-team-id="currentHomeApiId"
                            :away-team-id="currentAwayApiId"
                            :home-color="teamColors.homeColor"
                            :away-color="teamColors.awayColor"
                            :home-team="displayMatch.homeTeam"
                            :away-team="displayMatch.awayTeam"
                            class="flex-1"
                        />
                    </div>

                    <div class="flex h-full flex-col gap-3">
                        <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-600 dark:text-slate-300">Estadísticas</h3>
                        <article class="flex-1 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900/75">
                            <div v-if="statisticsRows.length" class="space-y-3">
                                <div v-for="row in statisticsRows" :key="row.type">
                                    <div class="mb-1 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                        <span>{{ row.homeValue ?? '-' }}</span>
                                        <span class="font-semibold uppercase tracking-[0.12em]">{{ row.type }}</span>
                                        <span>{{ row.awayValue ?? '-' }}</span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                        <div class="flex h-full w-full">
                                            <div
                                                class="h-full"
                                                :style="{
                                                    width: `${statPercent(row.homeValue, row.awayValue)}%`,
                                                    backgroundColor: teamColors.homeColor,
                                                }"
                                            />
                                            <div
                                                class="h-full"
                                                :style="{
                                                    width: `${100 - statPercent(row.homeValue, row.awayValue)}%`,
                                                    backgroundColor: teamColors.awayColor,
                                                }"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-sm text-slate-500 dark:text-slate-400">Sin estadísticas disponibles por ahora.</p>
                        </article>
                    </div>
                </section>

                <!-- Selecciones -->
                <section class="space-y-3 pt-1">
                    <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-600 dark:text-slate-300">Selecciones</h3>
                    <div v-if="playerTeamCards.length" class="grid gap-4 md:grid-cols-2">
                        <article
                            v-for="teamPack in playerTeamCards"
                            :key="teamPack.teamId"
                            class="selection-card rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-base leading-none">{{ flagFromCode(teamPack.teamCode) }}</span>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-300">{{ teamPack.teamName }}</p>
                            </div>
                            <div class="mt-2 h-px w-full bg-gray-200 dark:bg-gray-700" />
                            <ul class="mt-2 space-y-1 text-sm text-gray-700 dark:text-gray-200">
                                <li v-for="player in teamPack.players" :key="player.id" class="flex items-center justify-between gap-3">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <span class="inline-flex w-8 shrink-0 justify-end text-xs font-semibold text-gray-500 dark:text-gray-400">#{{ player.number }}</span>
                                        <span class="truncate">{{ player.name }}</span>
                                    </span>
                                    <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ player.position }} · {{ player.rating }}</span>
                                </li>
                            </ul>
                        </article>
                    </div>
                    <p v-else class="text-sm text-slate-500 dark:text-slate-400">Sin datos de selecciones disponibles por ahora.</p>
                </section>
            </section>
        </section>

        <section v-else class="-mt-8">
            <div class="rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                Juego no encontrado.
            </div>
        </section>
    </UserDashboardLayout>
</template>
