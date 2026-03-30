export const imageUrl = (path) => {
    if (!path) return null

    const isDev = import.meta.env.DEV
    const baseUrl = isDev ? import.meta.env.VITE_APP_URL?.replace(/\/$/, '') : ''

    // URL externa (API)
    if (path.startsWith('http')) {
        return path
    }

    // ya viene con /storage
    if (path.startsWith('/storage')) {
        return baseUrl ? `${baseUrl}${path}` : path
    }

    // storage local
    const normalizedPath = String(path).replace(/^\/+/, '')
    const relativeStoragePath = `/storage/${normalizedPath}`

    return baseUrl ? `${baseUrl}${relativeStoragePath}` : relativeStoragePath
}
