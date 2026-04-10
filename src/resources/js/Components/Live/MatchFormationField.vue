<script setup>
/**
 * MatchFormationField.vue
 *
 * Renders an SVG football pitch with both teams' lineups positioned
 * according to their formation string (e.g. "4-3-3", "4-4-2").
 *
 * Props:
 *   lineups  – Array de alineaciones de API-Football (/fixtures/lineups)
 *              Cada elemento: { team, formation, startXI: [{player:{name,number,pos,grid}}], coach }
 *
 * Uso:
 *   <MatchFormationField :lineups="feed.lineups" />
 */
import { computed } from 'vue'
import TeamShirtIcon from '@/Components/Live/TeamShirtIcon.vue'

const props = defineProps({
    lineups: {
        type: Array,
        default: () => [],
    },
    homeTeamId: {
        type: Number,
        default: 0,
    },
    awayTeamId: {
        type: Number,
        default: 0,
    },
    homeColor: {
        type: String,
        default: '#22d3ee',
    },
    awayColor: {
        type: String,
        default: '#a3e635',
    },
    homeTeam: {
        type: String,
        default: 'Local',
    },
    awayTeam: {
        type: String,
        default: 'Visitante',
    },
})

// ── Field dimensions (SVG viewport) ─────────────────────────────────────────
const W = 520   // width
const H = 580   // height
const PAD = 34  // side padding for player circles
const VIEWBOX_Y = -10
const VIEWBOX_H = H + 10

// ── Parse formation string into row counts ───────────────────────────────────
// "4-3-3" → [4, 3, 3]   (outfield rows, GK handled separately)
const parseFormation = (str) => {
    if (!str) return [4, 3, 3]
    return str.split('-').map(Number).filter((n) => !Number.isNaN(n) && n > 0)
}

// ── Map grid string "row:col" from API to [row, col] ────────────────────────
const parseGrid = (gridStr) => {
    if (!gridStr) return null
    const [r, c] = gridStr.split(':').map(Number)
    return Number.isFinite(r) && Number.isFinite(c) ? [r, c] : null
}

// ── Compute (x, y) for each player ──────────────────────────────────────────
//  - homeTeam occupies the bottom half (y: H*0.55 → H-PAD)
//  - awayTeam occupies the top half    (y: PAD    → H*0.45)
//  - GK is always at the extremes (row 1)
const computePositions = (players, formation, isHome) => {
    // Try grid-based positioning first (API provides "grid" on each player)
    const rows = parseFormation(formation)
    const gridRows = players
        .map((entry) => parseGrid(entry?.player?.grid)?.[0] ?? 0)
        .filter((value) => value > 0)
    // API grid manda filas reales (1..N). Si no viene, usamos formación.
    const totalRows = gridRows.length ? Math.max(...gridRows) : (rows.length + 1)

    // Zonas simétricas para que la formación quede visualmente centrada.
    const yMin = isHome ? H * 0.58 : H * 0.18
    const yMax = isHome ? H * 0.86 : H * 0.46
    const yRange = yMax - yMin

    return players.map((entry) => {
        const p = entry?.player ?? {}
        const grid = parseGrid(p.grid)

        let rowIdx, colIdx, colCount

        if (grid) {
            rowIdx  = grid[0] - 1   // 0-based
            colIdx  = grid[1] - 1   // 0-based
            // colCount = players in same row
            colCount = players.filter((e) => {
                const g = parseGrid(e?.player?.grid)
                return g && g[0] === grid[0]
            }).length
        } else {
            rowIdx   = 0
            colIdx   = 0
            colCount = 1
        }

        // Y: distribute rows evenly
        const yStep = totalRows > 1 ? yRange / (totalRows - 1) : 0
        let y = isHome
            ? yMax - rowIdx * yStep
            : yMin + rowIdx * yStep

        // X: distribute columns evenly within row
        const xUsable = W - PAD * 2
        const xStep  = colCount > 1 ? xUsable / (colCount - 1) : 0
        let x = colCount === 1
            ? W / 2
            : PAD + colIdx * xStep

        return {
            id:     p.id     ?? `p-${Math.random().toString(36).slice(2, 6)}`,
            number: p.number ?? '-',
            x,
            y,
        }
    })
}

