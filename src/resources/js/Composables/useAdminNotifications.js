import { computed, ref } from 'vue'
import axios from 'axios'
import { route } from 'ziggy-js'

const STORAGE_KEY = 'admin.notifications.v1'
const MAX_ITEMS = 50
const adminActivityChannelName = 'admin.activity'

const notificationItems = ref([])
const unreadNotifications = computed(() => notificationItems.value.filter((item) => !item.read).length)
const totalNotifications = ref(0)

let isInitialized = false
let isListening = false
let echoRetryTimer = null
let pollingTimer = null

const hydrate = () => {
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY)
        if (!raw) return

        const parsed = JSON.parse(raw)
        if (Array.isArray(parsed)) {
            notificationItems.value = parsed
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

const pushNotification = (payload) => {
    notificationItems.value = [
        {
            id: `${payload?.poolEntryId ?? 'pool'}-${payload?.occurredAt ?? Date.now()}`,
            action: payload?.action ?? 'updated',
            userName: payload?.userName ?? 'Usuario',
            userEmail: payload?.userEmail ?? '',
            message: payload?.message ?? 'Actividad de quiniela registrada.',
            messageSuffix: payload?.messageSuffix ?? 'actualizo una quiniela.',
            poolEntryId: payload?.poolEntryId ?? null,
            poolEntryName: payload?.poolEntryName ?? null,
            occurredAt: payload?.occurredAt ?? new Date().toISOString(),
            read: false,
        },
        ...notificationItems.value,
    ].slice(0, MAX_ITEMS)
    totalNotifications.value = Math.max(totalNotifications.value + 1, notificationItems.value.length)

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
        axios.get(route('admin.notifications.index'))
            .then((response) => {
                const items = Array.isArray(response?.data?.notifications) ? response.data.notifications : []
                notificationItems.value = items
                totalNotifications.value = Number(response?.data?.total ?? items.length)
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
        pollingTimer = window.setInterval(fetchNotifications, 10000)
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
