<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'
import { imageUrl } from '@/Utils/image'

const props = defineProps({
    tournament: { type: Object, default: null },
    stadium: { type: Object, default: null },
    matches: { type: Array, default: () => [] },
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

const statusClass = (status) => {
    if (status === 'finished') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
    if (status === 'in_progress') return 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'
    return 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
}
</script>

<template>
    <Head title="Detalle de estadio" />

    <UserDashboardLayout
        title="Detalle de estadio"
        description="Template de sede con encuentros programados y datos basicos."
        :ticker-class="[activeTickerTheme.tickerClass, activeTickerTheme.decorationClass || ''].join(' ')"
    >
        <template #ticker><div class="h-12 w-full" aria-hidden="true" /></template>
        <template #headerContent><div class="hidden" /></template>

        <section class="space-y-6">
            <div class="-mt-8 flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Sede oficial</p>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white">{{ stadium?.name || 'Estadio' }}</h1>
                </div>
                <Link
                    :href="route('teams.profile')"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-300 hover:text-cyan-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                >
                    Volver a selecciones
                </Link>
            </div>

            <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/75">
                <img
                    v-if="imageUrl(stadium?.image_url)"
                    :src="imageUrl(stadium?.image_url)"
                    :alt="stadium?.name"
                    class="h-56 w-full object-cover sm:h-72"
                >
                <div v-else class="flex h-56 items-center justify-center bg-[linear-gradient(120deg,_#0f172a,_#1e3a8a,_#0f172a)] sm:h-72">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-200">Imagen del estadio (template)</p>
                </div>
                <div class="p-5 sm:p-6">
                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ stadium?.info }}</p>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ stadium?.matches_count ?? 0 }} encuentros registrados</p>
                </div>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900/75">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Encuentros en esta sede</h2>
                <div class="mt-4 space-y-2">
                    <article
                        v-for="match in matches"
                        :key="match.id"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-900/70"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ match.stage_label }}
                                <span v-if="match.group_name"> · Grupo {{ match.group_name }}</span>
                                · {{ match.match_date_label }} {{ match.match_time_label }}
                            </p>
                            <span :class="statusClass(match.status)" class="rounded-full px-2 py-0.5 text-[10px] font-semibold">{{ match.status_label }}</span>
                        </div>
                        <div class="mt-2 grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ match.home_team.name }}</p>
                            <p class="text-base font-black text-slate-900 dark:text-white">
                                {{ Number.isInteger(match.home_score) ? match.home_score : '-' }} : {{ Number.isInteger(match.away_score) ? match.away_score : '-' }}
                            </p>
                            <p class="truncate text-right text-sm font-semibold text-slate-900 dark:text-white">{{ match.away_team.name }}</p>
                        </div>
                    </article>
                </div>
            </article>
        </section>
    </UserDashboardLayout>
</template>
