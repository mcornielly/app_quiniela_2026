<script setup>
import { computed } from 'vue'
import { ClockIcon, MapPinIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    match: {
        type: Object,
        required: true,
    },
    statusLabel: {
        type: String,
        default: 'En Directo',
    },
    statusShort: {
        type: String,
        default: '',
    },
    showStatusIcon: {
        type: Boolean,
        default: true,
    },
    hoverable: {
        type: Boolean,
        default: false,
    },
})

const isDraw = (match) => Number(match?.homeScore) === Number(match?.awayScore)
const isHomeWinner = (match) => Number(match?.homeScore) > Number(match?.awayScore)
const isAwayWinner = (match) => Number(match?.awayScore) > Number(match?.homeScore)
const crestSrc = (src) => src || null

const normalizedStatusShort = computed(() => String(props.statusShort || '').trim().toUpperCase())
const normalizedLabel = computed(() => String(props.statusLabel || '').toLowerCase())

const isFinished = computed(() => {
    if (['FT', 'AET', 'PEN'].includes(normalizedStatusShort.value)) return true
    return normalizedLabel.value.includes('finished') || normalizedLabel.value.includes('finalizado')
})

const displayStatusLabel = computed(() => (isFinished.value ? (props.statusLabel || 'Finalizado') : 'En progreso'))
</script>

<template>
    <section class="space-y-2">
        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                {{ match.groupName || '-' }}
            </p>

            <div class="inline-flex items-center gap-1.5">
                <ClockIcon class="h-4 w-4" :class="isFinished ? 'text-rose-500 dark:text-rose-400' : 'text-emerald-500 dark:text-emerald-400'" />
                <span class="text-2xl font-black leading-none" :class="isFinished ? 'text-rose-500 dark:text-rose-400' : 'text-emerald-500 dark:text-emerald-400'">
                    {{ match.matchTime }}
                </span>
            </div>

            <div class="flex justify-end">
                <span class="inline-flex items-center rounded-full px-2 py-1 text-[11px] font-bold tracking-wide" :class="isFinished ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'">
                    {{ displayStatusLabel }}
                </span>
            </div>
        </div>

        <div class="match-shell">
            <div class="shield-slot">
                <img
                    v-if="crestSrc(match.homeShieldUrl)"
                    :src="crestSrc(match.homeShieldUrl)"
                    :alt="match.homeTeam"
                    :title="match.homeTeam"
                    class="shield-image"
                    loading="lazy"
                />
                <span v-else class="shield-placeholder" aria-hidden="true" />
            </div>

            <article
                class="live-match-card relative overflow-hidden rounded-2xl border border-slate-200 bg-white px-5 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900/75"
                :class="[
                    hoverable ? 'transition hover:border-primary-300 hover:shadow-md dark:hover:border-primary-700' : '',
                    isFinished ? 'match-finished' : 'match-live',
                ]"
            >
                <div class="grid grid-cols-1 items-center gap-3 xl:grid-cols-[1fr]">
                    <div class="space-y-1.5">
                        <div class="match-location-row inline-flex w-full items-center justify-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                            <MapPinIcon class="h-4 w-4 text-cyan-500 dark:text-cyan-400" />
                            <span class="truncate">{{ match.venue || 'Sede por confirmar' }}</span>
                        </div>

                        <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-4 md:gap-8 xl:gap-14">
                            <div class="flex items-center justify-end pr-2 text-right md:pr-4 xl:pr-8">
                                <div class="min-w-0">
                                    <span class="team-name hidden truncate text-base font-semibold text-slate-900 dark:text-white sm:block md:text-[1.1rem]">{{ match.homeTeam }}</span>
                                    <span class="country-code block text-sm font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ match.homeCode }}</span>
                                </div>
                            </div>

                            <div class="match-score-box inline-flex min-w-[92px] items-center justify-center gap-3 rounded-xl bg-slate-100 px-3 py-2 text-2xl font-black dark:bg-slate-800 md:min-w-[110px]">
                                <span :class="(isHomeWinner(match) || isDraw(match)) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                    {{ match.homeScore ?? 0 }}
                                </span>
                                <span class="text-slate-400 dark:text-slate-500">-</span>
                                <span :class="(isAwayWinner(match) || isDraw(match)) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                    {{ match.awayScore ?? 0 }}
                                </span>
                            </div>

                            <div class="flex items-center justify-start pl-2 text-left md:pl-4 xl:pl-8">
                                <div class="min-w-0">
                                    <span class="team-name hidden truncate text-base font-semibold text-slate-900 dark:text-white sm:block md:text-[1.1rem]">{{ match.awayTeam }}</span>
                                    <span class="country-code block text-sm font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ match.awayCode }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <div class="shield-slot">
                <img
                    v-if="crestSrc(match.awayShieldUrl)"
                    :src="crestSrc(match.awayShieldUrl)"
                    :alt="match.awayTeam"
                    :title="match.awayTeam"
                    class="shield-image"
                    loading="lazy"
                />
                <span v-else class="shield-placeholder" aria-hidden="true" />
            </div>
        </div>
    </section>
