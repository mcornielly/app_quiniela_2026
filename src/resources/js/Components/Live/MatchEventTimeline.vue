<script setup>
/**
 * MatchEventTimeline.vue
 *
 * Props:
 *   events      – Array de eventos de API-Football (/fixtures/events)
 *   homeTeamId  – ID numérico del equipo local  (para alinear eventos)
 *   homeTeam    – Nombre del equipo local
 *   awayTeam    – Nombre del equipo visitante
 *
 * Uso:
 *   <MatchEventTimeline
 *     :events="feed.events"
 *     :home-team-id="currentHomeApiId"
 *     :home-team="displayMatch.homeTeam"
 *     :away-team="displayMatch.awayTeam"
 *   />
 */
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import TeamShirtIcon from '@/Components/Live/TeamShirtIcon.vue'
import SoccerBallIcon from '@/Components/Live/SoccerBallIcon.vue'

const props = defineProps({
    events: {
        type: Array,
        default: () => [],
    },
    homeTeamId: {
        type: Number,
        default: 0,
    },
    homeTeam: {
        type: String,
        default: 'Local',
    },
    awayTeam: {
        type: String,
        default: 'Visitante',
    },
    homeCode: {
        type: String,
        default: '',
    },
    awayCode: {
        type: String,
        default: '',
    },
    homeColor: {
        type: String,
        default: '#22d3ee',
    },
    awayColor: {
        type: String,
        default: '#a3e635',
    },
})

const eventTeamColor = (event) => {
    if (Number(event?.team?.id) === props.homeTeamId) {
        return props.homeColor
    }

    return props.awayColor
}

const eventColor = (event) => {
    if (event.type === 'Goal') return 'goal'
    if (event.type === 'Card' && event.detail === 'Yellow Card') return 'yellow'
    if (event.type === 'Card' && (event.detail === 'Red Card' || event.detail === 'Yellow Red Card')) return 'red'
    if (event.type === 'subst') return 'subst'
    return 'default'
}

const eventKind = (event) => {
    if (event?.type === 'Goal') return 'goal'
    if (event?.type === 'Card' && event?.detail === 'Yellow Card') return 'yellow'
    if (event?.type === 'Card' && (event?.detail === 'Red Card' || event?.detail === 'Yellow Red Card')) return 'red'
    if (event?.type === 'subst') return 'subst'
    if (event?.type === 'Var') return 'var'
    return 'team'
}

const minuteLabel = (event) => {
    const min = Number(event?.time?.elapsed ?? 0)
    const extra = Number(event?.time?.extra ?? 0)
    return extra > 0 ? `${min}+${extra}'` : `${min}'`
}

const shortPlayerName = (name) => {
    const safe = String(name || '').trim()
    if (!safe) return '-'
    const parts = safe.split(' ')
    if (parts.length < 2) return safe
    return `${parts[0][0]}. ${parts.slice(1).join(' ')}`
}

const eventTooltip = (event) => {
    const player = event?.player?.name || 'Sin jugador'
    const detail = event?.detail || event?.type || 'Evento'
    return `${player} · ${detail} · ${minuteLabel(event)}`
}

const flagFromCode = (code) => {
    const normalized = String(code || '').trim().toUpperCase()
    if (!/^[A-Z]{2}$/.test(normalized)) return '🏳️'
    const chars = [...normalized].map((ch) => 127397 + ch.charCodeAt(0))
    return String.fromCodePoint(...chars)
}

// ── Data ─────────────────────────────────────────────────────────────────────

const sorted = computed(() =>
    [...props.events].sort((a, b) => Number(a?.time?.elapsed ?? 0) - Number(b?.time?.elapsed ?? 0))
)

const homeEvents = computed(() =>
    sorted.value.filter((e) => Number(e?.team?.id) === props.homeTeamId)
)

const awayEvents = computed(() =>
    sorted.value.filter((e) => Number(e?.team?.id) !== props.homeTeamId)
)

// Posición porcentual a lo largo de la línea (0–100), clamp 2–98
const pct = (elapsed) => {
    const raw = Number(elapsed ?? 0)
    const maxMin = 95
    return Math.max(2, Math.min(98, (raw / maxMin) * 100))
}

// Minutos especiales marcadores en la línea
const MARKERS = [0, 15, 30, 45, 60, 75, 90]

const timelineScroller = ref(null)
const canScroll = ref(false)
const canScrollLeft = ref(false)
const canScrollRight = ref(false)

const updateScrollState = () => {
    const el = timelineScroller.value
    if (!el) {
        canScroll.value = false
        canScrollLeft.value = false
        canScrollRight.value = false
        return
    }

    const maxScroll = Math.max(0, el.scrollWidth - el.clientWidth)
    canScroll.value = maxScroll > 4
    canScrollLeft.value = el.scrollLeft > 4
    canScrollRight.value = el.scrollLeft < maxScroll - 4
}

const scrollTimeline = (direction) => {
    const el = timelineScroller.value
    if (!el) return
    const delta = Math.round(el.clientWidth * 0.6) * direction
    el.scrollBy({ left: delta, behavior: 'smooth' })
}

