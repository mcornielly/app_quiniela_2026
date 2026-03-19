<script setup>
import { computed, onMounted, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import AppTooltip from '@/Components/UI/AppTooltip.vue'
import UserAvatar from '@/Components/User/UserAvatar.vue'
import UserFooter from '@/Components/User/UserFooter.vue'
import {
    Bars3Icon,
    CalendarDaysIcon,
    ChartBarSquareIcon,
    ChevronDownIcon,
    MoonIcon,
    SunIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: 'Tu espacio para seguir partidos, resultados, quinielas y el pulso del Mundial 2026 desde una sola vista.',
    },
    ticker: {
        type: String,
        default: '',
    },
    tickerClass: {
        type: String,
        default: 'border-t border-slate-200/80 bg-slate-950 text-white dark:border-slate-800 dark:bg-slate-900',
    },
})

const page = usePage()
const user = computed(() => page.props.auth?.user ?? null)
const mobileNavOpen = ref(false)
const mobileWorldCupOpen = ref(false)
const currentTheme = ref('dark')

const worldCupNavigation = computed(() => [
    { name: 'Calendario', href: route('matches.index'), current: route().current('matches.index'), icon: 'calendar' },
    { name: 'Juegos', href: route('predictions.worldcup'), current: route().current('predictions.worldcup'), icon: 'ball' },
    { name: 'Resultados', href: route('pools.index'), current: route().current('pools.index'), icon: 'score' },
    { name: 'Estadisticas', href: route('leaderboard'), current: route().current('leaderboard'), icon: 'chart' },
    { name: 'Roadmap', href: route('groups.index'), current: route().current('groups.index'), icon: 'roadmap' },
])

const navigation = computed(() => [
    { name: 'Inicio', href: route('dashboard'), current: route().current('dashboard') },
    { name: 'Mis quinielas', href: route('pools.index'), current: route().current('pools.index') },
    { name: 'Crear quiniela', href: route('predictions.worldcup'), current: route().current('predictions.worldcup') },
])

const isWorldCupActive = computed(() => worldCupNavigation.value.some((item) => item.current))

const applyTheme = (theme) => {
    currentTheme.value = theme
    localStorage.setItem('user-theme', theme)
    document.documentElement.classList.toggle('dark', theme === 'dark')
}

const toggleTheme = () => {
    applyTheme(currentTheme.value === 'dark' ? 'light' : 'dark')
}

const worldCupIconPaths = {
    calendar: 'M152 64H128V16a16 16 0 1 0-32 0V64H64C28.7 64 0 92.7 0 128v320c0 35.3 28.7 64 64 64H448c35.3 0 64-28.7 64-64V128c0-35.3-28.7-64-64-64H416V16a16 16 0 1 0-32 0V64H152zm296 96v96H64V160H448z',
    ball: 'M222.7 32.1c-18.3-4.5-37.1-4.5-55.4 0L131 96H259L222.7 32.1zM94.1 118.6 62.6 173.1 94.9 228l73.2-3.4 31.3-54.6L167.1 115H104.5c-3.6 1.1-7.1 2.3-10.4 3.6zm323 54.5-31.2-54.1c-3.4-1.3-6.9-2.5-10.5-3.6H312.8l-32.3 55 31.3 54.6 73.2 3.4 32.1-54.9zm-193.4 79.8H184.3L153 307.4l36.3 63.9c18.3 4.5 37.1 4.5 55.4 0L281 307.4l-31.3-54.5zm-136 17.5-57.4-2.7L7.5 322.6c8.6 28.1 24.6 53 45.8 72.5l58.9-10.3L144 330.3 87.7 270.4zm336.6-2.7-57.4 2.7L368 330.3l31.8 54.5 58.9 10.3c21.2-19.6 37.2-44.4 45.8-72.5l-22.8-54.9zM127.8 412.2 83.1 420c24.3 16.7 53.7 27.1 85.2 28.8l-40.5-36.6zm216.4 0-40.5 36.6c31.5-1.7 60.9-12.1 85.2-28.8l-44.7-7.8z',
    score: 'M80 80c0-26.5 21.5-48 48-48H384c26.5 0 48 21.5 48 48V400c0 26.5-21.5 48-48 48H128c-26.5 0-48-21.5-48-48V80zm144 64a24 24 0 1 0 0 48 24 24 0 1 0 0-48zm0 112a24 24 0 1 0 0 48 24 24 0 1 0 0-48zM32 112c17.7 0 32 14.3 32 32V336c0 17.7-14.3 32-32 32S0 353.7 0 336V144c0-17.7 14.3-32 32-32zm448 0c17.7 0 32 14.3 32 32V336c0 17.7-14.3 32-32 32s-32-14.3-32-32V144c0-17.7 14.3-32 32-32z',
    chart: 'M32 32C14.3 32 0 46.3 0 64V448c0 17.7 14.3 32 32 32H480c17.7 0 32-14.3 32-32s-14.3-32-32-32H64V64c0-17.7-14.3-32-32-32zM160 224c17.7 0 32 14.3 32 32V416H128V256c0-17.7 14.3-32 32-32zm128-64c17.7 0 32 14.3 32 32V416H256V192c0-17.7 14.3-32 32-32zM480 96V416H416V96c0-17.7 14.3-32 32-32s32 14.3 32 32z',
    roadmap: 'M128 64c0-35.3 28.7-64 64-64H320c35.3 0 64 28.7 64 64v32h64c35.3 0 64 28.7 64 64V288c0 35.3-28.7 64-64 64H320c-35.3 0-64-28.7-64-64V256H192v32c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V160c0-35.3 28.7-64 64-64h64V64zm64 32H320V64H192V96zM64 160V288h64V160H64zm256 128h128V160H320V288z',
}

