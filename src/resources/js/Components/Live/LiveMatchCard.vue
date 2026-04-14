<script setup>
import { computed } from 'vue'
import { CheckCircleIcon, ClockIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import AppTooltip from '@/Components/UI/AppTooltip.vue'
import { resolveMatchTeamColors } from '@/Components/Live/teamColors'

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
    showHeaderMeta: {
        type: Boolean,
        default: true,
    },
    showFooterMeta: {
        type: Boolean,
        default: true,
    },
    showCenteredLocation: {
        type: Boolean,
        default: false,
    },
})

const crestSrc = (src) => src || null
const isDraw = (match) => Number(match?.homeScore) === Number(match?.awayScore)
const isHomeWinner = (match) => Number(match?.homeScore) > Number(match?.awayScore)
const isAwayWinner = (match) => Number(match?.awayScore) > Number(match?.homeScore)

const normalizedStatusShort = computed(() => String(props.statusShort || '').trim().toUpperCase())
const normalizedLabel = computed(() => String(props.statusLabel || '').toLowerCase())
const crestsInside = computed(() => props.crestPlacement === 'inside')

const isFinished = computed(() => {
    if (['FT', 'AET', 'PEN'].includes(normalizedStatusShort.value)) return true
    return normalizedLabel.value.includes('finished') || normalizedLabel.value.includes('finalizado')
})

const displayStatusLabel = computed(() => (isFinished.value ? (props.statusLabel || 'Finalizado') : 'En progreso'))

const homePossession = computed(() => {
    const value = Number(props.match?.homePossession)
    return Number.isFinite(value) ? Math.max(0, Math.min(100, value)) : null
})

const awayPossession = computed(() => {
    const value = Number(props.match?.awayPossession)
    return Number.isFinite(value) ? Math.max(0, Math.min(100, value)) : null
})

const hasPossession = computed(() => homePossession.value !== null && awayPossession.value !== null)
const scoreGoalCount = (value) => {
    const parsed = Number(value)
    return Number.isFinite(parsed) && parsed > 0 ? Math.floor(parsed) : 0
}

const ensureGoalFeed = (feed, score, side) => {
    const items = Array.isArray(feed) ? [...feed] : []
    const total = scoreGoalCount(score)

    while (items.length < total) {
        items.push({
            playerName: 'Jugador por confirmar',
            playerNumber: null,
            minute: '',
            isFallback: true,
        })
    }

    return items.slice(0, Math.max(total, items.length))
}

const homeGoalsFeed = computed(() => ensureGoalFeed(props.match?.homeGoalsFeed, props.match?.homeScore, 'home'))
const awayGoalsFeed = computed(() => ensureGoalFeed(props.match?.awayGoalsFeed, props.match?.awayScore, 'away'))
const hasFooterData = computed(() => props.showFooterMeta && crestsInside.value && (hasPossession.value || homeGoalsFeed.value.length > 0 || awayGoalsFeed.value.length > 0))

const teamColors = computed(() => resolveMatchTeamColors({
    home: {
        name: props.match?.homeTeam,
        code: props.match?.homeCode,
    },
    away: {
        name: props.match?.awayTeam,
        code: props.match?.awayCode,
    },
}))

const goalTooltipText = (goal) => {
    if (goal?.isFallback) {
        return goal?.playerName || 'Jugador por confirmar'
    }

    const number = goal?.playerNumber !== null && goal?.playerNumber !== undefined && goal?.playerNumber !== ''
        ? `#${goal.playerNumber} `
        : ''
    const player = String(goal?.playerName || 'Jugador por confirmar').trim()
    const minute = String(goal?.minute || '').trim()

    return `${number}${player}${minute ? ` ${minute}` : ''}`.trim()
}
</script>