const onTimelineScroll = () => updateScrollState()
const onWindowResize = () => updateScrollState()

onMounted(async () => {
    await nextTick()
    updateScrollState()
    window.addEventListener('resize', onWindowResize)
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', onWindowResize)
})

watch(() => props.events, async () => {
    await nextTick()
    updateScrollState()
}, { deep: true })
</script>

<template>
    <section class="timeline-root">

        <div v-if="!sorted.length" class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">
            Sin eventos disponibles por ahora.
        </div>

        <div v-else class="timeline-wrap">
            <div class="timeline-team-head">
                <span class="inline-flex min-w-0 items-center gap-2" :title="homeTeam">
                    <span class="text-xl leading-none">{{ flagFromCode(homeCode) }}</span>
                    <span class="truncate text-[11px] font-semibold uppercase tracking-[0.24em]">{{ homeTeam }}</span>
                </span>
                <span class="inline-flex min-w-0 items-center justify-end gap-2 text-right" :title="awayTeam">
                    <span class="truncate text-[11px] font-semibold uppercase tracking-[0.24em]">{{ awayTeam }}</span>
                    <span class="text-xl leading-none">{{ flagFromCode(awayCode) }}</span>
                </span>
            </div>

            <button
                v-if="canScroll && canScrollLeft"
                type="button"
                class="timeline-nav timeline-nav-left"
                aria-label="Ver eventos anteriores"
                @click="scrollTimeline(-1)"
            >
                <span class="timeline-nav-pill">
                    <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7" />
                    </svg>
                    <span class="sr-only">Anterior</span>
                </span>
            </button>

            <div ref="timelineScroller" class="timeline-scroll" @scroll="onTimelineScroll">
                <div class="timeline-board select-none">
                    <div class="timeline-lanes">
                        <div class="timeline-home-icon">
                            <span :title="homeTeam" aria-label="Equipo local">
                                <TeamShirtIcon :size="28" :color="homeColor" />
                            </span>
                        </div>
                        <div class="timeline-away-icon">
                            <span :title="awayTeam" aria-label="Equipo visitante">
                                <TeamShirtIcon :size="28" :color="awayColor" />
                            </span>
                        </div>

                        <div
                            v-for="min in MARKERS"
                            :key="`m-${min}`"
                            class="timeline-marker"
                            :style="{ left: `${pct(min)}%` }"
                        >
                            <span class="timeline-marker-dot">{{ min }}'</span>
                        </div>

                        <div
                            v-for="(event, idx) in homeEvents"
                            :key="`home-${idx}`"
                            class="timeline-event timeline-event-home"
                            :style="{ left: `${pct(event.time?.elapsed)}%` }"
                        >
                            <p class="timeline-player">{{ shortPlayerName(event.player?.name) }}</p>
                            <span class="timeline-glyph" :title="eventTooltip(event)">
                                <template v-if="eventKind(event) === 'goal'">
                                    <SoccerBallIcon :size="20" class="timeline-ball text-slate-700 dark:hidden" />
                                    <SoccerBallIcon :size="20" class="timeline-ball hidden text-white dark:inline" />
                                </template>
                                <span v-else-if="eventKind(event) === 'yellow'" class="timeline-card timeline-card-yellow" :title="eventTooltip(event)" />
                                <span v-else-if="eventKind(event) === 'red'" class="timeline-card timeline-card-red" :title="eventTooltip(event)" />
                                <span v-else-if="eventKind(event) === 'subst'" class="timeline-subst">↻</span>
                                <span v-else-if="eventKind(event) === 'var'" class="timeline-var">VAR</span>
                                <TeamShirtIcon v-else :size="20" :color="eventTeamColor(event)" />
                            </span>
                            <span class="timeline-minute" :class="`badge-${eventColor(event)}`">{{ minuteLabel(event) }}</span>
                        </div>

                        <div
                            v-for="(event, idx) in awayEvents"
                            :key="`away-${idx}`"
                            class="timeline-event timeline-event-away"
                            :style="{ left: `${pct(event.time?.elapsed)}%` }"
                        >
                            <span class="timeline-minute" :class="`badge-${eventColor(event)}`">{{ minuteLabel(event) }}</span>
                            <span class="timeline-glyph" :title="eventTooltip(event)">
                                <template v-if="eventKind(event) === 'goal'">
                                    <SoccerBallIcon :size="20" class="timeline-ball text-slate-700 dark:hidden" />
                                    <SoccerBallIcon :size="20" class="timeline-ball hidden text-white dark:inline" />
                                </template>
                                <span v-else-if="eventKind(event) === 'yellow'" class="timeline-card timeline-card-yellow" :title="eventTooltip(event)" />
                                <span v-else-if="eventKind(event) === 'red'" class="timeline-card timeline-card-red" :title="eventTooltip(event)" />
                                <span v-else-if="eventKind(event) === 'subst'" class="timeline-subst">↻</span>
                                <span v-else-if="eventKind(event) === 'var'" class="timeline-var">VAR</span>
                                <TeamShirtIcon v-else :size="20" :color="eventTeamColor(event)" />
                            </span>
                            <p class="timeline-player">{{ shortPlayerName(event.player?.name) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <button
                v-if="canScroll && canScrollRight"
                type="button"
                class="timeline-nav timeline-nav-right"
                aria-label="Ver eventos siguientes"
                @click="scrollTimeline(1)"
            >
                <span class="timeline-nav-pill">
                    <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                    </svg>
                    <span class="sr-only">Siguiente</span>
                </span>
            </button>
        </div>
    </section>
</template>

<style scoped>
/* Badge colors – fuera de Tailwind para mantener dinámico con :class */
.badge-goal    { background-color: #10b981; }
.badge-yellow  { background-color: #f59e0b; }
.badge-red     { background-color: #ef4444; }
.badge-subst   { background-color: #6366f1; }
.badge-default { background-color: #64748b; }

.timeline-root {
    overflow: hidden;
}

.timeline-wrap {
    position: relative;
}

.timeline-scroll {
    overflow-x: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.6) transparent;
}

.timeline-scroll::-webkit-scrollbar {
    height: 8px;
}

.timeline-scroll::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.5);
    border-radius: 999px;
}

.timeline-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 2;
    height: 100%;
    width: 2.25rem;
    padding: 0;
    border: 0;
    background: transparent;
    color: #0f172a;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.timeline-wrap:hover .timeline-nav,
.timeline-wrap:focus-within .timeline-nav {
    opacity: 1;
}

.timeline-nav-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(255, 255, 255, 0.3);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.25);
    transition: background-color 0.2s ease, transform 0.2s ease;
}

.timeline-nav:hover .timeline-nav-pill {
    background: rgba(255, 255, 255, 0.5);
    transform: scale(1.03);
}

.timeline-nav:focus-visible {
    outline: none;
}

.timeline-nav:focus-visible .timeline-nav-pill {
    box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.55);
}

.timeline-nav-left {
    left: 0;
}

.timeline-nav-right {
    right: 0;
}

.timeline-board {
    min-width: 800px;
}

.timeline-team-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.6rem;
    padding-bottom: 0.4rem;
    border-bottom: 1px solid #cbd5e1;
    color: rgb(51 65 85);
    font-size: 0.82rem;
    font-weight: 700;
}

