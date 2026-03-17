<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import AppTooltip from '@/Components/UI/AppTooltip.vue'
import { imageUrl } from '@/Utils/image'

const props = defineProps({
    groupName: {
        type: String,
        required: true,
    },
    standings: {
        type: Array,
        required: true,
    },
    showExtendedStats: {
        type: Boolean,
        default: true,
    },
})

const hiddenFlags = ref({})
const isWideScreen = ref(false)

const statColumns = [
    { key: 'played', short: 'PJ', description: 'Partidos jugados' },
    { key: 'won', short: 'G', description: 'Partidos ganados', extended: true },
    { key: 'drawn', short: 'E', description: 'Partidos empatados', extended: true },
    { key: 'lost', short: 'P', description: 'Partidos perdidos', extended: true },
    { key: 'gf', short: 'GF', description: 'Goles a favor', extended: true },
    { key: 'ga', short: 'GC', description: 'Goles en contra', extended: true },
    { key: 'gd', short: 'DG', description: 'Diferencia de goles' },
    { key: 'points', short: 'PTS', description: 'Puntos' },
]

const teamKey = (team) => team?.id || team?.code || team?.name || 'unknown'
const getFlagSrc = (team) => team?.flag_url || imageUrl(team?.flag_path)
const shouldShowFlag = (team) => Boolean(getFlagSrc(team)) && !hiddenFlags.value[teamKey(team)]
const hideFlag = (team) => {
    hiddenFlags.value = {
        ...hiddenFlags.value,
        [teamKey(team)]: true,
    }
}

const updateViewportState = () => {
    isWideScreen.value = window.innerWidth >= 1280
}

const visibleColumns = computed(() => {
    return statColumns.filter((column) => {
        if (!column.extended) {
            return true
        }

        return props.showExtendedStats && isWideScreen.value
    })
})

const wrapperClass = computed(() => {
    return isWideScreen.value ? 'overflow-x-visible' : 'overflow-x-auto'
})

const tableClass = computed(() => {
    return isWideScreen.value ? 'w-full table-auto' : 'min-w-[720px] w-full table-auto'
})

onMounted(() => {
    updateViewportState()
    window.addEventListener('resize', updateViewportState)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateViewportState)
})
</script>

<template>
    <div class="w-full overflow-hidden rounded-3xl border border-slate-200/80 bg-white/95 p-0 shadow-lg shadow-slate-200/60 backdrop-blur-xl transition-all duration-300 hover:border-primary-300 dark:border-slate-800 dark:bg-slate-900/85 dark:shadow-none dark:hover:border-primary-500/40">
        <div class="flex items-center justify-between border-b border-slate-200/80 bg-slate-50/90 px-4 py-3 dark:border-white/10 dark:bg-black/40">
            <h3 class="text-sm font-bold tracking-widest text-slate-900 dark:text-white">GRUPO {{ groupName }}</h3>
            <span class="text-xs font-medium tracking-wide text-slate-400 dark:text-gray-500">PTS</span>
        </div>
        <div :class="wrapperClass">
            <table :class="tableClass" class="text-left text-sm text-slate-600 dark:text-gray-300">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-400 dark:bg-black/20 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="w-8 px-3 py-2 text-center">#</th>
                        <th scope="col" class="w-full px-2 py-2">Equipo</th>
                        <th
                            v-for="column in visibleColumns"
                            :key="column.key"
                            scope="col"
                            class="px-1.5 py-2 text-center"
                            :class="column.key === 'points' ? 'text-primary-600 dark:text-cyan-300' : ''"
                        >
                            <AppTooltip :text="column.description" placement="top">
                                <span class="inline-flex cursor-default">{{ column.short }}</span>
                            </AppTooltip>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, index) in standings"
                        :key="row.team.code"
                        class="border-b border-slate-100 transition-colors duration-200 last:border-0 hover:bg-slate-50 dark:border-white/5 dark:hover:bg-white/10"
                        :class="{ 'bg-primary-50/80 dark:bg-blue-500/10': index < 2 }"
                    >
                        <td class="px-3 py-2.5 text-center font-bold" :class="index < 2 ? 'text-primary-600 dark:text-cyan-300' : 'text-slate-400 dark:text-gray-500'">
                            {{ index + 1 }}
                        </td>
                        <td class="px-2 py-2.5 font-bold text-slate-900 dark:text-white">
                            <AppTooltip :text="row.team.name || row.team.code" placement="top">
                                <div class="flex items-center space-x-2">
                                    <img
                                        v-if="shouldShowFlag(row.team)"
                                        :src="getFlagSrc(row.team)"
                                        :alt="row.team.name || row.team.code"
                                        class="h-5 w-7 rounded object-cover shadow-sm shadow-slate-200 dark:shadow-black/30"
                                        @error="hideFlag(row.team)"
                                    >
                                    <span v-else class="text-lg drop-shadow-sm text-slate-500 dark:text-slate-300">{{ row.team.flag }}</span>
                                    <span class="tracking-wide">{{ row.team.code }}</span>
                                </div>
                            </AppTooltip>
                        </td>
                        <td
                            v-for="column in visibleColumns"
                            :key="`${row.team.code}-${column.key}`"
                            class="px-1.5 py-2.5 text-center"
                            :class="[
                                column.key === 'points' ? 'text-lg font-black text-primary-600 dark:text-cyan-300' : 'text-slate-500 dark:text-gray-400',
                                column.key === 'gd' && row.gd > 0 ? 'text-emerald-400' : '',
                                column.key === 'gd' && row.gd < 0 ? 'text-red-400' : '',
                            ]"
                        >
                            <template v-if="column.key === 'gd'">
                                {{ row.gd > 0 ? '+' : '' }}{{ row.gd }}
                            </template>
                            <template v-else>
                                {{ row[column.key] }}
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200/80 bg-slate-50/90 py-2 text-center text-[10px] uppercase tracking-widest text-slate-400 dark:border-white/5 dark:bg-black/40 dark:text-gray-500">
            Top 2 avanza a Round 32
        </div>
    </div>
</template>