<template>
    <section class="card-shell">
        <div v-if="!crestsInside" class="card-topline">
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

        <div class="card-parent" :class="crestsInside ? 'card-parent-inside' : 'card-parent-outside'">
            <div v-if="!crestsInside" class="crest-outer-slot">
                <img
                    v-if="crestSrc(match.homeShieldUrl)"
                    :src="crestSrc(match.homeShieldUrl)"
                    :alt="match.homeTeam"
                    :title="match.homeTeam"
                    class="crest-image"
                    loading="lazy"
                />
                <span v-else class="crest-placeholder" aria-hidden="true" />
            </div>

            <article
                class="live-card relative overflow-hidden rounded-2xl border border-slate-200 bg-white px-5 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900/75"
                :class="[
                    hoverable ? 'transition hover:border-primary-300 hover:shadow-md dark:hover:border-primary-700' : '',
                    isFinished ? 'match-finished' : 'match-live',
                ]"
            >
                <header v-if="showHeaderMeta" class="card-header">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                        {{ match.groupName || '-' }}
                    </p>

                    <div class="header-center">
                        <div class="inline-flex items-center gap-1.5">
                            <ClockIcon class="h-4 w-4" :class="isFinished ? 'text-rose-500 dark:text-rose-400' : 'text-emerald-500 dark:text-emerald-400'" />
                            <span class="text-2xl font-black leading-none" :class="isFinished ? 'text-rose-500 dark:text-rose-400' : 'text-emerald-500 dark:text-emerald-400'">
                                {{ match.matchTime }}
                            </span>
                        </div>
                        <span class="header-dot" aria-hidden="true" />
                        <div class="location-chip inline-flex items-center justify-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                            <MapPinIcon class="h-4 w-4 text-cyan-500 dark:text-cyan-400" />
                            <span class="location-text truncate">{{ match.venue || 'Sede por confirmar' }}</span>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-bold tracking-wide" :class="isFinished ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'">
                            <CheckCircleIcon v-if="showStatusIcon && isFinished" class="h-3.5 w-3.5" />
                            <ClockIcon v-else-if="showStatusIcon" class="h-3.5 w-3.5" />
                            {{ displayStatusLabel }}
                        </span>
                    </div>
                </header>

                <div v-if="!crestsInside && showHeaderMeta" class="card-location-outside">
                    <MapPinIcon class="h-4 w-4 text-cyan-500 dark:text-cyan-400" />
                    <span class="truncate">{{ match.venue || 'Sede por confirmar' }}</span>
                </div>

                <div v-if="showCenteredLocation" class="card-location-centered">
                    <MapPinIcon class="h-4 w-4 text-cyan-500 dark:text-cyan-400" />
                    <span class="truncate">{{ match.venue || 'Sede por confirmar' }}</span>
                </div>

                <div class="body-governor" :class="crestsInside ? 'body-governor-inside' : 'body-governor-outside'">
                    <div v-if="crestsInside" class="crest-inner-slot crest-inner-slot-home">
                        <img
                            v-if="crestSrc(match.homeShieldUrl)"
                            :src="crestSrc(match.homeShieldUrl)"
                            :alt="match.homeTeam"
                            :title="match.homeTeam"
                            class="crest-image"
                            loading="lazy"
                        />
                        <span v-else class="crest-placeholder" aria-hidden="true" />
                    </div>

                    <div class="body-stack">
                        <div class="card-body" :class="crestsInside ? 'card-body-inside' : 'card-body-outside'">
                            <div class="country-block country-block-home">
                                <div class="country-copy">
                                    <span class="team-name block truncate text-base font-semibold text-slate-900 dark:text-white md:text-[1.1rem]">{{ match.homeTeam }}</span>
                                    <span class="country-code block text-sm font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ match.homeCode }}</span>
                                </div>
                            </div>

                            <div class="score-block">
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

                            <div class="country-block country-block-away">
                                <div class="country-copy">
                                    <span class="team-name block truncate text-base font-semibold text-slate-900 dark:text-white md:text-[1.1rem]">{{ match.awayTeam }}</span>
                                    <span class="country-code block text-sm font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ match.awayCode }}</span>
                                </div>
                            </div>
                        </div>

                        <footer v-if="hasFooterData" class="card-footer">
                            <div class="footer-box goal-side goal-side-home">
                                <AppTooltip
                                    v-for="(goal, index) in homeGoalsFeed"
                                    :key="`home-goal-${index}-${goal.minute}`"
                                    :text="goalTooltipText(goal)"
                                    placement="top"
                                    tooltip-class="max-w-none whitespace-nowrap"
                                >
                                    <span class="goal-icon goal-icon-home" aria-label="Gol local">
                                        <svg viewBox="0 0 640 512" class="h-3.5 w-3.5 fill-current" aria-hidden="true">
                                            <path d="M320.2 112c44.2 0 80-35.8 80-80l53.5 0c17 0 33.3 6.7 45.3 18.7L617.6 169.4c12.5 12.5 12.5 32.8 0 45.3l-50.7 50.7c-12.5 12.5-32.8 12.5-45.3 0l-41.4-41.4 0 224c0 35.3-28.7 64-64 64l-192 0c-35.3 0-64-28.7-64-64l0-224-41.4 41.4c-12.5 12.5-32.8 12.5-45.3 0L22.9 214.6c-12.5-12.5-12.5-32.8 0-45.3L141.5 50.7c12-12 28.3-18.7 45.3-18.7l53.5 0c0 44.2 35.8 80 80 80z"/>
                                        </svg>
                                    </span>
                                </AppTooltip>
                            </div>

                            <div class="footer-box possession-core" :class="{ 'possession-core-empty': !hasPossession }">
                                <div class="possession-slot">
                                    <AppTooltip :text="hasPossession ? `${homePossession}% posesion` : 'Sin datos de posesion'" placement="top">
                                        <div class="possession-lane possession-lane-home">
                                            <div class="possession-track">
                                                <div
                                                    class="possession-line possession-line-home"
                                                    :style="{
                                                        '--possession-pct': hasPossession ? homePossession : 0,
                                                        background: `linear-gradient(270deg, ${teamColors.homeColor}88 0%, ${teamColors.homeColor} 100%)`,
                                                    }"
                                                />
                                            </div>
                                        </div>
                                    </AppTooltip>
                                </div>

                                <AppTooltip text="Posesion del balon" placement="top">
                                    <div class="possession-ball" aria-hidden="true">
                                        <svg viewBox="0 0 512 512" class="h-4 w-4 fill-current">
                                            <path d="M417.3 360.1l-71.6-4.8c-5.2-.3-10.3 1.1-14.5 4.2s-7.2 7.4-8.4 12.5l-17.6 69.6C289.5 445.8 273 448 256 448s-33.5-2.2-49.2-6.4L189.2 372c-1.3-5-4.3-9.4-8.4-12.5s-9.3-4.5-14.5-4.2l-71.6 4.8c-17.6-27.2-28.5-59.2-30.4-93.6L125 228.3c4.4-2.8 7.6-7 9.2-11.9s1.4-10.2-.5-15l-26.7-66.6C128 109.2 155.3 89 186.7 76.9l55.2 46c4 3.3 9 5.1 14.1 5.1s10.2-1.8 14.1-5.1l55.2-46c31.3 12.1 58.7 32.3 79.6 57.9l-26.7 66.6c-1.9 4.8-2.1 10.1-.5 15s4.9 9.1 9.2 11.9l60.7 38.2c-1.9 34.4-12.8 66.4-30.4 93.6zM256 512a256 256 0 1 0 0-512 256 256 0 1 0 0 512zm14.1-325.7c-8.4-6.1-19.8-6.1-28.2 0L194 221c-8.4 6.1-11.9 16.9-8.7 26.8l18.3 56.3c3.2 9.9 12.4 16.6 22.8 16.6l59.2 0c10.4 0 19.6-6.7 22.8-16.6l18.3-56.3c3.2-9.9-.3-20.7-8.7-26.8l-47.9-34.8z"/>
                                        </svg>
                                    </div>
                                </AppTooltip>

                                <div class="possession-slot">
                                    <AppTooltip :text="hasPossession ? `${awayPossession}% posesion` : 'Sin datos de posesion'" placement="top">
                                        <div class="possession-lane possession-lane-away">
                                            <div class="possession-track">
                                                <div
                                                    class="possession-line possession-line-away"
                                                    :style="{
                                                        '--possession-pct': hasPossession ? awayPossession : 0,
                                                        background: `linear-gradient(90deg, ${teamColors.awayColor} 100%, ${teamColors.awayColor}88 0%)`,
                                                    }"
                                                />
                                            </div>
                                        </div>
                                    </AppTooltip>
                                </div>
                            </div>

                            <div class="footer-box goal-side goal-side-away">
                                <AppTooltip
                                    v-for="(goal, index) in awayGoalsFeed"
                                    :key="`away-goal-${index}-${goal.minute}`"
                                    :text="goalTooltipText(goal)"
                                    placement="top"
                                    tooltip-class="max-w-none whitespace-nowrap"
                                >
                                    <span class="goal-icon goal-icon-away" aria-label="Gol visitante">
                                        <svg viewBox="0 0 640 512" class="h-3.5 w-3.5 fill-current" aria-hidden="true">
                                            <path d="M320.2 112c44.2 0 80-35.8 80-80l53.5 0c17 0 33.3 6.7 45.3 18.7L617.6 169.4c12.5 12.5 12.5 32.8 0 45.3l-50.7 50.7c-12.5 12.5-32.8 12.5-45.3 0l-41.4-41.4 0 224c0 35.3-28.7 64-64 64l-192 0c-35.3 0-64-28.7-64-64l0-224-41.4 41.4c-12.5 12.5-32.8 12.5-45.3 0L22.9 214.6c-12.5-12.5-12.5-32.8 0-45.3L141.5 50.7c12-12 28.3-18.7 45.3-18.7l53.5 0c0 44.2 35.8 80 80 80z"/>
                                        </svg>
                                    </span>
                                </AppTooltip>
                            </div>
                        </footer>
                    </div>

                    <div v-if="crestsInside" class="crest-inner-slot crest-inner-slot-away">
                        <img
                            v-if="crestSrc(match.awayShieldUrl)"
                            :src="crestSrc(match.awayShieldUrl)"
                            :alt="match.awayTeam"
                            :title="match.awayTeam"
                            class="crest-image"
                            loading="lazy"
                        />
                        <span v-else class="crest-placeholder" aria-hidden="true" />
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
            </article>

            <div v-if="!crestsInside" class="crest-outer-slot">
                <img
                    v-if="crestSrc(match.awayShieldUrl)"
                    :src="crestSrc(match.awayShieldUrl)"
                    :alt="match.awayTeam"
                    :title="match.awayTeam"
                    class="crest-image"
                    loading="lazy"
                />
                <span v-else class="crest-placeholder" aria-hidden="true" />
            </div>
        </div>
    </section>
