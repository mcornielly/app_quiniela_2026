<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import {
    CalendarDaysIcon,
    CheckCircleIcon,
    ClockIcon,
    FireIcon,
} from '@heroicons/vue/24/outline'
import AdminTableSection from '@/Components/Admin/Dashboard/AdminTableSection.vue'
import AppDatePicker from '@/Components/UI/AppDatePicker.vue'
import AppTooltip from '@/Components/UI/AppTooltip.vue'
import DashboardMetricCard from '@/Components/User/DashboardMetricCard.vue'
import FavoriteTeamModal from '@/Components/User/FavoriteTeamModal.vue'
import SectionCard from '@/Components/User/SectionCard.vue'
import { launchThemeChangeConfetti } from '@/Utils/confetti'
import { imageUrl } from '@/Utils/image'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'


const props = defineProps({
    todayLabel: {
        type: String,
        default: '',
    },
    todayIso: {
        type: String,
        default: '',
    },
    tournament: {
        type: Object,
        default: null,
    },
    favoriteTeams: {
        type: Array,
        default: () => [],
    },
    favoriteTeamCard: {
        type: Object,
        default: null,
    },
    favoriteTeamTheme: {
        type: Object,
        default: null,
    },
    dashboardMetrics: {
        type: Object,
        default: () => ({}),
    },
    upcomingMatches: {
        type: Array,
        default: () => [],
    },
    resultMatches: {
        type: Array,
        default: () => [],
    },
    featuredResults: {
        type: Array,
        default: () => [],
    },
    upcomingGames: {
        type: Array,
        default: () => [],
    },
    topPredictionsRanking: {
        type: Array,
        default: () => [],
    },
    tournamentCoverage: {
        type: Object,
        default: () => ({
            overallProgress: 0,
            rows: [],
            hasGames: false,
        }),
    },
})

const page = usePage()
const userName = computed(() => {
    const fullName = String(page.props.auth?.user?.name ?? '').trim()
    if (!fullName) {
        return 'Jugador'
    }

    return fullName.split(/\s+/).slice(0, 2).join(' ')
})
const currentUserId = computed(() => Number(page.props.auth?.user?.id ?? 0))
const userPoolEntriesCount = computed(() => Number(page.props.auth?.user?.pool_entries_count ?? 0))
const userTournamentPoolEntriesCount = computed(() => Number(
    props.dashboardMetrics?.userPoolEntriesCount ?? userPoolEntriesCount.value ?? 0,
))
const userTotalPoints = computed(() => Number(props.dashboardMetrics?.userTotalPoints ?? 0))
const tournamentLogo = computed(() => imageUrl(props.tournament?.logo))
const tournamentTitle = computed(() => props.tournament?.name ?? 'World Cup 2026')
const currentFavoriteTeam = computed(() => page.props.auth?.user?.favorite_team ?? null)
const favoriteTeamTheme = computed(() => props.favoriteTeamTheme ?? page.props.auth?.user?.favorite_team_theme ?? null)
const favoriteTeamShield = computed(() => imageUrl(currentFavoriteTeam.value?.shield_path))
const favoriteTeamFlag = computed(() => imageUrl(currentFavoriteTeam.value?.flag_path))
const defaultIdentityShield = '/logo_world_cup_2026.png'
const identityName = computed(() => currentFavoriteTeam.value?.name ?? 'FIFA')
const identityTitle = computed(() => currentFavoriteTeam.value?.name ?? tournamentTitle.value)
const identityShield = computed(() => favoriteTeamShield.value || favoriteTeamFlag.value || tournamentLogo.value || defaultIdentityShield)
const favoriteTeamModalOpen = ref(false)
const isApplyingFavoriteTeam = ref(false)
const isDarkTheme = ref(false)
const APPLYING_THEME_MIN_VISIBLE_MS = 1500

const worldCupKickoff = new Date('2026-06-11T19:00:00-04:00')
const countdown = ref({
    days: '00',
    hours: '00',
    minutes: '00',
    seconds: '00',
})

let timerId = null
let realtimeRefreshTimer = null
let liveUpdatesChannel = null
let themeClassObserver = null
const selectedDate = ref('')

const syncThemeMode = () => {
    isDarkTheme.value = document?.documentElement?.classList?.contains('dark') ?? false
}

const applyingThemeOverlayBackdropClass = computed(() => (
    isDarkTheme.value
        ? 'bg-slate-950/70'
        : 'bg-white/62'
))
const applyingThemeOverlayCardClass = computed(() => (
    isDarkTheme.value
        ? 'border-slate-700/80 bg-slate-900/95 shadow-none'
        : 'border-white/80 bg-white/95 shadow-2xl shadow-slate-300/40'
))
const applyingThemeOverlaySpinnerClass = computed(() => (
    isDarkTheme.value
        ? 'border-primary-500/25 border-t-primary-300'
        : 'border-primary-200 border-t-primary-600'
))
const applyingThemeOverlayTitleClass = computed(() => (
    isDarkTheme.value
        ? 'text-white'
        : 'text-slate-950'
))
const applyingThemeOverlayBodyClass = computed(() => (
    isDarkTheme.value
        ? 'text-slate-300'
        : 'text-slate-600'
))

const tickerThemes = {
    neutral: {
        label: 'Neutral',
        tickerClass: 'border-t border-slate-300/70 bg-[linear-gradient(to_right,_#cfd6df_0%,_#e4e8ee_45%,_#f4f6f9_100%)] text-slate-900',
        surfaceClass: 'rounded-[1.5rem] bg-white/38 px-3 py-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.42)] ring-1 ring-white/34 backdrop-blur-md',
        iconClass: 'bg-white/80 text-slate-600 ring-1 ring-slate-200/80',
        eyebrowClass: 'text-slate-700',
        bodyClass: 'text-slate-700',
        counterClass: 'rounded-2xl bg-[rgba(151,170,189,0.10)] px-3 py-2 text-center ring-1 ring-slate-200/60 shadow-[inset_0_1px_0_rgba(255,255,255,0.10)]',
        counterValueClass: 'text-slate-900',
        counterLabelClass: 'text-slate-600',
    },
}

