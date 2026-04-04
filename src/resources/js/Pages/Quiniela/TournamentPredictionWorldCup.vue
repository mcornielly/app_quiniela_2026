<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import StandingWidget from '@/Components/Quiniela/StandingsWidget.vue'
import WorldCupMatchCard from '@/Components/Quiniela/WorldCupMatchCard.vue'
import PredictionSuccessCard from '@/Components/Quiniela/PredictionSuccessCard.vue'
import FlowbiteModal from '@/Components/UI/FlowbiteModal.vue'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'
import { notifyWarning } from '@/Utils/notify'
import { ElMessageBox } from 'element-plus'

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
const favoriteTeamTheme = computed(() => page.props.auth?.user?.favorite_team_theme ?? null)
const tickerThemes = {
    neutral: {
        tickerClass: 'border-t border-slate-300/70 bg-[linear-gradient(to_right,_#cfd6df_0%,_#e4e8ee_45%,_#f4f6f9_100%)] text-slate-900',
    },
}
const activeTickerTheme = computed(() => ({
    ...tickerThemes.neutral,
    ...(favoriteTeamTheme.value ?? {}),
}))
const themedSecondaryButtonClass = computed(() => activeTickerTheme.value?.buttonSecondaryClass
    ?? 'border-cyan-400 bg-slate-100 text-slate-700 hover:text-white hover:bg-cyan-300 focus:ring-slate-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:focus:ring-slate-700')
const themedPrimaryButtonClass = computed(() => activeTickerTheme.value?.buttonPrimaryClass
    ?? 'bg-cyan-400 text-slate-950 hover:bg-cyan-300 hover:text-white focus:ring-cyan-200 dark:bg-cyan-400 dark:hover:bg-cyan-300 dark:focus:ring-cyan-900')
