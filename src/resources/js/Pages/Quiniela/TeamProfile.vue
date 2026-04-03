<script setup>
import { imageUrl } from '@/Utils/image'
import FixtureMatchCard from '@/Components/Quiniela/FixtureMatchCard.vue'

const props = defineProps({
    selectedTeam: { type: Object, default: null },
    groupStandings: { type: Array, default: () => [] },
    groupStageMatches: { type: Array, default: () => [] },
})
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="selectedTeam"
            class="flex items-center justify-between border-b border-slate-200 px-1 pb-3 dark:border-slate-700"
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

        <div class="grid gap-5 xl:grid-cols-[0.9fr_1.1fr]">
            <article class="flex h-full flex-col overflow-hidden rounded-2xl border border-slate-300/90 bg-white dark:border-slate-800 dark:bg-slate-900/75">
                <div>
                    <div class="bg-slate-300/40 px-4 py-3 sm:px-5 dark:bg-slate-700/40">
                        <p class="text-center text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                            Fase de grupo {{ selectedTeam?.group_name || '-' }}
                        </p>
                    </div>
                    <div class="border-b border-slate-200 dark:border-slate-700" />
                </div>
                <div class="p-4 sm:p-5">
                <div class="mb-4 pb-2 flex items-center">
                    <div class="mx-auto grid w-full grid-cols-4 gap-2 md:max-w-3xl">
                        <div class="flex h-16 flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-center dark:border-slate-700 dark:bg-slate-900/60">
                            <p class="text-[10px] uppercase text-slate-500">FIFA</p>
                            <p class="text-lg font-black text-slate-900 dark:text-white">{{ selectedTeam?.fifa_ranking ?? '-' }}</p>
                        </div>
                        <div class="flex h-16 flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-center dark:border-slate-700 dark:bg-slate-900/60">
                            <p class="text-[10px] uppercase text-slate-500">PJ</p>
                            <p class="text-lg font-black text-slate-900 dark:text-white">{{ selectedTeam?.group_stats?.played ?? 0 }}</p>
                        </div>
                        <div class="flex h-16 flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-center dark:border-slate-700 dark:bg-slate-900/60">
                            <p class="text-[10px] uppercase text-slate-500">DG</p>
                            <p class="text-lg font-black text-slate-900 dark:text-white">{{ selectedTeam?.group_stats?.gd ?? 0 }}</p>
                        </div>
                        <div class="flex h-16 flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-center dark:border-slate-700 dark:bg-slate-900/60">
                            <p class="text-[10px] uppercase text-slate-500">PTS</p>
                            <p class="text-lg font-black text-slate-900 dark:text-white">{{ selectedTeam?.group_stats?.points ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="-mx-4 -mb-4 mt-3 sm:-mx-5 sm:-mb-5 sm:mt-4">
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
                                <td class="px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ row.team_name }}</td>
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
                </div>
            </article>

            <div class="space-y-2">
                <FixtureMatchCard
                    v-for="match in groupStageMatches"
                    :key="match.id"
                    :match="match"
                />
                <p
                    v-if="!groupStageMatches.length"
                    class="rounded-xl border border-dashed border-slate-300 px-3 py-4 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400"
                >
                    No hay encuentros de fase de grupos para mostrar.
                </p>
            </div>
        </div>
    </div>
</template>