.timeline-lanes {
    position: relative;
    height: 200px;
    padding-inline: 1.75rem;
}

.timeline-lanes::before {
    content: '';
    position: absolute;
    left: 1.75rem;
    right: 1.75rem;
    top: 50%;
    height: 2px;
    transform: translateY(-50%);
    background: #cbd5e1;
}

.timeline-home-icon,
.timeline-away-icon {
    position: absolute;
    left: 0.2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.timeline-home-icon {
    top: 28%;
    transform: translateY(-50%);
}

.timeline-away-icon {
    top: 72%;
    transform: translateY(-50%);
}

.timeline-marker {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
}

.timeline-marker-dot {
    display: inline-flex;
    min-width: 28px;
    justify-content: center;
    border-radius: 9999px;
    border: 1px solid #cbd5e1;
    background: #fff;
    padding: 2px 6px;
    font-size: 9px;
    font-weight: 700;
    color: #64748b;
}

.timeline-event {
    position: absolute;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.18rem;
}

.timeline-event-home {
    top: 20%;
}

.timeline-event-away {
    top: 56%;
}

.timeline-player {
    max-width: 120px;
    text-align: center;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.05;
    color: #334155;
    white-space: nowrap;
}

.timeline-glyph {
    min-height: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #1e293b;
}

.timeline-ball {
    filter: none;
}

.timeline-minute {
    display: inline-flex;
    border-radius: 9999px;
    padding: 3px 7px;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    line-height: 1;
}

.timeline-card {
    display: inline-block;
    width: 12px;
    height: 16px;
    border-radius: 2px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.25);
}

.timeline-card-yellow { background: #facc15; }
.timeline-card-red { background: #ef4444; }

.timeline-subst {
    font-size: 16px;
    font-weight: 800;
    line-height: 1;
    color: #06b6d4;
}

.timeline-var {
    border-radius: 4px;
    background: #e2e8f0;
    color: #334155;
    font-size: 8px;
    font-weight: 800;
    line-height: 1;
    padding: 2px 4px;
}

:global(.dark) .timeline-team-head { color: #f1f5f9; }
:global(.dark) .timeline-team-head { border-bottom-color: #64748b; }
:global(.dark) .timeline-lanes::before { background: #475569; }
:global(.dark) .timeline-marker-dot {
    border-color: #475569;
    background: #0f172a;
    color: #e2e8f0;
}
:global(.dark) .timeline-player { color: #f1f5f9; }
:global(.dark) .timeline-glyph { color: #f1f5f9; }
:global(.dark) .timeline-var {
    background: #475569;
    color: #f1f5f9;
}
:global(.dark) .timeline-nav {
    color: #f1f5f9;
}

:global(.dark) .timeline-nav-pill {
    border-color: rgba(51, 65, 85, 0.9);
    background: rgba(30, 41, 59, 0.35);
}

:global(.dark) .timeline-nav:hover .timeline-nav-pill {
    background: rgba(30, 41, 59, 0.65);
}
</style>