</template>

<style scoped>
.live-match-card::before {
    content: '';
    position: absolute;
    top: -1px;
    left: -36%;
    width: 36%;
    height: 3px;
    border-radius: 9999px;
}

.match-live.live-match-card::before {
    background: linear-gradient(90deg, rgba(16, 185, 129, 0) 0%, rgba(16, 185, 129, 0.75) 35%, rgba(34, 197, 94, 1) 55%, rgba(52, 211, 153, 0.85) 75%, rgba(16, 185, 129, 0) 100%);
    box-shadow: 0 0 10px rgba(34, 197, 94, 0.8);
    animation: live-scan 2.2s ease-in-out infinite alternate;
}

.match-finished.live-match-card::before {
    left: 0;
    width: 100%;
    background: linear-gradient(90deg, rgba(244, 63, 94, 0.1) 0%, rgba(251, 113, 133, 0.45) 50%, rgba(244, 63, 94, 0.1) 100%);
    box-shadow: 0 0 12px rgba(244, 63, 94, 0.35);
    animation: finished-pulse 2.1s ease-in-out infinite;
}

@keyframes live-scan {
    from {
        transform: translateX(0);
    }
    to {
        transform: translateX(390%);
    }
}

@keyframes finished-pulse {
    0%, 100% {
        opacity: 0.55;
    }
    50% {
        opacity: 1;
    }
}

@media (prefers-reduced-motion: reduce) {
    .live-match-card::before {
        animation: none;
        left: 0;
        width: 100%;
        opacity: 0.9;
    }
}

.match-shell {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 0.75rem;
}

.shield-slot {
    width: clamp(56px, 8vw, 110px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.shield-image {
    width: 100%;
    height: auto;
    max-height: 110px;
    object-fit: contain;
}

.shield-placeholder {
    width: 100%;
    height: 1px;
    visibility: hidden;
}

@media (max-width: 640px) {
    .match-shell {
        grid-template-columns: 84px minmax(0, 1fr) 84px;
        gap: 0.5rem;
    }

    .shield-slot {
        width: 84px;
    }

    .shield-image {
        max-height: 86px;
    }

    .country-code {
        display: none;
    }
}

@media (max-width: 420px) {
    .match-shell {
        grid-template-columns: 88px minmax(0, 1fr) 88px;
        gap: 0.35rem;
        align-items: center;
    }

    .shield-slot {
        width: 88px;
    }

    .shield-image {
        max-height: 92px;
    }

    .live-match-card {
        padding: 0.6rem 0.55rem;
    }

    .match-location-row {
        font-size: 0.9rem;
        line-height: 1.2rem;
    }

    .match-score-box {
        min-width: 84px;
        padding: 0.45rem 0.55rem;
    }

    .team-name {
        display: none;
    }
}
</style>