</template>

<style scoped>
.live-card::before {
    content: '';
    position: absolute;
    top: -1px;
    left: -36%;
    width: 36%;
    height: 3px;
    border-radius: 9999px;
}

.match-live.live-card::before {
    background: linear-gradient(90deg, rgba(16, 185, 129, 0) 0%, rgba(16, 185, 129, 0.75) 35%, rgba(34, 197, 94, 1) 55%, rgba(52, 211, 153, 0.85) 75%, rgba(16, 185, 129, 0) 100%);
    box-shadow: 0 0 10px rgba(34, 197, 94, 0.8);
    animation: live-scan 2.2s ease-in-out infinite alternate;
}

.match-finished.live-card::before {
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
    .live-card::before {
        animation: none;
        left: 0;
        width: 100%;
        opacity: 0.9;
    }
}

.card-shell {
    display: grid;
    gap: 0.5rem;
}

.card-topline {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 0.5rem;
}

.card-parent {
    display: grid;
    align-items: center;
    gap: 1rem;
}

.card-parent-outside {
    grid-template-columns: auto minmax(0, 1fr) auto;
}

.card-parent-inside {
    grid-template-columns: minmax(0, 1fr);
}

.body-governor {
    display: grid;
    align-items: center;
    gap: 1rem;
}

