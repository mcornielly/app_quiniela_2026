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

const homeScore = computed(() => props.modelValue.home ?? '')
const awayScore = computed(() => props.modelValue.away ?? '')

const updateScores = (nextHome, nextAway) => {
    emit('update:modelValue', {
        ...props.modelValue,
        home: sanitizeScore(nextHome),
        away: sanitizeScore(nextAway),
    })
    emit('score-changed')
}

const setScore = (side, value) => {
    if (side === 'home') {
        updateScores(value, props.modelValue.away)
        return
    }

    updateScores(props.modelValue.home, value)
}

const normalizeIncompleteScore = () => {
    const home = sanitizeScore(props.modelValue.home)
    const away = sanitizeScore(props.modelValue.away)

    if (home === null && away === null) {
        return
    }

    if (home === null) {
        updateScores(0, away)
        return
    }

    if (away === null) {
        updateScores(home, 0)
    }
}

const adjustScore = (side, delta) => {
    const current = Number.isFinite(Number(props.modelValue?.[side]))
        ? Number(props.modelValue?.[side])
        : 0
    const next = Math.min(20, Math.max(0, current + delta))
    setScore(side, next)
}

const isThirdPlaceRuleSlot = (slot) => /^3-[A-Z]+$/.test(String(slot || ''))
const teamLabel = (team, slot) => team?.name || (isThirdPlaceRuleSlot(slot) ? 'Por definir' : (slot || 'Por definir'))
const teamCode = (team, slot) => team?.code || (isThirdPlaceRuleSlot(slot) ? 'TBD' : (slot || 'TBD'))
const hiddenFlags = ref({})

const teamKey = (team, slot) => team?.id || team?.code || slot || 'unknown'
const flagSrc = (team) => team?.flag_url || imageUrl(team?.flag_path)
const shouldShowFlag = (team, slot) => Boolean(flagSrc(team)) && !hiddenFlags.value[teamKey(team, slot)]
const flagClass = (team) => team?.is_special_slot
    ? 'h-6 w-9 rounded object-contain scale-125'
    : 'h-6 w-9 rounded object-cover'
const hideFlag = (team, slot) => {
    hiddenFlags.value = {
        ...hiddenFlags.value,
        [teamKey(team, slot)]: true,
    }
}
</script>

