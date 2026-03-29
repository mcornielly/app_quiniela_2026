<script setup>
import { computed, ref } from 'vue'
import {
    MagnifyingGlassIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline'
import FlowbiteModal from '@/Components/UI/FlowbiteModal.vue'
import AppTooltip from '@/Components/UI/AppTooltip.vue'
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
    <FlowbiteModal :show="show" max-width="2xl" @close="$emit('close')">
        <div class="flex items-start justify-between border-b border-slate-200/80 pb-3 dark:border-slate-800">
            <div class="max-w-xl">
                <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-primary-600 dark:text-primary-400">
                    Personaliza tu espacio
                </p>
                <h3 class="mt-2 text-lg font-medium text-slate-950 md:text-[1.65rem] md:font-black md:tracking-tight dark:text-white">
                    Elige tu seleccion favorita
                </h3>
                <p class="mt-2.5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    Selecciona una bandera para personalizar tu espacio. Puedes mantener el estilo neutro cuando quieras.
                </p>
            </div>

            <button
                type="button"
                class="ms-auto inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-transparent text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white"
                @click="$emit('close')"
            >
                <XMarkIcon class="h-5 w-5" />
                <span class="sr-only">Cerrar modal</span>
            </button>
        </div>

        <div class="space-y-4 py-4 md:space-y-4 md:py-4">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <label class="relative block w-full md:max-w-[24rem]">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </span>
                    <input
                        v-model="search"
                        type="text"
                        class="w-full rounded-[1.2rem] border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-4 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-primary-300 focus:bg-white focus:ring-4 focus:ring-primary-100 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-200 dark:placeholder:text-slate-500 dark:focus:border-primary-500 dark:focus:ring-primary-500/10"
                        placeholder="Buscar por pais o seleccion"
                    >
                </label>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-[1.2rem] border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:text-white"
                    @click="clearSelection"
                >
                    Usar estilo neutro
                </button>
            </div>

            <div class="mt-4 rounded-[1.4rem] bg-slate-50/85 px-4 py-4 md:mt-4 md:px-4 md:py-4 dark:bg-slate-950/45">
                <div class="min-h-[18rem]">
                    <div class="grid gap-3.5 pr-1 sm:grid-cols-2">
                        <button
                            v-for="team in filteredTeams"
                            :key="team.id"
                            type="button"
                            class="group relative flex items-center gap-3 rounded-[1.2rem] border border-slate-200 bg-white p-3 text-left shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-md hover:shadow-slate-200/70 dark:border-slate-800 dark:bg-slate-900/70 dark:hover:border-primary-500 dark:hover:shadow-none"
                            :class="team.id === currentTeamId
                                ? 'border-sky-300 bg-sky-50/50 shadow-[0_0_0_1px_rgba(147,197,253,0.65)] dark:border-sky-400/60 dark:bg-sky-500/10'
                                : ''"
                            @click="handleSelect(team.id)"
                        >
                            <AppTooltip
                                :text="team.name"
                                placement="top"
                                tooltip-class="z-[120]"
                            >
                                <div class="relative shrink-0 overflow-visible">
                                    <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-950">
                                        <img
                                            v-if="teamFlag(team)"
                                            :src="teamFlag(team)"
                                            :alt="`Bandera de ${team.name}`"
                                            class="h-full w-full rounded-full object-cover"
                                        >
                                        <span
                                            v-else
                                            class="text-sm font-black uppercase text-slate-500 dark:text-slate-300"
                                        >
                                            {{ team.country_code }}
                                        </span>
                                    </div>
                                </div>
                            </AppTooltip>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[1.2rem] font-semibold tracking-tight text-slate-950 dark:text-white">
                                    {{ team.name }}
                                </p>
                                <p class="mt-1 text-[11px] font-medium uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">
                                    {{ team.theme_label ?? team.name }}
                                </p>
                            </div>
                        </button>

                        <div
                            v-if="filteredTeams.length === 0"
                            class="col-span-full rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50/80 px-6 py-12 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/50 dark:text-slate-400"
                        >
                            No encontramos coincidencias con esa busqueda.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FlowbiteModal>
</template>