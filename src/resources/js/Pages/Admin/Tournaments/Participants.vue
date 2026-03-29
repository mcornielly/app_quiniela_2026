<script setup>
import { computed, reactive } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Header from '@/Layouts/Partials/Header.vue'
import { notifyError, notifySuccess } from '@/Utils/notify'

const props = defineProps({
    tournament: Object,
    participants: Array,
    tiebreakers: {
        type: Object,
        default: () => ({
            use_fifa_ranking: false,
            use_fair_play: false,
        }),
    },
})

const showFifaRanking = computed(() => Boolean(props.tiebreakers?.use_fifa_ranking))
const showFairPlay = computed(() => Boolean(props.tiebreakers?.use_fair_play))

const forms = reactive(
    props.participants.reduce((acc, participant) => {
        acc[participant.id] = {
            fifa_ranking: participant.fifa_ranking ?? '',
            fair_play_points: participant.fair_play_points ?? 0,
            saving: false,
        }

        return acc
    }, {})
)

const saveParticipant = (participantId) => {
    const form = forms[participantId]
    form.saving = true

    router.patch(
        route('admin.tournaments.participants.update', {
            tournament: props.tournament.id,
            participant: participantId,
        }),
        {
            ...(showFifaRanking.value ? { fifa_ranking: form.fifa_ranking === '' ? null : Number(form.fifa_ranking) } : {}),
            ...(showFairPlay.value ? { fair_play_points: Number(form.fair_play_points) } : {}),
        },
        {
            preserveScroll: true,
            onSuccess: () => notifySuccess('Participant metrics updated'),
            onError: () => notifyError('Could not update participant metrics'),
            onFinish: () => {
                form.saving = false
            },
        }
    )
}
</script>

<template>
    <AdminLayout :title="`Participants - ${tournament.name}`">
        <main class="p-4 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <Header :title="`${tournament.name} Participants`" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Tournament-level metrics shared by group standings, best third-place ranking, and Round of 32 assignment.
                    </p>
                </div>

                <Link
                    :href="route('admin.tournaments.index')"
                    class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                >
                    Back to tournaments
                </Link>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/60">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">Team</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">Group</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">Position</th>
                                <th v-if="showFifaRanking" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">FIFA Ranking</th>
                                <th v-if="showFairPlay" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">Fair Play</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="participant in participants" :key="participant.id">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ participant.team_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ participant.country_name ?? 'Special slot' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ participant.group_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ participant.group_position ?? '-' }}</td>
                                <td v-if="showFifaRanking" class="px-4 py-3">
                                    <input
                                        v-model="forms[participant.id].fifa_ranking"
                                        type="number"
                                        min="1"
                                        max="999"
                                        class="w-28 rounded border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                                    >
                                </td>
                                <td v-if="showFairPlay" class="px-4 py-3">
                                    <input
                                        v-model="forms[participant.id].fair_play_points"
                                        type="number"
                                        max="0"
                                        class="w-28 rounded border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:placeholder-gray-400 dark:focus:border-blue-400 dark:focus:ring-blue-400"
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <button
                                        type="button"
                                        class="rounded bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-60"
                                        :disabled="forms[participant.id].saving"
                                        @click="saveParticipant(participant.id)"
                                    >
                                        {{ forms[participant.id].saving ? 'Saving...' : 'Save' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </AdminLayout>
</template>