const activeTickerTheme = computed(() => ({
    ...tickerThemes.neutral,
    ...(favoriteTeamTheme.value ?? {}),
}))
const activeCounterClass = computed(() => activeTickerTheme.value?.counterClass ?? tickerThemes.neutral.counterClass)
const activeCounterValueClass = computed(() => activeTickerTheme.value?.counterValueClass ?? tickerThemes.neutral.counterValueClass)
const activeCounterLabelClass = computed(() => activeTickerTheme.value?.counterLabelClass ?? tickerThemes.neutral.counterLabelClass)
const neutralTeamsTextClass = computed(() => activeTickerTheme.value?.neutralTeamsTextClass ?? 'text-slate-500 dark:text-slate-400')
const activeRightPanelClass = computed(() => activeTickerTheme.value?.rightPanelClass ?? '')
const activeShieldImageClass = computed(() => activeTickerTheme.value?.shieldImageClass ?? '')
const activeShieldImageBaseClass = computed(() => activeTickerTheme.value?.shieldImageBaseClass ?? 'sm:object-cover sm:p-0')
const identityNameClass = computed(() => activeTickerTheme.value?.teamNameClass ?? 'text-slate-400 dark:text-[#39C4E0]')
const favoriteTeamStatsValueClass = computed(() => activeTickerTheme.value?.statsValueClass ?? 'text-[#39C4E0] dark:text-[#39C4E0]')
const favoriteTeamGroupValueClass = computed(() => activeTickerTheme.value?.statsValueClass ?? 'text-[#39C4E0] dark:text-[#39C4E0]')
const themedSecondaryButtonClass = computed(() => activeTickerTheme.value?.buttonSecondaryClass
    ?? 'border-cyan-400 bg-slate-100 text-slate-700 hover:text-white hover:bg-cyan-300 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:focus:ring-slate-700')
const themedPrimaryButtonClass = computed(() => activeTickerTheme.value?.buttonPrimaryClass
    ?? 'bg-cyan-400 text-slate-950 hover:bg-cyan-300 hover:text-white focus:ring-cyan-200 dark:bg-cyan-400 dark:hover:bg-cyan-300 dark:focus:ring-cyan-900')
const favoriteTeamButtonLabel = computed(() => currentFavoriteTeam.value ? 'Cambiar equipo' : 'Elegir equipo')
const favoriteTeamCard = computed(() => props.favoriteTeamCard ?? null)
const favoriteTeamGroupLabel = computed(() => favoriteTeamCard.value?.group_name ?? null)
const favoriteTeamPositionLabel = computed(() => favoriteTeamCard.value?.position ?? null)
const favoriteTeamMetaLine = computed(() => {
    if (favoriteTeamGroupLabel.value && favoriteTeamPositionLabel.value) {
        return `Grupo ${favoriteTeamGroupLabel.value} #${favoriteTeamPositionLabel.value}`
    }

    return 'FIFA'
})
const showFavoritePositionLine = computed(() => Boolean(currentFavoriteTeam.value))
const favoriteTeamGroupDisplay = computed(() => favoriteTeamGroupLabel.value ?? '')
const favoriteTeamPositionDisplay = computed(() => favoriteTeamPositionLabel.value ? `#${favoriteTeamPositionLabel.value}` : '')
const favoriteTeamStats = computed(() => {
    const stats = favoriteTeamCard.value?.stats ?? {}

    return [
        { label: 'PTS', value: stats.points ?? 0 },
        { label: 'PJ', value: stats.played ?? 0 },
        { label: 'G', value: stats.won ?? 0 },
        { label: 'E', value: stats.drawn ?? 0 },
        { label: 'P', value: stats.lost ?? 0 },
        { label: 'GF', value: stats.gf ?? 0 },
        { label: 'GC', value: stats.ga ?? 0 },
    ]
})

const emitFavoriteThemeApplyingState = (applying) => {
    if (typeof window === 'undefined') {
        return
    }

    window.dispatchEvent(new CustomEvent('favorite-team:applying', {
        detail: { applying: Boolean(applying) },
    }))
}

const submitFavoriteTeam = (teamId) => {
    if (isApplyingFavoriteTeam.value) {
        return
    }

    if (teamId === currentFavoriteTeam.value?.id || (teamId === null && !currentFavoriteTeam.value)) {
        favoriteTeamModalOpen.value = false
        return
    }

    isApplyingFavoriteTeam.value = true
    emitFavoriteThemeApplyingState(true)
    const applyingStartedAt = Date.now()

    router.patch(route('dashboard.favorite-team.update'), {
        favorite_team_id: teamId,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            favoriteTeamModalOpen.value = false
            window.setTimeout(() => {
                launchThemeChangeConfetti()
            }, 520)
        },
        onFinish: () => {
            const elapsed = Date.now() - applyingStartedAt
            const remaining = Math.max(APPLYING_THEME_MIN_VISIBLE_MS - elapsed, 0)

            window.setTimeout(() => {
                isApplyingFavoriteTeam.value = false
                emitFavoriteThemeApplyingState(false)
            }, remaining)
        },
    })
}

const metricIcons = {
    trophy: {
        viewBox: '0 0 512 512',
        path: 'M144.3 0l224 0c26.5 0 48.1 21.8 47.1 48.2-.2 5.3-.4 10.6-.7 15.8l49.6 0c26.1 0 49.1 21.6 47.1 49.8-7.5 103.7-60.5 160.7-118 190.5-15.8 8.2-31.9 14.3-47.2 18.8-20.2 28.6-41.2 43.7-57.9 51.8l0 73.1 64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-192 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l64 0 0-73.1c-16-7.7-35.9-22-55.3-48.3-18.4-4.8-38.4-12.1-57.9-23.1-54.1-30.3-102.9-87.4-109.9-189.9-1.9-28.1 21-49.7 47.1-49.7l49.6 0c-.3-5.2-.5-10.4-.7-15.8-1-26.5 20.6-48.2 47.1-48.2zM101.5 112l-52.4 0c6.2 84.7 45.1 127.1 85.2 149.6-14.4-37.3-26.3-86-32.8-149.6zM380 256.8c40.5-23.8 77.1-66.1 83.3-144.8L411 112c-6.2 60.9-17.4 108.2-31 144.8z',
    },
    transmission: {
        viewBox: '0 0 576 512',
        path: 'M87.9 11.5c-11.3-6.9-26.1-3.2-33 8.1-24.8 41-39 89.1-39 140.4s14.2 99.4 39 140.4c6.9 11.3 21.6 15 33 8.1s15-21.6 8.1-33C75.7 241.9 64 202.3 64 160S75.7 78.1 96.1 44.4c6.9-11.3 3.2-26.1-8.1-33zm400.1 0c-11.3 6.9-15 21.6-8.1 33 20.4 33.7 32.1 73.3 32.1 115.6s-11.7 81.9-32.1 115.6c-6.9 11.3-3.2 26.1 8.1 33s26.1 3.2 33-8.1c24.8-41 39-89.1 39-140.4S545.8 60.6 521 19.6c-6.9-11.3-21.6-15-33-8.1zM320 215.4c19.1-11.1 32-31.7 32-55.4 0-35.3-28.7-64-64-64s-64 28.7-64 64c0 23.7 12.9 44.4 32 55.4L256 480c0 17.7 14.3 32 32 32s32-14.3 32-32l0-264.6zM180.2 91c7.2-11.2 3.9-26-7.2-33.2s-26-3.9-33.2 7.2c-17.6 27.4-27.8 60-27.8 95s10.2 67.6 27.8 95c7.2 11.2 22 14.4 33.2 7.2s14.4-22 7.2-33.2c-12.8-19.9-20.2-43.6-20.2-69s7.4-49.1 20.2-69zM436.2 65c-7.2-11.2-22-14.4-33.2-7.2s-14.4 22-7.2 33.2c12.8 19.9 20.2 43.6 20.2 69s-7.4 49.1-20.2 69c-7.2 11.2-3.9 26 7.2 33.2s26 3.9 33.2-7.2c17.6-27.4 27.8-60 27.8-95s-10.2-67.6-27.8-95z',
    },
    calendar: {
        viewBox: '0 0 448 512',
        path: 'M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 32 0c35.3 0 64 28.7 64 64l0 288c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 128C0 92.7 28.7 64 64 64l32 0 0-32c0-17.7 14.3-32 32-32zM64 240l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 368l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z',
    },
    chartline: {
        viewBox: '0 0 512 512',
        path: 'M64 64c0-17.7-14.3-32-32-32S0 46.3 0 64L0 400c0 44.2 35.8 80 80 80l400 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L80 416c-8.8 0-16-7.2-16-16L64 64zm406.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L320 210.7 262.6 153.4c-12.5-12.5-32.8-12.5-45.3 0l-96 96c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l73.4-73.4 57.4 57.4c12.5 12.5 32.8 12.5 45.3 0l128-128z',
    },
}

