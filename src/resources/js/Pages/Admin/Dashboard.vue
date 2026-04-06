<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AppDatePicker from '@/Components/UI/AppDatePicker.vue'
import AdminResultCard from '@/Components/Admin/Dashboard/AdminResultCard.vue'
import AdminGroupMatchesTable from '@/Components/Admin/Dashboard/AdminGroupMatchesTable.vue'
import AdminTableSection from '@/Components/Admin/Dashboard/AdminTableSection.vue'
import { imageUrl } from '@/Utils/image'
import { formatGoalDiff, formatCurrency } from '@/Utils/format'

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
    poolStats: {
        type: Object,
        default: () => ({
            total: 0,
            paid: 0,
            pending: 0,
            revenue: 0
        }),
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
</script>

<template>
    <AdminLayout :title="title">
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <div class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="w-full px-4 py-5 sm:px-6 lg:px-8 2xl:px-10">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                                Dashboard Administración
                            </h1>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ tournament?.name }} - {{ tournament?.year }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pool Metrics -->
            <div class="w-full px-4 py-6 sm:px-6 lg:px-8 2xl:px-10">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Quinielas</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ poolStats.total }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pagadas</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ poolStats.paid }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pendientes</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ poolStats.pending }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12V8a2 2 0 00-2-2H6a2 2 0 00-2 2v4m16 0l-4 4m4-4l-4-4m-12 12l4-4m-4 4l4 4"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ingresos (Aproximado)</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(poolStats.revenue) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid w-full grid-cols-1 gap-6 px-4 py-6 sm:px-6 lg:grid-cols-12 lg:px-8 2xl:px-10">
                <div class="space-y-6 lg:col-span-7 xl:col-span-8">
                    <!-- Resultados del Dia -->
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="mb-4 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Resultados del día</h2>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Fecha: {{ selectedDateLabel }}</span>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <AppDatePicker v-model="selectedDate" placeholder="Seleccionar fecha" />
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

                    <!-- Proximos Juegos -->
                    <AdminTableSection
                        title="Próximos Juegos"
                        description="Lista de los siguientes encuentros programados."
                    >
                        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="border-b border-t border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Fecha/Hora</th>
                                    <th class="px-6 py-3 text-center font-medium">Partidos</th>
                                    <th class="px-6 py-3 font-medium">Sede</th>
                                    <th class="px-6 py-3 text-right font-medium">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="game in upcomingGames" :key="game.id" class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <td class="px-6 py-4">
                                        <div class="font-medium">{{ game.date }}</div>
                                        <div class="text-xs text-gray-500">{{ formatMatchTime(game.time) }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <div class="grid grid-cols-[minmax(0,1fr)_40px_minmax(0,1fr)] items-center gap-2">
                                            <div class="flex items-center justify-end gap-2 text-right">
                                                <span class="truncate">{{ game.homeTeam?.name }}</span>
                                                <img v-if="game.homeTeam?.flag_url" :src="game.homeTeam.flag_url" class="h-4 w-6 rounded shadow-sm">
                                            </div>
                                            <div class="text-center font-bold">vs</div>
                                            <div class="flex items-center gap-2">
                                                <img v-if="game.awayTeam?.flag_url" :src="game.awayTeam.flag_url" class="h-4 w-6 rounded shadow-sm">
                                                <span class="truncate">{{ game.awayTeam?.name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">{{ game.venue }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <Link :href="upcomingActionHref(game)" class="text-blue-600 hover:underline">Gestionar</Link>
                                    </td>
                                </tr>
                                <tr v-if="!upcomingGames.length">
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-400">Sin juegos próximos</td>
                                </tr>
                            </tbody>
                        </table>
                    </AdminTableSection>
                </div>

                <!-- Lado Derecho: Ranking y Grupos -->
                <div class="space-y-6 lg:col-span-5 xl:col-span-4">
                    <!-- Top Quiniela -->
                    <AdminTableSection
                        title="Ranking Quiniela - Top 15"
                        :description="`Ranking actual de punteros. Actualizado: ${rankingUpdatedAt}`"
                    >
                        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="border-b border-t border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Pos</th>
                                    <th class="px-6 py-3 font-medium">Usuario</th>
                                    <th class="px-6 py-3 text-right font-medium">Pts</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(entry, index) in ranking" :key="entry.id" :class="[
                                    'border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800',
                                    index === 0 ? 'bg-yellow-50/50 dark:bg-yellow-900/10' : '',
                                    index === 1 ? 'bg-slate-50/50 dark:bg-slate-900/10' : '',
                                    index === 2 ? 'bg-orange-50/50 dark:bg-orange-900/10' : ''
                                ]">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span v-if="index === 0" class="flex h-5 w-5 items-center justify-center rounded-full bg-yellow-400 text-[10px] font-bold text-yellow-900">1º</span>
                                            <span v-else-if="index === 1" class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-300 text-[10px] font-bold text-slate-900">2º</span>
                                            <span v-else-if="index === 2" class="flex h-5 w-5 items-center justify-center rounded-full bg-orange-400 text-[10px] font-bold text-orange-900">3º</span>
                                            <span v-else>{{ index + 1 }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ entry.name }}</td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">{{ entry.totalPoints }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </AdminTableSection>

                    <!-- Grupos Quick View -->
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Equipos por grupo</h2>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="group in groups"
                                :key="group.id"
                                @click="selectedGroup = String(group.id)"
                                class="rounded-lg border border-gray-200 bg-gray-50 p-2 text-left text-xs hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600"
                                :class="selectedGroup === String(group.id) ? 'ring-2 ring-blue-500' : ''"
                            >
                                <span class="font-bold text-gray-800 dark:text-white">{{ group.label }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
