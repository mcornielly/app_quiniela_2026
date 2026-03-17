<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
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
import SectionCard from '@/Components/User/SectionCard.vue'
import StatCard from '@/Components/User/StatCard.vue'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'

const page = usePage()
const userName = computed(() => page.props.auth?.user?.name?.split(' ')[0] ?? 'Jugador')

const worldCupKickoff = new Date('2026-06-11T19:00:00-04:00')
const countdown = ref({
    days: '00',
    hours: '00',
    minutes: '00',
    seconds: '00',
})

let timerId = null

const tickerThemes = {
    alemania: {
        label: 'Alemania',
        tickerClass: 'border-t border-slate-300/60 bg-[linear-gradient(to_bottom,_#3b3b3b_0%,_#3b3b3b_33%,_#c97a7a_33%,_#c97a7a_66%,_#f0d88a_66%,_#f0d88a_100%)] text-white',
        surfaceClass: 'rounded-[1.5rem] bg-slate-950/24 px-3 py-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.06)] ring-1 ring-white/10 backdrop-blur-md',
        iconClass: 'bg-white/16 text-white ring-1 ring-white/15',
        eyebrowClass: 'text-white/75',
        bodyClass: 'text-white',
        counterClass: 'rounded-2xl bg-black/18 px-3 py-2 text-center ring-1 ring-white/14 backdrop-blur',
        counterValueClass: 'text-white',
        counterLabelClass: 'text-white/72',
        chipClass: 'border-slate-400/70 bg-slate-900/65 text-white hover:bg-slate-900/80',
        activeChipClass: 'border-white/20 bg-white/16 text-white shadow-sm',
    },
    argentina: {
        label: 'Argentina',
        tickerClass: 'border-t border-sky-200/70 bg-[linear-gradient(to_bottom,_#a9dbff_0%,_#a9dbff_33%,_#f7fbff_33%,_#f7fbff_66%,_#a9dbff_66%,_#a9dbff_100%)] text-slate-900',
        surfaceClass: 'rounded-[1.5rem] bg-white/48 px-3 py-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.45)] ring-1 ring-sky-200/70 backdrop-blur-md',
        iconClass: 'bg-white/80 text-sky-600 ring-1 ring-sky-200/70',
        eyebrowClass: 'text-sky-700',
        bodyClass: 'text-slate-700',
        counterClass: 'rounded-2xl bg-white/72 px-3 py-2 text-center ring-1 ring-sky-200/70 backdrop-blur',
        counterValueClass: 'text-slate-950',
        counterLabelClass: 'text-slate-500',
        chipClass: 'border-sky-200 bg-white/70 text-sky-700 hover:bg-white',
        activeChipClass: 'border-sky-300 bg-sky-50 text-sky-800 shadow-sm',
    },
    brasil: {
        label: 'Brasil',
        tickerClass: 'relative overflow-hidden border-t border-emerald-300/60 bg-[#3f9f63] text-white',
        decorationClass: 'before:absolute before:left-1/2 before:top-1/2 before:h-32 before:w-32 before:-translate-x-1/2 before:-translate-y-1/2 before:rotate-45 before:rounded-[0.75rem] before:bg-[#f0d25a] before:content-[\'\'] after:absolute after:left-1/2 after:top-1/2 after:h-16 after:w-16 after:-translate-x-1/2 after:-translate-y-1/2 after:rounded-full after:bg-[#29539b] after:content-[\'\']',
        surfaceClass: 'relative rounded-[1.5rem] bg-slate-950/16 px-3 py-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.06)] ring-1 ring-white/12 backdrop-blur-md',
        iconClass: 'bg-white/16 text-white ring-1 ring-white/20',
        eyebrowClass: 'text-white/80',
        bodyClass: 'text-white',
        counterClass: 'rounded-2xl bg-black/16 px-3 py-2 text-center ring-1 ring-white/14 backdrop-blur',
        counterValueClass: 'text-white',
        counterLabelClass: 'text-white/72',
        chipClass: 'border-emerald-300/30 bg-slate-950/20 text-white hover:bg-slate-950/30',
        activeChipClass: 'border-white/20 bg-white/16 text-white shadow-sm',
    },
    francia: {
        label: 'Francia',
        tickerClass: 'border-t border-slate-300/60 bg-[linear-gradient(to_right,_#8fb2d9_0%,_#8fb2d9_33%,_#f8fafc_33%,_#f8fafc_66%,_#e3a19b_66%,_#e3a19b_100%)] text-slate-900',
        surfaceClass: 'rounded-[1.5rem] bg-white/44 px-3 py-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.42)] ring-1 ring-slate-200/70 backdrop-blur-md',
        iconClass: 'bg-white/82 text-slate-700 ring-1 ring-slate-200/80',
        eyebrowClass: 'text-slate-700',
        bodyClass: 'text-slate-700',
        counterClass: 'rounded-2xl bg-white/72 px-3 py-2 text-center ring-1 ring-slate-200/80 backdrop-blur',
        counterValueClass: 'text-slate-950',
        counterLabelClass: 'text-slate-500',
        chipClass: 'border-slate-200 bg-white/68 text-slate-700 hover:bg-white',
        activeChipClass: 'border-slate-300 bg-white text-slate-900 shadow-sm',
    },
    italia: {
        label: 'Italia',
        tickerClass: 'border-t border-slate-300/60 bg-[linear-gradient(to_right,_#8dc7a2_0%,_#8dc7a2_33%,_#f8fafc_33%,_#f8fafc_66%,_#e5a0a0_66%,_#e5a0a0_100%)] text-slate-900',
        surfaceClass: 'rounded-[1.5rem] bg-white/46 px-3 py-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.42)] ring-1 ring-slate-200/70 backdrop-blur-md',
        iconClass: 'bg-white/82 text-slate-700 ring-1 ring-slate-200/80',
        eyebrowClass: 'text-slate-700',
        bodyClass: 'text-slate-700',
        counterClass: 'rounded-2xl bg-white/74 px-3 py-2 text-center ring-1 ring-slate-200/80 backdrop-blur',
        counterValueClass: 'text-slate-950',
        counterLabelClass: 'text-slate-500',
        chipClass: 'border-slate-200 bg-white/68 text-slate-700 hover:bg-white',
        activeChipClass: 'border-slate-300 bg-white text-slate-900 shadow-sm',
    },
}

