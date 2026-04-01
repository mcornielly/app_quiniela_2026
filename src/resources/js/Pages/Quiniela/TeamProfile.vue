<script setup>
import { computed, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'
import { imageUrl } from '@/Utils/image'

const props = defineProps({
    tournament: { type: Object, default: null },
    teams: { type: Array, default: () => [] },
    selectedTeam: { type: Object, default: null },
    groupStandings: { type: Array, default: () => [] },
    teamMatches: { type: Array, default: () => [] },
    venueCards: { type: Array, default: () => [] },
})

const page = usePage()
const favoriteTeamTheme = computed(() => page.props.auth?.user?.favorite_team_theme ?? null)
const activeTab = ref('teams')

const tickerThemes = {
    neutral: {
        tickerClass: 'border-t border-slate-300/70 bg-[linear-gradient(to_right,_#cfd6df_0%,_#e4e8ee_45%,_#f4f6f9_100%)] text-slate-900',
    },
}
const activeTickerTheme = computed(() => ({
    ...tickerThemes.neutral,
    ...(favoriteTeamTheme.value ?? {}),
}))

const groupedTeams = computed(() => {
    const grouped = {}

    props.teams.forEach((team) => {
        const key = team.group_name || '-'
        if (!grouped[key]) grouped[key] = []
        grouped[key].push(team)
    })

    return Object.entries(grouped)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([groupName, teams]) => ({ groupName, teams }))
})

