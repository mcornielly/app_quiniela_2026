<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    notifications: {
        type: Array,
        default: () => [],
    },
    viewAllHref: {
        type: String,
        default: '',
    },
    totalCount: {
        type: Number,
        default: 0,
    },
    onMarkRead: {
        type: Function,
        default: null,
    },
    onClearAll: {
        type: Function,
        default: null,
    },
})

const actionIconClass = (action) => {
    if (action === 'created') return 'bg-primary-700'
    if (action === 'inactivated') return 'bg-rose-600'
    if (action === 'reactivated') return 'bg-emerald-600'
    return 'bg-slate-700'
}

const actionSymbol = (action) => {
    if (action === 'created') return '+'
    if (action === 'inactivated') return '||'
    if (action === 'reactivated') return 'R'
    return '*'
}

const initials = (name) => {
    const parts = String(name ?? '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)

    if (!parts.length) return 'U'

    return parts.map((part) => part.charAt(0).toUpperCase()).join('')
}

const timeAgo = (value) => {
    if (!value) return 'hace un momento'

    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return 'hace un momento'

    const diffMs = Date.now() - date.getTime()
    const diffMin = Math.max(0, Math.floor(diffMs / 60000))

    if (diffMin < 1) return 'hace unos segundos'
    if (diffMin < 60) return `hace ${diffMin} minuto${diffMin === 1 ? '' : 's'}`

    const diffHours = Math.floor(diffMin / 60)
    if (diffHours < 24) return `hace ${diffHours} hora${diffHours === 1 ? '' : 's'}`

    const diffDays = Math.floor(diffHours / 24)
    return `hace ${diffDays} dia${diffDays === 1 ? '' : 's'}`
}

const registrationLabel = (item) => {
    if (item?.registrationNumber) {
        return String(item.registrationNumber)
    }

    const numericId = Number(item?.poolEntryId)
    if (!Number.isFinite(numericId) || numericId <= 0) {
        return null
    }

    return `#${String(Math.trunc(numericId)).padStart(5, '0')}`
}

const visibleNotifications = computed(() => props.notifications)
const visibleCount = computed(() => visibleNotifications.value.length)

const markRead = (id) => {
    props.onMarkRead?.(id)
}

const clearAll = () => {
    props.onClearAll?.()
}
</script>

<template>
    <div class="max-w-sm overflow-hidden rounded-xl bg-white shadow-lg dark:bg-gray-700">
        <div class="relative block bg-gray-50 px-4 py-2 text-center text-base font-medium text-gray-700 dark:bg-gray-600 dark:text-gray-300">
            Notifications
            <button
                v-if="visibleCount > 0"
                type="button"
                @click.stop="clearAll"
                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md bg-transparent p-1.5 text-slate-600 transition hover:bg-slate-300 hover:text-red-600 dark:text-slate-200 dark:hover:bg-slate-500/40 dark:hover:text-red-400"
                title="Limpiar todo"
                aria-label="Limpiar todo"
            >
                <svg class="h-4 w-4" viewBox="0 0 448 512" fill="currentColor" aria-hidden="true">
                    <path d="M136.7 5.9C141.1-7.2 153.3-16 167.1-16l113.9 0c13.8 0 26 8.8 30.4 21.9L320 32 416 32c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 96C14.3 96 0 81.7 0 64S14.3 32 32 32l96 0 8.7-26.1zM32 144l384 0 0 304c0 35.3-28.7 64-64 64L96 512c-35.3 0-64-28.7-64-64l0-304zm88 64c-13.3 0-24 10.7-24 24l0 192c0 13.3 10.7 24 24 24s24-10.7 24-24l0-192c0-13.3-10.7-24-24-24zm104 0c-13.3 0-24 10.7-24 24l0 192c0 13.3 10.7 24 24 24s24-10.7 24-24l0-192c0-13.3-10.7-24-24-24zm104 0c-13.3 0-24 10.7-24 24l0 192c0 13.3 10.7 24 24 24s24-10.7 24-24l0-192c0-13.3-10.7-24-24-24z"/>
                </svg>
            </button>
        </div>

        <div v-if="visibleCount > 0" class="max-h-[22rem] overflow-y-auto">
            <div
                v-for="item in visibleNotifications"
                :key="item.id"
                class="relative flex border-b border-gray-200 px-4 py-3 last:border-b-0 hover:bg-gray-100 dark:border-gray-600 dark:hover:bg-gray-600"
            >
                <div class="relative flex-shrink-0">
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-300 text-sm font-bold text-slate-700 dark:bg-slate-500 dark:text-white">
                        {{ initials(item.userName) }}
                    </div>
                    <div
                        class="absolute -mt-5 ml-6 flex h-5 w-5 items-center justify-center rounded-full border border-white text-[10px] font-bold text-white dark:border-gray-700"
                        :class="actionIconClass(item.action)"
                    >
                        {{ actionSymbol(item.action) }}
                    </div>
                </div>
                <div class="w-full min-w-0 pl-3 pr-10">
                    <div class="mb-1.5 text-sm font-normal text-gray-500 dark:text-gray-400">
                        <span class="font-semibold text-gray-900 dark:text-white">{{ item.userName }}</span>
                        {{ item.messageSuffix }}
                        <span v-if="registrationLabel(item)"> Registro: {{ registrationLabel(item) }}.</span>
                    </div>
                    <div class="text-xs font-medium text-primary-600 dark:text-primary-500">
                        {{ timeAgo(item.occurredAt) }}
                    </div>
                </div>
                <button
                    type="button"
                    @click.stop="markRead(item.id)"
                    class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full border border-slate-300 bg-transparent text-slate-400 transition hover:border-red-300 hover:bg-red-100 hover:text-red-600 dark:border-slate-500 dark:text-slate-300 dark:hover:border-red-500/40 dark:hover:bg-red-900/30 dark:hover:text-red-400"
                    title="Marcar como leida"
                    aria-label="Marcar como leida"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.22 4.22a.75.75 0 0 1 1.06 0L10 8.94l4.72-4.72a.75.75 0 1 1 1.06 1.06L11.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 1 1-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>

        <div v-else class="px-4 py-5 text-center text-sm text-gray-500 dark:text-gray-400">
            Aun no hay notificaciones para administracion.
        </div>

        <div v-if="viewAllHref" class="bg-gray-50 dark:bg-gray-600">
            <Link
                :href="viewAllHref"
                class="group relative flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-900 transition hover:bg-blue-50 hover:text-blue-700 dark:text-white dark:hover:bg-blue-900/20 dark:hover:text-blue-300"
            >
                <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4" viewBox="0 0 576 512" fill="currentColor" aria-hidden="true">
                        <path d="M288 80c-118.6 0-223.3 76.2-267.3 189.3a31.88 31.88 0 0 0 0 23.4C64.7 405.8 169.4 482 288 482s223.3-76.2 267.3-189.3a31.88 31.88 0 0 0 0-23.4C511.3 156.2 406.6 80 288 80zm0 320c-70.7 0-128-57.3-128-128S217.3 144 288 144s128 57.3 128 128-57.3 128-128 128zm0-208a80 80 0 1 0 0 160 80 80 0 1 0 0-160z"/>
                    </svg>
                    View all
                </span>
                <span class="absolute right-4 inline-flex min-w-6 items-center justify-center rounded-full bg-slate-200 px-2 py-0.5 text-xs font-bold text-slate-700 transition group-hover:bg-green-200 group-hover:text-green-900 dark:bg-slate-500 dark:text-white dark:group-hover:bg-green-700/30 dark:group-hover:text-green-200">
                    {{ totalCount }}
                </span>
            </Link>
        </div>
    </div>
</template>

