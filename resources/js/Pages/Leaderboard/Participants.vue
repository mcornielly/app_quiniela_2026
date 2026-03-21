<script setup>
import { computed, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'

const props = defineProps({
    tournament: {
        type: Object,
        default: null,
    },
    participants: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const currentUserId = computed(() => Number(page.props.auth?.user?.id ?? 0))
const search = ref('')
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

const filteredParticipants = computed(() => {
    const term = search.value.trim().toLowerCase()

    if (!term) {
        return props.participants
    }

    return props.participants.filter((participant) => (participant.name || '').toLowerCase().includes(term))
})
</script>

<template>
    <Head title="Ranking Participantes" />

    <UserDashboardLayout
        title="Ranking de Participantes"
        description="Clasificacion general de participantes del Mundial 2026."
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
                    <svg class="h-8 w-8 text-emerald-500 dark:text-emerald-400" fill="currentColor" viewBox="0 0 640 512" aria-hidden="true">
                        <path d="M320 16a104 104 0 1 1 0 208 104 104 0 1 1 0-208zM96 88a72 72 0 1 1 0 144 72 72 0 1 1 0-144zM0 416c0-70.7 57.3-128 128-128 12.8 0 25.2 1.9 36.9 5.4-32.9 36.8-52.9 85.4-52.9 138.6l0 16c0 11.4 2.4 22.2 6.7 32L32 480c-17.7 0-32-14.3-32-32l0-32zm521.3 64c4.3-9.8 6.7-20.6 6.7-32l0-16c0-53.2-20-101.8-52.9-138.6 11.7-3.5 24.1-5.4 36.9-5.4 70.7 0 128 57.3 128 128l0 32c0 17.7-14.3 32-32 32l-86.7 0zM472 160a72 72 0 1 1 144 0 72 72 0 1 1 -144 0zM160 432c0-88.4 71.6-160 160-160s160 71.6 160 160l0 16c0 17.7-14.3 32-32 32l-256 0c-17.7 0-32-14.3-32-32l0-16z" />
                    </svg>
                    <h1>Ranking Participantes</h1>
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
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative w-full sm:max-w-xs">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6 6a7.5 7.5 0 0 0 10.65 10.65Z" />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Buscar quiniela..."
                            class="block w-full rounded-xl border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 focus:border-cyan-500 focus:outline-none focus:ring-1 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-400"
                        >
                    </div>
                    <p class="text-sm leading-tight text-slate-500 dark:text-slate-400 sm:text-right">{{ filteredParticipants.length }} participante(s) en ranking</p>
                </div>
                <div class="mt-[6px] border-b border-slate-300 dark:border-slate-700" />
            </div>

            <div v-if="filteredParticipants.length" class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/75">
                <table class="min-w-full text-left text-sm text-slate-700 dark:text-slate-200">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800 dark:bg-slate-800/70 dark:text-slate-300">
                        <tr>
                            <th class="px-6 py-3 font-medium">Pos</th>
                            <th class="px-6 py-3 font-medium">Participante</th>
                            <th class="px-6 py-3 text-right font-medium">Quinielas</th>
                            <th class="px-6 py-3 text-right font-medium text-emerald-600 dark:text-emerald-400">EXA</th>
                            <th class="px-6 py-3 text-right font-medium text-sky-600 dark:text-sky-400">ACI</th>
                            <th class="px-6 py-3 text-right font-medium text-emerald-600 dark:text-emerald-400">Total</th>
                            <th class="px-6 py-3 text-right font-medium">Actualizado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr
                            v-for="participant in filteredParticipants"
                            :key="participant.poolEntryId || participant.userId"
                            class="transition hover:bg-slate-50 dark:hover:bg-slate-800/40"
                        >
                            <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ participant.rank }}</td>
                            <td
                                class="px-6 py-4 font-medium"
                                :class="participant.userId === currentUserId
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-slate-900 dark:text-white'"
                            >
                                <div>{{ participant.name }}</div>
                                <div v-if="participant.email" class="text-xs text-slate-500 dark:text-slate-400">{{ participant.email }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">{{ participant.poolsCount }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-emerald-600 dark:text-emerald-400">{{ participant.exactHits }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-sky-600 dark:text-sky-400">{{ participant.correctResults }}</td>
                            <td class="px-6 py-4 text-right text-lg font-black text-emerald-600 dark:text-emerald-400">{{ participant.totalPoints }}</td>
                            <td class="px-6 py-4 text-right text-xs text-slate-500 dark:text-slate-400">{{ participant.updatedAt || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="mt-6 rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                No hay quinielas para el criterio de busqueda.
            </div>
        </section>
    </UserDashboardLayout>
</template>

