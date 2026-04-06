<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AppDatePicker from '@/Components/UI/AppDatePicker.vue'
import AdminResultCard from '@/Components/Admin/Dashboard/AdminResultCard.vue'
import AdminGroupMatchesTable from '@/Components/Admin/Dashboard/AdminGroupMatchesTable.vue'
import AdminTableSection from '@/Components/Admin/Dashboard/AdminTableSection.vue'
import { imageUrl } from '@/Utils/image'

const title = 'Dashboard'

const props = defineProps({
    todayLabel: {
        type: String,
        default: '',
    },
    todayIso: {
        type: String,
        default: '',
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
    groupStageMatches: {
        type: Array,
        default: () => [],
    },
    groups: {
        type: Array,
        default: () => [],
    },
    standingsByGroup: {
        type: Object,
        default: () => ({}),
    },
    ranking: {
        type: Array,
        default: () => [],
    },
    tournament: {
        type: Object,
        default: null,
    },
})

const selectedGroup = ref(props.groups?.[0] ? String(props.groups[0].id) : null)
const selectedDate = ref(props.todayIso || '')
const hiddenFlags = ref({})

const selectedGroupData = computed(() => {
    return props.groups.find((group) => String(group.id) === selectedGroup.value) ?? null
})

const filteredResultMatches = computed(() => {
    let matches = props.resultMatches

    if (selectedDate.value) {
        matches = props.resultMatches.filter((match) => match.matchDateIso === selectedDate.value)
    }

    // Sort by time descending (most recent first)
    return [...matches].sort((a, b) => {
        const timeA = a.matchTimeIso || '00:00:00'
        const timeB = b.matchTimeIso || '00:00:00'
        return timeB.localeCompare(timeA)
    })
})

const displayedResults = computed(() => {
    if (filteredResultMatches.value.length) {
        return filteredResultMatches.value
    }

    return props.featuredResults
})

const groupMatches = computed(() => {
    return props.groupStageMatches.filter((match) => String(match.groupId) === selectedGroup.value)
})

const currentStandings = computed(() => {
    if (!selectedGroup.value) {
        return []
    }

    return props.standingsByGroup?.[selectedGroup.value] ?? props.standingsByGroup?.[Number(selectedGroup.value)] ?? []
})

const rankingUpdatedAt = computed(() => props.ranking?.[0]?.updatedAt ?? '-')

const selectedDateLabel = computed(() => {
    if (!selectedDate.value) {
        return props.todayLabel || '-'
    }

    return new Date(`${selectedDate.value}T00:00:00`).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    })
})

const upcomingActionHref = (game) => {
    return game.status === 'LIVE' ? route('admin.calendar.index') : route('admin.games.index')
}

const formatMatchTime = (value) => {
    if (!value) {
        return '--:--'
    }

    return String(value).slice(0, 5)
}

const teamKey = (team) => team?.id || team?.code || team?.name || 'unknown'
const getFlagSrc = (team) => team?.flag_url || imageUrl(team?.flag_path)
const shouldShowFlag = (team) => Boolean(getFlagSrc(team)) && !hiddenFlags.value[teamKey(team)]
const hideFlag = (team) => {
    hiddenFlags.value = {
        ...hiddenFlags.value,
        [teamKey(team)]: true,
    }
}

const formatGoalDiff = (value) => {
    if (value > 0) {
        return `+${value}`
    }

    return `${value}`
}
</script>

