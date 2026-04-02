<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppTooltip from '@/Components/UI/AppTooltip.vue'
import { imageUrl } from '@/Utils/image'

const props = defineProps({
    match: {
        type: Object,
        required: true,
    },
    actionHref: {
        type: String,
        default: null,
    },
    showAction: {
        type: Boolean,
        default: true,
    },
})

const hiddenFlags = ref({})

const teamLabel = (team, slot) => team?.name || slot || 'Por definir'
const teamCode = (team, slot) => team?.code || slot || 'TBD'
const teamSubLabel = (team, slot) => team?.is_special_slot ? 'FIFA' : teamLabel(team, slot)
const teamKey = (team, slot) => team?.id || team?.code || slot || 'unknown'
const flagSrc = (team) => team?.flag_url || imageUrl(team?.flag_path)
const shouldShowFlag = (team, slot) => Boolean(flagSrc(team)) && !hiddenFlags.value[teamKey(team, slot)]
const hideFlag = (team, slot) => {
    hiddenFlags.value = {
        ...hiddenFlags.value,
        [teamKey(team, slot)]: true,
    }
}

const flagClass = () => 'h-10 w-14 rounded-md border border-gray-200 object-cover dark:border-gray-600'

const homeScore = computed(() => props.match.homeScore ?? '-')
const awayScore = computed(() => props.match.awayScore ?? '-')
const actionLabel = computed(() => props.match.actionLabel || 'Resultados')
</script>

<template>
    <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-3 dark:border-gray-700">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-400">
                    {{ match.stage === 'group' ? `Grupo ${match.group_name}` : match.stage_label }}
                </p>
                <h3 class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                    {{ match.display_date }}
                    <span v-if="match.display_time" class="text-gray-500 dark:text-gray-400"> - {{ match.display_time }}</span>
                </h3>
            </div>
            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                {{ actionLabel }}
            </span>
        </div>

        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3 py-5">
            <div class="flex flex-col items-center text-center">
                <img
                    v-if="shouldShowFlag(match.home_team, match.home_slot)"
                    :src="flagSrc(match.home_team)"
                    :alt="teamLabel(match.home_team, match.home_slot)"
                    :class="flagClass(match.home_team)"
                    @error="hideFlag(match.home_team, match.home_slot)"
                >
                <div
                    v-else
                    class="flex h-10 w-14 items-center justify-center rounded-md border border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-[0.18em] text-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                >
                    {{ teamCode(match.home_team, match.home_slot) }}
                </div>
                <p class="mt-3 break-words text-lg font-bold leading-tight text-gray-900 dark:text-white">
                    {{ teamCode(match.home_team, match.home_slot) }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ teamSubLabel(match.home_team, match.home_slot) }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <div class="flex h-14 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-center text-2xl font-bold text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    {{ homeScore }}
                </div>
                <span class="text-lg font-bold text-gray-400 dark:text-gray-500">:</span>
                <div class="flex h-14 w-12 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-center text-2xl font-bold text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    {{ awayScore }}
                </div>
            </div>

            <div class="flex flex-col items-center text-center">
                <img
                    v-if="shouldShowFlag(match.away_team, match.away_slot)"
                    :src="flagSrc(match.away_team)"
                    :alt="teamLabel(match.away_team, match.away_slot)"
                    :class="flagClass(match.away_team)"
                    @error="hideFlag(match.away_team, match.away_slot)"
                >
                <div
                    v-else
                    class="flex h-10 w-14 items-center justify-center rounded-md border border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-[0.18em] text-gray-600 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                >
                    {{ teamCode(match.away_team, match.away_slot) }}
                </div>
                <p class="mt-3 break-words text-lg font-bold leading-tight text-gray-900 dark:text-white">
                    {{ teamCode(match.away_team, match.away_slot) }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ teamSubLabel(match.away_team, match.away_slot) }}
                </p>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-3 text-sm dark:border-gray-700">
            <AppTooltip :text="`Estadio: ${match.venue || 'Sede por confirmar'}`" placement="top">
                <div class="inline-flex min-w-0 items-center gap-2 text-gray-500 dark:text-gray-400">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 384 512"
                        class="h-4 w-4 shrink-0 fill-current text-gray-400 dark:text-gray-500"
                        aria-hidden="true"
                    >
                        <path d="M0 188.6C0 84.4 86 0 192 0S384 84.4 384 188.6c0 119.3-120.2 262.3-170.4 316.8-11.8 12.8-31.5 12.8-43.3 0-50.2-54.5-170.4-197.5-170.4-316.8zM192 256a64 64 0 1 0 0-128 64 64 0 1 0 0 128z" />
                    </svg>
                    <span class="truncate">
                        {{ match.venue || 'Sede por confirmar' }}
                    </span>
                </div>
            </AppTooltip>

            <component
                v-if="showAction"
                :is="actionHref ? Link : 'button'"
                :href="actionHref || undefined"
                :type="actionHref ? undefined : 'button'"
                class="inline-flex items-center rounded-lg bg-gray-900 px-3 py-2 text-xs font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 dark:focus:ring-gray-700"
            >
                {{ actionLabel }}
            </component>
        </div>
    </article>
</template>