<template>
    <article class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm transition hover:border-primary-300 dark:border-slate-800 dark:bg-slate-900/75 dark:hover:border-primary-500/40">
        <div class="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 border-b border-slate-200 pb-2.5 dark:border-slate-800">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                    {{ match.stage === 'group' ? `Grupo ${match.group_name}` : match.stage_label }}
                </p>
            </div>

            <div class="min-w-0 text-center">
                <div class="flex items-center justify-center gap-1.5 sm:gap-2 text-[10px] sm:text-[11px] font-semibold uppercase tracking-[0.14em] sm:tracking-[0.16em] text-slate-400 dark:text-slate-500">
                    <span class="h-px w-6 bg-slate-200 dark:bg-slate-700" />
                    <span>Partido #{{ match.match_number }}</span>
                    <span class="h-px w-6 bg-slate-200 dark:bg-slate-700" />
                </div>
                <div class="mt-0.5 flex min-w-0 flex-wrap items-center justify-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <span>{{ match.display_date }} <span v-if="match.display_time" class="font-semibold">- {{ match.display_time }}</span></span>
                    <AppTooltip :text="`Estadio: ${match.venue || 'Sede por confirmar'}`" placement="top">
                        <span class="inline-flex min-w-0 items-center gap-1.5 truncate">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="h-3.5 w-3.5 shrink-0 fill-current text-cyan-500 dark:text-cyan-400" aria-hidden="true">
                                <path d="M0 188.6C0 84.4 86 0 192 0S384 84.4 384 188.6c0 119.3-120.2 262.3-170.4 316.8-11.8 12.8-31.5 12.8-43.3 0-50.2-54.5-170.4-197.5-170.4-316.8zM192 256a64 64 0 1 0 0-128 64 64 0 1 0 0 128z"/>
                            </svg>
                            <span class="truncate transition-colors hover:text-cyan-500 dark:hover:text-cyan-400">{{ match.venue || 'Sede por confirmar' }}</span>
                        </span>
                    </AppTooltip>
                </div>
            </div>

            <AppTooltip :text="match.probability_text" placement="top">
                <span class="inline-flex cursor-default items-center rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700 dark:border-cyan-500/30 dark:bg-cyan-500/10 dark:text-cyan-300">
                    {{ match.win_probability }}
                </span>
            </AppTooltip>
        </div>

        <div class="grid gap-3 py-3 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] md:items-center">
            <div class="flex items-center justify-start gap-3 md:justify-end md:text-right">
                <div class="min-w-0">
                    <p class="truncate text-base font-semibold text-slate-900 dark:text-white">{{ teamLabel(match.home_team, match.home_slot) }}</p>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ teamCode(match.home_team, match.home_slot) }}</p>
                </div>
                <img
                    v-if="shouldShowFlag(match.home_team, match.home_slot)"
                    :src="flagSrc(match.home_team)"
                    :alt="teamLabel(match.home_team, match.home_slot)"
                    :class="flagClass(match.home_team)"
                    @error="hideFlag(match.home_team, match.home_slot)"
                >
                <span
                    v-else
                    class="inline-flex h-6 min-w-9 items-center justify-center rounded bg-slate-300 px-1 text-[10px] font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200"
                >
                    {{ teamCode(match.home_team, match.home_slot) }}
                </span>
            </div>

            <div class="flex items-center justify-center gap-2 md:gap-3">
                <div class="inline-flex items-center gap-1 rounded-xl bg-slate-100 px-2 py-1 dark:bg-slate-800">
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-lg font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        @click="adjustScore('home', -1)"
                    >
                        -
                    </button>
                    <input
                        :value="homeScore"
                        type="text"
                        inputmode="numeric"
                        maxlength="2"
                        autocorrect="off"
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="0"
                        class="h-9 w-10 rounded-lg border border-slate-300 bg-white text-center text-xl font-black text-slate-900 focus:border-primary-500 focus:ring-primary-200 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none dark:border-slate-600 dark:bg-slate-900 dark:text-white dark:focus:border-primary-500 dark:focus:ring-primary-500/20"
                        @input="setScore('home', $event.target.value)"
                        @blur="normalizeIncompleteScore"
                    >
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-lg font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        @click="adjustScore('home', 1)"
                    >
                        +
                    </button>
                </div>

                <span class="text-xl font-black text-slate-400 dark:text-slate-500">-</span>

                <div class="inline-flex items-center gap-1 rounded-xl bg-slate-100 px-2 py-1 dark:bg-slate-800">
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-lg font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        @click="adjustScore('away', -1)"
                    >
                        -
                    </button>
                    <input
                        :value="awayScore"
                        type="text"
                        inputmode="numeric"
                        maxlength="2"
                        autocorrect="off"
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="0"
                        class="h-9 w-10 rounded-lg border border-slate-300 bg-white text-center text-xl font-black text-slate-900 focus:border-primary-500 focus:ring-primary-200 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none dark:border-slate-600 dark:bg-slate-900 dark:text-white dark:focus:border-primary-500 dark:focus:ring-primary-500/20"
                        @input="setScore('away', $event.target.value)"
                        @blur="normalizeIncompleteScore"
                    >
                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-lg font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        @click="adjustScore('away', 1)"
                    >
                        +
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 md:justify-start">
                <img
                    v-if="shouldShowFlag(match.away_team, match.away_slot)"
                    :src="flagSrc(match.away_team)"
                    :alt="teamLabel(match.away_team, match.away_slot)"
                    :class="flagClass(match.away_team)"
                    @error="hideFlag(match.away_team, match.away_slot)"
                >
                <span
                    v-else
                    class="inline-flex h-6 min-w-9 items-center justify-center rounded bg-slate-300 px-1 text-[10px] font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200"
                >
                    {{ teamCode(match.away_team, match.away_slot) }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-base font-semibold text-slate-900 dark:text-white">{{ teamLabel(match.away_team, match.away_slot) }}</p>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ teamCode(match.away_team, match.away_slot) }}</p>
                </div>
            </div>
        </div>
    </article>
</template>
