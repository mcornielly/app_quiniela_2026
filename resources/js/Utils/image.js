export const imageUrl = (path) => {

    if (!path) return null

    // URL externa (API)
    if (path.startsWith('http')) {
        return path
    }

    // ya viene con /storage
    if (path.startsWith('/storage')) {
        return path
    }

    // storage local
    return `/storage/${path}`
}
