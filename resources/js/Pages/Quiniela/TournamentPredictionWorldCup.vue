<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'
import StandingWidget from '@/Components/Quiniela/StandingsWidget.vue'
import WorldCupMatchCard from '@/Components/Quiniela/WorldCupMatchCard.vue'
import PredictionSuccessCard from '@/Components/Quiniela/PredictionSuccessCard.vue'

const props = defineProps({
    tournament: {
        type: Object,
        required: true,
    },
    groups: {
        type: Array,
        default: () => [],
    },
    games: {
        type: Array,
        default: () => [],
    },
    bracketRules: {
        type: Object,
        default: () => ({}),
    },
})

const page = usePage()
const poolEntryForm = useForm({
    tournament_id: props.tournament.id,
    predictions: [],
})

const stageDefinitions = [
    { key: 'group', label: 'Grupos' },
    { key: 'round_32', label: 'Round 32' },
    { key: 'round_16', label: 'Octavos' },
    { key: 'quarter', label: 'Cuartos' },
    { key: 'semi', label: 'Semis' },
    { key: 'third_place', label: '3er lugar' },
    { key: 'final', label: 'Final' },
]

const currentStage = ref('group')
const currentGroupIndex = ref(0)
const worldCupKickoff = new Date('2026-06-11T19:00:00-04:00')
const countdown = ref({
    days: '00',
    hours: '00',
    minutes: '00',
    seconds: '00',
})
let countdownTimer = null

const predictions = reactive(
    Object.fromEntries(
        props.games.map((game) => [game.id, { home: null, away: null }]),
    ),
)

const clamp = (value, min, max) => Math.min(max, Math.max(min, value))

const updateCountdown = () => {
    const difference = worldCupKickoff.getTime() - Date.now()

    if (difference <= 0) {
        countdown.value = { days: '00', hours: '00', minutes: '00', seconds: '00' }
        return
    }

    const totalSeconds = Math.floor(difference / 1000)
    const days = Math.floor(totalSeconds / 86400)
    const hours = Math.floor((totalSeconds % 86400) / 3600)
    const minutes = Math.floor((totalSeconds % 3600) / 60)
    const seconds = totalSeconds % 60

    countdown.value = {
        days: String(days).padStart(2, '0'),
        hours: String(hours).padStart(2, '0'),
        minutes: String(minutes).padStart(2, '0'),
        seconds: String(seconds).padStart(2, '0'),
    }
}

