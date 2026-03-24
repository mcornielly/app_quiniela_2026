<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import StandingWidget from '@/Components/Quiniela/StandingsWidget.vue'
import WorldCupMatchCard from '@/Components/Quiniela/WorldCupMatchCard.vue'
import PredictionSuccessCard from '@/Components/Quiniela/PredictionSuccessCard.vue'
import FlowbiteModal from '@/Components/UI/FlowbiteModal.vue'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'

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
    participationRules: {
        type: Object,
        default: () => ({}),
    },
})

const page = usePage()
const poolEntryForm = useForm({
    tournament_id: props.tournament.id,
    name: '',
    predictions: [],
})
const showNameStepModal = ref(false)
const hasConfirmedPoolName = ref(false)

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

const predictions = reactive(
    Object.fromEntries(
        props.games.map((game) => [game.id, { home: null, away: null }]),
    ),
)

const clamp = (value, min, max) => Math.min(max, Math.max(min, value))

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
        && props.participationRules?.isOpen !== false
})

const createdPoolEntry = computed(() => page.props.flash?.created_pool_entry ?? null)
const flashError = computed(() => page.props.flash?.error ?? null)
const currentUserName = computed(() => String(page.props.auth?.user?.name ?? 'Usuario').trim() || 'Usuario')

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
const currentGroupMatches = computed(() => {
    if (!currentGroup.value) {
        return []
    }

    return (displayGamesByStage.value.group || []).filter(
        (game) => game.group_name === currentGroup.value.name,
    )
})

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
    if (!hasConfirmedPoolName.value || !poolEntryForm.name.trim()) {
        showNameStepModal.value = true
        return
    }

    if (!canSubmitPoolEntry.value || poolEntryForm.processing) {
        return
    }

    poolEntryForm.transform(() => ({
        tournament_id: props.tournament.id,
        name: poolEntryForm.name.trim(),
        predictions: predictionPayloads.value,
    })).post(route('pools.store'), {
        preserveScroll: true,
    })
}

const openNameStepModal = () => {
    if (!createdPoolEntry.value) {
        showNameStepModal.value = true
    }
}

const closeNameStepModal = () => {
    if (!hasConfirmedPoolName.value) {
        return
    }

    showNameStepModal.value = false
}

const confirmPoolName = () => {
    const normalizedName = poolEntryForm.name.trim().replace(/\s+/g, ' ')

    if (normalizedName.length < 3) {
        return
    }

    poolEntryForm.name = normalizedName
    hasConfirmedPoolName.value = true
    showNameStepModal.value = false
}

watch(
    createdPoolEntry,
    (value) => {
        if (value) {
            showNameStepModal.value = false
        }
    },
)

watch(
    () => props.participationRules?.isOpen,
    (isOpen) => {
        if (isOpen === false) {
            showNameStepModal.value = false
            return
        }

        if (!hasConfirmedPoolName.value && !createdPoolEntry.value) {
            showNameStepModal.value = true
        }
    },
    { immediate: true },
)

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
</script>

