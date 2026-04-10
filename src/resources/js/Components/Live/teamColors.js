const FALLBACK_PALETTE = [
    '#22d3ee', // cyan
    '#a3e635', // lime
    '#fb7185', // rose
    '#f59e0b', // amber
    '#60a5fa', // blue
    '#f97316', // orange
    '#34d399', // emerald
    '#a78bfa', // violet
    '#ef4444', // red
    '#06b6d4', // cyan-600
]

const TEAM_CODE_COLORS = {
    MEX: '#16a34a',
    RSA: '#facc15',
    ZAF: '#facc15',
    ARG: '#38bdf8',
    BRA: '#eab308',
    GER: '#4b5563',
    FRA: '#2563eb',
    ESP: '#ef4444',
    ENG: '#f8fafc',
    KOR: '#ef4444',
    JPN: '#2563eb',
    USA: '#2563eb',
    CAN: '#dc2626',
    ITA: '#2563eb',
    NED: '#f97316',
}

const normalizeCode = (value) => String(value || '').trim().toUpperCase()

const hashColor = (seed) => {
    const key = String(seed || '').trim().toLowerCase()

    if (!key) {
        return FALLBACK_PALETTE[0]
    }

    let hash = 0

    for (let i = 0; i < key.length; i += 1) {
        hash = (hash << 5) - hash + key.charCodeAt(i)
        hash |= 0
    }

    const index = Math.abs(hash) % FALLBACK_PALETTE.length
    return FALLBACK_PALETTE[index]
}

const hexToRgb = (hex) => {
    const clean = String(hex || '').replace('#', '')

    if (!/^[0-9a-fA-F]{6}$/.test(clean)) {
        return null
    }

    return {
        r: Number.parseInt(clean.slice(0, 2), 16),
        g: Number.parseInt(clean.slice(2, 4), 16),
        b: Number.parseInt(clean.slice(4, 6), 16),
    }
}

const colorDistance = (a, b) => {
    const rgbA = hexToRgb(a)
    const rgbB = hexToRgb(b)

    if (!rgbA || !rgbB) {
        return 255
    }

    const dr = rgbA.r - rgbB.r
    const dg = rgbA.g - rgbB.g
    const db = rgbA.b - rgbB.b

    return Math.sqrt(dr ** 2 + dg ** 2 + db ** 2)
}

const pickDifferentColor = (baseColor, usedColor) => {
    if (colorDistance(baseColor, usedColor) >= 90) {
        return baseColor
    }

    const candidate = FALLBACK_PALETTE.find((color) => colorDistance(color, usedColor) >= 90)
    return candidate || '#f59e0b'
}

const resolveTeamColor = (team = {}, fallbackSeed = '') => {
    const code = normalizeCode(team.code || team.short || team.abbr)

    if (code && TEAM_CODE_COLORS[code]) {
        return TEAM_CODE_COLORS[code]
    }

    return hashColor(team.name || fallbackSeed)
}

export const resolveMatchTeamColors = ({ home = {}, away = {} } = {}) => {
    const homeColor = resolveTeamColor(home, 'home')
    const rawAwayColor = resolveTeamColor(away, 'away')
    const awayColor = pickDifferentColor(rawAwayColor, homeColor)

    return {
        homeColor,
        awayColor,
    }
}