.body-governor-inside {
    grid-template-columns: clamp(64px, 7vw, 88px) minmax(0, 1fr) clamp(64px, 7vw, 88px);
}

.body-governor-outside {
    grid-template-columns: minmax(0, 1fr);
}

.body-stack {
    display: grid;
    gap: 0.7rem;
    min-width: 0;
}

.crest-outer-slot,
.crest-inner-slot {
    display: flex;
    align-items: center;
    justify-content: center;
}

.crest-inner-slot-home {
    justify-self: start;
}

.crest-inner-slot-away {
    justify-self: end;
}

.crest-outer-slot {
    width: clamp(80px, 8vw, 108px);
    height: clamp(80px, 8vw, 108px);
}

.crest-inner-slot {
    width: clamp(64px, 7vw, 88px);
    height: clamp(64px, 7vw, 88px);
}

.crest-image {
    display: block;
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
    object-position: center center;
}

.crest-placeholder {
    width: 100%;
    height: 1px;
    visibility: hidden;
}

.card-header {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    align-items: center;
    gap: 0.75rem;
}

.header-center {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    min-width: 0;
}

.header-dot {
    width: 0.32rem;
    height: 0.32rem;
    border-radius: 9999px;
    background: rgb(148 163 184);
    flex: 0 0 auto;
}

