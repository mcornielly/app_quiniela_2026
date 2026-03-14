<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

defineProps({
    entries: {
        type: Array,
        default: () => [],
    },
})

const statusClasses = {
    draft: 'bg-amber-100 text-amber-700',
    complete: 'bg-emerald-100 text-emerald-700',
    paid_locked: 'bg-sky-100 text-sky-700',
    live: 'bg-violet-100 text-violet-700',
    finished: 'bg-slate-200 text-slate-700',
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Mis Quinielas" />

        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Mis Quinielas</h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Aqui veras todas tus quinielas registradas y el puntaje acumulado de cada una.
                    </p>
                </div>

                <Link
                    :href="route('predictions.worldcup')"
                    class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Crear otra quiniela
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Quinielas registradas</p>
                    <p class="mt-2 text-3xl font-black text-gray-900">{{ entries.length }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Puntaje total acumulado</p>
                    <p class="mt-2 text-3xl font-black text-gray-900">
                        {{ entries.reduce((sum, entry) => sum + (entry.total_points || 0), 0) }}
                    </p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Completadas</p>
                    <p class="mt-2 text-3xl font-black text-gray-900">
                        {{ entries.filter((entry) => entry.status === 'complete').length }}
                    </p>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">Listado de quinielas</h2>
                </div>

                <div v-if="entries.length === 0" class="px-6 py-12 text-center">
                    <p class="text-lg font-semibold text-gray-900">Aun no has registrado quinielas.</p>
                    <p class="mt-2 text-sm text-gray-600">
                        Completa tu primera quiniela del Mundial y aqui aparecera con su numero de registro y puntaje.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left font-semibold uppercase tracking-wide text-gray-500">Registro</th>
                                <th class="px-6 py-4 text-left font-semibold uppercase tracking-wide text-gray-500">Nombre</th>
                                <th class="px-6 py-4 text-left font-semibold uppercase tracking-wide text-gray-500">Torneo</th>
                                <th class="px-6 py-4 text-left font-semibold uppercase tracking-wide text-gray-500">Estado</th>
                                <th class="px-6 py-4 text-left font-semibold uppercase tracking-wide text-gray-500">Progreso</th>
                                <th class="px-6 py-4 text-left font-semibold uppercase tracking-wide text-gray-500">Puntaje</th>
                                <th class="px-6 py-4 text-left font-semibold uppercase tracking-wide text-gray-500">Creada</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="entry in entries" :key="entry.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-bold text-gray-900">#{{ entry.registration_number }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ entry.name }}</td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ entry.tournament?.name }}
                                    <span v-if="entry.tournament?.year" class="text-gray-400">({{ entry.tournament.year }})</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="statusClasses[entry.status] || 'bg-gray-100 text-gray-700'"
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                    >
                                        {{ entry.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-700">{{ entry.completion_percent }}%</td>
                                <td class="px-6 py-4 text-lg font-bold text-gray-900">{{ entry.total_points }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ entry.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
