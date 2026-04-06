import { computed, ref } from 'vue'
import axios from 'axios'
import { route } from 'ziggy-js'
import { notifyInfo } from '@/Utils/notify'

const STORAGE_KEY = 'admin.notifications.v1'
const MAX_ITEMS = 50
const adminActivityChannelName = 'admin.activity'
const ADMIN_NOTIFICATIONS_POLLING_MS = 10000

const notificationItems = ref([])
const unreadNotifications = computed(() => notificationItems.value.filter((item) => !item.read).length)
const totalNotifications = ref(0)

let isInitialized = false
let isListening = false
let echoRetryTimer = null
let pollingTimer = null
let hasFetchedOnce = false
const emittedToastKeys = new Set()

const hydrate = () => {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY)
        if (!raw) return

        const parsed = JSON.parse(raw)
        if (Array.isArray(parsed)) {
            notificationItems.value = parsed
            parsed.forEach((item) => {
                if (item?.id) {
                    emittedToastKeys.add(buildToastKey(item))
                }
            })
        }
    } catch (error) {
        notificationItems.value = []
    }
}

const persist = () => {
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(notificationItems.value))
    } catch (error) {
        // no-op: local storage can fail in private mode/quota limits
    }
}

const formatRegistrationNumber = (payload) => {
    const explicit = payload?.registrationNumber ?? payload?.poolEntryReference ?? null
    if (explicit) {
        return String(explicit)
    }

    const numericId = Number(payload?.poolEntryId)
    if (!Number.isFinite(numericId) || numericId <= 0) {
        return null
    }

    return `#${String(Math.trunc(numericId)).padStart(5, '0')}`
}

const buildAdminInfoMessage = (payload) => {
    const userName = String(payload?.userName ?? 'Usuario').trim() || 'Usuario'
    const poolName = payload?.poolEntryName ? ` "${payload.poolEntryName}"` : ''
    const registrationNumber = formatRegistrationNumber(payload)
    const registrationLabel = registrationNumber ? ` Registro ${registrationNumber}.` : ''
    const action = payload?.action ?? 'updated'

    if (action === 'created') {
        return `${userName}: ha creado su quiniela${poolName}.${registrationLabel}`.trim()
    }

    if (action === 'inactivated') {
        return `${userName}: ha inactivado su quiniela${poolName}.${registrationLabel}`.trim()
    }

    if (action === 'reactivated') {
        return `${userName}: ha reactivado su quiniela${poolName}.${registrationLabel}`.trim()
    }

    return `${userName}: ha actualizado su quiniela${poolName}.${registrationLabel}`.trim()
}

const buildToastKey = (payload) => {
    const action = payload?.action ?? 'updated'
    const userName = payload?.userName ?? 'usuario'
    const poolEntryId = payload?.poolEntryId ?? 'pool'
    const occurredAt = payload?.occurredAt ?? ''

    return `${action}|${userName}|${poolEntryId}|${occurredAt}`
}

const emitInfoToast = (payload) => {
    const key = buildToastKey(payload)
    if (emittedToastKeys.has(key)) {
        return
    }

    emittedToastKeys.add(key)
    if (emittedToastKeys.size > 400) {
        const firstKey = emittedToastKeys.values().next().value
        emittedToastKeys.delete(firstKey)
    }

    notifyInfo(buildAdminInfoMessage(payload))
}

const normalizeNotificationItem = (payload) => ({
    id: payload?.id ?? `${payload?.poolEntryId ?? 'pool'}-${payload?.occurredAt ?? Date.now()}`,
    action: payload?.action ?? 'updated',
    userName: payload?.userName ?? 'Usuario',
    userEmail: payload?.userEmail ?? '',
    message: payload?.message ?? 'Actividad de quiniela registrada.',
    messageSuffix: payload?.messageSuffix ?? 'actualizo una quiniela.',
    poolEntryId: payload?.poolEntryId ?? null,
    poolEntryReference: payload?.poolEntryReference ?? null,
    registrationNumber: formatRegistrationNumber(payload),
    poolEntryName: payload?.poolEntryName ?? null,
    occurredAt: payload?.occurredAt ?? new Date().toISOString(),
    read: Boolean(payload?.read ?? false),
})

const pushNotification = (payload) => {
    const normalized = normalizeNotificationItem(payload)

    notificationItems.value = [
        {
            ...normalized,
            read: false,
        },
        ...notificationItems.value,
    ].slice(0, MAX_ITEMS)
    totalNotifications.value = Math.max(totalNotifications.value + 1, notificationItems.value.length)

    emitInfoToast(normalized)
    persist()
}

const markNotificationsRead = () => {
    notificationItems.value = notificationItems.value.map((item) => ({ ...item, read: true }))
    persist()

    axios.post(route('admin.notifications.read-all')).catch(() => {})
}

const markNotificationRead = (notificationId) => {
    notificationItems.value = notificationItems.value.map((item) => (
        item.id === notificationId ? { ...item, read: true } : item
    ))
    persist()

    axios.post(route('admin.notifications.read', notificationId)).catch(() => {})
}

const clearNotifications = () => {
    notificationItems.value = notificationItems.value.map((item) => ({ ...item, read: true }))
    persist()

    axios.post(route('admin.notifications.read-all')).catch(() => {})
}

const initAdminNotifications = ({ canListen } = {}) => {
    if (!isInitialized) {
        hydrate()
        isInitialized = true
    }

    if (!canListen || isListening) {
        return
    }

    const fetchNotifications = () => {
        if (typeof document !== 'undefined' && document.visibilityState !== 'visible') {
            return
        }

        axios.get(route('admin.notifications.index'))
            .then((response) => {
                const incoming = Array.isArray(response?.data?.notifications) ? response.data.notifications : []
                const items = incoming.map((item) => normalizeNotificationItem(item))

                if (!hasFetchedOnce) {
                    items.forEach((item) => emittedToastKeys.add(buildToastKey(item)))
                } else {
                    items
                        .filter((item) => !item.read)
                        .forEach((item) => emitInfoToast(item))
                }

                notificationItems.value = items
                totalNotifications.value = Number(response?.data?.total ?? items.length)
                hasFetchedOnce = true
                persist()
            })
            .catch(() => {})
    }

    const tryAttachListener = () => {
        if (isListening) {
            return
        }

        if (!window.Echo) {
            echoRetryTimer = window.setTimeout(tryAttachListener, 300)
            return
        }

        window.Echo.private(adminActivityChannelName)
            .listen('.admin.pool.activity', (payload) => {
                pushNotification(payload)
            })

        isListening = true

        if (echoRetryTimer) {
            window.clearTimeout(echoRetryTimer)
            echoRetryTimer = null
        }
    }

    tryAttachListener()
    fetchNotifications()

    if (!pollingTimer) {
        pollingTimer = window.setInterval(fetchNotifications, ADMIN_NOTIFICATIONS_POLLING_MS)
    }
}

export const useAdminNotifications = () => ({
    notificationItems,
    unreadNotifications,
    totalNotifications,
    markNotificationsRead,
    markNotificationRead,
    clearNotifications,
    initAdminNotifications,
})