const formatMatchDate = (date) => {
    if (!date) {
        return 'Fecha por definir'
    }

    return new Date(`${date}T00:00:00`).toLocaleDateString('es-VE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

const formatMatchTime = (time) => {
    if (!time) {
        return null
    }

    return time.slice(0, 5)
}

const getWinProbability = (game) => {
    const homeSeed = game.home_team?.group_position ?? 2.5
    const awaySeed = game.away_team?.group_position ?? 2.5
    const weight = game.stage === 'group' ? 7 : 5
    const homeProbability = clamp(Math.round(50 + (awaySeed - homeSeed) * weight), 35, 65)

    return {
        label: `${homeProbability}%`,
        text: `${homeProbability}% prob. victoria`,
    }
}

const decoratedGames = computed(() => {
    return props.games.map((game) => {
        const probability = getWinProbability(game)

        return {
            ...game,
            display_date: formatMatchDate(game.match_date),
            display_time: formatMatchTime(game.match_time),
            win_probability: probability.label,
            probability_text: probability.text,
        }
    })
})

const gamesByStage = computed(() => {
    return stageDefinitions.reduce((accumulator, stage) => {
        accumulator[stage.key] = decoratedGames.value.filter((game) => game.stage === stage.key)
        return accumulator
    }, {})
})

const groupedStageMatches = computed(() => {
    return props.groups.map((group) => ({
        ...group,
        games: decoratedGames.value.filter(
            (game) => game.stage === 'group' && game.group_name === group.name,
        ),
    }))
})

const isGameFilled = (gameId) => {
    const prediction = predictions[gameId]
    return prediction?.home !== null && prediction?.away !== null
}

const isPredictionDecisive = (game) => {
    if (!isGameFilled(game.id)) {
        return false
    }

    if (game.stage === 'group') {
        return true
    }

    return predictions[game.id].home !== predictions[game.id].away
}

const progress = computed(() => {
    const filled = props.games.filter((game) => isGameFilled(game.id)).length
    const total = props.games.length || 104

    return {
        filled,
        total,
        percentage: total === 0 ? 0 : Math.round((filled / total) * 100),
    }
})

const predictionPayloads = computed(() => {
    return props.games
        .filter((game) => isGameFilled(game.id))
        .map((game) => ({
            game_id: game.id,
            home_score: predictions[game.id].home,
            away_score: predictions[game.id].away,
        }))
})

const hasInvalidKnockoutDraws = computed(() => {
    return decoratedGames.value.some((game) => game.stage !== 'group' && isGameFilled(game.id) && !isPredictionDecisive(game))
})

const canSubmitPoolEntry = computed(() => {
    return progress.value.total > 0
        && progress.value.filled === progress.value.total
        && !hasInvalidKnockoutDraws.value
})

const createdPoolEntry = computed(() => page.props.flash?.created_pool_entry ?? null)
const flashError = computed(() => page.props.flash?.error ?? null)

const stageProgress = computed(() => {
    return stageDefinitions.map((stage, index) => {
        const matches = gamesByStage.value[stage.key] || []
        const completed = matches.filter((game) => isPredictionDecisive(game)).length
        const unlocked = index === 0 || stageDefinitions
            .slice(0, index)
            .every((previousStage) => {
                const previousMatches = gamesByStage.value[previousStage.key] || []
                return previousMatches.length > 0 && previousMatches.every((game) => isPredictionDecisive(game))
            })

        return {
            ...stage,
            count: matches.length,
            completed,
            unlocked,
            isComplete: matches.length > 0 && completed === matches.length,
        }
    })
})

const groupProgress = computed(() => {
    return groupedStageMatches.value.map((group, index) => {
        const completed = group.games.filter((game) => isGameFilled(game.id)).length

        return {
            id: group.id,
            index,
            name: group.name,
            total: group.games.length,
            completed,
            isComplete: group.games.length > 0 && completed === group.games.length,
        }
    })
})

const calculateStandings = (group) => {
    const table = Object.fromEntries(
        group.teams.map((team) => [
            team.id,
            {
                team,
                played: 0,
                won: 0,
                drawn: 0,
                lost: 0,
                gf: 0,
                ga: 0,
                gd: 0,
                points: 0,
            },
        ]),
    )

    const games = decoratedGames.value.filter(
        (game) => game.stage === 'group' && game.group_name === group.name,
    )

    games.forEach((game) => {
        const prediction = predictions[game.id]
        if (!prediction || prediction.home === null || prediction.away === null) {
            return
        }

        const homeRow = table[game.home_team?.id]
        const awayRow = table[game.away_team?.id]

        if (!homeRow || !awayRow) {
            return
        }

        homeRow.played += 1
        awayRow.played += 1
        homeRow.gf += prediction.home
        homeRow.ga += prediction.away
        awayRow.gf += prediction.away
        awayRow.ga += prediction.home

        if (prediction.home > prediction.away) {
            homeRow.won += 1
            awayRow.lost += 1
            homeRow.points += 3
        } else if (prediction.home < prediction.away) {
            awayRow.won += 1
            homeRow.lost += 1
            awayRow.points += 3
        } else {
            homeRow.drawn += 1
            awayRow.drawn += 1
            homeRow.points += 1
            awayRow.points += 1
        }
    })

    return Object.values(table)
        .map((row) => ({
            ...row,
            gd: row.gf - row.ga,
        }))
        .sort((left, right) => {
            if (right.points !== left.points) {
                return right.points - left.points
            }

            if (right.gd !== left.gd) {
                return right.gd - left.gd
            }

            if (right.gf !== left.gf) {
                return right.gf - left.gf
            }

            return (left.team.group_position ?? 9) - (right.team.group_position ?? 9)
        })
}

const standingsByGroup = computed(() => {
    return Object.fromEntries(
        props.groups.map((group) => [group.name, calculateStandings(group)]),
    )
})

const teamsById = computed(() => {
    return Object.fromEntries(
        props.groups
            .flatMap((group) => group.teams)
            .map((team) => [team.id, team]),
    )
})

const compareStandingsRows = (left, right) => {
    if (right.points !== left.points) {
        return right.points - left.points
    }

    if (right.gd !== left.gd) {
        return right.gd - left.gd
    }

    if (left.ga !== right.ga) {
        return left.ga - right.ga
    }

    if (right.gf !== left.gf) {
        return right.gf - left.gf
    }

    if ((left.team?.group_name ?? '') !== (right.team?.group_name ?? '')) {
        return String(left.team?.group_name ?? '').localeCompare(String(right.team?.group_name ?? ''))
    }

    return (left.team?.group_position ?? 9) - (right.team?.group_position ?? 9)
}

const rankedThirdPlaceRows = computed(() => {
    return Object.values(standingsByGroup.value)
        .map((rows) => rows[2])
        .filter(Boolean)
        .sort(compareStandingsRows)
})

const qualifiedThirdPlaceRows = computed(() => rankedThirdPlaceRows.value.slice(0, 8))

const thirdPlaceAssignments = computed(() => {
    const qualifiedByGroup = Object.fromEntries(
        qualifiedThirdPlaceRows.value.map((row) => [row.team?.group_name, row]),
    )

    const slotCandidates = {}
    const thirdPlaceGames = decoratedGames.value.filter((game) => game.stage === 'round_32')

    thirdPlaceGames.forEach((game) => {
        ;['home', 'away'].forEach((side) => {
            const slot = side === 'home' ? game.home_slot : game.away_slot
            const matches = String(slot || '').match(/^3-([A-Z]+)$/)

            if (!matches) {
                return
            }

            const allowedGroups = matches[1]
                .split('')
                .filter((groupLetter) => Boolean(qualifiedByGroup[groupLetter]))

            const slotKey = `${game.match_number}:${side}`

            slotCandidates[slotKey] = {
                key: slotKey,
                matchNumber: Number(game.match_number),
                side,
                slot,
                allowedGroups,
            }
        })
    })

    const qualifiedGroups = qualifiedThirdPlaceRows.value
        .map((row) => row.team?.group_name)
        .filter(Boolean)
        .sort()

    const combinationKey = qualifiedGroups.join('')
    const configuredMatrix = props.bracketRules?.third_place_matrix?.[combinationKey] ?? null

    if (configuredMatrix) {
        const assignment = {}

        for (const [slotKey, slotCandidate] of Object.entries(slotCandidates)) {
            const configuredGroup = configuredMatrix[slotCandidate.matchNumber]

            if (!configuredGroup || !slotCandidate.allowedGroups.includes(configuredGroup)) {
                return {}
            }

            assignment[slotKey] = qualifiedByGroup[configuredGroup] ?? null
        }

        return assignment
    }

    const orderedSlots = Object.values(slotCandidates).sort((left, right) => {
        if (left.matchNumber !== right.matchNumber) {
            return left.matchNumber - right.matchNumber
        }

        return left.side.localeCompare(right.side)
    })

    const usedGroups = new Set()
    const assignment = {}

    for (const currentSlot of orderedSlots) {
        const selectedGroup = [...currentSlot.allowedGroups].sort((left, right) => {
            const leftIndex = qualifiedThirdPlaceRows.value.findIndex((row) => row.team?.group_name === left)
            const rightIndex = qualifiedThirdPlaceRows.value.findIndex((row) => row.team?.group_name === right)

            if (leftIndex !== rightIndex) {
                return leftIndex - rightIndex
            }

            return left.localeCompare(right)
        }).find((groupLetter) => !usedGroups.has(groupLetter))

        if (!selectedGroup) {
            return {}
        }

        assignment[currentSlot.key] = qualifiedByGroup[selectedGroup]
        usedGroups.add(selectedGroup)
    }

    return assignment
})

const resolvedKnockoutGamesById = computed(() => {
    const resolvedById = {}
    const resolvedByMatchNumber = {}

    const resolveWinnerForGame = (resolvedGame) => {
        const prediction = predictions[resolvedGame.id]

        if (!prediction || prediction.home === null || prediction.away === null) {
            return null
        }

        if (prediction.home === prediction.away) {
            return null
        }

        return prediction.home > prediction.away
            ? resolvedGame.home_team
            : resolvedGame.away_team
    }

    const resolveRunnerUpForGame = (resolvedGame) => {
        const winner = resolveWinnerForGame(resolvedGame)

        if (!winner) {
            return null
        }

        return winner.id === resolvedGame.home_team?.id
            ? resolvedGame.away_team
            : resolvedGame.home_team
    }

    const resolveSlotTeam = (slot, side, game) => {
        if (!slot) {
            return null
        }

        let matches = slot.match(/^([1-3])([A-Z])$/)
        if (matches) {
            const position = Number(matches[1]) - 1
            const groupLetter = matches[2]

            return standingsByGroup.value[groupLetter]?.[position]?.team ?? null
        }

        matches = slot.match(/^3-([A-Z]+)$/)
        if (matches) {
            const thirdPlaceRow = thirdPlaceAssignments.value[`${game.match_number}:${side}`]
            return thirdPlaceRow?.team ?? null
        }

        matches = slot.match(/^W(\d+)$/)
        if (matches) {
            const previousGame = resolvedByMatchNumber[matches[1]]
            return previousGame ? resolveWinnerForGame(previousGame) : null
        }

        matches = slot.match(/^RU(\d+)$/)
        if (matches) {
            const previousGame = resolvedByMatchNumber[matches[1]]
            return previousGame ? resolveRunnerUpForGame(previousGame) : null
        }

        return null
    }

    decoratedGames.value
        .filter((game) => game.stage !== 'group')
        .sort((left, right) => Number(left.match_number) - Number(right.match_number))
        .forEach((game) => {
            const homeTeam = game.home_slot
                ? (resolveSlotTeam(game.home_slot, 'home', game) ?? game.home_team)
                : game.home_team
            const awayTeam = game.away_slot
                ? (resolveSlotTeam(game.away_slot, 'away', game) ?? game.away_team)
                : game.away_team

            const resolvedGame = {
                ...game,
                home_team: homeTeam ? (teamsById.value[homeTeam.id] ?? homeTeam) : null,
                away_team: awayTeam ? (teamsById.value[awayTeam.id] ?? awayTeam) : null,
            }

            resolvedById[game.id] = resolvedGame
            resolvedByMatchNumber[String(game.match_number)] = resolvedGame
        })

    return resolvedById
})

const displayGames = computed(() => {
    return decoratedGames.value.map((game) => {
        if (game.stage === 'group') {
            return game
        }

        return resolvedKnockoutGamesById.value[game.id] ?? game
    })
})

const displayGamesByStage = computed(() => {
    return stageDefinitions.reduce((accumulator, stage) => {
        accumulator[stage.key] = displayGames.value.filter((game) => game.stage === stage.key)
        return accumulator
    }, {})
})

const displayGroupedStageMatches = computed(() => {
    return props.groups.map((group) => ({
        ...group,
        games: displayGames.value.filter(
            (game) => game.stage === 'group' && game.group_name === group.name,
        ),
    }))
})

const currentGroup = computed(() => displayGroupedStageMatches.value[currentGroupIndex.value] ?? null)

const currentGroupStatus = computed(() => groupProgress.value[currentGroupIndex.value] ?? null)

const currentStageStatus = computed(() => {
    return stageProgress.value.find((stage) => stage.key === currentStage.value) ?? null
})

const visibleStageMatches = computed(() => displayGamesByStage.value[currentStage.value] || [])

const nextUnlockedStage = computed(() => {
    const currentIndex = stageDefinitions.findIndex((stage) => stage.key === currentStage.value)

    return stageProgress.value
        .slice(currentIndex + 1)
        .find((stage) => stage.unlocked)
})

const switchStage = (stageKey, unlocked) => {
    if (unlocked) {
        currentStage.value = stageKey
    }
}

const selectGroup = (index) => {
    currentGroupIndex.value = index
}

const goToPreviousGroup = () => {
    if (currentGroupIndex.value > 0) {
        currentGroupIndex.value -= 1
    }
}

const goToNextGroup = () => {
    if (currentGroupIndex.value < groupedStageMatches.value.length - 1) {
        currentGroupIndex.value += 1
        return
    }

    const nextStage = stageProgress.value.find((stage) => stage.key === 'round_32' && stage.unlocked)
    if (nextStage) {
        currentStage.value = nextStage.key
    }
}

const goToNextStage = () => {
    if (nextUnlockedStage.value) {
        currentStage.value = nextUnlockedStage.value.key
    }
}

const submitPoolEntry = () => {
    if (!canSubmitPoolEntry.value || poolEntryForm.processing) {
        return
    }

    poolEntryForm.transform(() => ({
        tournament_id: props.tournament.id,
        predictions: predictionPayloads.value,
    })).post(route('pools.store'), {
        preserveScroll: true,
    })
}

watch(
    currentStage,
    (stage) => {
        if (stage !== 'group') {
            return
        }

        const firstIncompleteIndex = groupProgress.value.findIndex((group) => !group.isComplete)
        currentGroupIndex.value = firstIncompleteIndex >= 0 ? firstIncompleteIndex : 0
    },
    { immediate: true },
)

onMounted(() => {
    updateCountdown()
    countdownTimer = window.setInterval(updateCountdown, 1000)
})

onBeforeUnmount(() => {
    if (countdownTimer) {
        window.clearInterval(countdownTimer)
    }
})
</script>

<template>
    <Head :title="`Quiniela Mundial ${tournament.year ?? ''}`" />

    <UserDashboardLayout
        :title="`Quiniela del Mundial ${tournament.year ?? ''}`"
        description="Captura tus marcadores, sigue la tabla por grupo y deja que el bracket se vaya resolviendo a medida que completas cada fase."
    >
        <template #ticker>
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-500/15 text-primary-300">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="h-4 w-4 fill-current">
                            <path d="M0 188.6C0 84.4 86 0 192 0S384 84.4 384 188.6c0 119.3-120.2 262.3-170.4 316.8-11.8 12.8-31.5 12.8-43.3 0-50.2-54.5-170.4-197.5-170.4-316.8zM192 256a64 64 0 1 0 0-128 64 64 0 1 0 0 128z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.26em] text-primary-300">
                            Mundial 2026 en marcha
                        </p>
                        <p class="text-sm text-slate-200">
                            Completa tus 104 partidos y deja lista tu quiniela oficial.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-2 md:gap-3">
                    <div class="rounded-2xl border border-slate-500/60 bg-slate-800/80 px-3 py-2 text-center shadow-lg shadow-black/25 backdrop-blur">
                        <p class="text-xl font-black tracking-tight text-white">{{ countdown.days }}</p>
                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-200">Dias</p>
                    </div>
                    <div class="rounded-2xl border border-slate-500/60 bg-slate-800/80 px-3 py-2 text-center shadow-lg shadow-black/25 backdrop-blur">
                        <p class="text-xl font-black tracking-tight text-white">{{ countdown.hours }}</p>
                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-200">Horas</p>
                    </div>
                    <div class="rounded-2xl border border-slate-500/60 bg-slate-800/80 px-3 py-2 text-center shadow-lg shadow-black/25 backdrop-blur">
                        <p class="text-xl font-black tracking-tight text-white">{{ countdown.minutes }}</p>
                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-200">Min</p>
                    </div>
                    <div class="rounded-2xl border border-slate-500/60 bg-slate-800/80 px-3 py-2 text-center shadow-lg shadow-black/25 backdrop-blur">
                        <p class="text-xl font-black tracking-tight text-white">{{ countdown.seconds }}</p>
                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-200">Seg</p>
                    </div>
                </div>
            </div>
        </template>

        <template #headerActions>
            <button
                type="button"
                @click="submitPoolEntry"
                :disabled="!canSubmitPoolEntry || poolEntryForm.processing"
                class="inline-flex min-w-[220px] items-center justify-center rounded-2xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-sm shadow-primary-500/30 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ poolEntryForm.processing ? 'Registrando quiniela...' : 'Registrar quiniela' }}
            </button>
        </template>

        <div class="space-y-8 pb-16">
            <PredictionSuccessCard
                v-if="createdPoolEntry"
                :pool-entry="createdPoolEntry"
            />

            <section class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white/95 p-6 shadow-xl shadow-slate-200/70 md:p-8 dark:border-slate-800 dark:bg-slate-900/85 dark:shadow-none">
                <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                    <div class="max-w-3xl">
                        <p class="inline-flex rounded-full border border-primary-200 bg-primary-50 px-4 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-primary-700 dark:border-cyan-400/20 dark:bg-cyan-400/10 dark:text-cyan-200">
                            Quiniela guiada
                        </p>
                        <h1 class="mt-4 text-4xl font-black tracking-tight text-slate-950 md:text-5xl dark:text-white">
                            {{ tournament.name }}
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 md:text-base dark:text-slate-300">
                            Vista guiada para capturar la quiniela con datos reales del torneo, grupos, juegos, equipos y banderas.
                            La tabla por grupo se recalcula al vuelo mientras el usuario escribe sus marcadores.
                        </p>
                    </div>

                    <div class="w-full max-w-xl rounded-3xl border border-slate-200 bg-slate-50/90 p-5 backdrop-blur-sm dark:border-white/10 dark:bg-black/25">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Progreso global</p>
                                <p class="mt-1 text-3xl font-black text-slate-950 dark:text-white">
                                    {{ progress.filled }}
                                    <span class="text-slate-400 dark:text-slate-500">/ {{ progress.total }}</span>
                                </p>
                            </div>
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-right dark:border-emerald-400/20 dark:bg-emerald-400/10">
                                <p class="text-xs uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-200/80">Completado</p>
                                <p class="text-2xl font-black text-emerald-600 dark:text-emerald-300">{{ progress.percentage }}%</p>
                            </div>
                        </div>

                        <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-white/10">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-primary-500 via-sky-500 to-emerald-500 transition-all duration-500"
                                :style="{ width: `${progress.percentage}%` }"
                            />
                        </div>
                    </div>
                </div>

                <div v-if="flashError" class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-300/20 dark:bg-rose-400/10 dark:text-rose-100">
                    {{ flashError }}
                </div>

                <div v-if="hasInvalidKnockoutDraws" class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-300/20 dark:bg-amber-300/10 dark:text-amber-100">
                    En fases eliminatorias no se permiten empates en la quiniela. Define un ganador para que el bracket pueda avanzar correctamente.
                </div>

                <div class="mt-6 flex flex-col gap-4 rounded-3xl border border-slate-200 bg-slate-50/90 p-4 lg:flex-row lg:items-center lg:justify-between dark:border-white/10 dark:bg-black/20">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Registro final</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Cuando completes los {{ progress.total }} partidos, guardaremos la quiniela en tu cuenta y el numero de registro sera el ID unico de esa inscripcion.
                        </p>
                    </div>

                    <div class="rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm dark:bg-slate-900 dark:text-slate-200">
                        {{ progress.percentage }}% completado
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200/80 bg-white/95 p-4 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/85">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
                    <button
                        v-for="stage in stageProgress"
                        :key="stage.key"
                        type="button"
                        @click="switchStage(stage.key, stage.unlocked)"
                        :disabled="!stage.unlocked"
                        :class="[
                            currentStage === stage.key
                                ? 'border-primary-300 bg-primary-50 text-slate-950 shadow-sm dark:border-cyan-300 dark:bg-cyan-300/15 dark:text-white dark:shadow-lg dark:shadow-cyan-950/20'
                                : 'border-slate-200 bg-slate-50 text-slate-500 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-400',
                            !stage.unlocked && 'cursor-not-allowed opacity-50',
                        ]"
                        class="rounded-2xl border px-4 py-3 text-left transition hover:border-primary-300 hover:text-slate-950 dark:hover:border-cyan-300/40 dark:hover:text-white"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.2em]">
                            {{ stage.label }}
                        </p>
                        <p class="mt-2 text-lg font-black">
                            {{ stage.completed }}/{{ stage.count }}
                        </p>
                    </button>
                </div>
            </section>

            <section v-if="currentStage === 'group' && currentGroup" class="space-y-6">
                <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                    <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-5 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/85">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-600 dark:text-cyan-300/80">Progreso por grupos</p>
                                <h2 class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">Fase de grupos guiada</h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Completa un grupo a la vez. Cuando termines, avanzas al siguiente sin bajar por toda la pagina.
                                </p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-right dark:border-white/10 dark:bg-white/5">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Actual</p>
                                <p class="text-3xl font-black text-slate-950 dark:text-white">{{ currentGroup.name }}</p>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-6">
                            <button
                                v-for="group in groupProgress"
                                :key="group.id"
                                type="button"
                                @click="selectGroup(group.index)"
                                :class="[
                                    currentGroupIndex === group.index
                                        ? 'border-primary-300 bg-primary-50 text-slate-950 dark:border-cyan-300 dark:bg-cyan-300/15 dark:text-white'
                                        : group.isComplete
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-200'
                                            : 'border-slate-200 bg-slate-50 text-slate-600 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-300',
                                ]"
                                class="rounded-2xl border px-4 py-3 text-left transition"
                            >
                                <p class="text-xs font-semibold uppercase tracking-[0.2em]">
                                    Grupo {{ group.name }}
                                </p>
                                <p class="mt-2 text-xl font-black">
                                    {{ group.completed }}/{{ group.total }}
                                </p>
                            </button>
                        </div>
                    </div>

                    <StandingWidget :group-name="currentGroup.name" :standings="standingsByGroup[currentGroup.name] || []" />
                </div>

                <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-4 shadow-lg shadow-slate-200/60 backdrop-blur-xl md:p-6 dark:border-slate-800 dark:bg-slate-900/85 dark:shadow-none">
                    <div class="mb-6 flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                        <div class="rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-6 xl:w-[360px] dark:border-white/10 dark:from-white/5 dark:to-transparent">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-600 dark:text-cyan-300/80">Grupo</p>
                            <div class="mt-3 flex items-end gap-4">
                                <span class="text-6xl font-black leading-none text-slate-950 md:text-7xl dark:text-white">{{ currentGroup.name }}</span>
                                <div class="pb-2 text-sm text-slate-500 dark:text-slate-400">
                                    {{ currentGroupStatus?.completed || 0 }} / {{ currentGroupStatus?.total || 0 }} partidos
                                </div>
                            </div>
                            <p class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Estadistica en vivo a la derecha. Debajo tienes los partidos con banderas, iniciales, sede y probabilidad estimada.
                            </p>
                        </div>

                        <div class="flex flex-1 flex-col justify-between gap-4 rounded-3xl border border-slate-200 bg-slate-50/90 p-5 dark:border-white/10 dark:bg-white/5">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Guia del usuario</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                    Llena todos los marcadores del grupo {{ currentGroup.name }} para habilitar el siguiente paso.
                                </p>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <button
                                    type="button"
                                    @click="goToPreviousGroup"
                                    :disabled="currentGroupIndex === 0"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-primary-300 hover:text-primary-700 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-white/20 dark:hover:bg-slate-800"
                                >
                                    Anterior
                                </button>
                                <button
                                    type="button"
                                    @click="goToNextGroup"
                                    :disabled="!currentGroupStatus?.isComplete"
                                    class="inline-flex items-center justify-center rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm font-semibold text-primary-700 transition hover:bg-primary-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-cyan-300/20 dark:bg-cyan-400/10 dark:text-cyan-200 dark:hover:bg-cyan-400/20"
                                >
                                    {{ currentGroupIndex === groupedStageMatches.length - 1 ? 'Continuar a Round 32' : 'Siguiente grupo' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 2xl:grid-cols-2">
                        <WorldCupMatchCard
                            v-for="match in currentGroup.games"
                            :key="match.id"
                            v-model="predictions[match.id]"
                            :match="match"
                        />
                    </div>
                </div>
            </section>

            <section v-else class="space-y-6">
                <div class="grid gap-6 xl:grid-cols-[1fr_auto]">
                    <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-700 dark:border-amber-300/15 dark:bg-amber-300/5 dark:text-amber-100">
                        La navegacion entre fases avanza a medida que completas la etapa previa. Los cruces muestran equipos resueltos cuando existen y, mientras tanto, mantienen un placeholder visual.
                    </div>
                    <button
                        v-if="currentStageStatus?.isComplete && nextUnlockedStage"
                        type="button"
                        @click="goToNextStage"
                        class="inline-flex items-center justify-center rounded-2xl border border-primary-200 bg-primary-50 px-5 py-4 text-sm font-semibold text-primary-700 transition hover:bg-primary-100 dark:border-cyan-300/20 dark:bg-cyan-400/10 dark:text-cyan-200 dark:hover:bg-cyan-400/20"
                    >
                        Continuar a {{ nextUnlockedStage.label }}
                    </button>
                </div>

                <div class="grid gap-4 2xl:grid-cols-2">
                    <WorldCupMatchCard
                        v-for="match in visibleStageMatches"
                        :key="match.id"
                        v-model="predictions[match.id]"
                        :match="match"
                    />
                </div>
            </section>
        </div>
    </UserDashboardLayout>
</template>