const alignTeamByGoalkeeper = (team) => {
    if (!team?.players?.length) {
        return team
    }

    const currentGkY = team.isHome
        ? Math.max(...team.players.map((player) => player.y))
        : Math.min(...team.players.map((player) => player.y))

    const topTarget = 68
    const bottomTarget = H - 68
    const targetGkY = team.isHome ? bottomTarget : topTarget
    const delta = targetGkY - currentGkY

    return {
        ...team,
        players: team.players.map((player) => ({
            ...player,
            y: Math.max(PAD + 10, Math.min(H - PAD - 10, player.y + delta)),
        })),
    }
}

// ── Build render data from lineups prop ─────────────────────────────────────
const teams = computed(() => {
    if (!props.lineups?.length) return []

    const byTeamId = new Map(props.lineups.map((lineup) => [Number(lineup?.team?.id || 0), lineup]))
    const homeLineup = byTeamId.get(props.homeTeamId) || props.lineups[0]
    const awayLineup = byTeamId.get(props.awayTeamId) || props.lineups.find((lineup) => lineup !== homeLineup) || props.lineups[1]

    const rawTeams = [homeLineup, awayLineup].filter(Boolean).map((lineup, idx) => {
        const isHome = idx === 0
        const players = lineup.startXI ?? []
        const formation = lineup.formation ?? ''
        const positions = computePositions(players, formation, isHome)

        return {
            teamName: lineup.team?.name ?? (isHome ? 'Local' : 'Visitante'),
            formation,
            isHome,
            color: isHome ? props.homeColor : props.awayColor,
            players: positions,
        }
    })

    return rawTeams.map((team) => alignTeamByGoalkeeper(team))
})

const homeLegend = computed(() => ({
    name: props.homeTeam || teams.value?.[0]?.teamName || 'Local',
    formation: teams.value?.[0]?.formation || '-',
}))

const awayLegend = computed(() => ({
    name: props.awayTeam || teams.value?.[1]?.teamName || 'Visitante',
    formation: teams.value?.[1]?.formation || '-',
}))

// ── SVG pitch lines helper values ────────────────────────────────────────────
const pitch = {
    W, H,
    // Center circle
    cx: W / 2, cy: H / 2, cr: 52,
    // Penalty areas
    penaltyW: 220, penaltyH: 68,
    penaltyX: (W - 220) / 2,
    // Goal areas
    goalW: 110, goalH: 28,
    goalX: (W - 110) / 2,
    // Goals
    goalPostW: 66,
    goalPostX: (W - 66) / 2,
}
</script>

