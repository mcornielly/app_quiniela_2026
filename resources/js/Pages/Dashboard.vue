<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import {
    ArrowTrendingUpIcon,
    CalendarDaysIcon,
    ClockIcon,
    FireIcon,
    PlayCircleIcon,
    SparklesIcon,
    TrophyIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline'
import FavoriteTeamModal from '@/Components/User/FavoriteTeamModal.vue'
import SectionCard from '@/Components/User/SectionCard.vue'
import StatCard from '@/Components/User/StatCard.vue'
import { launchThemeChangeConfetti } from '@/Utils/confetti'
import { imageUrl } from '@/Utils/image'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'


const props = defineProps({
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
})

const page = usePage()
const userName = computed(() => {
    const fullName = String(page.props.auth?.user?.name ?? '').trim()
    if (!fullName) {
        return 'Jugador'
    }

    return fullName.split(/\s+/).slice(0, 2).join(' ')
})
const userPoolEntriesCount = computed(() => Number(page.props.auth?.user?.pool_entries_count ?? 0))
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

const worldCupKickoff = new Date('2026-06-11T19:00:00-04:00')
const countdown = ref({
    days: '00',
    hours: '00',
    minutes: '00',
    seconds: '00',
})

let timerId = null

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

const submitFavoriteTeam = (teamId) => {
    if (isApplyingFavoriteTeam.value) {
        return
    }

    if (teamId === currentFavoriteTeam.value?.id || (teamId === null && !currentFavoriteTeam.value)) {
        favoriteTeamModalOpen.value = false
        return
    }

    isApplyingFavoriteTeam.value = true

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
            window.setTimeout(() => {
                isApplyingFavoriteTeam.value = false
            }, 550)
        },
    })
}

const quickStats = computed(() => [
    {
        title: 'Mis quinielas',
        value: String(userPoolEntriesCount.value),
        helper: 'Activa',
        tone: 'primary',
        description: 'Tu espacio ya esta listo para registrar y seguir tus predicciones.',
    },
    {
        title: 'Partidos del torneo',
        value: '104',
        helper: 'Calendario completo',
        tone: 'slate',
        description: 'Incluye fase de grupos, ronda de 32 y toda la eliminatoria.',
    },
    {
        title: 'Selecciones',
        value: '48',
        helper: 'Nueva era FIFA',
        tone: 'success',
        description: 'Un Mundial mas amplio, con mas grupos y mas cruces por seguir.',
    },
    {
        title: 'Ranking de quiniela',
        value: 'Top 25',
        helper: 'Referencia inicial',
        tone: 'warning',
        description: 'Cuando publiques mas quinielas aqui veras tu posicion real dentro del juego.',
    },
])

const nextMatches = [
    {
        date: '11 jun. 2026 - 19:00',
        match: 'Mexico vs Sudafrica',
        venue: 'Estadio Azteca, Ciudad de Mexico',
        stage: 'Apertura del torneo',
    },
    {
        date: '12 jun. 2026 - 16:00',
        match: 'Canada vs ITA/NIR/WAL/BIH',
        venue: 'Toronto Stadium',
        stage: 'Grupo B',
    },
    {
        date: '12 jun. 2026 - 21:00',
        match: 'Estados Unidos vs TUR/ROU/SVK/KAZ',
        venue: 'SoFi Stadium, Los Angeles',
        stage: 'Grupo D',
    },
]

const rankingPreview = computed(() => [
    { name: 'Andres F.', points: 182, status: 'Lider actual' },
    { name: 'Camila R.', points: 176, status: 'Persiguiendo el podio' },
    { name: userName.value, points: 0, status: 'Tu punto de partida' },
])

const pulseItems = [
    'El Mundial arranca con 12 grupos y una ronda de 32 historica.',
    'Tu proxima quiniela puede aprovechar la matriz oficial de mejores terceros.',
    'La nueva vista unifica resultados, calendario y ranking en un solo flujo.',
]

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

onMounted(() => {
    updateCountdown()
    timerId = window.setInterval(updateCountdown, 1000)
})

