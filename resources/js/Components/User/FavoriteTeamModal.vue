<script setup>
import { computed, ref } from 'vue'
import {
    CheckCircleIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline'
import Modal from '@/Components/Modal.vue'
import { imageUrl } from '@/Utils/image'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    teams: {
        type: Array,
        default: () => [],
    },
    currentTeamId: {
        type: Number,
        default: null,
    },
    processing: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['close', 'select'])
const search = ref('')

const normalizedSearch = computed(() => normalize(search.value))

const filteredTeams = computed(() => {
    if (!normalizedSearch.value) {
        return props.teams
    }

    return props.teams.filter((team) => {
        const haystack = normalize(`${team.name} ${team.country_code ?? ''} ${team.theme_label ?? ''}`)
        return haystack.includes(normalizedSearch.value)
    })
})

const teamFlag = (team) => imageUrl(team.flag_path)

const handleSelect = (teamId) => {
    if (props.processing) {
        return
    }

    emit('select', teamId)
}

const clearSelection = () => {
    if (props.processing) {
        return
    }

    emit('select', null)
}

function normalize(value) {
    return (value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
}
</script>

<template>
    <Modal :show="show" max-width="2xl" @close="$emit('close')">
        <div class="relative overflow-hidden rounded-[1.75rem] bg-white text-slate-900 dark:bg-slate-900 dark:text-slate-100">
            <div class="absolute inset-x-0 top-0 h-24 bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.18),_transparent_70%)] dark:bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.16),_transparent_70%)]" />

            <div class="relative p-6 sm:p-7">
                <div class="flex items-start justify-between gap-4">
                    <div class="max-w-xl">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-primary-600 dark:text-primary-400">
                            Personaliza tu espacio
                        </p>
                        <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950 dark:text-white">
                            Elige tu seleccion favorita
                        </h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Esta preferencia es opcional. Si eliges un equipo, tu ticker adoptara la identidad visual de su bandera.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white/90 text-slate-500 transition hover:border-slate-300 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:text-slate-200"
                        @click="$emit('close')"
                    >
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>

                <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <label class="relative block w-full sm:max-w-md">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                            <MagnifyingGlassIcon class="h-5 w-5" />
                        </span>
                        <input
                            v-model="search"
                            type="text"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary-300 focus:bg-white focus:ring-4 focus:ring-primary-100 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-200 dark:placeholder:text-slate-500 dark:focus:border-primary-500 dark:focus:ring-primary-500/10"
                            placeholder="Buscar por pais o seleccion"
                        >
                    </label>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:text-white"
                        @click="clearSelection"
                    >
                        Usar estilo neutro
                    </button>
                </div>

                <div class="mt-6 grid max-h-[28rem] gap-3 overflow-y-auto pr-1 sm:grid-cols-2">
                    <button
                        v-for="team in filteredTeams"
                        :key="team.id"
                        type="button"
                        class="group relative flex items-center gap-4 rounded-3xl border border-slate-200/80 bg-white/88 p-4 text-left shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-lg hover:shadow-slate-200/70 dark:border-slate-800 dark:bg-slate-950/65 dark:hover:border-primary-500 dark:hover:shadow-none"
                        :class="team.id === currentTeamId
                            ? 'border-primary-300 bg-primary-50/80 ring-2 ring-primary-100 dark:border-primary-500/60 dark:bg-primary-500/10 dark:ring-primary-500/10'
                            : ''"
                        @click="handleSelect(team.id)"
                    >
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700">
                            <img
                                v-if="teamFlag(team)"
                                :src="teamFlag(team)"
                                :alt="`Bandera de ${team.name}`"
                                class="h-full w-full object-cover"
                            >
                            <span v-else class="text-sm font-black uppercase text-slate-500 dark:text-slate-300">
                                {{ team.country_code }}
                            </span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="truncate text-base font-semibold text-slate-950 dark:text-white">
                                        {{ team.name }}
                                    </p>
                                    <p class="mt-1 text-xs font-medium uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                                        {{ team.theme_label ?? 'Tema personalizado' }}
                                    </p>
                                </div>

                                <CheckCircleIcon
                                    v-if="team.id === currentTeamId"
                                    class="h-5 w-5 shrink-0 text-primary-600 dark:text-primary-300"
                                />
                            </div>
                        </div>
                    </button>

                    <div
                        v-if="filteredTeams.length === 0"
                        class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-slate-50/80 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/50 dark:text-slate-400"
                    >
                        No encontramos coincidencias con esa busqueda.
                    </div>
                </div>

                <p class="mt-5 text-xs leading-5 text-slate-500 dark:text-slate-400">
                    Puedes cambiar esta preferencia cuando quieras. Si no eliges equipo, mantendremos una apariencia neutra y limpia.
                </p>
            </div>
        </div>
    </Modal>
</template>