const selectedTickerTheme = ref('francia')
const activeTickerTheme = computed(() => tickerThemes[selectedTickerTheme.value] ?? tickerThemes.alemania)

const setTickerTheme = (themeKey) => {
    selectedTickerTheme.value = tickerThemes[themeKey] ? themeKey : 'alemania'
    localStorage.setItem('dashboard-ticker-theme', selectedTickerTheme.value)
}

const quickStats = computed(() => [
    {
        title: 'Mis quinielas',
        value: '1',
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
    const storedTickerTheme = localStorage.getItem('dashboard-ticker-theme')
    if (storedTickerTheme && tickerThemes[storedTickerTheme]) {
        selectedTickerTheme.value = storedTickerTheme
    }

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
            <div :class="activeTickerTheme.surfaceClass" class="flex items-center gap-3">
                <div class="flex items-center gap-3">
                    <div :class="activeTickerTheme.iconClass" class="flex h-9 w-9 items-center justify-center rounded-full">
                        <CalendarDaysIcon class="h-4 w-4" />
                    </div>
                    <div>
                        <p :class="activeTickerTheme.eyebrowClass" class="text-[11px] font-semibold uppercase tracking-[0.26em]">
                            Cuenta regresiva al Mundial 2026
                        </p>
                        <p :class="activeTickerTheme.bodyClass" class="text-sm">
                            Cada segundo nos acerca al partido inaugural en Ciudad de Mexico.
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <template #headerCenter>
            <div class="grid grid-cols-4 gap-2 md:gap-3">
                <div class="rounded-2xl border border-slate-200/80 bg-white/92 px-4 py-3 text-center shadow-sm dark:border-slate-700 dark:bg-slate-950/70">
                    <p class="text-2xl font-black tracking-tight text-slate-950 dark:text-white">{{ countdown.days }}</p>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Dias</p>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-white/92 px-4 py-3 text-center shadow-sm dark:border-slate-700 dark:bg-slate-950/70">
                    <p class="text-2xl font-black tracking-tight text-slate-950 dark:text-white">{{ countdown.hours }}</p>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Horas</p>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-white/92 px-4 py-3 text-center shadow-sm dark:border-slate-700 dark:bg-slate-950/70">
                    <p class="text-2xl font-black tracking-tight text-slate-950 dark:text-white">{{ countdown.minutes }}</p>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Min</p>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-white/92 px-4 py-3 text-center shadow-sm dark:border-slate-700 dark:bg-slate-950/70">
                    <p class="text-2xl font-black tracking-tight text-slate-950 dark:text-white">{{ countdown.seconds }}</p>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">Seg</p>
                </div>
            </div>
        </template>

        <template #headerActions>
            <Link
                :href="route('predictions.worldcup')"
                class="inline-flex items-center justify-center rounded-2xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-sm shadow-primary-500/30 transition hover:bg-primary-700"
            >
                Crear quiniela
            </Link>
            <Link
                :href="route('matches.index')"
                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-primary-300 hover:text-primary-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-primary-500 dark:hover:text-primary-300"
            >
                Ver partidos
            </Link>
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

        <section class="mt-6">
            <SectionCard
                title="Temas del ticker"
                subtitle="Base lista para que el usuario cambie la franja visual segun el equipo o pais que prefiera."
            >
                <div class="flex flex-wrap gap-3">
                    <button
                        v-for="(theme, key) in tickerThemes"
                        :key="key"
                        type="button"
                        @click="setTickerTheme(key)"
                        :class="selectedTickerTheme === key ? theme.activeChipClass : theme.chipClass"
                        class="inline-flex items-center justify-center rounded-2xl border px-4 py-2 text-sm font-semibold transition"
                    >
                        {{ theme.label }}
                    </button>
                </div>
            </SectionCard>
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
    </UserDashboardLayout>
</template>
