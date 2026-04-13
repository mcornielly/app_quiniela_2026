<script setup>
import { computed } from 'vue'
import { CheckCircleIcon, ClockIcon, MapPinIcon } from '@heroicons/vue/24/outline'

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
    crestPlacement: {
        type: String,
        default: 'outside',
    },
})

const isDraw = (match) => Number(match?.homeScore) === Number(match?.awayScore)
const isHomeWinner = (match) => Number(match?.homeScore) > Number(match?.awayScore)
const isAwayWinner = (match) => Number(match?.awayScore) > Number(match?.homeScore)
const crestSrc = (src) => src || null

const normalizedStatusShort = computed(() => String(props.statusShort || '').trim().toUpperCase())
const normalizedLabel = computed(() => String(props.statusLabel || '').toLowerCase())
const crestsInside = computed(() => props.crestPlacement === 'inside')

const isFinished = computed(() => {
    if (['FT', 'AET', 'PEN'].includes(normalizedStatusShort.value)) return true
    return normalizedLabel.value.includes('finished') || normalizedLabel.value.includes('finalizado')
})

const displayStatusLabel = computed(() => (isFinished.value ? (props.statusLabel || 'Finalizado') : 'En progreso'))
</script>

<template>
    <section class="space-y-2">
        <div v-if="!crestsInside" class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
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
                <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-bold tracking-wide" :class="isFinished ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'">
                    <CheckCircleIcon v-if="showStatusIcon && isFinished" class="h-3.5 w-3.5" />
                    <ClockIcon v-else-if="showStatusIcon" class="h-3.5 w-3.5" />
                    {{ displayStatusLabel }}
                </span>
            </div>
        </div>

        <div class="match-shell" :class="crestsInside ? 'match-shell-inside' : 'match-shell-outside'">
            <div v-if="!crestsInside" class="shield-slot">
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
                <div class="match-body space-y-3">
                    <div v-if="crestsInside" class="match-meta-row">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                            {{ match.groupName || '-' }}
                        </p>

                        <div class="match-meta-center">
                            <div class="inline-flex items-center gap-1.5">
                                <ClockIcon class="h-4 w-4" :class="isFinished ? 'text-rose-500 dark:text-rose-400' : 'text-emerald-500 dark:text-emerald-400'" />
                                <span class="text-2xl font-black leading-none" :class="isFinished ? 'text-rose-500 dark:text-rose-400' : 'text-emerald-500 dark:text-emerald-400'">
                                    {{ match.matchTime }}
                                </span>
                            </div>
                            <span class="match-meta-dot" aria-hidden="true" />
                            <div class="location-meta inline-flex items-center justify-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                <MapPinIcon class="h-4 w-4 text-cyan-500 dark:text-cyan-400" />
                                <span class="location-meta-text truncate">{{ match.venue || 'Sede por confirmar' }}</span>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-bold tracking-wide" :class="isFinished ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'">
                                <CheckCircleIcon v-if="showStatusIcon && isFinished" class="h-3.5 w-3.5" />
                                <ClockIcon v-else-if="showStatusIcon" class="h-3.5 w-3.5" />
                                {{ displayStatusLabel }}
                            </span>
                        </div>
                    </div>

                    <div v-if="!crestsInside" class="match-location-row inline-flex w-full items-center justify-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                        <MapPinIcon class="h-4 w-4 text-cyan-500 dark:text-cyan-400" />
                        <span class="truncate">{{ match.venue || 'Sede por confirmar' }}</span>
                    </div>

                    <div class="match-content-grid" :class="crestsInside ? 'match-content-grid-inside' : 'match-content-grid-outside'">
                        <div v-if="crestsInside" class="team-crest-panel team-crest-panel-home">
                            <div class="shield-slot shield-slot-inside">
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
                        </div>

                        <div class="team-panel team-panel-home">
                            <div class="team-copy">
                                <span class="team-name block truncate text-base font-semibold text-slate-900 dark:text-white md:text-[1.1rem]">{{ match.homeTeam }}</span>
                                <span class="country-code block text-sm font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ match.homeCode }}</span>
                            </div>
                        </div>

                        <div class="match-score-wrap">
                            <div class="match-score-box inline-flex min-w-[92px] items-center justify-center gap-3 rounded-xl bg-slate-100 px-3 py-2 text-2xl font-black dark:bg-slate-800 md:min-w-[110px]">
                                <span :class="(isHomeWinner(match) || isDraw(match)) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                    {{ match.homeScore ?? 0 }}
                                </span>
                                <span class="text-slate-400 dark:text-slate-500">-</span>
                                <span :class="(isAwayWinner(match) || isDraw(match)) ? 'text-emerald-500 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                    {{ match.awayScore ?? 0 }}
                                </span>
                            </div>
                        </div>

                        <div class="team-panel team-panel-away">
                            <div class="team-copy">
                                <span class="team-name block truncate text-base font-semibold text-slate-900 dark:text-white md:text-[1.1rem]">{{ match.awayTeam }}</span>
                                <span class="country-code block text-sm font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ match.awayCode }}</span>
                            </div>
                        </div>

                        <div v-if="crestsInside" class="team-crest-panel team-crest-panel-away">
                            <div class="shield-slot shield-slot-inside">
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
                    </div>

                    <div class="match-mobile-teams">
                        <div class="mobile-team-line">
                            <span class="truncate font-semibold text-slate-900 dark:text-white">{{ match.homeTeam }}</span>
                            <span class="font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ match.homeCode }}</span>
                        </div>
                        <div class="mobile-team-line">
                            <span class="truncate font-semibold text-slate-900 dark:text-white">{{ match.awayTeam }}</span>
                            <span class="font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ match.awayCode }}</span>
                        </div>
                    </div>
                </div>
            </article>

            <div v-if="!crestsInside" class="shield-slot">
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
    align-items: center;
    gap: 1rem;
}

