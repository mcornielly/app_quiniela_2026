export const imageUrl = (path) => {
    if (!path) return null

    // URL externa (API)
    if (path.startsWith('http')) {
        return path
    }

    // ya viene con /storage
    if (path.startsWith('/storage')) {
        const baseUrl = import.meta.env.VITE_APP_URL?.replace(/\/$/, '')
        return baseUrl ? `${baseUrl}${path}` : path
    }

    // storage local
    const normalizedPath = String(path).replace(/^\/+/, '')
    const relativeStoragePath = `/storage/${normalizedPath}`
    const baseUrl = import.meta.env.VITE_APP_URL?.replace(/\/$/, '')

    return baseUrl ? `${baseUrl}${relativeStoragePath}` : relativeStoragePath
}
