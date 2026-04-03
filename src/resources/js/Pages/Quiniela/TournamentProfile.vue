<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'
import { imageUrl } from '@/Utils/image'
import TeamProfile from '@/Pages/Quiniela/TeamProfile.vue'
import StadiumProfile from '@/Pages/Quiniela/StadiumProfile.vue'

const props = defineProps({
    tournament: { type: Object, default: null },
    teams: { type: Array, default: () => [] },
    selectedTeam: { type: Object, default: null },
    groupStandings: { type: Array, default: () => [] },
    teamMatches: { type: Array, default: () => [] },
    venueCards: { type: Array, default: () => [] },
    selectedStadium: { type: Object, default: null },
    stadiumMatches: { type: Array, default: () => [] },
})

const page = usePage()
const favoriteTeamTheme = computed(() => page.props.auth?.user?.favorite_team_theme ?? null)
const favoriteTeamId = computed(() => page.props.auth?.user?.favorite_team_id ?? null)
const resolveTabFromUrl = (url) => {
    const urlString = String(url ?? '')
    if (urlString.includes('/quiniela/stadiums/')) {
        return 'stadiums'
    }

    const queryString = String(url ?? '').includes('?') ? String(url).split('?')[1] : ''
    const query = new URLSearchParams(queryString)
    const requestedTab = query.get('tab')

    if (requestedTab === 'stadiums' || requestedTab === 'roadmap') {
        return requestedTab
    }

    return 'teams'
}

const getCurrentLocationQuery = () => {
    if (typeof window === 'undefined') {
        return ''
    }

    return String(window.location.search ?? '')
}

const getInitialTab = () => {
    if (props.selectedStadium) {
        return 'stadiums'
    }

    const locationQuery = getCurrentLocationQuery()
    const locationTab = resolveTabFromUrl(locationQuery)

    if (locationTab !== 'teams') {
        return locationTab
    }

    return resolveTabFromUrl(page.url ?? '')
}

const activeTab = ref(getInitialTab())

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

const selectedTeamHeadline = computed(() => String(props.selectedTeam?.name ?? '').toUpperCase())
const groupStageMatches = computed(() => props.teamMatches.filter((match) => match.stage === 'group'))
const isFavoriteTeamSelected = computed(() => {
    if (!favoriteTeamId.value || !props.selectedTeam?.id) return false
    return Number(favoriteTeamId.value) === Number(props.selectedTeam.id)
})

const goToSelections = () => {
    if (props.selectedTeam) {
        router.visit(route('teams.profile'))
        return
    }

    activeTab.value = 'teams'
}

const goToStadiums = () => {
    if (props.selectedStadium) {
        router.visit(`${route('teams.profile')}?tab=stadiums`)
        return
    }

    activeTab.value = 'stadiums'
}

watch(
    () => page.url,
    (url) => {
        const locationQuery = getCurrentLocationQuery()
        const nextTab = resolveTabFromUrl(locationQuery || url)
        activeTab.value = nextTab
    }
)
</script>