.match-shell-outside {
    grid-template-columns: auto minmax(0, 1fr) auto;
}

.match-shell-inside {
    grid-template-columns: minmax(0, 1fr);
}

.shield-slot {
    width: clamp(80px, 8vw, 108px);
    height: clamp(80px, 8vw, 108px);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.shield-image {
    display: block;
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    object-position: center center;
}

.match-body {
    min-width: 0;
}

.match-meta-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    align-items: center;
    gap: 0.75rem;
}

.match-meta-center {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    min-width: 0;
}

.match-meta-dot {
    width: 0.32rem;
    height: 0.32rem;
    border-radius: 9999px;
    background: rgb(148 163 184);
    flex: 0 0 auto;
}

.location-meta-text {
    transition: color 180ms ease;
}

.location-meta:hover .location-meta-text {
    color: rgb(6 182 212);
}

.dark .location-meta:hover .location-meta-text {
    color: rgb(34 211 238);
}

.match-content-grid {
    display: grid;
    align-items: center;
    gap: 1rem;
}

.match-content-grid-outside {
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
}

.match-content-grid-inside {
    grid-template-columns: auto minmax(0, 1fr) auto minmax(0, 1fr) auto;
}

.team-panel {
    display: flex;
    align-items: center;
    min-width: 0;
}

.team-panel-home {
    justify-content: flex-end;
    text-align: right;
}

.team-panel-away {
    justify-content: flex-start;
    text-align: left;
}

.team-copy {
    min-width: 0;
}

.match-score-wrap {
    display: flex;
    justify-content: center;
}

.team-crest-panel {
    display: flex;
    align-items: center;
    justify-content: center;
}

.shield-slot-inside {
    width: clamp(64px, 7vw, 88px);
    height: clamp(64px, 7vw, 88px);
}

.match-mobile-teams {
    display: none;
}

.shield-placeholder {
    width: 100%;
    height: 1px;
    visibility: hidden;
}

@media (max-width: 640px) {
    .match-shell-outside {
        grid-template-columns: 84px minmax(0, 1fr) 84px;
        gap: 0.75rem;
    }

    .shield-slot {
        width: 84px;
        height: 84px;
    }

    .match-content-grid {
        grid-template-columns: auto;
        gap: 0.75rem;
    }

    .match-meta-row {
        grid-template-columns: 1fr;
        justify-items: center;
        text-align: center;
        gap: 0.5rem;
    }

    .match-meta-center {
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .team-panel {
        display: none;
    }

    .team-crest-panel {
        display: none;
    }

    .match-mobile-teams {
        display: grid;
        gap: 0.35rem;
        text-align: center;
    }

    .mobile-team-line {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        min-width: 0;
    }
}

@media (max-width: 420px) {
    .match-shell-outside {
        grid-template-columns: 88px minmax(0, 1fr) 88px;
        gap: 0.5rem;
        align-items: center;
    }

    .shield-slot {
        width: 88px;
        height: 88px;
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
        font-size: 0.96rem;
    }
}
</style>