const panelClass = computed(() => activeTickerTheme.value?.groupPanelClass
    ?? 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/75')

const accentClass = computed(() => activeTickerTheme.value?.statsValueClass ?? 'text-emerald-600 dark:text-emerald-300')
const selectedTeamHeadline = computed(() => String(props.selectedTeam?.name ?? '').toUpperCase())
const groupStageMatches = computed(() => props.teamMatches.filter((match) => match.stage === 'group'))

const statusClass = (status) => {
    if (status === 'finished') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
    if (status === 'in_progress') return 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'
    return 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
}
</script>

<template>
    <Head title="Selecciones" />

    <UserDashboardLayout
        title="Selecciones"
        description="Organiza la informacion del torneo por equipos, estadios y roadmap."
        :ticker-class="[activeTickerTheme.tickerClass, activeTickerTheme.decorationClass || ''].join(' ')"
    >
        <template #ticker><div class="h-12 w-full" aria-hidden="true" /></template>
        <template #headerContent><div class="hidden" /></template>

        <section class="space-y-6">
            <div class="-mt-8">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Mundial {{ tournament?.year ?? '' }}</p>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white">{{ tournament?.name || 'Selecciones' }}</h1>
            </div>

            <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-2 dark:border-slate-800">
                <button
                    type="button"
                    class="rounded-xl px-4 py-2 text-sm font-semibold transition"
                    :class="activeTab === 'teams' ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                    @click="activeTab = 'teams'"
                >
                    Equipos
                </button>
                <button
                    type="button"
                    class="rounded-xl px-4 py-2 text-sm font-semibold transition"
                    :class="activeTab === 'stadiums' ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                    @click="activeTab = 'stadiums'"
                >
                    Estadios
                </button>
                <button
                    type="button"
                    class="rounded-xl px-4 py-2 text-sm font-semibold transition"
                    :class="activeTab === 'roadmap' ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                    @click="activeTab = 'roadmap'"
                >
                    Roadmap
                </button>
            </div>

            <div v-if="activeTab === 'teams'" class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p v-if="!selectedTeam" class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                            Vista por grupos
                        </p>
                        <h2 v-if="!selectedTeam" class="text-2xl font-black text-slate-900 dark:text-white">
                            Grupos clasificados
                        </h2>
                        <div v-else>
                            <h2 class="text-4xl font-black text-slate-900 dark:text-white">{{ selectedTeamHeadline }}</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ selectedTeam.country_code || '---' }} · GRUPO {{ selectedTeam.group_name || '-' }}
                            </p>
                        </div>
                    </div>

                    <Link
                        v-if="selectedTeam"
                        :href="route('teams.profile')"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-emerald-400 dark:hover:text-emerald-300"
                    >
                        Regresar a grupos
                    </Link>
                </div>

                <div
                    v-if="selectedTeam"
                    class="flex items-center justify-between rounded-xl border-b border-slate-200 px-1 pb-3 dark:border-slate-700"
                >
                    <img
                        v-if="imageUrl(selectedTeam.shield_url)"
                        :src="imageUrl(selectedTeam.shield_url)"
                        :alt="selectedTeam.name"
                        class="h-40 w-40 rounded object-contain sm:h-48 sm:w-48"
                    >
                    <span v-else class="text-xs font-bold uppercase text-slate-500">Shield</span>
                    <img
                        v-if="imageUrl(selectedTeam.flag_url)"
                        :src="imageUrl(selectedTeam.flag_url)"
                        :alt="selectedTeam.name"
                        class="h-28 w-48 rounded object-cover sm:h-32 sm:w-56"
                    >
                    <span v-else class="inline-flex h-28 w-48 items-center justify-center rounded bg-slate-200 text-[10px] font-bold dark:bg-slate-700 sm:h-32 sm:w-56">
                        {{ selectedTeam.country_code || '---' }}
                    </span>
                </div>

                <Transition
                    mode="out-in"
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-1"
                >
                    <div v-if="!selectedTeam" key="groups-grid" class="grid gap-4 xl:grid-cols-2">
                        <section
                            v-for="group in groupedTeams"
                            :key="group.groupName"
                            class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900/70"
                        >
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                                Grupo {{ group.groupName }}
                            </p>

                            <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
                                <Link
                                    v-for="team in group.teams"
                                    :key="team.id"
                                    :href="route('teams.profile', team.id)"
                                    class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 transition hover:border-emerald-300 hover:bg-emerald-50/70 dark:border-slate-700 dark:bg-slate-900/70 dark:hover:border-emerald-500 dark:hover:bg-slate-900"
                                >
                                    <img
                                        v-if="imageUrl(team.flag_url)"
                                        :src="imageUrl(team.flag_url)"
                                        :alt="team.name"
                                        class="h-6 w-9 rounded object-cover"
                                    >
                                    <span v-else class="inline-flex h-6 w-9 items-center justify-center rounded bg-slate-200 text-[10px] font-bold dark:bg-slate-700">
                                        {{ team.country_code || '---' }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-slate-400">
                                            {{ team.country_code || '---' }}
                                        </p>
                                        <p class="truncate text-lg font-semibold leading-tight text-slate-900 dark:text-white">{{ team.name }}</p>
                                    </div>
                                </Link>
                            </div>
                        </section>
                    </div>

                    <div v-else key="team-detail" class="space-y-4">
                        <div class="grid gap-5 xl:grid-cols-[0.9fr_1.1fr]">
                            <article :class="panelClass" class="flex h-full flex-col rounded-2xl border border-slate-300/90 bg-slate-100 p-4 sm:p-5 dark:border-slate-800 dark:bg-slate-900/75">
                            <div class="flex flex-1 items-center">
                                <div class="grid w-full grid-cols-4 gap-2">
                                <div class="rounded-xl border border-slate-200 bg-white p-2 text-center dark:border-slate-700 dark:bg-slate-900/60">
                                    <p class="text-[10px] uppercase text-slate-500">FIFA</p>
                                    <p class="text-lg font-black text-slate-900 dark:text-white">{{ selectedTeam.fifa_ranking ?? '-' }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-2 text-center dark:border-slate-700 dark:bg-slate-900/60">
                                    <p class="text-[10px] uppercase text-slate-500">PJ</p>
                                    <p class="text-lg font-black text-slate-900 dark:text-white">{{ selectedTeam.group_stats?.played ?? 0 }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-2 text-center dark:border-slate-700 dark:bg-slate-900/60">
                                    <p class="text-[10px] uppercase text-slate-500">DG</p>
                                    <p class="text-lg font-black text-slate-900 dark:text-white">{{ selectedTeam.group_stats?.gd ?? 0 }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white p-2 text-center dark:border-slate-700 dark:bg-slate-900/60">
                                    <p class="text-[10px] uppercase text-slate-500">PTS</p>
                                    <p :class="accentClass" class="text-lg font-black">{{ selectedTeam.group_stats?.points ?? 0 }}</p>
                                </div>
                                </div>
                            </div>

                            <div class="mt-auto -mx-4 -mb-4 sm:-mx-5 sm:-mb-5">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-slate-200 text-[11px] uppercase tracking-[0.14em] text-slate-600 dark:bg-slate-800">
                                        <tr>
                                            <th class="px-3 py-2">#</th>
                                            <th class="px-3 py-2">Equipo</th>
                                            <th class="px-3 py-2 text-right">PTS</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-300 dark:divide-slate-700">
                                        <tr
                                            v-for="row in groupStandings"
                                            :key="row.team_id"
                                            :class="row.is_selected ? 'bg-emerald-50 dark:bg-emerald-500/10' : 'bg-white dark:bg-slate-900/60'"
                                        >
                                            <td class="px-3 py-2 font-bold">{{ row.position }}</td>
                                            <td class="px-3 py-2">{{ row.team_name }}</td>
                                            <td class="px-3 py-2 text-right font-black">{{ row.points }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-slate-200 dark:bg-slate-800">
                                        <tr>
                                            <td colspan="3" class="h-8" />
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            </article>

                            <div class="space-y-2">
                                <article
                                    v-for="match in groupStageMatches"
                                    :key="match.id"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-900/70"
                                >
                                    <div class="flex items-center justify-center">
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ match.stage_label }} · {{ match.match_date_label }} {{ match.match_time_label }}
                                        </p>
                                    </div>
                                    <div class="mt-2 grid grid-cols-[1fr_auto_1fr] items-center gap-2 overflow-visible">
                                        <div class="flex items-center gap-3 overflow-visible">
                                            <img
                                                v-if="imageUrl(match.home_team.shield_url || match.home_team.flag_url)"
                                                :src="imageUrl(match.home_team.shield_url || match.home_team.flag_url)"
                                                :alt="match.home_team.name"
                                                class="relative -top-[5px] h-16 w-16 shrink-0 rounded object-contain mix-blend-multiply dark:mix-blend-normal"
                                            >
                                            <span
                                                v-else
                                                class="relative -top-[5px] flex h-16 w-16 shrink-0 items-center justify-center rounded bg-slate-200 text-xs font-bold dark:bg-slate-700"
                                            >
                                                {{ match.home_team.country_code || match.home_team.code }}
                                            </span>
                                            <p class="text-sm font-semibold leading-tight text-slate-900 dark:text-white">
                                                {{ match.home_team.name }}
                                            </p>
                                        </div>

                                        <p class="px-2 text-xl font-black tracking-widest text-slate-900 dark:text-white">
                                            {{ Number.isInteger(match.home_score) ? match.home_score : '-' }}
                                            :
                                            {{ Number.isInteger(match.away_score) ? match.away_score : '-' }}
                                        </p>

                                        <div class="flex items-center justify-end gap-3 overflow-visible">
                                            <p class="text-right text-sm font-semibold leading-tight text-slate-900 dark:text-white">
                                                {{ match.away_team.name }}
                                            </p>
                                            <img
                                                v-if="imageUrl(match.away_team.shield_url || match.away_team.flag_url)"
                                                :src="imageUrl(match.away_team.shield_url || match.away_team.flag_url)"
                                                :alt="match.away_team.name"
                                                class="relative -top-[5px] h-16 w-16 shrink-0 rounded object-contain mix-blend-multiply dark:mix-blend-normal"
                                            >
                                            <span
                                                v-else
                                                class="relative -top-[5px] flex h-16 w-16 shrink-0 items-center justify-center rounded bg-slate-200 text-xs font-bold dark:bg-slate-700"
                                            >
                                                {{ match.away_team.country_code || match.away_team.code }}
                                            </span>
                                        </div>
                                    </div>
                                </article>
                                <p
                                    v-if="!groupStageMatches.length"
                                    class="rounded-xl border border-dashed border-slate-300 px-3 py-4 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400"
                                >
                                    No hay encuentros de fase de grupos para mostrar.
                                </p>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>

            <div v-else-if="activeTab === 'stadiums'" class="space-y-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Sedes del torneo</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Template inicial: al hacer click entras al detalle de la sede con encuentros, fechas y equipos.
                    </p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="stadium in venueCards"
                        :key="stadium.slug"
                        :href="route('stadiums.show', stadium.slug)"
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:border-emerald-300 dark:border-slate-700 dark:bg-slate-900/70"
                    >
                        <img
                            v-if="imageUrl(stadium.image_url)"
                            :src="imageUrl(stadium.image_url)"
                            :alt="stadium.venue"
                            class="h-32 w-full object-cover"
                        >
                        <div v-else class="flex h-32 items-center justify-center bg-[linear-gradient(120deg,_#0f172a,_#1e3a8a,_#0f172a)] px-3 text-center">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-200">Foto del estadio</p>
                        </div>
                        <div class="p-3">
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ stadium.venue }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {{ stadium.matches_count }} partidos · {{ stadium.first_match_date }} {{ stadium.first_match_time }}
                            </p>
                        </div>
                    </Link>
                </div>
            </div>

            <div v-else class="space-y-4">
                <article :class="panelClass" class="rounded-3xl border p-5">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Roadmap de fases</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Aqui organizaremos el avance fase a fase y luego la capa de jugadores por equipo.
                    </p>
                    <div class="mt-4 flex gap-3">
                        <Link
                            :href="route('groups.index')"
                            class="inline-flex items-center rounded-xl bg-cyan-500 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-400"
                        >
                            Ir al roadmap visual
                        </Link>
                    </div>
                </article>
            </div>
        </section>
    </UserDashboardLayout>
</template>