const liveGamesCount = computed(() => Number(props.dashboardMetrics?.liveGamesCount ?? 0))

const quickStats = computed(() => [
    {
        title: 'Juego directo',
        value: String(liveGamesCount.value),
        badge: 'En directo',
        tone: 'primary',
        valueTone: liveGamesCount.value > 0 ? 'rose' : 'roseMuted',
        badgeVariant: liveGamesCount.value > 0 ? 'live' : 'clockRose',
        iconTone: 'rose',
        icon: metricIcons.transmission,
        signal: liveGamesCount.value > 0,
        description: 'Sigue la Copa en Directo',
        href: route('live.index'),
    },
    {
        title: 'Calendario',
        value: String(activeCoverageRow.value?.total ?? 0),
        badge: hasCoverageGames.value
            ? (activeCoverageRow.value?.label ?? 'Torneo en curso')
            : 'Torneo no iniciado',
        tone: 'sky',
        valueTone: Number(activeCoverageRow.value?.total ?? 0) > 0 ? 'sky' : 'skyMuted',
        badgeVariant: hasCoverageGames.value ? 'progressSky' : 'clockSky',
        icon: metricIcons.calendar,
        description: 'Atento a tus fechas',
        href: route('calendar.index'),
    },
    {
        title: 'Mis quinielas',
        value: String(userTournamentPoolEntriesCount.value > 0 ? userTournamentPoolEntriesCount.value : 0),
        badge: userTournamentPoolEntriesCount.value > 0 ? 'Activas' : 'Torneo no iniciado',
        tone: 'amber',
        valueTone: userTournamentPoolEntriesCount.value > 0 ? 'amber' : 'amberMuted',
        badgeVariant: userTournamentPoolEntriesCount.value > 0 ? 'flameAmber' : 'clockAmber',
        icon: metricIcons.trophy,
        description: 'Crea tus quinielas',
        href: route('pools.index'),
    },
    {
        title: 'Puntos',
        value: String(userTotalPoints.value),
        badge: 'Rendimiento',
        tone: 'emerald',
        valueTone: userTotalPoints.value > 0 ? 'emerald' : 'emeraldMuted',
        badgeVariant: userTotalPoints.value > 0 ? 'progress' : 'clock',
        icon: metricIcons.chartline,
        description: 'Total Puntos Acumulados',
        href: route('leaderboard'),
    },
])

const liveMatches = computed(() => props.upcomingMatches.filter((match) => match.status === 'LIVE'))
const filteredResultMatches = computed(() => {
    if (!selectedDate.value) {
        return props.resultMatches
    }

    return props.resultMatches.filter((match) => match.matchDateIso === selectedDate.value)
})
const displayedResults = computed(() => {
    if (filteredResultMatches.value.length) {
        return filteredResultMatches.value.slice(0, 4)
    }

    return props.featuredResults.slice(0, 4)
})
const selectedDateLabel = computed(() => {
    if (!selectedDate.value) {
        return '-'
    }

    return new Date(`${selectedDate.value}T00:00:00`).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    })
})
const rankingUpdatedAt = computed(() => props.topPredictionsRanking?.[0]?.updatedAt ?? '-')
const coverageRows = computed(() => props.tournamentCoverage?.rows ?? [])
const overallCoverageProgress = computed(() => Number(props.tournamentCoverage?.overallProgress ?? 0))
const hasCoverageGames = computed(() => Boolean(props.tournamentCoverage?.hasGames))
const activeCoverageKey = computed(() => {
    const rows = coverageRows.value
    const inProgress = rows.find((row) => row.total > 0 && !row.completed && row.finished > 0)
    if (inProgress) {
        return inProgress.key
    }

    return rows.find((row) => row.total > 0 && !row.completed)?.key ?? null
})
const activeCoverageRow = computed(() => coverageRows.value.find((row) => row.key === activeCoverageKey.value) ?? null)
const formatMatchTime = (value) => value ? String(value).slice(0, 5) : '--:--'
const teamDisplayName = (team, slot) => team?.name || slot || 'Por definir'
const teamDisplayCode = (team, slot) => team?.code || slot || 'TBD'
const resultTeamName = (team, slot) => team?.name || slot || 'Por definir'
const resultTeamCode = (team, slot) => team?.code || slot || 'TBD'
const resultTeamFlag = (team) => team?.flag_url || imageUrl(team?.flag_path)
const toScoreNumber = (value) => {
    const numericValue = Number(value)
    return Number.isFinite(numericValue) ? numericValue : null
}
const isDrawResult = (match) => {
    const home = toScoreNumber(match.homeScore)
    const away = toScoreNumber(match.awayScore)
    return home !== null && away !== null && home === away
}
const isHomeWinner = (match) => {
    const home = toScoreNumber(match.homeScore)
    const away = toScoreNumber(match.awayScore)
    return home !== null && away !== null && home > away
}
const isAwayWinner = (match) => {
    const home = toScoreNumber(match.homeScore)
    const away = toScoreNumber(match.awayScore)
    return home !== null && away !== null && away > home
}
const resultRowStatusClass = (match) => {
    if (match.status === 'FT') {
        return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
    }

    if (match.status === 'LIVE') {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
    }

    return 'bg-slate-200 text-slate-700 dark:bg-slate-700/70 dark:text-slate-200'
}
const resultStatusLabel = (match) => {
    if (match.status === 'FT') {
        return 'Finalizado'
    }

    if (match.status === 'LIVE') {
        return 'En juego'
    }

    return 'Programado'
}
const upcomingTitleIcon = {
    viewBox: '0 0 512 512',
    path: 'M464 256a208 208 0 1 1 -416 0 208 208 0 1 1 416 0zM0 256a256 256 0 1 0 512 0 256 256 0 1 0 -512 0zM232 120l0 136c0 8 4 15.5 10.7 20l96 64c11 7.4 25.9 4.4 33.3-6.7s4.4-25.9-6.7-33.3L280 243.2 280 120c0-13.3-10.7-24-24-24s-24 10.7-24 24z',
}

