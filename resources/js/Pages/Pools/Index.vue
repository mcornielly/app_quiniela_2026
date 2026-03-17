<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import SectionCard from '@/Components/User/SectionCard.vue'
import StatCard from '@/Components/User/StatCard.vue'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'

const props = defineProps({
    poolEntries: {
        type: Array,
        default: () => [],
    },
})

const statusClasses = {
    draft: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
    complete: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    paid_locked: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
    live: 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
    finished: 'bg-slate-200 text-slate-700 dark:bg-slate-700/60 dark:text-slate-200',
}

const totalPoints = computed(() => props.poolEntries.reduce((sum, poolEntry) => sum + (poolEntry.totalPoints || 0), 0))
const completedEntries = computed(() => props.poolEntries.filter((poolEntry) => poolEntry.status === 'complete').length)
const liveEntries = computed(() => props.poolEntries.filter((poolEntry) => ['live', 'paid_locked'].includes(poolEntry.status)).length)
</script>

<template>
    <Head title="Mis Quinielas" />

    <UserDashboardLayout
        title="Mis quinielas"
        description="Aqui veras todas tus quinielas registradas, su progreso y una lectura rapida de tu desempeno dentro del juego."
        ticker="Tus registros quedan centralizados aqui: revisa progreso, estados y puntaje acumulado de cada quiniela."
    >
        <template #headerActions>
            <Link
                :href="route('predictions.worldcup')"
                class="inline-flex items-center justify-center rounded-2xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-sm shadow-primary-500/30 transition hover:bg-primary-700"
            >
                Crear otra quiniela
            </Link>
        </template>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard title="Quinielas registradas" :value="poolEntries.length" helper="Total" tone="primary">
                Sigue todas tus participaciones desde un solo lugar.
            </StatCard>
            <StatCard title="Puntaje acumulado" :value="totalPoints" helper="Global" tone="success">
                Suma total de puntos obtenidos en todas tus quinielas.
            </StatCard>
            <StatCard title="Completadas" :value="completedEntries" helper="Cerradas" tone="warning">
                Quinielas listas con seleccion completa del torneo.
            </StatCard>
            <StatCard title="En juego" :value="liveEntries" helper="Activas" tone="slate">
                Entradas que ya estan participando o bloqueadas para edicion.
            </StatCard>
        </section>

        <section class="mt-6">
            <SectionCard
                title="Listado de quinielas"
                subtitle="Tu historico personal de registros, estado, progreso y puntaje."
            >
                <div v-if="poolEntries.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-800/40">
                    <p class="text-lg font-semibold text-slate-900 dark:text-white">Aun no has registrado quinielas.</p>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        Completa tu primera quiniela del Mundial y aqui aparecera con su numero de registro y su puntaje acumulado.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-800/70">
                            <tr>
                                <th class="px-6 py-4 text-left font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Registro</th>
                                <th class="px-6 py-4 text-left font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Nombre</th>
                                <th class="px-6 py-4 text-left font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Torneo</th>
                                <th class="px-6 py-4 text-left font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Estado</th>
                                <th class="px-6 py-4 text-left font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Progreso</th>
                                <th class="px-6 py-4 text-left font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Puntaje</th>
                                <th class="px-6 py-4 text-left font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Creada</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900">
                            <tr v-for="poolEntry in poolEntries" :key="poolEntry.id" class="transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">#{{ poolEntry.registrationNumber }}</td>
                                <td class="px-6 py-4 text-slate-700 dark:text-slate-200">{{ poolEntry.name }}</td>
                                <td class="px-6 py-4 text-slate-700 dark:text-slate-200">
                                    {{ poolEntry.tournament?.name }}
                                    <span v-if="poolEntry.tournament?.year" class="text-slate-400 dark:text-slate-500">({{ poolEntry.tournament.year }})</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="statusClasses[poolEntry.status] || 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'"
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                    >
                                        {{ poolEntry.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-700 dark:text-slate-200">{{ poolEntry.completionPercent }}%</td>
                                <td class="px-6 py-4 text-lg font-bold text-slate-900 dark:text-white">{{ poolEntry.totalPoints }}</td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ poolEntry.createdAt }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </SectionCard>
        </section>
    </UserDashboardLayout>
</template>
