<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'
import { imageUrl } from '@/Utils/image'

const props = defineProps({
    tournament: {
        type: Object,
        default: null,
    },
    roadmapStages: {
        type: Array,
        default: () => [],
    },
    totals: {
        type: Object,
        default: () => ({
            games: 0,
            finished: 0,
            inProgress: 0,
            upcoming: 0,
        }),
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

const completionPercent = computed(() => {
    const totalGames = Number(props.totals?.games ?? 0)

    if (totalGames <= 0) {
        return 0
    }

    return Math.round((Number(props.totals?.finished ?? 0) / totalGames) * 100)
})

const stageCompletion = (stage) => {
    const total = Number(stage?.stats?.total ?? 0)

    if (total <= 0) {
        return 0
    }

    return Math.round((Number(stage?.stats?.finished ?? 0) / total) * 100)
}

const statusClass = (status) => {
    if (status === 'finished') {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
    }

    if (status === 'in_progress') {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'
    }

    return 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
}

const roadmapPanelClass = computed(() => activeTickerTheme.value?.groupPanelClass
    ?? 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/75')
const roadmapAccentClass = computed(() => activeTickerTheme.value?.statsValueClass ?? 'text-cyan-500 dark:text-cyan-300')
</script>

<template>
    <Head title="Roadmap del torneo" />

    <UserDashboardLayout
        title="Roadmap del torneo"
        description="Visualiza el progreso de cada fase y sigue el camino completo de los encuentros hasta la final."
        :ticker-class="[activeTickerTheme.tickerClass, activeTickerTheme.decorationClass || ''].join(' ')"
    >
        <template #ticker>
            <div class="h-12 w-full" aria-hidden="true" />
        </template>

        <template #headerContent>
            <div class="hidden" />
        </template>

        <section class="space-y-6">
            <div class="-mt-8 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Mapa de avance</p>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white">
                        {{ tournament?.name }} {{ tournament?.year ?? '' }}
                    </h1>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('teams.profile')"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-300 hover:text-cyan-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-cyan-400 dark:hover:text-cyan-300"
                    >
                        Ver selecciones
                    </Link>
                </div>
            </div>

            <article :class="roadmapPanelClass" class="rounded-3xl border p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Progreso general</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ completionPercent }}%</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-2xl border border-slate-200 bg-white/70 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/70">
                            <p class="text-[10px] uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Partidos</p>
                            <p class="text-lg font-black text-slate-900 dark:text-white">{{ totals.games }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white/70 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/70">
                            <p class="text-[10px] uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Finalizados</p>
                            <p class="text-lg font-black text-emerald-500">{{ totals.finished }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white/70 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/70">
                            <p class="text-[10px] uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">En juego</p>
                            <p class="text-lg font-black text-amber-500">{{ totals.inProgress }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-white/70 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/70">
                            <p class="text-[10px] uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Pendientes</p>
                            <p class="text-lg font-black text-slate-900 dark:text-white">{{ totals.upcoming }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 h-3 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                    <div
                        class="h-full rounded-full bg-[linear-gradient(90deg,_#22d3ee,_#38bdf8,_#3b82f6)] transition-all duration-700"
                        :style="{ width: `${completionPercent}%` }"
                    />
                </div>
            </article>

            <div v-if="!roadmapStages.length" class="rounded-3xl border border-slate-200 bg-white p-8 text-center dark:border-slate-800 dark:bg-slate-900/70">
                <p class="text-sm text-slate-500 dark:text-slate-400">No hay encuentros disponibles para mostrar en el roadmap.</p>
            </div>

            <div v-else class="overflow-x-auto pb-3">
                <div class="flex min-w-max gap-4">
                    <section
                        v-for="stage in roadmapStages"
                        :key="stage.key"
                        :class="roadmapPanelClass"
                        class="w-[20rem] shrink-0 rounded-3xl border p-4 sm:p-5"
                    >
                        <header class="border-b border-slate-200 pb-3 dark:border-slate-700">
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="text-lg font-black text-slate-900 dark:text-white">{{ stage.label }}</h2>
                                <span :class="roadmapAccentClass" class="text-xs font-semibold uppercase tracking-[0.16em]">
                                    {{ stage.short }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ stage.stats.finished }}/{{ stage.stats.total }} finalizados · {{ stageCompletion(stage) }}%
                            </p>
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div
                                    class="h-full rounded-full bg-[linear-gradient(90deg,_#06b6d4,_#3b82f6)]"
                                    :style="{ width: `${stageCompletion(stage)}%` }"
                                />
                            </div>
                        </header>

                        <div class="mt-4 space-y-3">
                            <article
                                v-for="(match, index) in stage.games"
                                :key="match.id"
                                class="relative rounded-2xl border border-slate-200 bg-white px-3 py-3 dark:border-slate-700 dark:bg-slate-900/70"
                            >
                                <div
                                    v-if="index < stage.games.length - 1"
                                    class="absolute bottom-[-12px] left-1/2 h-3 w-px -translate-x-1/2 bg-slate-300 dark:bg-slate-600"
                                />
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                                        Partido #{{ match.match_number }}
                                        <span v-if="match.group_name"> · Grupo {{ match.group_name }}</span>
                                    </p>
                                    <span :class="statusClass(match.status)" class="rounded-full px-2 py-0.5 text-[10px] font-semibold">
                                        {{ match.status_label }}
                                    </span>
                                </div>

                                <div class="mt-3 grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                                    <div class="flex items-center gap-1.5">
                                        <img
                                            v-if="imageUrl(match.home_team.flag_url)"
                                            :src="imageUrl(match.home_team.flag_url)"
                                            :alt="match.home_team.name"
                                            class="h-5 w-7 rounded object-cover"
                                        >
                                        <span v-else class="inline-flex h-5 min-w-7 items-center justify-center rounded bg-slate-200 text-[10px] font-bold dark:bg-slate-700">
                                            {{ match.home_team.code }}
                                        </span>
                                        <span class="truncate text-xs font-semibold text-slate-900 dark:text-white">{{ match.home_team.name }}</span>
                                    </div>

                                    <div class="text-center">
                                        <p class="text-base font-black text-slate-900 dark:text-white">
                                            {{ Number.isInteger(match.home_score) ? match.home_score : '-' }}
                                            :
                                            {{ Number.isInteger(match.away_score) ? match.away_score : '-' }}
                                        </p>
                                    </div>

                                    <div class="flex items-center justify-end gap-1.5">
                                        <span class="truncate text-right text-xs font-semibold text-slate-900 dark:text-white">{{ match.away_team.name }}</span>
                                        <img
                                            v-if="imageUrl(match.away_team.flag_url)"
                                            :src="imageUrl(match.away_team.flag_url)"
                                            :alt="match.away_team.name"
                                            class="h-5 w-7 rounded object-cover"
                                        >
                                        <span v-else class="inline-flex h-5 min-w-7 items-center justify-center rounded bg-slate-200 text-[10px] font-bold dark:bg-slate-700">
                                            {{ match.away_team.code }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-3 border-t border-slate-200 pt-2 text-[11px] text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                    <p>{{ match.date_label }} · {{ match.time_label }}</p>
                                    <p class="truncate">{{ match.venue || 'Sede por definir' }}</p>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>
            </div>
        </section>
    </UserDashboardLayout>
</template>
