// Funciones de formato
export const capitalize = (v) =>
    v.charAt(0).toUpperCase() + v.slice(1)


// Función para convertir un plural a singular (muy básica, no cubre todos los casos)
export function singular(word) {
    if (!word) return ''
    // reglas simples
    if (word.endsWith('ies')) {
        return word.slice(0, -3) + 'y'
    }
    if (word.endsWith('s')) {
        return word.slice(0, -1)
    }
    return word
}

export function formatDate(date) {

    if (!date) return '—'

    const d = new Date(date)

    return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })

}

export function formatDateTime(date, locale = 'es-VE') {
    if (!date) return '—'

    const d = new Date(date)

    if (Number.isNaN(d.getTime())) return '—'

    return d.toLocaleString(locale, {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    })
}

export function formatRegistrationNumber(value, digits = 5) {
    const numericValue = Number.parseInt(value, 10)

    if (!Number.isFinite(numericValue) || numericValue < 0) {
        return '-'
    }

    return `# ${String(numericValue).padStart(digits, '0')}`
}

export function formatStage(stage) {
    if (!stage) return '—'

    const labels = {
        'group': 'Group',
        'round_32': 'Round of 32',
        'round_16': 'Round of 16',
        'quarter': 'Quarter-finals',
        'semi': 'Semi-finals',
        'third_place': 'Third Place',
        'final': 'Final'
    }

    return labels[stage] || capitalize(stage.replace('_', ' '))
}

export function formatTime(time) {
    if (!time) return '—'
    
    // Remove seconds if present (HH:mm:ss -> HH:mm)
    return time.split(':').slice(0, 2).join(':')
}

export function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value)
}

export function formatGoalDiff(value) {
    if (value > 0) {
        return `+${value}`
    }

    return `${value}`
}
