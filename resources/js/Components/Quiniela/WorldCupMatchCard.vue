<script setup>
import { computed, ref } from 'vue'
import AppTooltip from '@/Components/UI/AppTooltip.vue'
import { imageUrl } from '@/Utils/image'

const props = defineProps({
    match: {
        type: Object,
        required: true,
    },
    modelValue: {
        type: Object,
        default: () => ({ home: null, away: null }),
    },
})

const emit = defineEmits(['update:modelValue', 'score-changed'])

const sanitizeScore = (value) => {
    const digits = String(value ?? '').replace(/\D+/g, '').slice(0, 2)

    if (digits === '') {
        return null
    }

    return Math.min(20, Math.max(0, Number.parseInt(digits, 10)))
}

const homeScore = computed({
    get: () => props.modelValue.home ?? '',
    set: (value) => {
        emit('update:modelValue', {
            ...props.modelValue,
            home: sanitizeScore(value),
        })
        emit('score-changed')
    },
})

const awayScore = computed({
    get: () => props.modelValue.away ?? '',
    set: (value) => {
        emit('update:modelValue', {
            ...props.modelValue,
            away: sanitizeScore(value),
        })
        emit('score-changed')
    },
})

const isThirdPlaceRuleSlot = (slot) => /^3-[A-Z]+$/.test(String(slot || ''))
const teamLabel = (team, slot) => team?.name || (isThirdPlaceRuleSlot(slot) ? 'Por definir' : (slot || 'Por definir'))
const teamCode = (team, slot) => team?.code || (isThirdPlaceRuleSlot(slot) ? 'TBD' : (slot || 'TBD'))
const teamSubLabel = (team, slot) => team?.is_special_slot ? 'FIFA' : teamLabel(team, slot)
const teamCodeClass = () => 'mt-3 text-2xl font-black text-slate-900 whitespace-nowrap leading-tight dark:text-white'
const hiddenFlags = ref({})

const teamKey = (team, slot) => team?.id || team?.code || slot || 'unknown'
const flagSrc = (team) => team?.flag_url || imageUrl(team?.flag_path)
const shouldShowFlag = (team, slot) => Boolean(flagSrc(team)) && !hiddenFlags.value[teamKey(team, slot)]
const flagClass = (team) => team?.is_special_slot
    ? 'h-[42px] w-14 rounded-lg object-contain bg-white shadow-md shadow-slate-200 dark:shadow-black/30'
    : 'h-[42px] w-14 rounded-lg object-cover shadow-md shadow-slate-200 dark:shadow-black/30'
const hideFlag = (team, slot) => {
    hiddenFlags.value = {
        ...hiddenFlags.value,
        [teamKey(team, slot)]: true,
    }
}
</script>

<template>
    <article class="rounded-3xl border border-slate-200/80 bg-white/95 p-4 shadow-lg shadow-slate-200/70 backdrop-blur-xl transition duration-300 hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900/85 dark:shadow-none dark:hover:border-primary-500/40 dark:hover:bg-slate-900">
        <div class="flex items-start justify-between gap-3 border-b border-slate-200/80 pb-3 dark:border-white/5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-600 dark:text-cyan-300/80">
                    {{ match.stage === 'group' ? `Grupo ${match.group_name}` : match.stage_label }}
                </p>
                <h3 class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-300">
                    {{ match.display_date }}
                    <span v-if="match.display_time" class="text-slate-400 dark:text-slate-500"> - {{ match.display_time }}</span>
                </h3>
            </div>

            <span class="rounded-full border border-primary-200 bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:border-cyan-400/20 dark:bg-cyan-400/10 dark:text-cyan-200">
                {{ match.win_probability }}
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
                    class="flex h-10 w-14 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-[0.2em] text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-300"
                >
                    {{ teamCode(match.home_team, match.home_slot) }}
                </div>
                <p :class="teamCodeClass()">
                    {{ teamCode(match.home_team, match.home_slot) }}
                </p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ teamSubLabel(match.home_team, match.home_slot) }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <input
                    v-model="homeScore"
                    type="text"
                    inputmode="numeric"
                    maxlength="2"
                    autocorrect="off"
                    autocomplete="off"
                    spellcheck="false"
                    placeholder="-"
                    class="h-16 w-14 rounded-2xl border border-slate-200 bg-slate-50 text-center text-3xl font-black text-slate-900 focus:border-primary-400 focus:ring-primary-300/40 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none dark:border-white/10 dark:bg-black/40 dark:text-white dark:focus:border-cyan-400 dark:focus:ring-cyan-400/30"
                >
                <span class="text-xl font-black text-slate-400 dark:text-slate-500">:</span>
                <input
                    v-model="awayScore"
                    type="text"
                    inputmode="numeric"
                    maxlength="2"
                    autocorrect="off"
                    autocomplete="off"
                    spellcheck="false"
                    placeholder="-"
                    class="h-16 w-14 rounded-2xl border border-slate-200 bg-slate-50 text-center text-3xl font-black text-slate-900 focus:border-primary-400 focus:ring-primary-300/40 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none dark:border-white/10 dark:bg-black/40 dark:text-white dark:focus:border-cyan-400 dark:focus:ring-cyan-400/30"
                >
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
                    class="flex h-10 w-14 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-[0.2em] text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-300"
                >
                    {{ teamCode(match.away_team, match.away_slot) }}
                </div>
                <p :class="teamCodeClass()">
                    {{ teamCode(match.away_team, match.away_slot) }}
                </p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ teamSubLabel(match.away_team, match.away_slot) }}
                </p>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-slate-200/80 pt-3 text-sm dark:border-white/5">
            <p class="font-medium text-primary-600 dark:text-cyan-300">
                {{ match.probability_text }}
            </p>
            <AppTooltip :text="`Estadio: ${match.venue || 'Sede por confirmar'}`" placement="top">
                <div class="inline-flex min-w-0 items-center gap-2 text-slate-500 dark:text-slate-400">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 384 512"
                        class="h-4 w-4 shrink-0 fill-current text-slate-400 transition hover:text-primary-600 dark:text-slate-500 dark:hover:text-cyan-300"
                        aria-hidden="true"
                    >
                        <path d="M0 188.6C0 84.4 86 0 192 0S384 84.4 384 188.6c0 119.3-120.2 262.3-170.4 316.8-11.8 12.8-31.5 12.8-43.3 0-50.2-54.5-170.4-197.5-170.4-316.8zM192 256a64 64 0 1 0 0-128 64 64 0 1 0 0 128z"/>
                    </svg>
                    <span class="truncate">
                        {{ match.venue || 'Sede por confirmar' }}
                    </span>
                </div>
            </AppTooltip>
        </div>
    </article>
</template>