onMounted(() => {
    const storedTheme = localStorage.getItem('user-theme')
    const initialTheme = storedTheme === 'light' || storedTheme === 'dark'
        ? storedTheme
        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')

    applyTheme(initialTheme)
})

</script>

<template>
    <div class="min-h-screen bg-slate-100 text-slate-900 transition-colors dark:bg-slate-950 dark:text-slate-100">
        <div class="absolute inset-x-0 top-0 -z-10 h-80 bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.16),_transparent_55%)] dark:bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.18),_transparent_45%)]" />

        <header class="sticky top-0 z-40 border-b border-white/80 bg-white/88 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/92">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <Link :href="route('dashboard')" class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-500 to-sky-500 text-white shadow-lg shadow-primary-500/25">
                            <ApplicationLogo class="h-6 w-6 fill-current" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-primary-600 dark:text-primary-400">
                                Quiniela 2026
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Experiencia del usuario
                            </p>
                        </div>
                    </Link>
                </div>

                <nav class="hidden items-center gap-7 lg:flex">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        :class="item.current
                            ? 'text-slate-950 dark:text-white'
                            : 'text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white'"
                        class="group relative inline-flex items-center py-2 text-sm font-medium transition-colors duration-300"
                    >
                        <span>{{ item.name }}</span>
                        <span
                            :class="item.current
                                ? 'w-full bg-primary-500'
                                : 'w-0 bg-slate-900 group-hover:w-full dark:bg-white'"
                            class="absolute -bottom-1 left-0 h-0.5 rounded-full transition-all duration-300"
                        />
                    </Link>

                    <div class="group relative">
                        <button
                            type="button"
                            :class="isWorldCupActive
                                ? 'text-slate-950 dark:text-white'
                                : 'text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white'"
                            class="inline-flex items-center gap-2 py-2 text-sm font-medium transition-colors duration-300"
                        >
                            <span>Mundial</span>
                            <ChevronDownIcon class="h-4 w-4 transition-transform duration-300 group-hover:rotate-180" />
                        </button>
                        <span
                            :class="isWorldCupActive ? 'w-full bg-primary-500' : 'w-0 bg-slate-900 group-hover:w-full dark:bg-white'"
                            class="absolute -bottom-1 left-0 h-0.5 rounded-full transition-all duration-300"
                        />

                        <div class="invisible absolute left-1/2 top-full z-50 mt-4 w-56 -translate-x-1/2 rounded-2xl border border-slate-200/80 bg-white/95 p-2 opacity-0 shadow-xl shadow-slate-200/70 transition-all duration-200 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 dark:border-slate-800 dark:bg-slate-900/95 dark:shadow-none">
                            <Link
                                v-for="item in worldCupNavigation"
                                :key="item.name"
                                :href="item.href"
                                :class="item.current
                                    ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300'
                                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white'"
                                class="flex items-center justify-between rounded-xl px-4 py-3 text-sm font-medium transition"
                            >
                                <span class="flex items-center gap-3">
                                    <svg viewBox="0 0 512 512" class="h-4 w-4 fill-current opacity-70" aria-hidden="true">
                                        <path :d="worldCupIconPaths[item.icon]" />
                                    </svg>
                                    <span>{{ item.name }}</span>
                                </span>
                                <span class="h-1.5 w-1.5 rounded-full bg-current opacity-25" />
                            </Link>
                        </div>
                    </div>
                </nav>

                <div class="flex items-center gap-3">
                    <AppTooltip
                        :text="currentTheme === 'dark' ? 'Toggle light mode' : 'Toggle dark mode'"
                        placement="bottom"
                        tooltip-class="border border-slate-700/90 bg-slate-800/95 shadow-lg shadow-slate-950/25 backdrop-blur-sm"
                        arrow-class="border-slate-700/90 bg-slate-800/95"
                    >
                        <button
                            type="button"
                            @click="toggleTheme()"
                            class="hidden h-11 w-11 items-center justify-center rounded-full border border-transparent bg-transparent text-slate-500 transition-all duration-200 ease-out hover:border-slate-200 hover:bg-slate-100/90 hover:text-slate-700 focus:border-primary-200 focus:bg-primary-50/90 focus:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-200/45 active:scale-95 sm:inline-flex dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-800/80 dark:hover:text-slate-100 dark:focus:border-sky-400/20 dark:focus:bg-sky-400/10 dark:focus:text-white dark:focus:ring-sky-400/15"
                            :aria-label="currentTheme === 'dark' ? 'Activar modo claro' : 'Activar modo oscuro'"
                        >
                            <SunIcon v-if="currentTheme === 'dark'" class="h-4 w-4" />
                            <MoonIcon v-else class="h-4 w-4" />
                        </button>
                    </AppTooltip>

                    <div class="hidden h-8 w-px bg-slate-200 dark:bg-slate-800 sm:block" />

                    <Dropdown align="right" width="48" content-classes="py-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xl">
                        <template #trigger>
                            <button type="button" class="inline-flex items-center gap-3 rounded-2xl bg-transparent px-1 py-1 text-left transition hover:opacity-90">
                                <UserAvatar :name="user?.name" />
                                <div class="hidden min-w-0 sm:block">
                                    <p class="truncate text-sm font-semibold text-slate-950 dark:text-white">
                                        {{ user?.name ?? 'Usuario' }}
                                    </p>
                                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                                        {{ user?.email ?? '' }}
                                    </p>
                                </div>
                            </button>
                        </template>

                        <template #content>
                            <DropdownLink :href="route('profile.edit')">
                                Mi perfil
                            </DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">
                                Cerrar sesion
                            </DropdownLink>
                        </template>
                    </Dropdown>

                    <button
                        type="button"
                        @click="mobileNavOpen = !mobileNavOpen"
                        class="inline-flex rounded-2xl border border-slate-200/80 bg-white p-3 text-slate-600 shadow-sm transition hover:text-slate-950 lg:hidden dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:text-white"
                    >
                        <Bars3Icon v-if="!mobileNavOpen" class="h-5 w-5" />
                        <XMarkIcon v-else class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div
                v-if="ticker || $slots.ticker"
                :class="props.tickerClass"
            >
                <div class="mx-auto max-w-7xl px-4 py-2 sm:px-6 lg:px-8">
                    <slot name="ticker">
                        <div class="flex items-center gap-3">
                            <p class="text-sm font-medium tracking-wide text-slate-100">
                                {{ ticker }}
                            </p>
                        </div>
                    </slot>
                </div>
            </div>

            <div v-if="mobileNavOpen" class="border-t border-slate-200/80 bg-white px-4 py-4 lg:hidden dark:border-slate-800 dark:bg-slate-950">
                <div class="grid gap-2">
                    <Link
                        v-for="item in navigation"
                        :key="`mobile-${item.name}`"
                        :href="item.href"
                        :class="item.current
                            ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300'
                            : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-900'"
                        class="rounded-2xl px-4 py-3 text-sm font-medium transition"
                    >
                        <span>{{ item.name }}</span>
                    </Link>

                    <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800">
                        <button
                            type="button"
                            @click="mobileWorldCupOpen = !mobileWorldCupOpen"
                            :class="isWorldCupActive
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300'
                                : 'text-slate-600 dark:text-slate-300'"
                            class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm font-medium transition"
                        >
                            <span>Mundial</span>
                            <ChevronDownIcon
                                :class="mobileWorldCupOpen ? 'rotate-180' : ''"
                                class="h-4 w-4 transition-transform duration-300"
                            />
                        </button>

                        <div v-if="mobileWorldCupOpen" class="grid gap-1 px-2 pb-2">
                            <Link
                                v-for="item in worldCupNavigation"
                                :key="`mobile-worldcup-${item.name}`"
                                :href="item.href"
                                :class="item.current
                                    ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300'
                                    : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-900'"
                                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm transition"
                            >
                                <svg viewBox="0 0 512 512" class="h-4 w-4 fill-current opacity-70" aria-hidden="true">
                                    <path :d="worldCupIconPaths[item.icon]" />
                                </svg>
                                <span>{{ item.name }}</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <AppTooltip
                        :text="currentTheme === 'dark' ? 'Toggle light mode' : 'Toggle dark mode'"
                        placement="top"
                        tooltip-class="border border-slate-700/90 bg-slate-800/95 shadow-lg shadow-slate-950/25 backdrop-blur-sm"
                        arrow-class="border-slate-700/90 bg-slate-800/95"
                    >
                        <button
                            type="button"
                            @click="toggleTheme()"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-transparent bg-transparent text-slate-500 transition-all duration-200 ease-out hover:border-slate-200 hover:bg-slate-100/90 hover:text-slate-700 focus:border-primary-200 focus:bg-primary-50/90 focus:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-200/45 active:scale-95 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:bg-slate-800/80 dark:hover:text-slate-100 dark:focus:border-sky-400/20 dark:focus:bg-sky-400/10 dark:focus:text-white dark:focus:ring-sky-400/15"
                            :aria-label="currentTheme === 'dark' ? 'Activar modo claro' : 'Activar modo oscuro'"
                        >
                            <SunIcon v-if="currentTheme === 'dark'" class="h-4 w-4" />
                            <MoonIcon v-else class="h-4 w-4" />
                        </button>
                    </AppTooltip>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8 flex flex-col gap-6 xl:flex-row xl:items-stretch">
                <div v-if="$slots.headerAside" class="xl:w-[19rem] xl:shrink-0">
                    <slot name="headerAside" />
                </div>

                <slot v-if="$slots.headerContent" name="headerContent" />

                <div v-else class="min-w-0 flex-1 overflow-hidden rounded-[2rem] border border-white/80 bg-white/90 p-6 shadow-xl shadow-slate-200/70 backdrop-blur dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-none">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-2xl">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-primary-600 dark:text-primary-400">
                                Bienvenido
                            </p>
                            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">
                                {{ props.title || 'Panel principal' }}
                            </h1>
                            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                {{ props.description }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <slot name="headerActions">
                                <Link
                                    :href="route('predictions.worldcup')"
                                    class="inline-flex items-center justify-center rounded-2xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-sm shadow-primary-500/30 transition hover:bg-primary-700"
                                >
                                    Crear quiniela
                                </Link>
                                <Link
                                    :href="route('pools.index')"
                                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-primary-300 hover:text-primary-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-primary-500 dark:hover:text-primary-300"
                                >
                                    Ver mis quinielas
                                </Link>
                            </slot>
                        </div>
                    </div>
                </div>
            </div>
            <slot />

            <UserFooter />
        </main>
    </div>
</template>