onBeforeUnmount(() => {
    if (timerId) {
        window.clearInterval(timerId)
    }
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
            <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
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
                                Mis quinielas ({{ userPoolEntriesCount }})
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
            <StatCard
                v-for="stat in quickStats"
                :key="stat.title"
                :title="stat.title"
                :value="stat.value"
                :helper="stat.helper"
                :tone="stat.tone"
            >
                {{ stat.description }}
            </StatCard>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[1.55fr_1fr]">
            <div class="space-y-6">
                <SectionCard
                    title="Proximos partidos"
                    subtitle="Un vistazo rapido a los primeros cruces del torneo."
                >
                    <div class="space-y-4">
                        <div
                            v-for="match in nextMatches"
                            :key="`${match.date}-${match.match}`"
                            class="flex flex-col gap-4 rounded-2xl border border-slate-200/80 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-800/40"
                        >
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-primary-600 dark:text-primary-400">
                                    {{ match.stage }}
                                </p>
                                <h3 class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">
                                    {{ match.match }}
                                </h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    {{ match.venue }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm dark:bg-slate-900 dark:text-slate-200">
                                {{ match.date }}
                            </div>
                        </div>
                    </div>
                </SectionCard>
            </div>

            <div class="space-y-6">
                <SectionCard
                    title="Acciones rapidas"
                    subtitle="Atajos para moverte dentro de tu experiencia de quiniela."
                >
                    <div class="grid gap-3">
                        <Link
                            :href="route('predictions.worldcup')"
                            class="flex items-center justify-between rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-4 transition hover:border-primary-300 hover:bg-white dark:border-slate-800 dark:bg-slate-800/40 dark:hover:border-primary-500 dark:hover:bg-slate-800"
                        >
                            <div class="flex items-center gap-3">
                                <div class="rounded-2xl bg-primary-100 p-3 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300">
                                    <SparklesIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-950 dark:text-white">Crear una nueva quiniela</p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Registra otra prediccion del Mundial.</p>
                                </div>
                            </div>
                            <PlayCircleIcon class="h-5 w-5 text-slate-400" />
                        </Link>

                        <Link
                            :href="route('pools.index')"
                            class="flex items-center justify-between rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-4 transition hover:border-primary-300 hover:bg-white dark:border-slate-800 dark:bg-slate-800/40 dark:hover:border-primary-500 dark:hover:bg-slate-800"
                        >
                            <div class="flex items-center gap-3">
                                <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                    <TrophyIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-950 dark:text-white">Revisar mis quinielas</p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Consulta tus registros y su progreso.</p>
                                </div>
                            </div>
                            <PlayCircleIcon class="h-5 w-5 text-slate-400" />
                        </Link>

                        <Link
                            :href="route('leaderboard')"
                            class="flex items-center justify-between rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-4 transition hover:border-primary-300 hover:bg-white dark:border-slate-800 dark:bg-slate-800/40 dark:hover:border-primary-500 dark:hover:bg-slate-800"
                        >
                            <div class="flex items-center gap-3">
                                <div class="rounded-2xl bg-amber-100 p-3 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                    <ArrowTrendingUpIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-950 dark:text-white">Explorar ranking</p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Observa como se mueve la competencia.</p>
                                </div>
                            </div>
                            <PlayCircleIcon class="h-5 w-5 text-slate-400" />
                        </Link>
                    </div>
                </SectionCard>

                <SectionCard
                    title="Pulso de la quiniela"
                    subtitle="Mensajes cortos para mantener el foco del torneo."
                >
                    <div class="space-y-3">
                        <div
                            v-for="item in pulseItems"
                            :key="item"
                            class="rounded-2xl bg-slate-100 p-4 text-sm leading-6 text-slate-700 dark:bg-slate-800/70 dark:text-slate-200"
                        >
                            {{ item }}
                        </div>
                    </div>
                </SectionCard>

                <SectionCard
                    title="Vista rapida del ranking"
                    subtitle="Una referencia visual del tablero competitivo."
                >
                    <div class="space-y-3">
                        <div
                            v-for="entry in rankingPreview"
                            :key="entry.name"
                            class="flex items-center justify-between rounded-2xl border border-slate-200/80 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/40"
                        >
                            <div class="flex items-center gap-3">
                                <div class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white dark:bg-white dark:text-slate-900">
                                    {{ entry.points }}
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-950 dark:text-white">{{ entry.name }}</p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ entry.status }}</p>
                                </div>
                            </div>
                            <UserGroupIcon class="h-5 w-5 text-slate-400" />
                        </div>
                    </div>
                </SectionCard>
            </div>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-3">
            <SectionCard
                title="Cobertura del torneo"
                subtitle="Indicadores globales del Mundial 2026."
            >
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <CalendarDaysIcon class="h-5 w-5 text-primary-500" />
                            <span class="text-sm text-slate-600 dark:text-slate-300">Fase de grupos</span>
                        </div>
                        <span class="text-sm font-semibold text-slate-950 dark:text-white">72 partidos</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <ClockIcon class="h-5 w-5 text-primary-500" />
                            <span class="text-sm text-slate-600 dark:text-slate-300">Ronda de 32</span>
                        </div>
                        <span class="text-sm font-semibold text-slate-950 dark:text-white">16 partidos</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <FireIcon class="h-5 w-5 text-primary-500" />
                            <span class="text-sm text-slate-600 dark:text-slate-300">Eliminatoria final</span>
                        </div>
                        <span class="text-sm font-semibold text-slate-950 dark:text-white">16 partidos</span>
                    </div>
                </div>
            </SectionCard>
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
                class="fixed inset-0 z-[80] flex items-center justify-center bg-white/55 backdrop-blur-sm dark:bg-slate-950/55"
            >
                <div class="rounded-[1.75rem] border border-white/70 bg-white/90 px-8 py-7 text-center shadow-2xl shadow-slate-300/40 dark:border-slate-700 dark:bg-slate-900/92 dark:shadow-none">
                    <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-primary-200 border-t-primary-600 dark:border-primary-500/20 dark:border-t-primary-300" />
                    <p class="mt-4 text-base font-semibold text-slate-950 dark:text-white">
                        Aplicando tu identidad visual
                    </p>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Ajustando el banner para reflejar tu seleccion favorita.
                    </p>
                </div>
            </div>
        </Transition>
    </UserDashboardLayout>
</template>