<template>
    <section class="rounded-2xl border border-slate-200 bg-white px-3 pt-3 pb-3 dark:border-slate-800 dark:bg-slate-900/75">
        <div v-if="teams.length" class="mb-1.5 flex items-center justify-between gap-3 text-xs text-slate-600 dark:text-slate-300">
            <span class="flex items-center gap-1.5">
                <span class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: props.homeColor }" />
                <span class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-300">{{ homeLegend.name }}</span>
                <span class="text-slate-400">({{ homeLegend.formation }})</span>
            </span>

            <span class="flex items-center gap-1.5 text-right">
                <span class="text-slate-400">({{ awayLegend.formation }})</span>
                <span class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-300">{{ awayLegend.name }}</span>
                <span class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: props.awayColor }" />
            </span>
        </div>

        <div v-if="!lineups?.length" class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">
            Sin alineaciones disponibles por ahora.
        </div>

        <div v-else class="formation-canvas flex items-center justify-center overflow-hidden rounded-none">
            <svg
                :viewBox="`0 ${VIEWBOX_Y} ${pitch.W} ${VIEWBOX_H}`"
                xmlns="http://www.w3.org/2000/svg"
                class="field-svg w-full"
                role="img"
                aria-label="Formaciones del partido"
            >
                <!-- ── Pitch background ── -->
                <defs>
                    <!-- Grass base tone -->
                    <linearGradient id="grass-base" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#215f33" />
                        <stop offset="45%" stop-color="#1b532c" />
                        <stop offset="100%" stop-color="#164726" />
                    </linearGradient>

                    <!-- Vertical mow stripes -->
                    <pattern id="grass-stripes" width="96" height="96" patternUnits="userSpaceOnUse">
                        <rect x="0" y="0" width="48" height="96" fill="#2c7a42" />
                        <rect x="48" y="0" width="48" height="96" fill="#22693a" />
                    </pattern>

                    <!-- Subtle noise / grain -->
                    <pattern id="grass-noise" width="8" height="8" patternUnits="userSpaceOnUse">
                        <circle cx="1.2" cy="1.4" r="0.5" fill="rgba(255,255,255,0.12)" />
                        <circle cx="5.8" cy="2.2" r="0.45" fill="rgba(255,255,255,0.09)" />
                        <circle cx="3.4" cy="5.9" r="0.5" fill="rgba(0,0,0,0.12)" />
                        <circle cx="7.1" cy="6.8" r="0.4" fill="rgba(0,0,0,0.1)" />
                    </pattern>

                    <!-- Edge vignette for depth -->
                    <radialGradient id="grass-vignette" cx="50%" cy="50%" r="68%">
                        <stop offset="72%" stop-color="rgba(0,0,0,0)" />
                        <stop offset="100%" stop-color="rgba(0,0,0,0.36)" />
                    </radialGradient>

                    <!-- Player circle shadow -->
                    <filter id="player-shadow" x="-40%" y="-40%" width="180%" height="180%">
                        <feDropShadow dx="0" dy="1" stdDeviation="1.5" flood-color="#000000" flood-opacity="0.4" />
                    </filter>
                </defs>

                <!-- Field fill layers -->
                <rect width="100%" height="100%" fill="url(#grass-base)" />
                <rect width="100%" height="100%" fill="url(#grass-stripes)" opacity="0.42" />
                <rect width="100%" height="100%" fill="url(#grass-noise)" opacity="0.22" />
                <rect width="100%" height="100%" fill="url(#grass-vignette)" />

                <!-- ── Pitch lines ── -->
                <g stroke="rgba(255,255,255,0.68)" stroke-width="2.6" fill="none">
                    <!-- Border -->
                    <rect :x="4" :y="4" :width="W - 8" :height="H - 8" rx="5" />
                    <!-- Halfway line -->
                    <line :x1="4" :y1="H / 2" :x2="W - 4" :y2="H / 2" />
                    <!-- Center circle -->
                    <circle :cx="pitch.cx" :cy="pitch.cy" :r="pitch.cr" />
                    <!-- Center spot -->
                    <circle :cx="pitch.cx" :cy="pitch.cy" r="2.8" fill="rgba(255,255,255,0.65)" />

                    <!-- Top penalty area -->
                    <rect :x="pitch.penaltyX" :y="4" :width="pitch.penaltyW" :height="pitch.penaltyH" />
                    <!-- Top goal area -->
                    <rect :x="pitch.goalX" :y="4" :width="pitch.goalW" :height="pitch.goalH" />
                    <!-- Top goal (post) -->
                    <rect :x="pitch.goalPostX" :y="0" :width="pitch.goalPostW" :height="5" fill="rgba(255,255,255,0.35)" stroke="rgba(255,255,255,0.72)" />
                    <!-- Top penalty spot -->
                    <circle :cx="W / 2" :cy="pitch.penaltyH - 2" r="2.2" fill="rgba(255,255,255,0.65)" />

                    <!-- Bottom penalty area -->
                    <rect :x="pitch.penaltyX" :y="H - 4 - pitch.penaltyH" :width="pitch.penaltyW" :height="pitch.penaltyH" />
                    <!-- Bottom goal area -->
                    <rect :x="pitch.goalX" :y="H - 4 - pitch.goalH" :width="pitch.goalW" :height="pitch.goalH" />
                    <!-- Bottom goal (post) -->
                    <rect :x="pitch.goalPostX" :y="H - 5" :width="pitch.goalPostW" :height="5" fill="rgba(255,255,255,0.35)" stroke="rgba(255,255,255,0.72)" />
                    <!-- Bottom penalty spot -->
                    <circle :cx="W / 2" :cy="H - pitch.penaltyH + 2" r="2.2" fill="rgba(255,255,255,0.65)" />
                </g>

                <!-- ── Players ── -->
                <g v-for="team in teams" :key="team.teamName">
                    <g
                        v-for="player in team.players"
                        :key="player.id"
                        class="player-node"
                    >
                        <foreignObject
                            :x="player.x - 32"
                            :y="player.y - 33"
                            width="64"
                            height="62"
                            class="overflow-visible"
                        >
                            <div class="flex h-full w-full items-center justify-center">
                                <TeamShirtIcon :color="team.color" :number="player.number" :size="50" />
                            </div>
                        </foreignObject>
                    </g>
                </g>
            </svg>
        </div>
    </section>
</template>

<style scoped>
.field-svg {
    display: block;
    margin-inline: auto;
    width: 100%;
    height: auto;
    max-height: 640px;
}

.formation-canvas {
    width: 100%;
    min-height: 0;
}
</style>