<template>
<Head title="Quiniela Mundial" />

    <UserDashboardLayout>
        <Head :title="`Quiniela Mundial ${tournament.year ?? ''}`" />

        <template #ticker>
            <div class="h-12 w-full" aria-hidden="true" />
        </template>

        <template #headerContent>
            <div class="hidden" />
        </template>

        <div class="space-y-8 pb-16">
            <PredictionSuccessCard
                v-if="createdPoolEntry"
                :pool-entry="createdPoolEntry"
            />

            <section>
                <div class="-mt-8 flex items-center justify-between gap-4 text-left">
                    <div class="inline-flex items-center gap-2 text-3xl font-bold text-slate-900 dark:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="h-8 w-8 fill-current text-cyan-500" aria-hidden="true">
                            <path d="M96 96c0-35.3 28.7-64 64-64H416c35.3 0 64 28.7 64 64V352c0 35.3-28.7 64-64 64H160c-35.3 0-64-28.7-64-64V96zM0 128c0-17.7 14.3-32 32-32s32 14.3 32 32V384c0 53 43 96 96 96H384c17.7 0 32 14.3 32 32s-14.3 32-32 32H160C71.6 544 0 472.4 0 384V128z"/>
                        </svg>
                        <h1>Crear quiniela</h1>
                    </div>
                    <Link
                        :href="route('pools.index')"
                        class="inline-flex items-center whitespace-nowrap rounded-md px-2 py-1.5 text-xs font-bold uppercase tracking-wide text-primary-700 transition hover:bg-gray-200 dark:text-primary-500 dark:hover:bg-gray-600"
                    >
                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Volver a mis quinielas
                    </Link>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/75">
                    <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-end">
                        <div class="grid gap-2 text-xs sm:grid-cols-2">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-950/50">
                                <p class="uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Usuario</p>
                                <p class="mt-1 truncate text-sm font-semibold text-slate-900 dark:text-white">{{ currentUserName }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-950/50">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Nombre de quiniela</p>
                                    <button
                                        type="button"
                                        @click="openNameStepModal"
                                        class="inline-flex items-center rounded-full border border-cyan-300/40 bg-cyan-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-cyan-700 transition hover:bg-cyan-100 dark:border-cyan-300/20 dark:bg-cyan-400/10 dark:text-cyan-200 dark:hover:bg-cyan-400/20"
                                    >
                                        Editar
                                    </button>
                                </div>
                                <p class="mt-1 truncate text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ poolEntryForm.name || 'Pendiente por definir' }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="submitPoolEntry"
                            :disabled="!canSubmitPoolEntry || poolEntryForm.processing || !hasConfirmedPoolName"
                            class="inline-flex min-w-[220px] items-center justify-center rounded-xl border border-emerald-300/50 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-emerald-300/20 dark:bg-emerald-400/10 dark:text-emerald-100 dark:hover:bg-emerald-400/20"
                        >
                            {{ poolEntryForm.processing ? 'Registrando quiniela...' : 'Registrar quiniela' }}
                        </button>
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/60">
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Progreso global</p>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ progress.filled }} / {{ progress.total }} · {{ progress.percentage }}%</p>
                        </div>
                        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-200 dark:bg-white/10">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-cyan-400 via-blue-500 to-emerald-400 transition-all duration-500"
                                :style="{ width: `${progress.percentage}%` }"
                            />
                        </div>
                    </div>
                </div>

                <div v-if="flashError" class="mt-4 rounded-xl border border-rose-300/40 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-300/20 dark:bg-rose-400/10 dark:text-rose-100">
                    {{ flashError }}
                </div>

                <div v-if="props.participationRules?.isOpen === false" class="mt-4 rounded-xl border border-rose-300/40 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-300/20 dark:bg-rose-400/10 dark:text-rose-100">
                    La participacion de quinielas esta cerrada desde el {{ props.participationRules?.closeAtLabel ?? 'limite configurado' }}.
                </div>

                <div v-if="hasInvalidKnockoutDraws" class="mt-4 rounded-xl border border-amber-300/40 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-300/20 dark:bg-amber-300/10 dark:text-amber-100">
                    En fases eliminatorias no se permiten empates en la quiniela. Define un ganador para que el bracket pueda avanzar correctamente.
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-slate-100 p-4 dark:border-slate-800 dark:bg-slate-900/70">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
                    <button
                        v-for="stage in stageProgress"
                        :key="stage.key"
                        type="button"
                        @click="switchStage(stage.key, stage.unlocked)"
                        :disabled="!stage.unlocked"
                        :class="[
                            currentStage === stage.key
                                ? 'border-cyan-300 bg-cyan-50 text-cyan-800 dark:bg-cyan-300/15 dark:text-white'
                                : 'border-slate-300 bg-white text-slate-500 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-400',
                            !stage.unlocked && 'cursor-not-allowed opacity-50',
                        ]"
                        class="rounded-2xl border px-4 py-3 text-left transition hover:border-cyan-300/40"
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
                    <div class="rounded-2xl border border-slate-200 bg-slate-100 p-5 dark:border-slate-800 dark:bg-slate-900/70">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-600 dark:text-cyan-300/80">Progreso por grupos</p>
                                <h2 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Fase de grupos guiada</h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Completa un grupo a la vez.
                                </p>
                            </div>
                            <div class="rounded-2xl border border-slate-300 bg-white px-4 py-3 text-right dark:border-white/10 dark:bg-white/5">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Actual</p>
                                <p class="text-3xl font-black text-slate-900 dark:text-white">{{ currentGroup.name }}</p>
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
                                        ? 'border-cyan-300 bg-cyan-50 text-cyan-800 dark:bg-cyan-300/15 dark:text-white'
                                        : group.isComplete
                                            ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-200'
                                            : 'border-slate-300 bg-white text-slate-700 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-300',
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

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/75 md:p-5">
                    <div class="mb-5 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 xl:w-[360px] dark:border-white/10 dark:bg-slate-950/60">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-600 dark:text-cyan-300/80">Grupo</p>
                            <div class="mt-3 flex items-end gap-4">
                                <span class="text-5xl font-black leading-none text-slate-900 md:text-6xl dark:text-white">{{ currentGroup.name }}</span>
                                <div class="pb-2 text-sm text-slate-500 dark:text-slate-400">
                                    {{ currentGroupStatus?.completed || 0 }} / {{ currentGroupStatus?.total || 0 }} partidos
                                </div>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Encuentros del grupo seleccionado.
                            </p>
                        </div>

                        <div class="flex flex-1 flex-col justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-slate-950/60">
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
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-white/20 dark:hover:bg-slate-800"
                                >
                                    Anterior
                                </button>
                                <button
                                    type="button"
                                    @click="goToNextGroup"
                                    :disabled="!currentGroupStatus?.isComplete"
                                    class="inline-flex items-center justify-center rounded-xl border border-cyan-300/50 bg-cyan-50 px-4 py-3 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-cyan-300/20 dark:bg-cyan-400/10 dark:text-cyan-200 dark:hover:bg-cyan-400/20"
                                >
                                    {{ currentGroupIndex === groupedStageMatches.length - 1 ? 'Continuar a Round 32' : 'Siguiente grupo' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <WorldCupMatchCard
                            v-for="match in currentGroupMatches"
                            :key="match.id"
                            v-model="predictions[match.id]"
                            :match="match"
                        />
                    </div>
                </div>
            </section>

            <section v-else class="space-y-6">
                <div class="grid gap-6 xl:grid-cols-[1fr_auto]">
                    <div class="rounded-2xl border border-amber-300/40 bg-amber-50 p-5 text-sm text-amber-700 dark:border-amber-300/15 dark:bg-amber-300/5 dark:text-amber-100">
                        La navegacion entre fases avanza a medida que completas la etapa previa. Los cruces muestran equipos resueltos cuando existen y, mientras tanto, mantienen un placeholder visual.
                    </div>
                    <button
                        v-if="currentStageStatus?.isComplete && nextUnlockedStage"
                        type="button"
                        @click="goToNextStage"
                        class="inline-flex items-center justify-center rounded-xl border border-cyan-300/50 bg-cyan-50 px-5 py-4 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-100 dark:border-cyan-300/20 dark:bg-cyan-400/10 dark:text-cyan-200 dark:hover:bg-cyan-400/20"
                    >
                        Continuar a {{ nextUnlockedStage.label }}
                    </button>
                </div>

                <div class="space-y-3">
                    <WorldCupMatchCard
                        v-for="match in visibleStageMatches"
                        :key="match.id"
                        v-model="predictions[match.id]"
                        :match="match"
                    />
                </div>
            </section>
        </div>

        <FlowbiteModal
            :show="showNameStepModal"
            max-width="xl"
            :closeable="hasConfirmedPoolName"
            @close="closeNameStepModal"
        >
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-primary-600 dark:text-primary-400">
                        Paso 1 de 2
                    </p>
                    <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                        Nombra tu quiniela
                    </h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        Este nombre se guarda en la pool junto al usuario actual.
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Usuario</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ currentUserName }}</p>
                </div>

                <div>
                    <label for="pool-entry-name" class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                        Nombre de la quiniela
                    </label>
                    <input
                        id="pool-entry-name"
                        v-model="poolEntryForm.name"
                        type="text"
                        maxlength="80"
                        placeholder="Ej: Jrmcorneilly #3"
                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary-400 focus:ring-4 focus:ring-primary-200/50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-primary-500 dark:focus:ring-primary-500/20"
                    >
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        Minimo 3 caracteres. Maximo 80.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button
                        v-if="hasConfirmedPoolName"
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        @click="closeNameStepModal"
                    >
                        Cerrar
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-primary-300/30 bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="poolEntryForm.name.trim().length < 3"
                        @click="confirmPoolName"
                    >
                        Continuar a creacion
                    </button>
                </div>
            </div>
        </FlowbiteModal>
    </UserDashboardLayout>
</template>
