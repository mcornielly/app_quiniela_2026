<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
    BoltIcon,
    CheckCircleIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'

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
        return 'bg-slate-200 text-slate-700 dark:bg-slate-700/70 dark:text-slate-200'
    }

    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
}

const rowStatusClass = (prediction) => {
    if (prediction.hasOfficialResult) {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
    }

    if (prediction.status === 'in_progress') {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
    }

    return 'bg-slate-200 text-slate-700 dark:bg-slate-700/70 dark:text-slate-200'
}
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
                        <BoltIcon class="h-3.5 w-3.5" />
                        {{ poolEntry.statusLabel }}
                    </span>
                    <div class="ml-auto flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                        <p>Creada: {{ poolEntry.createdDate || '-' }}</p>
                        <p>Registro: #{{ poolEntry.registrationNumber }}</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                    <div class="rounded-xl bg-slate-100 px-3 py-2 text-right dark:bg-slate-800/70">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Puntos</p>
                        <p class="text-2xl font-black text-cyan-500 dark:text-cyan-400">{{ stats.totalPoints }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-100 px-3 py-2 text-right dark:bg-slate-800/70">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Partidos</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.matchesTotal }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-100 px-3 py-2 text-right dark:bg-slate-800/70">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Con resultado</p>
                        <p class="text-2xl font-black text-emerald-500 dark:text-emerald-400">{{ stats.matchesPlayed }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-100 px-3 py-2 text-right dark:bg-slate-800/70">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Pendientes</p>
                        <p class="text-2xl font-black text-amber-500 dark:text-amber-400">{{ stats.matchesPending }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-100 px-3 py-2 text-right dark:bg-slate-800/70">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Aciertos exactos</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.exactHits }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-100 px-3 py-2 text-right dark:bg-slate-800/70">
                        <p class="text-xs text-slate-500 dark:text-slate-400">Resultado correcto</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.correctResults }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-2">
                <article class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/75">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Partidos con resultado</h2>
                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                            {{ poolEntry.playedPredictions.length }}
                        </span>
                    </div>

                    <div v-if="poolEntry.playedPredictions.length" class="space-y-3 p-4">
                        <article
                            v-for="prediction in poolEntry.playedPredictions"
                            :key="prediction.id"
                            class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/40"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-400">
                                        {{ prediction.groupName || prediction.stageLabel }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        {{ prediction.matchDate }} - {{ prediction.matchTime }}
                                    </p>
                                </div>
                                <span :class="rowStatusClass(prediction)" class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-bold">
                                    <CheckCircleIcon class="h-3.5 w-3.5" />
                                    {{ prediction.statusLabel }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-[1fr_auto_1fr] items-center gap-2 text-sm">
                                <div class="flex items-center justify-end gap-2 text-right">
                                    <span class="truncate font-semibold text-slate-900 dark:text-white">{{ prediction.homeTeamName }}</span>
                                    <img v-if="prediction.homeFlagUrl" :src="prediction.homeFlagUrl" :alt="prediction.homeTeamName" class="h-4 w-6 rounded object-cover">
                                </div>
                                <span class="rounded-lg bg-slate-200 px-2 py-1 text-center font-black text-slate-900 dark:bg-slate-700 dark:text-white">
                                    {{ prediction.predictedHomeScore }} - {{ prediction.predictedAwayScore }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <img v-if="prediction.awayFlagUrl" :src="prediction.awayFlagUrl" :alt="prediction.awayTeamName" class="h-4 w-6 rounded object-cover">
                                    <span class="truncate font-semibold text-slate-900 dark:text-white">{{ prediction.awayTeamName }}</span>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <p class="text-slate-500 dark:text-slate-400">
                                    Real: <span class="font-bold text-slate-700 dark:text-slate-200">{{ prediction.actualScore }}</span>
                                </p>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full bg-cyan-100 px-2 py-1 font-bold text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                                        +{{ prediction.awardedPoints ?? 0 }} pts
                                    </span>
                                    <span
                                        v-if="prediction.isExactHit"
                                        class="rounded-full bg-emerald-100 px-2 py-1 font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                                    >
                                        Exacto
                                    </span>
                                    <span
                                        v-else-if="prediction.isCorrectResult"
                                        class="rounded-full bg-sky-100 px-2 py-1 font-bold text-sky-700 dark:bg-sky-900/30 dark:text-sky-300"
                                    >
                                        Resultado
                                    </span>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-else class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">
                        Aún no hay partidos con resultado oficial para esta quiniela.
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/75">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Partidos pendientes</h2>
                        <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                            {{ poolEntry.pendingPredictions.length }}
                        </span>
                    </div>

                    <div v-if="poolEntry.pendingPredictions.length" class="space-y-3 p-4">
                        <article
                            v-for="prediction in poolEntry.pendingPredictions"
                            :key="prediction.id"
                            class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/40"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-400">
                                        {{ prediction.groupName || prediction.stageLabel }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        {{ prediction.matchDate }} - {{ prediction.matchTime }}
                                    </p>
                                </div>
                                <span :class="rowStatusClass(prediction)" class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-bold">
                                    <ClockIcon class="h-3.5 w-3.5" />
                                    {{ prediction.statusLabel }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-[1fr_auto_1fr] items-center gap-2 text-sm">
                                <div class="flex items-center justify-end gap-2 text-right">
                                    <span class="truncate font-semibold text-slate-900 dark:text-white">{{ prediction.homeTeamName }}</span>
                                    <img v-if="prediction.homeFlagUrl" :src="prediction.homeFlagUrl" :alt="prediction.homeTeamName" class="h-4 w-6 rounded object-cover">
                                </div>
                                <span class="rounded-lg bg-slate-200 px-2 py-1 text-center font-black text-slate-900 dark:bg-slate-700 dark:text-white">
                                    {{ prediction.predictedHomeScore }} - {{ prediction.predictedAwayScore }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <img v-if="prediction.awayFlagUrl" :src="prediction.awayFlagUrl" :alt="prediction.awayTeamName" class="h-4 w-6 rounded object-cover">
                                    <span class="truncate font-semibold text-slate-900 dark:text-white">{{ prediction.awayTeamName }}</span>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-else class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">
                        No quedan partidos pendientes en esta quiniela.
                    </div>
                </article>
            </div>
        </section>
    </UserDashboardLayout>
</template>
