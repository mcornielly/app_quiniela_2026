<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { CheckCircleIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'

const props = defineProps({
    tournament: {
        type: Object,
        default: null,
    },
    liveMatches: {
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

const isDraw = (match) => Number(match.homeScore) === Number(match.awayScore)
const isHomeWinner = (match) => Number(match.homeScore) > Number(match.awayScore)
const isAwayWinner = (match) => Number(match.awayScore) > Number(match.homeScore)
</script>

<template>
    <Head title="Juego Directo" />

    <UserDashboardLayout
        title="Juego Directo"
        description="Partidos en curso del Mundial 2026."
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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" class="h-7 w-7 fill-current text-rose-500 dark:text-rose-400" aria-hidden="true">
                        <path d="M87.9 11.5c-11.3-6.9-26.1-3.2-33 8.1-24.8 41-39 89.1-39 140.4s14.2 99.4 39 140.4c6.9 11.3 21.6 15 33 8.1s15-21.6 8.1-33C75.7 241.9 64 202.3 64 160S75.7 78.1 96.1 44.4c6.9-11.3 3.2-26.1-8.1-33zm400.1 0c-11.3 6.9-15 21.6-8.1 33 20.4 33.7 32.1 73.3 32.1 115.6s-11.7 81.9-32.1 115.6c-6.9 11.3-3.2 26.1 8.1 33s26.1 3.2 33-8.1c24.8-41 39-89.1 39-140.4S545.8 60.6 521 19.6c-6.9-11.3-21.6-15-33-8.1zM320 215.4c19.1-11.1 32-31.7 32-55.4 0-35.3-28.7-64-64-64s-64 28.7-64 64c0 23.7 12.9 44.4 32 55.4L256 480c0 17.7 14.3 32 32 32s32-14.3 32-32l0-264.6zM180.2 91c7.2-11.2 3.9-26-7.2-33.2s-26-3.9-33.2 7.2c-17.6 27.4-27.8 60-27.8 95s10.2 67.6 27.8 95c7.2 11.2 22 14.4 33.2 7.2s14.4-22 7.2-33.2c-12.8-19.9-20.2-43.6-20.2-69s7.4-49.1 20.2-69zM436.2 65c-7.2-11.2-22-14.4-33.2-7.2s-14.4 22-7.2 33.2c12.8 19.9 20.2 43.6 20.2 69s-7.4 49.1-20.2 69c-7.2 11.2-3.9 26 7.2 33.2s26 3.9 33.2-7.2c17.6-27.4 27.8-60 27.8-95s-10.2-67.6-27.8-95z"/>
                    </svg>
                    <h1>Juego Directo</h1>
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
                <p class="text-sm leading-tight text-slate-500 dark:text-slate-400">{{ liveMatches.length }} juego(s) en vivo</p>
                <div class="mt-[6px] border-b border-slate-300 dark:border-slate-700" />
            </div>

            <div v-if="liveMatches.length" class="mt-6 space-y-3">
                <article
                    v-for="match in liveMatches"
                    :key="match.id"
                    class="live-match-card relative overflow-hidden rounded-2xl border border-slate-200 bg-white px-5 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900/75"
                >
                    <div class="grid grid-cols-1 items-center gap-3 xl:grid-cols-[170px_1fr_200px]">
                        <div>
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ match.groupName || '-' }}</p>
                            <p class="text-2xl font-black text-rose-500 dark:text-rose-400">{{ match.matchTime }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <div class="inline-flex w-full items-center justify-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                                <MapPinIcon class="h-4 w-4 text-cyan-500 dark:text-cyan-400" />
                                <span class="truncate transition-colors hover:text-cyan-500 dark:hover:text-cyan-400">{{ match.venue || 'Sede por confirmar' }}</span>
                            </div>

                            <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">
                                <div class="flex items-center justify-end gap-2 text-right">
                                    <span class="truncate text-lg font-semibold text-slate-900 dark:text-white">{{ match.homeTeam }}</span>
                                    <span class="text-base font-semibold uppercase text-slate-500 dark:text-slate-400">{{ match.homeCode }}</span>
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
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-start xl:justify-end">
                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-1 text-[11px] font-bold tracking-wide text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
                                <CheckCircleIcon class="h-3.5 w-3.5" />
                                En Directo
                            </span>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="mt-6 rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                No hay juegos en vivo en este momento.
            </div>
        </section>
    </UserDashboardLayout>
</template>

<style scoped>
.live-match-card::before {
    content: '';
    position: absolute;
    top: -1px;
    left: -36%;
    width: 36%;
    height: 3px;
    border-radius: 9999px;
    background: linear-gradient(90deg, rgba(16, 185, 129, 0) 0%, rgba(16, 185, 129, 0.75) 35%, rgba(34, 197, 94, 1) 55%, rgba(52, 211, 153, 0.85) 75%, rgba(16, 185, 129, 0) 100%);
    box-shadow: 0 0 10px rgba(34, 197, 94, 0.8);
    animation: live-scan 2.2s ease-in-out infinite alternate;
}

@keyframes live-scan {
    from {
        transform: translateX(0);
    }
    to {
        transform: translateX(390%);
    }
}

@media (prefers-reduced-motion: reduce) {
    .live-match-card::before {
        animation: none;
        left: 0;
        width: 100%;
        opacity: 0.9;
    }
}
</style>