const updateCountdown = () => {
    const difference = worldCupKickoff.getTime() - Date.now()

    if (difference <= 0) {
        countdown.value = { days: '00', hours: '00', minutes: '00', seconds: '00' }
        return
    }

    const totalSeconds = Math.floor(difference / 1000)
    const days = Math.floor(totalSeconds / 86400)
    const hours = Math.floor((totalSeconds % 86400) / 3600)
    const minutes = Math.floor((totalSeconds % 3600) / 60)
    const seconds = totalSeconds % 60

    countdown.value = {
        days: String(days).padStart(2, '0'),
        hours: String(hours).padStart(2, '0'),
        minutes: String(minutes).padStart(2, '0'),
        seconds: String(seconds).padStart(2, '0'),
    }
}

const onGlobalGameStatusUpdated = () => {
    scheduleRealtimeDashboardRefresh()
}

const scheduleRealtimeDashboardRefresh = () => {
    if (realtimeRefreshTimer) {
        window.clearTimeout(realtimeRefreshTimer)
    }

    realtimeRefreshTimer = window.setTimeout(() => {
        router.reload({
            only: [
                'dashboardMetrics',
                'upcomingMatches',
                'resultMatches',
                'featuredResults',
                'upcomingGames',
                'topPredictionsRanking',
                'tournamentCoverage',
                'favoriteTeamCard',
            ],
            preserveState: true,
            preserveScroll: true,
        })
    }, 500)
}
onMounted(() => {
    syncThemeMode()
    themeClassObserver = new MutationObserver(syncThemeMode)
    themeClassObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })

    updateCountdown()
    timerId = window.setInterval(updateCountdown, 1000)

    if (window.Echo) {
        liveUpdatesChannel = window.Echo.channel('matches.live')
        liveUpdatesChannel.listen('.game.status.updated', scheduleRealtimeDashboardRefresh)
    }

    window.addEventListener('dashboard:game-status-updated', onGlobalGameStatusUpdated)
})

onBeforeUnmount(() => {
    if (themeClassObserver) {
        themeClassObserver.disconnect()
        themeClassObserver = null
    }

    if (timerId) {
        window.clearInterval(timerId)
    }

    if (realtimeRefreshTimer) {
        window.clearTimeout(realtimeRefreshTimer)
    }

    if (liveUpdatesChannel) {
        liveUpdatesChannel.stopListening('.game.status.updated', scheduleRealtimeDashboardRefresh)
    }

    window.removeEventListener('dashboard:game-status-updated', onGlobalGameStatusUpdated)
})
</script>