.location-text {
    transition: color 180ms ease;
}

.location-chip:hover .location-text {
    color: rgb(6 182 212);
}

.dark .location-chip:hover .location-text {
    color: rgb(34 211 238);
}

.card-location-outside {
    display: inline-flex;
    width: 100%;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: rgb(100 116 139);
}

.card-location-centered {
    display: inline-flex;
    width: 100%;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    margin-bottom: 0.85rem;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: rgb(100 116 139);
    transition: color 180ms ease;
}

.card-location-centered:hover {
    color: rgb(8 145 178);
}

.card-location-centered svg {
    filter: drop-shadow(0 0 8px rgba(34, 211, 238, 0.45));
}

.card-body {
    display: grid;
    align-items: center;
    gap: 1rem;
}

.card-body-outside {
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
}

.card-body-inside {
    grid-template-columns: minmax(92px, 140px) auto minmax(92px, 140px);
    justify-content: center;
}

.country-block {
    display: flex;
    align-items: center;
    min-width: 0;
    min-height: 64px;
}

.country-block-home {
    justify-content: flex-end;
    text-align: right;
    padding-right: 4px;
}

.country-block-away {
    justify-content: flex-start;
    text-align: left;
    padding-left: 4px;
}

.country-copy {
    display: grid;
    align-content: center;
    gap: 0.2rem;
    width: 100%;
    min-width: 0;
}

.team-name {
    line-height: 1.05;
}

.country-code {
    line-height: 1;
}

.score-block {
    display: flex;
    justify-content: center;
}

.card-footer {
    display: grid;
    grid-template-columns: minmax(124px, 1fr) minmax(0, 1.6fr) minmax(124px, 1fr);
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    min-width: 0;
    padding-top: 0.25rem;
}

.footer-box {
    min-width: 0;
}

.goal-side {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    min-height: 28px;
    width: 100%;
}

.goal-side-home {
    justify-content: flex-start;
}

.goal-side-away {
    justify-content: flex-end;
}

