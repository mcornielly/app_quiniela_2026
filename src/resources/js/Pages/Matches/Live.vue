<script setup>
import axios from 'axios'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { ClockIcon, SignalIcon } from '@heroicons/vue/24/outline'
import LiveMatchCard from '@/Components/Live/LiveMatchCard.vue'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'

const props = defineProps({
    tournament: {
        type: Object,
        default: null,
    },
    liveMatches: {
        type: Array,
        default: () => [],
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
const activeTab = ref('live')
const allMatchesState = ref(Array.isArray(props.liveMatches) ? props.liveMatches : [])
const initialLoading = ref(true)
const pollIntervalMs = 25000
let pollTimer = null

const matchStatusShort = (match) => String(match?.statusShort || '').trim().toUpperCase()
const matchStatusLabel = (match) => String(match?.statusLabel || match?.status || '').toLowerCase()

const isMatchFinished = (match) => {
    if (['FT', 'AET', 'PEN'].includes(matchStatusShort(match))) return true
    const label = matchStatusLabel(match)
    return label.includes('finished') || label.includes('finalizado')
}

const liveBadgeLabel = (match) => (isMatchFinished(match) ? 'Finalizado' : 'En progreso')
const skeletonRows = Array.from({ length: 3 }, (_, index) => index)
const liveMatchesState = computed(() => allMatchesState.value.filter((match) => !isMatchFinished(match)))
const historyMatchesState = computed(() => allMatchesState.value.filter((match) => isMatchFinished(match)))
const tabs = computed(() => ([
    {
        key: 'live',
        label: 'En Vivo',
        count: liveMatchesState.value.length,
        icon: SignalIcon,
    },
    {
        key: 'history',
        label: 'Historial',
        count: historyMatchesState.value.length,
        icon: ClockIcon,
    },
]))
const activeTabMeta = computed(() => tabs.value.find((tab) => tab.key === activeTab.value) ?? tabs.value[0])
const centeredSummary = computed(() => {
    if (initialLoading.value) {
        return 'Cargando partidos...'
    }

    if (activeTab.value === 'history') {
        return `${historyMatchesState.value.length} juego(s) en historial`
    }

    return `${liveMatchesState.value.length} juego(s) en vivo`
})

const fetchLiveMatches = async ({ silent = false } = {}) => {
    if (!silent) {
        initialLoading.value = true
    }

    try {
        const { data } = await axios.get(route('live.cards.feed'))
        allMatchesState.value = Array.isArray(data?.matches) ? data.matches : []
    } catch {
        // Keep the last successful snapshot on screen if polling fails.
    } finally {
        if (!silent) {
            initialLoading.value = false
        }
    }
}

const startPolling = () => {
    if (pollTimer) clearInterval(pollTimer)
    pollTimer = window.setInterval(() => {
        fetchLiveMatches({ silent: true })
    }, pollIntervalMs)
}

onMounted(async () => {
    await fetchLiveMatches()
    startPolling()
})

onBeforeUnmount(() => {
    if (pollTimer) clearInterval(pollTimer)
})
</script>

<template>
    <Head title="Juego Directo" />

    <UserDashboardLayout
        title="Juego Directo"
        description="Partidos en curso del Mundial 2026."
        :ticker-class="[activeTickerTheme.tickerClass, activeTickerTheme.decorationClass || ''].join(' ')"
    >
        <template #ticker>
            <div class="h-12 w-full" aria-hidden="true" />
        </template>

        <template #headerContent>
            <div class="hidden" />
        </template>

        <section>
            <div class="-mt-8 flex items-center justify-between gap-4 text-left">
                <div class="inline-flex items-center gap-2 text-3xl font-bold text-slate-900 dark:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" class="h-7 w-7 fill-current text-rose-500 dark:text-rose-400" aria-hidden="true">
                        <path d="M87.9 11.5c-11.3-6.9-26.1-3.2-33 8.1-24.8 41-39 89.1-39 140.4s14.2 99.4 39 140.4c6.9 11.3 21.6 15 33 8.1s15-21.6 8.1-33C75.7 241.9 64 202.3 64 160S75.7 78.1 96.1 44.4c6.9-11.3 3.2-26.1-8.1-33zm400.1 0c-11.3 6.9-15 21.6-8.1 33 20.4 33.7 32.1 73.3 32.1 115.6s-11.7 81.9-32.1 115.6c-6.9 11.3-3.2 26.1 8.1 33s26.1 3.2 33-8.1c24.8-41 39-89.1 39-140.4S545.8 60.6 521 19.6c-6.9-11.3-21.6-15-33-8.1zM320 215.4c19.1-11.1 32-31.7 32-55.4 0-35.3-28.7-64-64-64s-64 28.7-64 64c0 23.7 12.9 44.4 32 55.4L256 480c0 17.7 14.3 32 32 32s32-14.3 32-32l0-264.6zM180.2 91c7.2-11.2 3.9-26-7.2-33.2s-26-3.9-33.2 7.2c-17.6 27.4-27.8 60-27.8 95s10.2 67.6 27.8 95c7.2 11.2 22 14.4 33.2 7.2s14.4-22 7.2-33.2c-12.8-19.9-20.2-43.6-20.2-69s7.4-49.1 20.2-69zM436.2 65c-7.2-11.2-22-14.4-33.2-7.2s-14.4 22-7.2 33.2c12.8 19.9 20.2 43.6 20.2 69s-7.4 49.1-20.2 69c-7.2 11.2-3.9 26 7.2 33.2s26 3.9 33.2-7.2c17.6-27.4 27.8-60 27.8-95s-10.2-67.6-27.8-95z"/>
                    </svg>
                    <h1>Juego Directo</h1>
                </div>
                <Link
                    :href="route('dashboard')"
                    class="inline-flex items-center whitespace-nowrap rounded-md px-2 py-1.5 text-xs font-bold uppercase tracking-wide text-primary-700 transition hover:bg-gray-200 dark:text-primary-500 dark:hover:bg-gray-600"
                >
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Regresar
                </Link>
            </div>

            <div class="mt-10">
                <div class="live-tabs border-b border-slate-300 dark:border-slate-700">
                <div class="flex flex-wrap items-center gap-2 md:gap-3">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="live-tab"
                        :class="tab.key === activeTab ? 'live-tab-active' : 'live-tab-inactive'"
                        @click="activeTab = tab.key"
                    >
                        <component :is="tab.icon" class="h-5 w-5" aria-hidden="true" />
                        <span>{{ tab.label }}</span>
                        <span class="live-tab-count" :class="tab.key === activeTab ? 'live-tab-count-active' : 'live-tab-count-inactive'">
                            {{ tab.count }}
                        </span>
                    </button>
                </div>
                </div>

                <div class="mt-2 flex justify-center">
                    <p class="text-sm leading-tight text-slate-500 dark:text-slate-400">
                        {{ centeredSummary }}
                    </p>
                </div>
            </div>

            <Transition name="soft-fade" mode="out-in">
                <div v-if="initialLoading" key="live-skeleton" class="mt-6 space-y-3">
                    <div
                        v-for="row in skeletonRows"
                        :key="`live-skeleton-${row}`"
                        class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/70"
                    >
                        <div class="animate-pulse space-y-5">
                            <div class="flex items-center justify-between gap-4">
                                <div class="h-4 w-20 rounded-full bg-slate-200 dark:bg-slate-800" />
                                <div class="h-4 w-56 rounded-full bg-slate-200 dark:bg-slate-800" />
                                <div class="h-8 w-28 rounded-full bg-slate-200 dark:bg-slate-800" />
                            </div>

                            <div class="grid grid-cols-[72px_minmax(0,1fr)_72px] items-center gap-4 md:grid-cols-[88px_minmax(0,1fr)_88px]">
                                <div class="h-[72px] w-[72px] rounded-2xl bg-slate-200 dark:bg-slate-800 md:h-[88px] md:w-[88px]" />

                                <div class="space-y-4">
                                    <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-4">
                                        <div class="justify-self-end space-y-2 text-right">
                                            <div class="ml-auto h-6 w-28 rounded-full bg-slate-200 dark:bg-slate-800" />
                                            <div class="ml-auto h-4 w-10 rounded-full bg-slate-200 dark:bg-slate-800" />
                                        </div>

                                        <div class="h-14 w-28 rounded-2xl bg-slate-200 dark:bg-slate-800" />

                                        <div class="space-y-2">
                                            <div class="h-6 w-28 rounded-full bg-slate-200 dark:bg-slate-800" />
                                            <div class="h-4 w-10 rounded-full bg-slate-200 dark:bg-slate-800" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-[88px_minmax(0,1fr)_88px] items-center gap-4">
                                        <div class="h-7 rounded-full bg-slate-200 dark:bg-slate-800" />
                                        <div class="h-3 rounded-full bg-slate-200 dark:bg-slate-800" />
                                        <div class="h-7 rounded-full bg-slate-200 dark:bg-slate-800" />
                                    </div>
                                </div>

                                <div class="h-[72px] w-[72px] justify-self-end rounded-2xl bg-slate-200 dark:bg-slate-800 md:h-[88px] md:w-[88px]" />
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'live' && liveMatchesState.length" key="live-content" class="mt-6 space-y-3">
                    <Link
                        v-for="match in liveMatchesState"
                        :key="match.id"
                        :href="route('live.show', match.id)"
                        class="block"
                    >
                        <LiveMatchCard
                            :match="match"
                            :status-label="liveBadgeLabel(match)"
                            :status-short="match.statusShort"
                            crest-placement="inside"
                            :hoverable="true"
                        />
                    </Link>
                </div>

                <div v-else-if="activeTab === 'history'" key="history-empty" class="mt-6 rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    Aun no hay historial disponible en esta vista.
                </div>

                <div v-else key="live-empty" class="mt-6 rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                    No hay juegos en vivo en este momento.
                </div>
            </Transition>
        </section>
    </UserDashboardLayout>
</template>

<style scoped>
.soft-fade-enter-active,
.soft-fade-leave-active {
    transition: opacity 260ms ease, transform 260ms ease;
}

.soft-fade-enter-from,
.soft-fade-leave-to {
    opacity: 0;
    transform: translateY(8px);
}

.live-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    padding: 0 1.15rem 0.55rem;
    font-size: 0.95rem;
    font-weight: 600;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    transition: color 180ms ease, border-color 180ms ease;
}

.live-tabs {
    margin-bottom: 0;
    width: 100%;
}

.live-tab-active {
    border-bottom-color: rgb(37 99 235);
    color: rgb(37 99 235);
}

.live-tab-inactive {
    color: rgb(100 116 139);
}

.live-tab-count {
    display: inline-flex;
    min-width: 1.9rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    padding: 0.22rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: normal;
}

.live-tab-count-active {
    background: rgb(219 234 254);
    color: rgb(37 99 235);
}

.live-tab-count-inactive {
    background: rgb(226 232 240);
    color: rgb(71 85 105);
}

.dark .live-tab-active {
    border-bottom-color: rgb(96 165 250);
    color: rgb(96 165 250);
}

.dark .live-tab-inactive {
    color: rgb(148 163 184);
}

.dark .live-tab-count-active {
    background: rgb(30 58 138 / 0.45);
    color: rgb(147 197 253);
}

.dark .live-tab-count-inactive {
    background: rgb(51 65 85);
    color: rgb(226 232 240);
}
</style>