const themedGroupPanelClass = computed(() => activeTickerTheme.value?.groupPanelClass
    ?? 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/70')
const themedGroupEyebrowClass = computed(() => activeTickerTheme.value?.groupEyebrowClass
    ?? activeTickerTheme.value?.eyebrowClass
    ?? 'text-cyan-600 dark:text-cyan-300/80')
const themedGroupTitleClass = computed(() => activeTickerTheme.value?.groupTitleClass
    ?? activeTickerTheme.value?.teamNameClass
    ?? 'text-slate-900 dark:text-white')
const themedGroupBodyClass = computed(() => activeTickerTheme.value?.groupBodyClass
    ?? 'text-slate-500 dark:text-slate-400')
const themedCurrentGroupBadgeClass = computed(() => activeTickerTheme.value?.groupBadgeClass
    ?? 'border-cyan-300/70 bg-cyan-400/15 shadow-[0_0_18px_rgba(34,211,238,0.28)]')
const themedCurrentGroupValueClass = computed(() => activeTickerTheme.value?.groupBadgeValueClass
    ?? activeTickerTheme.value?.statsValueClass
    ?? 'text-cyan-500 dark:text-cyan-300')
const themedActiveGroupCardClass = computed(() => activeTickerTheme.value?.groupActiveCardClass
    ?? activeTickerTheme.value?.buttonPrimaryClass
    ?? 'border-cyan-400 bg-cyan-400 text-slate-950 shadow-[0_0_16px_rgba(34,211,238,0.28)] dark:bg-cyan-400 dark:text-slate-950')
const themedInactiveGroupCardClass = computed(() => activeTickerTheme.value?.groupInactiveCardClass
    ?? 'border-slate-300 bg-white text-slate-500 opacity-70 hover:border-slate-400 hover:opacity-90 dark:border-slate-700/70 dark:bg-slate-950/40 dark:text-slate-500 dark:opacity-80 dark:hover:border-slate-600 dark:hover:opacity-100')

const extractHexColor = (...classValues) => {
    for (const classValue of classValues) {
        const match = String(classValue ?? '').match(/#(?:[0-9a-fA-F]{3,8})/)

        if (match) {
            return match[0]
        }
    }

    return null
}

const normalizeHexColor = (hexColor) => {
    const cleanValue = String(hexColor ?? '').replace('#', '')

    if (cleanValue.length === 3) {
        return cleanValue
            .split('')
            .map((char) => `${char}${char}`)
            .join('')
    }

    if (cleanValue.length === 6) {
        return cleanValue
    }

    return null
}

const hexToRgba = (hexColor, alpha = 1) => {
    const normalized = normalizeHexColor(hexColor)

    if (!normalized) {
        return null
    }

    const red = Number.parseInt(normalized.slice(0, 2), 16)
    const green = Number.parseInt(normalized.slice(2, 4), 16)
    const blue = Number.parseInt(normalized.slice(4, 6), 16)

    return `rgba(${red}, ${green}, ${blue}, ${alpha})`
}

const isDarkHexColor = (hexColor) => {
    const normalized = normalizeHexColor(hexColor)

    if (!normalized) {
        return false
    }

    const red = Number.parseInt(normalized.slice(0, 2), 16)
    const green = Number.parseInt(normalized.slice(2, 4), 16)
    const blue = Number.parseInt(normalized.slice(4, 6), 16)
    const luma = ((red * 299) + (green * 587) + (blue * 114)) / 1000

    return luma < 145
}

const themeAccentColor = computed(() => {
    return activeTickerTheme.value?.groupAccentColor
        ?? extractHexColor(
            activeTickerTheme.value?.groupTitleClass,
            activeTickerTheme.value?.teamNameClass,
            activeTickerTheme.value?.statsValueClass,
            activeTickerTheme.value?.counterValueClass,
        )
        ?? '#39C4E0'
})

const themeAccentTextColor = computed(() => (isDarkHexColor(themeAccentColor.value) ? '#F8FAFC' : '#0F172A'))

const themedCurrentGroupBadgeStyle = computed(() => {
    if (activeTickerTheme.value?.groupBadgeClass) {
        return undefined
    }

    const accent = themeAccentColor.value

    return {
        borderColor: hexToRgba(accent, 0.75),
        backgroundColor: hexToRgba(accent, 0.12),
        boxShadow: `0 0 18px ${hexToRgba(accent, 0.30)}`,
    }
})

const themedCurrentGroupValueStyle = computed(() => {
    if (activeTickerTheme.value?.groupBadgeValueClass) {
        return undefined
    }

    return {
        color: themeAccentColor.value,
    }
})

const themedActiveGroupCardStyle = computed(() => {
    if (activeTickerTheme.value?.groupActiveCardClass) {
        return undefined
    }

    const accent = themeAccentColor.value

    return {
        borderColor: accent,
        backgroundColor: accent,
        color: themeAccentTextColor.value,
        boxShadow: `0 0 16px ${hexToRgba(accent, 0.28)}`,
    }
})
const themedActiveStageClass = computed(() => activeTickerTheme.value?.stageActiveCardClass ?? '')
const themedActiveStageStyle = computed(() => {
    if (activeTickerTheme.value?.stageActiveCardClass) {
        return undefined
    }

    const accent = themeAccentColor.value

    return {
        borderColor: hexToRgba(accent, 0.75),
        backgroundColor: hexToRgba(accent, 0.12),
        color: '#334155',
        boxShadow: `0 0 16px ${hexToRgba(accent, 0.22)}`,
    }
})
const poolEntryForm = useForm({
    tournament_id: props.tournament.id,
    name: '',
    predictions: [],
})
const showNameStepModal = ref(false)
const showGroupResultsModal = ref(false)
const hasConfirmedPoolName = ref(false)
const groupTopNavRef = ref(null)
const stageMatchesTopRef = ref(null)
const hasShownKnockoutDrawWarning = ref(false)

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

const canClickNextGroup = computed(() => groupProgress.value.length > 0)

const switchStage = (stageKey, unlocked) => {
    if (unlocked) {
        currentStage.value = stageKey
    }
}

const selectGroup = (index) => {
    currentGroupIndex.value = index
}

const groupConfirmationRows = computed(() => {
    if (!currentGroup.value) {
        return []
    }

    return currentGroupMatches.value
        .map((match) => {
        const prediction = predictions[match.id] ?? { home: null, away: null }

        return {
            id: match.id,
            homeTeam: match.home_team?.name ?? 'Local',
            awayTeam: match.away_team?.name ?? 'Visitante',
            home: prediction.home,
            away: prediction.away,
        }
    })
        .filter((row) => row.home !== null && row.away !== null)
})

const hasAnyPredictionInCurrentGroup = () => {
    if (!currentGroup.value) {
        return false
    }

    return currentGroupMatches.value.some((match) => {
        const prediction = predictions[match.id] ?? { home: null, away: null }
        return prediction.home !== null || prediction.away !== null
    })
}

const showEmptyGroupAlert = async () => {
    await ElMessageBox.alert(
        'Debes completar los encuentros de la Fase de Grupo antes de continuar al siguiente grupo.',
        `Grupo ${currentGroup.value?.name ?? ''}`,
        {
            confirmButtonText: 'Entendido',
            type: 'warning',
        },
    )
}

const closeGroupResultsModal = () => {
    showGroupResultsModal.value = false
}

const scrollToCurrentGroupMatches = async () => {
    await nextTick()
    if (!groupTopNavRef.value) {
        return
    }

    const topOffset = 406
    const targetTop = groupTopNavRef.value.getBoundingClientRect().top + window.scrollY - topOffset
    window.scrollTo({
        top: Math.max(0, targetTop),
        behavior: 'smooth',
    })
}

const scrollToCurrentStageMatches = async () => {
    await nextTick()
    if (!stageMatchesTopRef.value) {
        return
    }

    const topOffset = 240
    const targetTop = stageMatchesTopRef.value.getBoundingClientRect().top + window.scrollY - topOffset
    window.scrollTo({
        top: Math.max(0, targetTop),
        behavior: 'smooth',
    })
}

const goToPreviousGroup = () => {
    if (currentGroupIndex.value > 0) {
        currentGroupIndex.value -= 1
    }
}

const advanceToNextGroup = async () => {
    if (currentGroupIndex.value < groupProgress.value.length - 1) {
        currentGroupIndex.value += 1
        await scrollToCurrentGroupMatches()
        return
    }

    if (currentStageStatus.value?.isComplete && nextUnlockedStage.value) {
        currentStage.value = nextUnlockedStage.value.key
        await scrollToCurrentStageMatches()
    }
}

const confirmAndAdvanceToNextGroup = async () => {
    showGroupResultsModal.value = false
    await advanceToNextGroup()
}

const goToNextGroup = async () => {
    if (!hasAnyPredictionInCurrentGroup()) {
        await showEmptyGroupAlert()
        return
    }

    if (groupConfirmationRows.value.length > 0) {
        showGroupResultsModal.value = true
        return
    }

    await advanceToNextGroup()
}

const goToNextStage = async () => {
    if (nextUnlockedStage.value) {
        currentStage.value = nextUnlockedStage.value.key
        await scrollToCurrentStageMatches()
    }
}

const submitPoolEntry = () => {
    const normalizedName = poolEntryForm.name.trim().replace(/\s+/g, ' ')

    if (normalizedName.length < 3) {
        showNameStepModal.value = true
        return
    }

    poolEntryForm.name = normalizedName
    hasConfirmedPoolName.value = true

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

watch(
    hasInvalidKnockoutDraws,
    (hasInvalid) => {
        if (!hasInvalid) {
            hasShownKnockoutDrawWarning.value = false
            return
        }

        if (hasShownKnockoutDrawWarning.value) {
            return
        }

        notifyWarning('En fases eliminatorias no se permiten empates en la quiniela. Define un ganador para que el bracket pueda avanzar correctamente.')
        hasShownKnockoutDrawWarning.value = true
    },
    { immediate: true },
)
</script>

<template>
<Head title="Quiniela Mundial" />

    <UserDashboardLayout
        :ticker-class="[activeTickerTheme.tickerClass, activeTickerTheme.decorationClass || ''].join(' ')"
    >
        <Head :title="`Quiniela Mundial ${tournament.year ?? ''}`" />

        <template #ticker>
            <div class="h-12 w-full" aria-hidden="true" />
        </template>

        <template #headerContent>
            <div class="hidden" />
        </template>

        <div class="space-y-8 pb-6">
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
                        <h1>Quiniela</h1>
                    </div>
                    <Link
                        :href="route('pools.index')"
                        class="inline-flex items-center whitespace-nowrap rounded-md px-2 py-1.5 text-xs font-bold uppercase tracking-wide text-primary-700 transition hover:bg-gray-200 dark:text-primary-500 dark:hover:bg-gray-600"
                    >
                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Mis quinielas
                    </Link>
                </div>

                <div class="mt-10 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                    <div>
                        <label for="pool-entry-name-inline" class="mb-2.5 block text-sm font-medium text-slate-900 dark:text-slate-100">
                            Nombre de la quiniela
                        </label>
                        <input
                            id="pool-entry-name-inline"
                            v-model="poolEntryForm.name"
                            type="text"
                            maxlength="80"
                            class="block w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-500 shadow-sm focus:border-cyan-400 focus:ring-cyan-300 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-100 dark:placeholder:text-slate-400 dark:focus:border-cyan-400"
                            placeholder="Ej: Jrmcorneilly #3"
                            required
                        >
                    </div>

                    <button
                        type="button"
                        @click="submitPoolEntry"
                        :disabled="!canSubmitPoolEntry || poolEntryForm.processing"
                        :class="[
                            themedPrimaryButtonClass,
                            'inline-flex min-w-[220px] items-center justify-center rounded-xl px-5 py-3 text-sm font-semibold transition focus:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                        ]"
                    >
                        {{ poolEntryForm.processing ? 'Registrando quiniela...' : 'Registrar quiniela' }}
                    </button>
                </div>
                <div class="mt-[6px] border-b border-slate-300 dark:border-slate-700" />
                <div v-if="flashError" class="mt-4 rounded-xl border border-rose-300/40 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-300/20 dark:bg-rose-400/10 dark:text-rose-100">
                    {{ flashError }}
                </div>

                <div v-if="props.participationRules?.isOpen === false" class="mt-4 rounded-xl border border-rose-300/40 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-300/20 dark:bg-rose-400/10 dark:text-rose-100">
                    La participacion de quinielas esta cerrada desde el {{ props.participationRules?.closeAtLabel ?? 'limite configurado' }}.
                </div>

            </section>

            <section class="mt-6">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
                    <button
                        v-for="stage in stageProgress"
                        :key="stage.key"
                        type="button"
                        @click="switchStage(stage.key, stage.unlocked)"
                        :disabled="!stage.unlocked"
                        :class="[
                            currentStage === stage.key
                                ? themedActiveStageClass
                                : 'border-slate-300 bg-white text-slate-500 dark:border-white/10 dark:bg-slate-900/80 dark:text-slate-400',
                            !stage.unlocked && 'cursor-not-allowed opacity-50',
                        ]"
                        :style="currentStage === stage.key ? themedActiveStageStyle : undefined"
                        class="rounded-2xl border px-4 py-3 text-left transition hover:border-slate-400 dark:hover:border-slate-600"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.2em]">
                            {{ stage.label }}
                        </p>
                        <p class="mt-2 text-lg font-black">
                            {{ stage.completed }}/{{ stage.count }}
                        </p>
                    </button>
                </div>
                <div class="mt-5 w-full">
                    <div class="w-full rounded-full bg-slate-300/80 dark:bg-slate-700/70">
                        <div
                            class="flex h-4 items-center justify-center rounded-full bg-primary-600 p-0.5 text-xs font-medium leading-none text-white transition-all duration-500"
                            :style="{ width: `${progress.percentage}%` }"
                        >
                            {{ progress.percentage }}%
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="currentStage === 'group' && currentGroup" class="space-y-6">
                <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                    <div :class="themedGroupPanelClass" class="rounded-2xl border p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p :class="themedGroupEyebrowClass" class="text-xs font-semibold uppercase tracking-[0.2em]">Progreso por grupos</p>
                                <h2 :class="themedGroupTitleClass" class="mt-2 text-2xl font-bold">Fase de grupos guiada</h2>
                                <p :class="themedGroupBodyClass" class="mt-1 text-sm">
                                    Completa un grupo a la vez.
                                </p>
                            </div>
                            <div :class="themedCurrentGroupBadgeClass" :style="themedCurrentGroupBadgeStyle" class="flex h-20 min-w-[5.75rem] items-center justify-center rounded-2xl border px-3 py-2">
                                <p :class="themedCurrentGroupValueClass" :style="themedCurrentGroupValueStyle" class="block -translate-y-1 text-6xl font-black leading-[0.9]">
                                    {{ currentGroup.name }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-4 xl:grid-cols-6">
                            <button
                                v-for="group in groupProgress"
                                :key="group.id"
                                type="button"
                                @click="selectGroup(group.index)"
                                :class="[
                                    currentGroupIndex === group.index
                                        ? themedActiveGroupCardClass
                                        : themedInactiveGroupCardClass,
                                ]"
                                :style="currentGroupIndex === group.index ? themedActiveGroupCardStyle : undefined"
                                class="flex min-h-[84px] flex-col rounded-2xl border px-3.5 py-2 text-left transition"
                            >
                                <p class="text-xs font-semibold uppercase tracking-[0.2em]">
                                    Grupo {{ group.name }}
                                </p>
                                <p class="mt-auto self-end text-2xl font-black leading-none">
                                    {{ group.completed }}/{{ group.total }}
                                </p>
                            </button>
                        </div>

                    </div>

                    <StandingWidget
                        class="self-start"
                        :group-name="currentGroup.name"
                        :standings="standingsByGroup[currentGroup.name] || []"
                    />
                </div>

                <div ref="groupTopNavRef" class="flex items-center justify-between gap-3">
                    <button
                        type="button"
                        :class="[
                            themedSecondaryButtonClass,
                            'inline-flex min-w-[170px] items-center justify-center rounded-2xl border px-6 py-3 text-sm font-semibold transition focus:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                        ]"
                        :disabled="currentGroupIndex === 0"
                        @click="goToPreviousGroup"
                    >
                        Anterior
                    </button>
                    <button
                        type="button"
                        :class="[
                            themedPrimaryButtonClass,
                            'inline-flex min-w-[170px] items-center justify-center rounded-2xl px-6 py-3 text-sm font-semibold shadow-sm transition focus:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                        ]"
                        :disabled="!canClickNextGroup"
                        @click="goToNextGroup"
                    >
                        {{ currentGroupIndex >= groupProgress.length - 1 && currentStageStatus?.isComplete && nextUnlockedStage ? `Continuar a ${nextUnlockedStage.label}` : 'Siguiente' }}
                    </button>
                </div>

                <div class="space-y-3">
                    <WorldCupMatchCard
                        v-for="match in currentGroupMatches"
                        :key="match.id"
                        v-model="predictions[match.id]"
                        :match="match"
                    />
                </div>

                <div class="flex items-center justify-between gap-3 pt-2">
                    <button
                        type="button"
                        :class="[
                            themedSecondaryButtonClass,
                            'inline-flex min-w-[170px] items-center justify-center rounded-2xl border px-6 py-3 text-sm font-semibold transition focus:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                        ]"
                        :disabled="currentGroupIndex === 0"
                        @click="goToPreviousGroup"
                    >
                        Anterior
                    </button>
                    <button
                        type="button"
                        :class="[
                            themedPrimaryButtonClass,
                            'inline-flex min-w-[170px] items-center justify-center rounded-2xl px-6 py-3 text-sm font-semibold shadow-sm transition focus:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                        ]"
                        :disabled="!canClickNextGroup"
                        @click="goToNextGroup"
                    >
                        {{ currentGroupIndex >= groupProgress.length - 1 && currentStageStatus?.isComplete && nextUnlockedStage ? `Continuar a ${nextUnlockedStage.label}` : 'Siguiente' }}
                    </button>
                </div>
            </section>

            <section v-else class="space-y-6">
                <div ref="stageMatchesTopRef" class="space-y-3">
                    <WorldCupMatchCard
                        v-for="match in visibleStageMatches"
                        :key="match.id"
                        v-model="predictions[match.id]"
                        :match="match"
                    />
                </div>

                <div class="flex items-center justify-end">
                    <button
                        v-if="currentStageStatus?.isComplete && nextUnlockedStage"
                        type="button"
                        @click="goToNextStage"
                        class="inline-flex items-center justify-center rounded-xl border border-cyan-300/50 bg-cyan-50 px-5 py-3 text-sm font-semibold text-cyan-700 transition hover:bg-cyan-100 dark:border-cyan-300/20 dark:bg-cyan-400/10 dark:text-cyan-200 dark:hover:bg-cyan-400/20"
                    >
                        Continuar a {{ nextUnlockedStage.label }}
                    </button>
                </div>
            </section>
        </div>

        <FlowbiteModal
            :show="showGroupResultsModal"
            max-width="xl"
            :closeable="true"
            @close="closeGroupResultsModal"
        >
            <div class="space-y-4 -mb-4 md:-mb-6">
                <div>
                    <h2 class="text-xl font-black tracking-tight text-slate-900 sm:text-2xl dark:text-white">
                        Grupo {{ currentGroup?.name ?? '' }}
                    </h2>
                </div>

                <div class="mx-auto flex max-w-[36rem] items-start justify-center gap-2 text-slate-700 sm:gap-2.5 dark:text-slate-200">
                    <span
                        aria-hidden="true"
                        class="mt-0.5 inline-flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-full bg-amber-500 text-[12px] font-bold leading-none text-white sm:h-[22px] sm:w-[22px] sm:text-[14px]"
                    >
                        !
                    </span>
                    <p class="m-0 text-[15px] font-medium leading-6 sm:text-[18px]">
                        Confirma los resultados antes de continuar al siguiente grupo.
                    </p>
                </div>

                <ul class="m-0 grid list-none gap-1.5 p-0">
                    <li
                        v-for="row in groupConfirmationRows"
                        :key="row.id"
                        class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 text-[14px] leading-6 text-slate-700 sm:gap-3 sm:text-[18px] sm:leading-7 dark:text-slate-200"
                    >
                        <strong class="justify-self-end text-right">{{ row.homeTeam }}</strong>
                        <span class="inline-block min-w-[62px] text-center font-semibold sm:min-w-[72px]">{{ row.home }} - {{ row.away }}</span>
                        <strong class="justify-self-start text-left">{{ row.awayTeam }}</strong>
                    </li>
                </ul>

                <div class="mt-1 flex min-h-[56px] items-center justify-center gap-2 border-t border-slate-200 py-2.5 sm:justify-end dark:border-slate-700">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        @click="closeGroupResultsModal"
                    >
                        Revisar
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-primary-300/30 bg-primary-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-600"
                        @click="confirmAndAdvanceToNextGroup"
                    >
                        Continuar
                    </button>
                </div>
            </div>
        </FlowbiteModal>

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