.goal-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.55rem;
    height: 1.55rem;
    border-radius: 9999px;
    background: rgb(241 245 249);
    color: rgb(71 85 105);
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.28);
}

.goal-icon-home {
    color: rgb(22 163 74);
}

.goal-icon-away {
    color: rgb(37 99 235);
}

.dark .goal-icon {
    background: rgb(30 41 59 / 0.88);
    color: rgb(226 232 240);
}

.possession-core {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 24px minmax(0, 1fr);
    align-items: center;
    column-gap: 0.85rem;
    width: 100%;
    min-width: 0;
    justify-self: center;
}

.possession-core-empty {
    min-height: 28px;
}

.possession-slot {
    width: 100%;
    min-width: 0;
}

.possession-core :deep(.group) {
    display: flex;
    width: 100%;
}

.possession-core :deep(.group > .inline-flex) {
    display: flex;
    width: 100%;
}

.possession-lane {
    display: flex;
    align-items: center;
    width: 100%;
    min-width: 0;
}

.possession-track {
    width: 100%;
    height: 0.34rem;
    border-radius: 9999px;
    background: rgb(226 232 240 / 0.95);
    display: flex;
    overflow: hidden;
}

.possession-lane-home {
    justify-content: flex-end;
}

.possession-lane-home .possession-track {
    justify-content: flex-end;
}

.possession-lane-away {
    justify-content: flex-start;
}

.possession-line {
    height: 0.34rem;
    width: calc(100% * var(--possession-pct) / 100);
    border-radius: 9999px;
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.18);
}

.possession-ball {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    color: rgb(71 85 105);
}

.possession-core-empty .possession-ball {
    opacity: 0.65;
}

.dark .possession-track {
    background: rgb(51 65 85 / 0.95);
}

.dark .possession-ball {
    color: rgb(148 163 184);
}

.match-mobile-teams {
    display: none;
}

@media (max-width: 960px) and (min-width: 641px) {
    .body-governor-inside {
        grid-template-columns: 56px minmax(0, 1fr) 56px;
        gap: 0.75rem;
    }

    .card-body-inside {
        grid-template-columns: minmax(88px, 120px) auto minmax(88px, 120px);
        gap: 0.65rem;
    }

    .crest-inner-slot {
        width: 56px;
        height: 56px;
    }

    .card-body-inside .team-name {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 1rem;
    }

    .goal-side {
        min-width: 104px;
    }
}

@media (max-width: 640px) {
    .card-parent-outside {
        grid-template-columns: 84px minmax(0, 1fr) 84px;
        gap: 0.75rem;
    }

    .crest-outer-slot {
        width: 84px;
        height: 84px;
    }

    .card-header {
        grid-template-columns: 1fr;
        justify-items: center;
        text-align: center;
        gap: 0.5rem;
    }

    .header-center {
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .card-body {
        grid-template-columns: auto;
        gap: 0.75rem;
    }

    .country-block,
    .crest-inner-slot {
        display: none;
    }

    .body-governor {
        outline: none;
    }

    .body-stack {
        gap: 0.6rem;
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

    .card-footer {
        grid-template-columns: 1fr;
        justify-items: center;
        gap: 0.55rem;
        width: 100%;
    }

    .goal-side {
        min-width: 0;
    }

    .goal-side-home,
    .goal-side-away {
        justify-content: center;
    }
}

@media (max-width: 420px) {
    .card-parent-outside {
        grid-template-columns: 88px minmax(0, 1fr) 88px;
        gap: 0.5rem;
    }

    .crest-outer-slot {
        width: 88px;
        height: 88px;
    }

    .live-card {
        padding: 0.6rem 0.55rem;
    }

    .card-location-outside {
        font-size: 0.9rem;
        line-height: 1.2rem;
    }

    .possession-core {
        column-gap: 0.45rem;
    }

    .goal-icon {
        width: 1.4rem;
        height: 1.4rem;
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