<template>
    <Head title="Selecciones" />

    <UserDashboardLayout
        title="Selecciones"
        description="Organiza la informacion del torneo por equipos, estadios y roadmap."
        :ticker-class="activeTickerTheme.tickerClass"
    >
        <template #ticker><div class="h-12 w-full" aria-hidden="true" /></template>
        <template #headerContent><div class="hidden" /></template>

        <section class="space-y-6">
            <div class="-mt-8 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white">{{ tournament?.name || 'Selecciones' }}</h1>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Mundial {{ tournament?.year ?? '' }}</p>
                </div>
                <Link
                    :href="route('dashboard')"
                    class="inline-flex shrink-0 items-center whitespace-nowrap rounded-md px-2 py-1.5 text-xs font-bold uppercase tracking-wide text-primary-700 transition hover:bg-gray-200 dark:text-primary-500 dark:hover:bg-gray-600"
                >
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Regresar
                </Link>
            </div>

            <div class="border-b border-slate-200 dark:border-slate-800">
                <ul class="-mb-px flex flex-wrap text-center text-sm font-medium text-slate-600 dark:text-slate-300">
                    <li v-if="favoriteTeamId" class="me-2">
                        <Link
                            :href="route('teams.profile', favoriteTeamId)"
                            class="group inline-flex items-center justify-center rounded-t-xl border-b px-4 py-4 leading-none transition"
                            :class="isFavoriteTeamSelected
                                ? 'border-primary-500 text-primary-700 dark:border-primary-400 dark:text-primary-300'
                                : 'border-transparent hover:border-primary-300 hover:text-primary-700 dark:hover:border-primary-500 dark:hover:text-primary-300'"
                        >
                            <span class="me-2 inline-flex h-6 w-6 shrink-0 items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="h-5 w-5 fill-current" aria-hidden="true">
                                    <path d="M0 32C0 14.3 14.3 0 32 0L352 0c17.7 0 32 14.3 32 32l0 192c0 112.3-75.4 210.2-183.7 242.4c-5.4 1.6-11.2 1.6-16.6 0C75.4 434.2 0 336.3 0 224L0 32z"/>
                                </svg>
                            </span>
                            <span class="text-xs font-semibold uppercase tracking-[0.2em]">Mi equipo</span>
                        </Link>
                    </li>
                    <li class="me-2">
                        <button
                            type="button"
                            class="group inline-flex items-center justify-center rounded-t-xl border-b px-4 py-4 leading-none transition"
                            :class="activeTab === 'teams' && !isFavoriteTeamSelected
                                ? 'border-primary-500 text-primary-700 dark:border-primary-400 dark:text-primary-300'
                                : 'border-transparent hover:border-primary-300 hover:text-primary-700 dark:hover:border-primary-500 dark:hover:text-primary-300'"
                            @click="goToSelections"
                        >
                            <span class="me-2 inline-flex h-6 w-6 shrink-0 items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" class="h-6 w-6 fill-current" aria-hidden="true">
                                    <path d="M320 16a104 104 0 1 1 0 208 104 104 0 1 1 0-208zM96 88a72 72 0 1 1 0 144 72 72 0 1 1 0-144zM0 416c0-70.7 57.3-128 128-128 12.8 0 25.2 1.9 36.9 5.4-32.9 36.8-52.9 85.4-52.9 138.6l0 16c0 11.4 2.4 22.2 6.7 32L32 480c-17.7 0-32-14.3-32-32l0-32zm521.3 64c4.3-9.8 6.7-20.6 6.7-32l0-16c0-53.2-20-101.8-52.9-138.6 11.7-3.5 24.1-5.4 36.9-5.4 70.7 0 128 57.3 128 128l0 32c0 17.7-14.3 32-32 32l-86.7 0zM472 160a72 72 0 1 1 144 0 72 72 0 1 1 -144 0zM160 432c0-88.4 71.6-160 160-160s160 71.6 160 160l0 16c0 17.7-14.3 32-32 32l-256 0c-17.7 0-32-14.3-32-32l0-16z"/>
                                </svg>
                            </span>
                            <span class="text-xs font-semibold uppercase tracking-[0.2em]">Selecciones</span>
                        </button>
                    </li>
                    <li class="me-2">
                        <button
                            type="button"
                            class="group inline-flex items-center justify-center rounded-t-xl border-b px-4 py-4 leading-none transition"
                            :class="activeTab === 'stadiums'
                                ? 'border-primary-500 text-primary-700 dark:border-primary-400 dark:text-primary-300'
                                : 'border-transparent hover:border-primary-300 hover:text-primary-700 dark:hover:border-primary-500 dark:hover:text-primary-300'"
                            @click="goToStadiums"
                        >
                            <img
                                src="/noun-stadium.png"
                                alt=""
                                aria-hidden="true"
                                class="me-2 h-6 w-6 shrink-0 object-contain"
                                :class="activeTab === 'stadiums' ? 'opacity-95' : 'opacity-75'"
                            >
                            <span class="text-xs font-semibold uppercase tracking-[0.2em]">Estadios</span>
                        </button>
                    </li>
                    <li class="me-2">
                        <button
                            type="button"
                            class="group inline-flex items-center justify-center rounded-t-xl border-b px-4 py-4 leading-none transition"
                            :class="activeTab === 'roadmap'
                                ? 'border-primary-500 text-primary-700 dark:border-primary-400 dark:text-primary-300'
                                : 'border-transparent hover:border-primary-300 hover:text-primary-700 dark:hover:border-primary-500 dark:hover:text-primary-300'"
                            @click="activeTab = 'roadmap'"
                        >
                            <span class="me-2 inline-flex h-6 w-6 shrink-0 items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="h-5 w-5 fill-current" aria-hidden="true">
                                    <path d="M512 48c0-11.1-5.7-21.4-15.2-27.2s-21.2-6.4-31.1-1.4L349.5 77.5 170.1 17.6c-8.1-2.7-16.8-2.1-24.4 1.7l-128 64C6.8 88.8 0 99.9 0 112L0 464c0 11.1 5.7 21.4 15.2 27.2s21.2 6.4 31.1 1.4l116.1-58.1 179.4 59.8c8.1 2.7 16.8 2.1 24.4-1.7l128-64c10.8-5.4 17.7-16.5 17.7-28.6l0-352zM192 376.9l0-284.5 128 42.7 0 284.5-128-42.7z"/>
                                </svg>
                            </span>
                            <span class="text-xs font-semibold uppercase tracking-[0.2em]">Roadmap</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Teams Content for each tab is conditionally rendered below based on the value of activeTab -->
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
                        v-if="selectedTeam && !isFavoriteTeamSelected"
                        :href="route('teams.profile')"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-emerald-400 dark:hover:text-emerald-300"
                    >
                        Selecciones
                    </Link>
                </div>
                <!-- TournamentProfile decides list/detail; TeamProfile renders only selected team detail -->
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

                    <TeamProfile
                        v-else
                        key="team-detail"
                        :selected-team="selectedTeam"
                        :group-standings="groupStandings"
                        :group-stage-matches="groupStageMatches"
                    />
                </Transition>
            </div>

            <!-- Stadiums Content for each tab is conditionally rendered below based on the value of activeTab -->
            <div v-else-if="activeTab === 'stadiums'" class="space-y-4">
                <template v-if="selectedStadium">
                    <StadiumProfile
                        :stadium="selectedStadium"
                        :matches="stadiumMatches"
                    />
                </template>
                <template v-else>
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
                </template>
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
