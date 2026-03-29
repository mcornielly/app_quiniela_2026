<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { imageUrl } from '@/Utils/image'

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    groups: {
        type: Array,
        default: () => [],
    },
    selectedGroup: {
        type: String,
        default: null,
    },
    matches: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['update:selectedGroup'])
const hiddenFlags = ref({})

const statusLabel = (status) => {
    if (status === 'FT') {
        return 'Finalizado'
    }

    if (status === 'LIVE') {
        return 'En vivo'
    }

    return 'Programado'
}

const statusBadgeClass = (status) => {
    if (status === 'FT') {
        return 'bg-green-100 text-green-800 dark:bg-green-900/60 dark:text-green-300'
    }

    if (status === 'LIVE') {
        return 'bg-red-100 text-red-800 dark:bg-red-900/60 dark:text-red-300'
    }

    return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'
}

const teamKey = (team, slot) => team?.id || team?.code || slot || 'unknown'
const getFlagSrc = (team) => team?.flag_url || imageUrl(team?.flag_path)
const shouldShowFlag = (team, slot) => Boolean(getFlagSrc(team)) && !hiddenFlags.value[teamKey(team, slot)]
const hideFlag = (team, slot) => {
    hiddenFlags.value = {
        ...hiddenFlags.value,
        [teamKey(team, slot)]: true,
    }
}

const teamName = (team, slot) => team?.name || slot || 'Por definir'
const actionHref = (match) => match.status === 'LIVE' ? route('admin.calendar.index') : route('admin.games.index')
const actionLabel = (match) => match.status === 'LIVE' ? 'Ver en vivo' : 'Resultados'
const centerDisplay = (match) => {
    if (match.status === 'FT' || match.status === 'LIVE') {
        return `${match.homeScore ?? '-'} - ${match.awayScore ?? '-'}`
    }

    return match.display_time ? String(match.display_time).slice(0, 5) : '--:--'
}
</script>

<template>
    <div class="relative overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-gray-700 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ title }}</h2>
                <p class="mt-1.5 text-sm font-normal text-gray-500 dark:text-gray-400">
                    Revisa resultados y calendario del grupo seleccionado.
                </p>
            </div>

            <div class="flex items-center gap-2 self-start">
                <span class="text-xs text-gray-500 dark:text-gray-400">Cambiar grupo:</span>
                <select
                    :value="selectedGroup"
                    class="block rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    @change="emit('update:selectedGroup', $event.target.value)"
                >
                    <option v-for="group in groups" :key="group.id" :value="String(group.id)">
                        {{ group.label }}
                    </option>
                </select>
            </div>
        </div>

        <table class="w-full table-fixed text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="border-b border-t border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300">
                <tr>
                    <th scope="col" class="w-[58%] px-6 py-3 text-center font-medium">Partidos</th>
                    <th scope="col" class="w-[25%] px-6 py-3 font-medium">Fecha y sede</th>
                    <th scope="col" class="w-[17%] px-6 py-3 text-right font-medium">Accion</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="match in matches"
                    :key="match.id"
                    class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
                >
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                        <div class="grid grid-cols-[minmax(0,1fr)_140px_minmax(0,1fr)] items-center gap-4">
                            <div class="flex min-w-0 items-center justify-end gap-2 pe-2 text-right">
                                <span class="truncate">{{ teamName(match.home_team, match.home_slot) }}</span>
                                <img
                                    v-if="shouldShowFlag(match.home_team, match.home_slot)"
                                    :src="getFlagSrc(match.home_team)"
                                    :alt="teamName(match.home_team, match.home_slot)"
                                    class="h-5 w-7 shrink-0 rounded object-cover"
                                    @error="hideFlag(match.home_team, match.home_slot)"
                                >
                            </div>

                            <div class="w-[140px] text-center">
                                <div class="flex justify-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-medium"
                                        :class="statusBadgeClass(match.status)"
                                    >
                                        {{ statusLabel(match.status) }}
                                    </span>
                                </div>
                                <div class="mt-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                                    {{ centerDisplay(match) }}
                                </div>
                            </div>

                            <div class="flex min-w-0 items-center gap-2 ps-2">
                                <img
                                    v-if="shouldShowFlag(match.away_team, match.away_slot)"
                                    :src="getFlagSrc(match.away_team)"
                                    :alt="teamName(match.away_team, match.away_slot)"
                                    class="h-5 w-7 shrink-0 rounded object-cover"
                                    @error="hideFlag(match.away_team, match.away_slot)"
                                >
                                <span class="truncate">{{ teamName(match.away_team, match.away_slot) }}</span>
                            </div>
                        </div>
                    </th>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        <div>{{ match.display_date }}</div>
                        <div class="mt-1">{{ match.venue || 'Sede por confirmar' }}</div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <Link :href="actionHref(match)" class="font-medium text-blue-700 hover:underline dark:text-blue-400">
                            {{ actionLabel(match) }}
                        </Link>
                    </td>
                </tr>
                <tr v-if="!matches.length" class="bg-white dark:bg-gray-800">
                    <td colspan="3" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        No hay partidos disponibles para este grupo.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