<template>
    <AdminLayout :title="title">
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900" style="will-change: auto">
            <div class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="w-full px-4 py-5 sm:px-6 lg:px-8 2xl:px-10">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                                Dashboard - Quiniela Mundial 2026
                            </h1>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ tournament?.name }} - {{ tournament?.year }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <Link
                                :href="route('admin.calendar.index')"
                                class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700"
                            >
                                Ver calendario
                            </Link>

                            <Link
                                :href="route('predictions.worldcup')"
                                class="inline-flex items-center rounded-lg bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                            >
                                Ir a mi quiniela
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid w-full grid-cols-1 gap-6 px-4 py-6 sm:px-6 lg:grid-cols-12 lg:px-8 2xl:px-10">
                <div class="space-y-6 lg:col-span-7 xl:col-span-8">
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Resultados del dia</h2>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Fecha seleccionada: {{ selectedDateLabel }}</span>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <AppDatePicker v-model="selectedDate" placeholder="Seleccionar fecha" />
                                <Link
                                    :href="route('admin.calendar.index')"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke="currentColor"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Z"
                                        />
                                    </svg>
                                    Ver calendario
                                </Link>
                            </div>
                        </div>

                        <div v-if="displayedResults.length" class="flex flex-col gap-4">
                            <AdminResultCard
                                v-for="match in displayedResults"
                                :key="match.id"
                                :match="match"
                                :action-href="match.status === 'LIVE' ? route('admin.calendar.index') : route('admin.games.index')"
                            />
                        </div>

                        <div v-else class="rounded-lg border border-dashed border-gray-200 p-6 text-center dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">No hay resultados finales para la fecha seleccionada.</p>
                        </div>
                    </div>

                    <AdminTableSection
                        title="Proximos juegos"
                        description="Consulta los siguientes encuentros programados y navega rapido a resultados o calendario."
                    >
                        <template #actions>
                            <Link :href="route('admin.calendar.index')" class="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">
                                Ver todos
                            </Link>
                        </template>

                        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="border-b border-t border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Fecha</th>
                                    <th class="px-6 py-3 text-center font-medium">Partidos</th>
                                    <th class="px-6 py-3 font-medium">Grupo</th>
                                    <th class="px-6 py-3 font-medium">Sede</th>
                                    <th class="px-6 py-3 text-right font-medium">Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="game in upcomingGames" :key="game.id" class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        <div class="font-medium">{{ game.date }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        <div class="grid grid-cols-[minmax(0,1fr)_88px_minmax(0,1fr)] items-center gap-4">
                                            <div class="flex min-w-0 items-center justify-end gap-2 pe-2 text-right">
                                                <span class="truncate">{{ game.homeTeam?.name }}</span>
                                                <img v-if="game.homeTeam?.flag_url" :src="game.homeTeam.flag_url" :alt="game.homeTeam.name" class="h-5 w-7 shrink-0 rounded object-cover">
                                                <span v-else class="text-xs font-semibold uppercase text-gray-400">{{ game.homeTeam?.code }}</span>
                                            </div>
                                            <div class="text-center text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                                                {{ formatMatchTime(game.time) }}
                                            </div>
                                            <div class="flex min-w-0 items-center gap-2 ps-2">
                                                <img v-if="game.awayTeam?.flag_url" :src="game.awayTeam.flag_url" :alt="game.awayTeam.name" class="h-5 w-7 shrink-0 rounded object-cover">
                                                <span v-else class="text-xs font-semibold uppercase text-gray-400">{{ game.awayTeam?.code }}</span>
                                                <span class="truncate">{{ game.awayTeam?.name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">Grupo {{ game.groupName || '-' }}</td>
                                    <td class="px-6 py-4">{{ game.venue || 'Sede por confirmar' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <Link :href="upcomingActionHref(game)" class="font-medium text-blue-700 hover:underline dark:text-blue-400">
                                            {{ game.actionLabel }}
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!upcomingGames.length" class="bg-white dark:bg-gray-800">
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No hay juegos programados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </AdminTableSection>

                    <AdminGroupMatchesTable
                        :title="`Partidos - ${selectedGroupData?.label ?? 'Grupo'}`"
                        :groups="groups"
                        :selected-group="selectedGroup"
                        :matches="groupMatches"
                        @update:selected-group="selectedGroup = $event"
                    />
                </div>

                <div class="space-y-6 lg:col-span-5 xl:col-span-4">
                    <AdminTableSection
                        title="Tabla de grupos"
                        description="Posiciones actuales del grupo seleccionado con sus estadisticas principales."
                    >
                        <template #actions>
                            <Link :href="route('admin.groups.index')" class="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">
                                Ver todas
                            </Link>
                        </template>

                        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="border-b border-t border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-6 py-3 font-medium">#</th>
                                    <th class="px-6 py-3 font-medium">Equipo</th>
                                    <th class="px-6 py-3 text-right font-medium">PJ</th>
                                    <th class="px-6 py-3 text-right font-medium">GF</th>
                                    <th class="px-6 py-3 text-right font-medium">GC</th>
                                    <th class="px-6 py-3 text-right font-medium">DG</th>
                                    <th class="px-6 py-3 text-right font-medium">Pts</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, index) in currentStandings" :key="row.teamId || row.team?.id || index" class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ index + 1 }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-2">
                                            <img
                                                v-if="shouldShowFlag(row.team)"
                                                :src="getFlagSrc(row.team)"
                                                :alt="row.team?.name || row.team?.code"
                                                class="h-5 w-7 rounded object-cover"
                                                @error="hideFlag(row.team)"
                                            >
                                            <span v-else class="text-xs font-semibold uppercase text-gray-400">{{ row.team?.code }}</span>
                                            <span>{{ row.team?.name || row.team?.code }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">{{ row.played }}</td>
                                    <td class="px-6 py-4 text-right">{{ row.gf }}</td>
                                    <td class="px-6 py-4 text-right">{{ row.ga }}</td>
                                    <td class="px-6 py-4 text-right" :class="row.gd > 0 ? 'text-emerald-600 dark:text-emerald-400' : row.gd < 0 ? 'text-red-600 dark:text-red-400' : ''">{{ formatGoalDiff(row.gd) }}</td>
                                    <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">{{ row.points }}</td>
                                </tr>
                                <tr v-if="!currentStandings.length" class="bg-white dark:bg-gray-800">
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No hay posiciones disponibles.</td>
                                </tr>
                            </tbody>
                        </table>
                    </AdminTableSection>

                    <AdminTableSection
                        title="Quiniela 2026 - Top 15"
                        :description="`Ranking por puntos - Actualizado: ${rankingUpdatedAt}`"
                    >
                        <template #actions>
                            <Link :href="route('admin.pools.index')" class="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">
                                Ver lista completa
                            </Link>
                        </template>

                        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="border-b border-t border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Pos</th>
                                    <th class="px-6 py-3 font-medium">Usuario</th>
                                    <th class="px-6 py-3 text-right font-medium text-emerald-600 dark:text-emerald-400">Exactos</th>
                                    <th class="px-6 py-3 text-right font-medium text-sky-600 dark:text-sky-400">Aciertos</th>
                                    <th class="px-6 py-3 text-right font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(entry, index) in ranking" :key="entry.id" class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">{{ index + 1 }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ entry.name }}</td>
                                    <td class="px-6 py-4 text-right font-semibold" :class="entry.exactHits === 0 ? 'text-gray-400 dark:text-gray-500' : 'text-emerald-600 dark:text-emerald-400'">{{ entry.exactHits }}</td>
                                    <td class="px-6 py-4 text-right font-semibold" :class="entry.correctResults === 0 ? 'text-gray-400 dark:text-gray-500' : 'text-sky-600 dark:text-sky-400'">{{ entry.correctResults }}</td>
                                    <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">{{ entry.totalPoints }}</td>
                                </tr>
                                <tr v-if="!ranking.length" class="bg-white dark:bg-gray-800">
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">No hay entradas en el ranking.</td>
                                </tr>
                            </tbody>
                        </table>
                    </AdminTableSection>

                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Equipos por grupo</h2>
                            <Link :href="route('admin.groups.index')" class="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">
                                Administrar torneo
                            </Link>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <button
                                v-for="group in groups"
                                :key="group.id"
                                type="button"
                                @click="selectedGroup = String(group.id)"
                                class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-left hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700"
                                :class="selectedGroup === String(group.id) ? 'ring-2 ring-blue-300 dark:ring-blue-800' : ''"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ group.label }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-300">Ver</div>
                                </div>
                                <div class="mt-2 space-y-2 text-xs text-gray-600 dark:text-gray-200">
                                    <div v-for="team in group.teams" :key="team.id" class="flex items-center gap-2 truncate">
                                        <img v-if="team.flag_url" :src="team.flag_url" :alt="team.name" class="h-4 w-6 rounded object-cover">
                                        <span v-else class="text-[10px] font-semibold uppercase text-gray-400">{{ team.code }}</span>
                                        <span class="truncate">{{ team.name }}</span>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>