<template>
    <Head title="Dashboard" />

    <UserDashboardLayout
        title="Tu zona de control"
        description="Un panel limpio para seguir el torneo, revisar tu quiniela y moverte rapido entre partidos, ranking y resultados."
        :ticker-class="[activeTickerTheme.tickerClass, activeTickerTheme.decorationClass || ''].join(' ')"
    >
        <template #ticker>
            <div class="h-12 w-full" aria-hidden="true" />
        </template>

        <template #headerContent>
            <div class="overflow-visible rounded-[2rem] border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-col lg:min-h-[12.75rem] lg:flex-row">
                    <div class="flex flex-col overflow-hidden border-b border-slate-200 lg:w-[24%] lg:border-b-0 lg:border-r dark:border-slate-700">
                        <div :class="activeTickerTheme.shieldContainerClass" class="h-44 sm:h-56 lg:h-auto lg:min-h-[8.25rem] lg:flex-1 overflow-hidden bg-slate-100 dark:bg-slate-950/40">
                            <img
                                :src="identityShield"
                                :alt="identityTitle"
                                :class="[activeShieldImageClass, activeShieldImageBaseClass]"
                                class="h-full w-full object-contain p-0 lg:p-2.5"
                            >
                        </div>

                        <div class="space-y-3 px-5 py-3">
                            <p v-if="showFavoritePositionLine" class="text-center text-xs font-semibold uppercase tracking-[0.38em] text-[#8FA8D8] dark:text-[#9FB5E8]">
                                ESTADISTICAS
                            </p>

                            <div class="border-t border-slate-200 pt-3 dark:border-slate-700">
                                <div v-if="showFavoritePositionLine" class="grid grid-cols-7 gap-2">
                                    <div v-for="item in favoriteTeamStats" :key="item.label" class="text-center">
                                        <p :class="favoriteTeamStatsValueClass" class="text-lg font-bold leading-none">
                                            {{ item.value }}
                                        </p>
                                        <p class="mt-1 text-[10px] font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                            {{ item.label }}
                                        </p>
                                    </div>
                                </div>
                                <p
                                    v-else
                                    :class="neutralTeamsTextClass"
                                    class="text-center text-xs font-semibold uppercase tracking-[0.28em]"
                                >
                                    USA | CANADA | MEXICO
                                </p>
                            </div>
                        </div>
                    </div>

                    <div :class="activeRightPanelClass" class="flex flex-1 flex-col justify-between px-6 py-3 lg:px-8 lg:py-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-[0.38em] text-[#8FA8D8] dark:text-[#9FB5E8]">
                                    Bienvenido
                                </p>
                                <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 dark:text-white md:text-4xl">
                                    {{ userName }}
                                </h1>
                                <div class="mt-2 space-y-1">
                                    <p class="text-xl font-bold tracking-[0.04em] text-slate-700 dark:text-slate-200">

                                        Team: <span :class="identityNameClass" class="text-[1.35rem] tracking-[0.38em] uppercase sm:text-inherit">{{ identityName.toUpperCase() }}</span>
                                    </p>
                                    <p v-if="showFavoritePositionLine" class="text-base font-bold text-slate-700 dark:text-slate-200">
                                        Pos: <span class="text-xl text-slate-400 dark:text-slate-200">{{ favoriteTeamPositionDisplay }}</span>
                                    </p>
                                </div>
                            </div>

                            <div v-if="showFavoritePositionLine" class="justify-self-end text-right">
                                <p class="text-xs font-semibold uppercase tracking-[0.38em] text-[#8FA8D8] dark:text-[#9FB5E8]">
                                    Grupo
                                </p>
                                <p :class="favoriteTeamGroupValueClass" class="mt-0.5 text-8xl font-black leading-none">
                                    {{ favoriteTeamGroupDisplay }}
                                </p>
                            </div>
                        </div>

                        <div class="-mx-6 mt-[31px] bg-violet-100/75 px-6 py-3 dark:bg-violet-500/15 lg:-mx-8 lg:px-8">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center overflow-hidden">
                                        <img
                                            v-if="tournamentLogo"
                                            :src="tournamentLogo"
                                            :alt="props.tournament?.name || 'World Cup logo'"
                                            class="h-[4.5rem] w-auto object-contain transform-gpu scale-110 translate-y-px"
                                        >
                                    </div>
                                    <div>
                                        <p :class="activeTickerTheme.eyebrowClass" class="text-[12px] font-bold uppercase tracking-[0.26em] dark:!text-white">
                                            Cuenta regresiva al Mundial 2026
                                        </p>
                                        <p :class="activeTickerTheme.bodyClass" class="text-sm dark:!text-slate-200">
                                            Cada segundo nos acerca al partido inaugural en Ciudad de Mexico.
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-4 gap-2 md:gap-3">
                                    <div class="px-1 text-center">
                                        <p :class="activeCounterValueClass" class="text-xl font-black tracking-tight">{{ countdown.days }}</p>
                                        <p :class="activeCounterLabelClass" class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] dark:!text-[#6F7FA5]">Dias</p>
                                    </div>
                                    <div class="px-1 text-center">
                                        <p :class="activeCounterValueClass" class="text-xl font-black tracking-tight">{{ countdown.hours }}</p>
                                        <p :class="activeCounterLabelClass" class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] dark:!text-[#6F7FA5]">Horas</p>
                                    </div>
                                    <div class="px-1 text-center">
                                        <p :class="activeCounterValueClass" class="text-xl font-black tracking-tight">{{ countdown.minutes }}</p>
                                        <p :class="activeCounterLabelClass" class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] dark:!text-[#6F7FA5]">Min</p>
                                    </div>
                                    <div class="px-1 text-center">
                                        <p :class="activeCounterValueClass" class="text-xl font-black tracking-tight">{{ countdown.seconds }}</p>
                                        <p :class="activeCounterLabelClass" class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] dark:!text-[#6F7FA5]">Seg</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-end justify-start gap-3 lg:justify-end">
                            <button
                                type="button"
                                :class="themedSecondaryButtonClass"
                                class="inline-flex min-w-[190px] flex-1 items-center justify-center rounded-xl border px-5 py-3 text-sm font-semibold transition focus:outline-none lg:flex-none"
                                @click="favoriteTeamModalOpen = true"
                            >
                                {{ favoriteTeamButtonLabel }}
                            </button>
                            <Link
                                :href="route('pools.index')"
                                :class="themedSecondaryButtonClass"
                                class="inline-flex min-w-[190px] flex-1 items-center justify-center rounded-xl border px-5 py-3 text-sm font-semibold transition focus:outline-none lg:flex-none"
                            >
                                Mis quinielas ({{ userTournamentPoolEntriesCount }})
                            </Link>
                            <Link
                                :href="route('predictions.worldcup')"
                                :class="themedPrimaryButtonClass"
                                class="inline-flex min-w-[190px] flex-1 items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold shadow-sm transition focus:outline-none lg:flex-none"
                            >
                                <svg class="me-2 h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                                </svg>
                                Crear quiniela
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Link
                v-for="stat in quickStats"
                :key="stat.title"
                :href="stat.href"
                class="block"
            >
                <DashboardMetricCard
                    :title="stat.title"
                    :value="stat.value"
                    :description="stat.description"
                    :badge="stat.badge"
                    :tone="stat.tone"
                    :value-tone="stat.valueTone"
                    :badge-variant="stat.badgeVariant"
                    :icon-tone="stat.iconTone"
                    :icon="stat.icon"
                    :signal="Boolean(stat.signal)"
                />
            </Link>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[1.55fr_1fr]">
            <div class="space-y-6">
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                    <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Resultados del dia</h2>
                            <span class="text-xs text-gray-500 dark:text-slate-400">Fecha seleccionada: {{ selectedDateLabel }}</span>
                        </div>

                        <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center md:w-auto md:justify-end">
                            <AppDatePicker v-model="selectedDate" placeholder="Seleccionar fecha" />
                        </div>
                    </div>

                    <div v-if="displayedResults.length" class="overflow-hidden border-y border-gray-200 dark:border-slate-800">
                        <div
                            v-for="match in displayedResults"
                            :key="match.id"
                            class="border-b border-gray-200 bg-white px-4 py-3 last:border-b-0 sm:px-5 dark:border-slate-800 dark:bg-slate-900/70"
                        >
                            <div class="mb-3 space-y-2 md:hidden">
                                <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-start gap-2">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                        {{ match.stage === 'group' ? `Grupo ${match.group_name || '-'}` : match.stage_label }}
                                    </p>
                                    <div class="flex min-w-0 items-center justify-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                                        <span class="whitespace-nowrap">{{ match.display_date }} <span v-if="match.display_time">- {{ match.display_time }}</span></span>
                                        <span class="inline-flex min-w-0 items-center gap-1 truncate">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="h-3 w-3 shrink-0 fill-current text-cyan-500 dark:text-cyan-400" aria-hidden="true">
                                                <path d="M0 188.6C0 84.4 86 0 192 0S384 84.4 384 188.6c0 119.3-120.2 262.3-170.4 316.8-11.8 12.8-31.5 12.8-43.3 0-50.2-54.5-170.4-197.5-170.4-316.8zM192 256a64 64 0 1 0 0-128 64 64 0 1 0 0 128z"/>
                                            </svg>
                                            <span class="max-w-[28vw] truncate transition-colors hover:text-cyan-500 dark:hover:text-cyan-400">{{ match.venue || 'Sede por confirmar' }}</span>
                                        </span>
                                    </div>
                                    <span
                                        :class="resultRowStatusClass(match)"
                                        class="inline-flex w-fit shrink-0 items-center justify-self-end gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    >
                                        <CheckCircleIcon v-if="match.status === 'FT'" class="h-3 w-3" />
                                        <ClockIcon v-else class="h-3 w-3" />
                                        {{ resultStatusLabel(match) }}
                                    </span>
                                </div>
                            </div>

                            <div class="mb-3 hidden md:grid md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] md:items-center md:gap-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                    {{ match.stage === 'group' ? `Grupo ${match.group_name || '-'}` : match.stage_label }}
                                </p>
                                <div class="flex min-w-0 items-center justify-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                    <span class="whitespace-nowrap">{{ match.display_date }} <span v-if="match.display_time">- {{ match.display_time }}</span></span>
                                    <span class="inline-flex min-w-0 items-center gap-1.5 truncate">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="h-3.5 w-3.5 shrink-0 fill-current text-cyan-500 dark:text-cyan-400" aria-hidden="true">
                                            <path d="M0 188.6C0 84.4 86 0 192 0S384 84.4 384 188.6c0 119.3-120.2 262.3-170.4 316.8-11.8 12.8-31.5 12.8-43.3 0-50.2-54.5-170.4-197.5-170.4-316.8zM192 256a64 64 0 1 0 0-128 64 64 0 1 0 0 128z"/>
                                        </svg>
                                        <span class="max-w-[42vw] truncate transition-colors hover:text-cyan-500 dark:hover:text-cyan-400 md:max-w-none">{{ match.venue || 'Sede por confirmar' }}</span>
                                    </span>
                                </div>
                                <span
                                    :class="resultRowStatusClass(match)"
                                    class="inline-flex w-fit shrink-0 items-center justify-self-end gap-1 rounded-full px-2 py-1 text-[11px] font-bold"
                                >
                                    <CheckCircleIcon v-if="match.status === 'FT'" class="h-3.5 w-3.5" />
                                    <ClockIcon v-else class="h-3.5 w-3.5" />
                                    {{ resultStatusLabel(match) }}
                                </span>
                            </div>

                            <div class="mx-auto flex w-full max-w-[720px] items-center justify-center gap-3">
                                <div class="flex w-auto min-w-0 items-center justify-end gap-2 md:w-[230px]">
                                    <span class="hidden min-w-0 truncate text-base font-semibold text-gray-900 dark:text-white md:inline">
                                        {{ resultTeamName(match.home_team, match.home_slot) }}
                                    </span>
                                    <AppTooltip :text="resultTeamName(match.home_team, match.home_slot)" placement="top" tooltip-class="max-w-none whitespace-nowrap">
                                        <img
                                            v-if="resultTeamFlag(match.home_team)"
                                            :src="resultTeamFlag(match.home_team)"
                                            :alt="resultTeamName(match.home_team, match.home_slot)"
                                            class="h-5 w-7 shrink-0 rounded object-cover"
                                        >
                                        <span v-else class="inline-flex h-5 min-w-7 items-center justify-center rounded bg-slate-300 px-1 text-[10px] font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                                            {{ resultTeamCode(match.home_team, match.home_slot) }}
                                        </span>
                                    </AppTooltip>
                                </div>

                                <div class="flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5 text-xl font-black dark:bg-slate-800">
                                    <span :class="(isHomeWinner(match) || isDrawResult(match)) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                        {{ match.homeScore ?? 0 }}
                                    </span>
                                    <span class="text-slate-400 dark:text-slate-500">-</span>
                                    <span :class="(isAwayWinner(match) || isDrawResult(match)) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                        {{ match.awayScore ?? 0 }}
                                    </span>
                                </div>

                                <div class="flex w-auto min-w-0 items-center justify-start gap-2 md:w-[230px]">
                                    <AppTooltip :text="resultTeamName(match.away_team, match.away_slot)" placement="top" tooltip-class="max-w-none whitespace-nowrap">
                                        <img
                                            v-if="resultTeamFlag(match.away_team)"
                                            :src="resultTeamFlag(match.away_team)"
                                            :alt="resultTeamName(match.away_team, match.away_slot)"
                                            class="h-5 w-7 shrink-0 rounded object-cover"
                                        >
                                        <span v-else class="inline-flex h-5 min-w-7 items-center justify-center rounded bg-slate-300 px-1 text-[10px] font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                                            {{ resultTeamCode(match.away_team, match.away_slot) }}
                                        </span>
                                    </AppTooltip>
                                    <span class="hidden min-w-0 truncate text-base font-semibold text-gray-900 dark:text-white md:inline">
                                        {{ resultTeamName(match.away_team, match.away_slot) }}
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div v-else class="rounded-lg border border-dashed border-gray-200 p-6 text-center dark:border-slate-800">
                        <p class="text-sm text-gray-500 dark:text-slate-400">No hay resultados finales para la fecha seleccionada.</p>
                    </div>

                    <div class="mt-4 flex items-center justify-end pt-3">
                        <Link
                            :href="route('results.index')"
                            class="inline-flex items-center whitespace-nowrap rounded-md px-2 py-1.5 text-xs font-bold uppercase tracking-wide text-primary-700 transition hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700"
                        >
                            Ver mas
                            <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <AdminTableSection
                    title="Proximos juegos"
                    :title-icon="upcomingTitleIcon"
                    variant="user-dashboard"
                >
                    <template #actions>
                        <Link
                            :href="route('calendar.index')"
                            class="inline-flex items-center whitespace-nowrap rounded-md px-2 py-1.5 text-xs font-bold uppercase tracking-wide text-primary-700 transition hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700"
                        >
                            Ver calendario
                            <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </template>

                    <div v-if="props.upcomingGames.length" class="overflow-hidden border-y border-gray-200 dark:border-slate-800">
                        <div
                            v-for="game in props.upcomingGames"
                            :key="game.id"
                            class="border-b border-gray-200 bg-white px-4 py-3 last:border-b-0 sm:px-5 dark:border-slate-800 dark:bg-slate-900/70"
                        >
                            <div class="mb-3 space-y-2 md:hidden">
                                <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-start gap-2">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                        {{ game.groupName || '-' }}
                                    </p>
                                    <div class="flex min-w-0 items-center justify-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                                        <span class="whitespace-nowrap">{{ game.date || '--/--/----' }} - {{ formatMatchTime(game.time) }}</span>
                                        <span class="inline-flex min-w-0 items-center gap-1 truncate">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="h-3 w-3 shrink-0 fill-current text-cyan-500 dark:text-cyan-400" aria-hidden="true">
                                                <path d="M0 188.6C0 84.4 86 0 192 0S384 84.4 384 188.6c0 119.3-120.2 262.3-170.4 316.8-11.8 12.8-31.5 12.8-43.3 0-50.2-54.5-170.4-197.5-170.4-316.8zM192 256a64 64 0 1 0 0-128 64 64 0 1 0 0 128z"/>
                                            </svg>
                                            <span class="max-w-[28vw] truncate transition-colors hover:text-cyan-500 dark:hover:text-cyan-400">{{ game.venue || 'Sede por confirmar' }}</span>
                                        </span>
                                    </div>
                                    <span class="inline-flex w-fit shrink-0 items-center justify-self-end gap-1 rounded-full bg-cyan-100 px-2 py-0.5 text-[10px] font-bold text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                                        <ClockIcon class="h-3 w-3" />
                                        Proximo
                                    </span>
                                </div>
                            </div>

                            <div class="mb-3 hidden md:grid md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] md:items-center md:gap-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                                    {{ game.groupName || '-' }}
                                </p>
                                <div class="flex min-w-0 items-center justify-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                    <span class="whitespace-nowrap">{{ game.date || '--/--/----' }} - {{ formatMatchTime(game.time) }}</span>
                                    <span class="inline-flex min-w-0 items-center gap-1.5 truncate">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="h-3.5 w-3.5 shrink-0 fill-current text-cyan-500 dark:text-cyan-400" aria-hidden="true">
                                            <path d="M0 188.6C0 84.4 86 0 192 0S384 84.4 384 188.6c0 119.3-120.2 262.3-170.4 316.8-11.8 12.8-31.5 12.8-43.3 0-50.2-54.5-170.4-197.5-170.4-316.8zM192 256a64 64 0 1 0 0-128 64 64 0 1 0 0 128z"/>
                                        </svg>
                                        <span class="max-w-[42vw] truncate transition-colors hover:text-cyan-500 dark:hover:text-cyan-400 md:max-w-none">{{ game.venue || 'Sede por confirmar' }}</span>
                                    </span>
                                </div>
                                <span class="inline-flex w-fit shrink-0 items-center justify-self-end gap-1 rounded-full bg-cyan-100 px-2 py-1 text-[11px] font-bold text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                                    <ClockIcon class="h-3.5 w-3.5" />
                                    Proximo
                                </span>
                            </div>

                            <div class="mx-auto flex w-full max-w-[720px] items-center justify-center gap-3">
                                <div class="flex w-auto min-w-0 items-center justify-end gap-2 md:w-[230px]">
                                    <span class="hidden min-w-0 truncate text-base font-semibold text-gray-900 dark:text-white md:inline">
                                        {{ teamDisplayName(game.homeTeam, game.homeSlot) }}
                                    </span>
                                    <AppTooltip :text="teamDisplayName(game.homeTeam, game.homeSlot)" placement="top" tooltip-class="max-w-none whitespace-nowrap">
                                        <img v-if="game.homeTeam?.flag_url" :src="game.homeTeam.flag_url" :alt="teamDisplayName(game.homeTeam, game.homeSlot)" class="h-5 w-7 shrink-0 rounded object-cover">
                                        <span v-else class="inline-flex h-5 min-w-7 items-center justify-center rounded bg-slate-300 px-1 text-[10px] font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                                            {{ teamDisplayCode(game.homeTeam, game.homeSlot) }}
                                        </span>
                                    </AppTooltip>
                                </div>

                                <div class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-black uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                    vs
                                </div>

                                <div class="flex w-auto min-w-0 items-center justify-start gap-2 md:w-[230px]">
                                    <AppTooltip :text="teamDisplayName(game.awayTeam, game.awaySlot)" placement="top" tooltip-class="max-w-none whitespace-nowrap">
                                        <img v-if="game.awayTeam?.flag_url" :src="game.awayTeam.flag_url" :alt="teamDisplayName(game.awayTeam, game.awaySlot)" class="h-5 w-7 shrink-0 rounded object-cover">
                                        <span v-else class="inline-flex h-5 min-w-7 items-center justify-center rounded bg-slate-300 px-1 text-[10px] font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                                            {{ teamDisplayCode(game.awayTeam, game.awaySlot) }}
                                        </span>
                                    </AppTooltip>
                                    <span class="hidden min-w-0 truncate text-base font-semibold text-gray-900 dark:text-white md:inline">
                                        {{ teamDisplayName(game.awayTeam, game.awaySlot) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-lg border border-dashed border-gray-200 p-6 text-center dark:border-slate-800">
                        <p class="text-sm text-gray-500 dark:text-slate-400">No hay juegos programados.</p>
                    </div>

                    <div class="min-h-[48px] border-t border-gray-200 dark:border-slate-800">
                    </div>
                </AdminTableSection>
            </div>

            <div class="space-y-6">
                <SectionCard
                    title="Cobertura del torneo"
                    subtitle="Indicadores globales del Mundial 2026."
                >
                    <div v-if="hasCoverageGames && coverageRows.length" class="space-y-4">
                        <div
                            v-for="row in coverageRows"
                            :key="row.key"
                            class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center sm:gap-3"
                        >
                            <div class="flex items-center gap-3">
                                <CalendarDaysIcon v-if="row.key === 'group'" class="h-5 w-5 text-primary-500" />
                                <ClockIcon v-else-if="row.key === 'round_32'" class="h-5 w-5 text-primary-500" />
                                <FireIcon v-else class="h-5 w-5 text-primary-500" />
                                <span
                                    class="text-sm"
                                    :class="row.key === 'group'
                                        ? 'font-semibold text-emerald-600 dark:text-emerald-400'
                                        : 'text-slate-600 dark:text-slate-300'"
                                >
                                    {{ row.label }}
                                </span>
                            </div>
                            <div class="min-w-[110px] flex justify-start sm:justify-center">
                                <span
                                    v-if="row.key === 'group' && !row.completed"
                                    class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="mr-1 h-3 w-3" fill="none" aria-hidden="true">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.122 17.645a7.185 7.185 0 0 1-2.656 2.495 7.06 7.06 0 0 1-3.52.853 6.617 6.617 0 0 1-3.306-.718 6.73 6.73 0 0 1-2.54-2.266c-2.672-4.57.287-8.846.887-9.668A4.448 4.448 0 0 0 8.07 6.31 4.49 4.49 0 0 0 7.997 4c1.284.965 6.43 3.258 5.525 10.631 1.496-1.136 2.7-3.046 2.846-6.216 1.43 1.061 3.985 5.462 1.754 9.23Z"/>
                                    </svg>
                                    En progreso {{ row.progress }}%
                                </span>
                                <span
                                    v-else-if="row.key === 'group' && row.completed"
                                    class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-700 dark:bg-red-900/30 dark:text-red-300"
                                >
                                    <CheckCircleIcon class="mr-1 h-3 w-3" />
                                    Finalizado {{ overallCoverageProgress }}%
                                </span>
                                <span
                                    v-else-if="row.key === activeCoverageKey && !row.completed"
                                    class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="mr-1 h-3 w-3" fill="none" aria-hidden="true">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.122 17.645a7.185 7.185 0 0 1-2.656 2.495 7.06 7.06 0 0 1-3.52.853 6.617 6.617 0 0 1-3.306-.718 6.73 6.73 0 0 1-2.54-2.266c-2.672-4.57.287-8.846.887-9.668A4.448 4.448 0 0 0 8.07 6.31 4.49 4.49 0 0 0 7.997 4c1.284.965 6.43 3.258 5.525 10.631 1.496-1.136 2.7-3.046 2.846-6.216 1.43 1.061 3.985 5.462 1.754 9.23Z"/>
                                    </svg>
                                    En progreso {{ row.progress }}%
                                </span>
                                <span
                                    v-else-if="row.completed"
                                    class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-700 dark:bg-red-900/30 dark:text-red-300"
                                >
                                    <CheckCircleIcon class="mr-1 h-3 w-3" />
                                    Finalizado
                                </span>
                            </div>
                            <span
                                class="text-sm font-semibold sm:text-right"
                                :class="row.key === 'group'
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-slate-950 dark:text-white'"
                            >
                                {{ row.total }} partidos
                            </span>
                        </div>

                        <div class="pt-1">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Avance del torneo</span>
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ overallCoverageProgress }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700/60">
                                <div
                                    class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                    :style="{ width: `${overallCoverageProgress}%` }"
                                />
                            </div>
                        </div>
                    </div>
                    <div v-else class="rounded-lg border border-dashed border-gray-200 p-5 text-sm text-gray-500 dark:border-slate-700 dark:text-slate-400">
                        No ha iniciado un torneo.
                    </div>
                </SectionCard>

                <AdminTableSection
                    title="Quiniela 2026 - Top 15"
                    :description="`Ranking por puntos - Actualizado: ${rankingUpdatedAt}`"
                    variant="user-dashboard"
                >
                    <template #actions>
                        <Link
                            :href="route('pools.index')"
                            class="inline-flex items-center whitespace-nowrap rounded-md px-2 py-1.5 text-xs font-bold uppercase tracking-wide text-primary-700 transition hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700"
                        >
                            Ver lista
                            <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </template>

                    <div v-if="props.topPredictionsRanking.length" class="overflow-hidden border-y border-gray-200 md:hidden dark:border-slate-800">
                        <div
                            v-for="entry in props.topPredictionsRanking"
                            :key="`ranking-mobile-${entry.poolEntryId}`"
                            class="border-b border-gray-200 bg-white px-4 py-3 last:border-b-0 dark:border-slate-800 dark:bg-slate-900/70"
                        >
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Pos #{{ entry.rank }}</span>
                                <span class="text-sm font-black text-slate-900 dark:text-white">{{ entry.totalPoints }} pts</span>
                            </div>
                            <p
                                class="truncate text-sm font-semibold"
                                :class="entry.userId === currentUserId
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-gray-900 dark:text-white'"
                            >
                                {{ entry.poolEntryName }}
                            </p>
                            <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                                <div class="rounded bg-emerald-50 px-2 py-1 font-semibold text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                                    EXA: {{ entry.exactHits }}
                                </div>
                                <div class="rounded bg-sky-50 px-2 py-1 font-semibold text-sky-700 dark:bg-sky-900/20 dark:text-sky-300">
                                    ACI: {{ entry.correctResults }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="rounded-lg border border-dashed border-gray-200 p-5 text-center text-sm text-gray-500 md:hidden dark:border-slate-700 dark:text-slate-400"
                    >
                        No hay entradas en el ranking.
                    </div>

                    <div class="hidden w-full overflow-x-auto overflow-y-visible md:block">
                        <table class="w-full min-w-[620px] text-left text-sm text-gray-600 dark:text-slate-300">
                            <thead class="border-b border-t border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-slate-800 dark:bg-slate-700/70 dark:text-slate-300">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Pos</th>
                                    <th class="px-6 py-3 font-medium">Quiniela</th>
                                    <th class="px-6 py-3 text-right font-medium text-emerald-600 dark:text-emerald-400">
                                        <AppTooltip text="Exactos" placement="bottom">
                                            <span class="inline-flex cursor-default">EXA</span>
                                        </AppTooltip>
                                    </th>
                                    <th class="px-6 py-3 text-right font-medium text-sky-600 dark:text-sky-400">
                                        <AppTooltip text="Aciertos" placement="bottom">
                                            <span class="inline-flex cursor-default">ACI</span>
                                        </AppTooltip>
                                    </th>
                                    <th class="px-6 py-3 text-right font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="entry in props.topPredictionsRanking" :key="entry.poolEntryId" class="border-b border-gray-200 bg-white dark:border-slate-800 dark:bg-slate-900/70">
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ entry.rank }}</td>
                                    <td
                                        class="px-6 py-4 text-sm font-medium"
                                        :class="entry.userId === currentUserId
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-gray-900 dark:text-white'"
                                    >
                                        {{ entry.poolEntryName }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-emerald-600 dark:text-emerald-400">{{ entry.exactHits }}</td>
                                    <td class="px-6 py-4 text-right font-semibold text-sky-600 dark:text-sky-400">{{ entry.correctResults }}</td>
                                    <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">{{ entry.totalPoints }}</td>
                                </tr>
                                <tr v-if="!props.topPredictionsRanking.length" class="bg-white dark:bg-slate-900/70">
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-slate-400">No hay entradas en el ranking.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="min-h-[48px] border-t border-gray-200 dark:border-slate-800">
                    </div>
                </AdminTableSection>

            </div>
        </section>

        <FavoriteTeamModal
            :show="favoriteTeamModalOpen"
            :teams="props.favoriteTeams"
            :current-team-id="currentFavoriteTeam?.id ?? null"
            :processing="isApplyingFavoriteTeam"
            @close="favoriteTeamModalOpen = false"
            @select="submitFavoriteTeam"
        />

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isApplyingFavoriteTeam"
                class="fixed inset-0 z-[80] flex items-center justify-center backdrop-blur-sm transition-colors duration-200"
                :class="applyingThemeOverlayBackdropClass"
            >
                <div
                    class="rounded-[1.75rem] border px-8 py-7 text-center transition-colors duration-200"
                    :class="applyingThemeOverlayCardClass"
                >
                    <div
                        class="mx-auto h-12 w-12 animate-spin rounded-full border-4 transition-colors duration-200"
                        :class="applyingThemeOverlaySpinnerClass"
                    />
                    <p class="mt-4 text-base font-semibold transition-colors duration-200" :class="applyingThemeOverlayTitleClass">
                        Aplicando tu identidad visual
                    </p>
                    <p class="mt-2 text-sm transition-colors duration-200" :class="applyingThemeOverlayBodyClass">
                        Ajustando el banner para reflejar tu seleccion favorita.
                    </p>
                </div>
            </div>
        </Transition>
    </UserDashboardLayout>
</template>
