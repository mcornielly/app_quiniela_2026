const PALETTE = [
    '#22d3ee', // cyan
    '#84cc16', // lime
    '#f97316', // orange
    '#3b82f6', // blue
    '#e11d48', // rose
    '#14b8a6', // teal
    '#8b5cf6', // violet
    '#f59e0b', // amber
    '#10b981', // emerald
    '#ef4444', // red
]

const COUNTRY_COLOR_HINTS = {
    MEX: '#00a94f',
    ZAF: '#0f7a3f',
    KOR: '#d62828',
    ARG: '#61b6ff',
    BRA: '#14a44d',
    FRA: '#1f4db8',
    ESP: '#c62828',
    GER: '#6b7280',
    ENG: '#d32f2f',
    USA: '#1d4ed8',
}

const normalizeKey = (value) => String(value || '').trim().toUpperCase()

const hashString = (value) => {
    const str = String(value || '')
    let hash = 0

    for (let i = 0; i < str.length; i++) {
        hash = (hash << 5) - hash + str.charCodeAt(i)
        hash |= 0
    }

    return Math.abs(hash)
}

export function buildTeamColorPair(homeKey, awayKey) {
    const homeNorm = normalizeKey(homeKey)
    const awayNorm = normalizeKey(awayKey)

    let home = COUNTRY_COLOR_HINTS[homeNorm] ?? PALETTE[hashString(homeNorm) % PALETTE.length]
    let away = COUNTRY_COLOR_HINTS[awayNorm] ?? PALETTE[hashString(awayNorm) % PALETTE.length]

    if (home === away) {
        away = PALETTE[(hashString(awayNorm || 'AWAY') + 3) % PALETTE.length]
    }

    return { home, away }
}

