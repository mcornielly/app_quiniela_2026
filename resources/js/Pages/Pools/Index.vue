<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
    BoltIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'
import AppTooltip from '@/Components/UI/AppTooltip.vue'
import { formatRegistrationNumber } from '@/Utils/format'

const props = defineProps({
    poolEntries: {
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
const themedPrimaryButtonClass = computed(() => activeTickerTheme.value?.buttonPrimaryClass
    ?? 'bg-cyan-400 text-slate-950 hover:bg-cyan-300 hover:text-white focus:ring-cyan-200 dark:bg-cyan-400 dark:hover:bg-cyan-300 dark:focus:ring-cyan-900')

const poolsCountLabel = computed(() => `${props.poolEntries.length} quiniela(s) creadas`)

const statusLabel = (status) => {
    const labels = {
        draft: 'Borrador',
        complete: 'Activa',
        paid_locked: 'Activa',
        live: 'Activa',
        finished: 'Finalizada',
    }

    return labels[status] ?? 'Activa'
}

const statusClass = (status) => {
    if (status === 'finished') {
        return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
    }

    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
}

const statusIcon = (status) => (status === 'finished' ? CheckCircleIcon : BoltIcon)
</script>

<template>
    <Head title="Mis Quinielas" />

    <UserDashboardLayout
        title="Mis Quinielas"
        description="Gestiona todas tus quinielas y compara tus últimos pronósticos contra los resultados reales."
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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="h-7 w-7 fill-current text-amber-400" aria-hidden="true">
                        <path d="M144.3 0l224 0c26.5 0 48.1 21.8 47.1 48.2-.2 5.3-.4 10.6-.7 15.8l49.6 0c26.1 0 49.1 21.6 47.1 49.8-7.5 103.7-60.5 160.7-118 190.5-15.8 8.2-31.9 14.3-47.2 18.8-20.2 28.6-41.2 43.7-57.9 51.8l0 73.1 64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-192 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l64 0 0-73.1c-16-7.7-35.9-22-55.3-48.3-18.4-4.8-38.4-12.1-57.9-23.1-54.1-30.3-102.9-87.4-109.9-189.9-1.9-28.1 21-49.7 47.1-49.7l49.6 0c-.3-5.2-.5-10.4-.7-15.8-1-26.5 20.6-48.2 47.1-48.2zM101.5 112l-52.4 0c6.2 84.7 45.1 127.1 85.2 149.6-14.4-37.3-26.3-86-32.8-149.6zM380 256.8c40.5-23.8 77.1-66.1 83.3-144.8L411 112c-6.2 60.9-17.4 108.2-31 144.8z"/>
                    </svg>
                    <h1>Mis Quinielas</h1>
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
                    <p class="text-sm leading-tight text-slate-500 dark:text-slate-400">{{ poolsCountLabel }}</p>
                    <Link
                        :href="route('predictions.worldcup')"
                        :class="[
                            themedPrimaryButtonClass,
                            'inline-flex min-w-[190px] flex-1 items-center justify-center self-start rounded-xl px-5 py-3 text-sm font-semibold shadow-sm transition focus:outline-none sm:self-auto lg:flex-none',
                        ]"
                    >
                        <svg class="me-2 h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                        </svg>
                        Crear quiniela
                    </Link>
                </div>
                <div class="mt-[6px] border-b border-slate-300 dark:border-slate-700" />
            </div>

            <div v-if="poolEntries.length" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="poolEntry in poolEntries"
                    :key="poolEntry.id"
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/75"
                >
                    <div class="space-y-4 px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-2xl font-bold text-slate-900 dark:text-white">{{ poolEntry.name }}</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Creada: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ poolEntry.createdDate || '-' }}</span>
                                </p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Registro: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ formatRegistrationNumber(poolEntry.registrationNumber) }}</span>
                                </p>
                            </div>
                            <span :class="statusClass(poolEntry.status)" class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-bold">
                                <component :is="statusIcon(poolEntry.status)" class="h-3.5 w-3.5" />
                                {{ statusLabel(poolEntry.status) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-5xl font-black leading-none text-cyan-500 dark:text-cyan-400">{{ poolEntry.totalPoints ?? 0 }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">pts</p>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-black leading-none text-slate-900 dark:text-white">{{ poolEntry.matchesCount ?? 0 }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Partidos</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="(prediction, index) in (poolEntry.latestPredictions || [])"
                                :key="`${poolEntry.id}-${index}`"
                                class="rounded-xl bg-slate-100/70 px-3 py-2 dark:bg-slate-800/70"
                            >
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <p class="flex min-w-0 items-center gap-3 font-bold text-slate-900 dark:text-white">
                                        <AppTooltip :text="prediction.homeName" placement="top">
                                            <img
                                                v-if="prediction.homeFlagUrl"
                                                :src="prediction.homeFlagUrl"
                                                :alt="prediction.homeName"
                                                class="h-4 w-6 shrink-0 rounded-[3px] object-cover"
                                            >
                                            <span
                                                v-else
                                                class="inline-flex h-4 w-6 shrink-0 items-center justify-center rounded-[3px] bg-slate-300 text-[9px] font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-300"
                                            >
                                                --
                                            </span>
                                        </AppTooltip>
                                        <span class="mx-1 tabular-nums">{{ prediction.predictedHomeScore }}-{{ prediction.predictedAwayScore }}</span>
                                        <AppTooltip :text="prediction.awayName" placement="top">
                                            <img
                                                v-if="prediction.awayFlagUrl"
                                                :src="prediction.awayFlagUrl"
                                                :alt="prediction.awayName"
                                                class="h-4 w-6 shrink-0 rounded-[3px] object-cover"
                                            >
                                            <span
                                                v-else
                                                class="inline-flex h-4 w-6 shrink-0 items-center justify-center rounded-[3px] bg-slate-300 text-[9px] font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-300"
                                            >
                                                --
                                            </span>
                                        </AppTooltip>
                                    </p>
                                    <p class="text-slate-500 dark:text-slate-400">
                                        Real:
                                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ prediction.actualScore ?? '-- - --' }}</span>
                                        <span
                                            v-if="prediction.awardedPoints !== null"
                                            class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold"
                                            :class="prediction.awardedPoints > 0
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                : 'bg-slate-200 text-slate-600 dark:bg-slate-700/70 dark:text-slate-300'"
                                        >
                                            +{{ prediction.awardedPoints }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="!(poolEntry.latestPredictions || []).length"
                                class="rounded-xl border border-dashed border-slate-300 px-3 py-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400"
                            >
                                Sin pronósticos recientes.
                            </div>
                        </div>
                    </div>

                    <Link
                        :href="`/pools/${poolEntry.id}`"
                        class="flex items-center justify-between border-t border-slate-200 px-5 py-4 transition hover:bg-slate-50/70 dark:border-slate-800 dark:hover:bg-slate-800/30"
                    >
                        <span class="inline-flex items-center whitespace-nowrap rounded-md px-1 py-1 text-xs font-bold uppercase tracking-wide text-primary-700 dark:text-primary-500">
                            Ver detalles
                        </span>
                        <svg class="h-5 w-5 text-primary-600 dark:text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </article>
            </div>

            <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center dark:border-slate-700">
                <p class="text-lg font-semibold text-slate-900 dark:text-white">Aún no tienes quinielas creadas.</p>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    Crea tu primera quiniela y aquí verás su rendimiento con tus últimos pronósticos.
                </p>
                <Link
                    :href="route('predictions.worldcup')"
                    :class="[
                        themedPrimaryButtonClass,
                        'mt-5 inline-flex min-w-[190px] flex-1 items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold shadow-sm transition focus:outline-none lg:flex-none',
                    ]"
                >
                    <svg class="me-2 h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                    </svg>
                    Crear quiniela
                </Link>
            </div>
        </section>
    </UserDashboardLayout>
</template>
