<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { route } from 'ziggy-js'
import 'flowbite'

const page = usePage()
const openGroup = ref(null)

const toggleGroup = (group) => {
  // si el grupo ya está abierto → ciérralo
  // si no, abre este y cierra cualquier otro
    openGroup.value = openGroup.value === group ? null : group
}

const isActive = (name) => {
    try {
        return route().current(name)
    } catch {
        return false
    }
}

</script>
<template>
    <aside
        class="fixed top-0 left-0 z-40 w-64 h-screen pt-14 transition-transform -translate-x-full bg-white border-r border-gray-200 md:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
        aria-label="Sidenav"
        id="drawer-navigation"
        >
        <div class="overflow-y-auto py-5 px-3 h-full bg-white dark:bg-gray-800">
            <form action="#" method="GET" class="md:hidden mb-2">
            <label for="sidebar-search" class="sr-only">Search</label>
                <div class="relative">
                    <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                        <svg
                            class="w-5 h-5 text-gray-500 dark:text-gray-400"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                            fill-rule="evenodd"
                            clip-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            ></path>
                        </svg>
                    </div>
                    <input
                    type="text"
                    name="search"
                    id="sidebar-search"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                    placeholder="Search"
                    />
                </div>
            </form>
            <ul class="space-y-2">
                <!-- Dashboard -->
                <li>
                    <Link
                        :href="route('admin.dashboard')" :active="route().current('dashboard')"
                        :class="[
                            'flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group',
                            isActive('admin.dashboard') && 'bg-gray-300 dark:bg-gray-400 text-gray-900 dark:text-white'
                        ]">
                        <svg
                            aria-hidden="true"
                            class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path>
                            <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path>
                        </svg>
                        <span class="ml-3">Dashboard</span>
                    </Link>
                </li>
                <!-- Tournaments -->
                <li>
                    <Link
                        :href="route('admin.tournaments.index')" :active="route().current('admin.tournaments.index')"
                        :class="[
                            'flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group',
                            isActive('admin.tournaments.index') && 'bg-gray-300 dark:bg-gray-400 text-gray-900 dark:text-white'
                        ]">
                        <svg
                            aria-hidden="true"
                            class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white"
                            fill="currentColor"
                            viewBox="0 0 512 512"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path d="M144.3 0l224 0c26.5 0 48.1 21.8 47.1 48.2-.2 5.3-.4 10.6-.7 15.8l49.6 0c26.1 0 49.1 21.6 47.1 49.8-7.5 103.7-60.5 160.7-118 190.5-15.8 8.2-31.9 14.3-47.2 18.8-20.2 28.6-41.2 43.7-57.9 51.8l0 73.1 64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-192 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l64 0 0-73.1c-16-7.7-35.9-22-55.3-48.3-18.4-4.8-38.4-12.1-57.9-23.1-54.1-30.3-102.9-87.4-109.9-189.9-1.9-28.1 21-49.7 47.1-49.7l49.6 0c-.3-5.2-.5-10.4-.7-15.8-1-26.5 20.6-48.2 47.1-48.2zM101.5 112l-52.4 0c6.2 84.7 45.1 127.1 85.2 149.6-14.4-37.3-26.3-86-32.8-149.6zM380 256.8c40.5-23.8 77.1-66.1 83.3-144.8L411 112c-6.2 60.9-17.4 108.2-31 144.8z"/>
                        </svg>
                        <span class="ml-3">Tournaments</span>
                    </Link>
                </li>
                <!-- Teams -->
                <li>
                    <Link
                        :href="route('admin.teams.index')" :active="route().current('admin.teams.index')"
                        :class="[
                            'flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group',
                            isActive('admin.teams.index') && 'bg-gray-300 dark:bg-gray-400 text-gray-900 dark:text-white'
                        ]">
                        <svg
                            aria-hidden="true"
                            class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white"
                            fill="currentColor"
                            viewBox="0 0 640 512"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path d="M31-33C21.7-23.6 21.7-8.4 31 1L87 57c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9L65-33c-9.4-9.4-24.6-9.4-33.9 0zM609-33c-9.4-9.4-24.6-9.4-33.9 0L519 23c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0L609 1c9.4-9.4 9.4-24.6 0-33.9zM65 545l56-56c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0L31 511c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0zm544 0c9.4-9.4 9.4-24.6 0-33.9l-56-56c-9.4-9.4-24.6-9.4-33.9 0s-9.4 24.6 0 33.9l56 56c9.4 9.4 24.6 9.4 33.9 0zM320 208a64 64 0 1 0 0-128 64 64 0 1 0 0 128zm0 32c-61.9 0-112 50.1-112 112l0 8c0 13.3 10.7 24 24 24l176 0c13.3 0 24-10.7 24-24l0-8c0-61.9-50.1-112-112-112zm216-80a56 56 0 1 0 -112 0 56 56 0 1 0 112 0zM451.2 260.4C469.4 286.3 480 317.9 480 352l0 8c0 8.4-1.4 16.5-4.1 24l78.8 0c11.8 0 21.3-9.6 21.3-21.3l0-10.7c0-53-43-96-96-96-10 0-19.7 1.5-28.8 4.4zm-262.4 0c-9.1-2.9-18.8-4.4-28.8-4.4-53 0-96 43-96 96l0 10.7c0 11.8 9.6 21.3 21.3 21.3l78.8 0c-2.7-7.5-4.1-15.6-4.1-24l0-8c0-34.1 10.6-65.7 28.8-91.6zM216 160a56 56 0 1 0 -112 0 56 56 0 1 0 112 0z"/>
                        </svg>
                        <span class="ml-3">Teams</span>
                    </Link>
                </li>
                <!-- Flags -->
                <li>
                    <Link
                        :href="route('admin.countries.index')" :active="route().current('admin.countries.index')"
                        :class="[
                            'flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group',
                            isActive('admin.countries.index') && 'bg-gray-300 dark:bg-gray-400 text-gray-900 dark:text-white'
                        ]">
                        <svg
                            aria-hidden="true"
                            class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white"
                            fill="currentColor"
                            viewBox="0 0 448 512"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path d="M64 32C64 14.3 49.7 0 32 0S0 14.3 0 32L0 480c0 17.7 14.3 32 32 32s32-14.3 32-32l0-121.6 62.7-18.8c41.9-12.6 87.1-8.7 126.2 10.9 42.7 21.4 92.5 24 137.2 7.2l37.1-13.9c12.5-4.7 20.8-16.6 20.8-30l0-247.7c0-23-24.2-38-44.8-27.7l-11.8 5.9c-44.9 22.5-97.8 22.5-142.8 0-36.4-18.2-78.3-21.8-117.2-10.1L64 54.4 64 32z"/>
                        </svg>
                        <span class="ml-3">Countries</span>
                    </Link>
                </li>
                <!-- Groups -->
                <li>
                    <Link
                        :href="route('admin.groups.index')" :active="route().current('admin.groups.index')"
                        :class="[
                            'flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group',
                            isActive('admin.groups.index') && 'bg-gray-300 dark:bg-gray-400 text-gray-900 dark:text-white'
                        ]">
                        <svg
                            aria-hidden="true"
                            class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white"
                            fill="currentColor"
                            viewBox="0 0 512 512"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path d="M256 0a64 64 0 1 1 0 128 64 64 0 1 1 0-128zm96 312c0 25-12.7 47-32 59.9l0 92.1c0 26.5-21.5 48-48 48l-32 0c-26.5 0-48-21.5-48-48l0-92.1C172.7 359 160 337 160 312l0-40c0-53 43-96 96-96s96 43 96 96l0 40zM96 32a56 56 0 1 1 0 112 56 56 0 1 1 0-112zm16 240l0 32c0 32.5 12.1 62.1 32 84.7l0 75.3c0 1.2 0 2.5 .1 3.7-8.5 7.6-19.7 12.3-32.1 12.3l-32 0c-26.5 0-48-21.5-48-48l0-56.6C12.9 364.4 0 343.7 0 320l0-32c0-53 43-96 96-96 12.7 0 24.8 2.5 35.9 6.9-12.6 21.4-19.9 46.4-19.9 73.1zM368 464l0-75.3c19.9-22.5 32-52.2 32-84.7l0-32c0-26.7-7.3-51.6-19.9-73.1 11.1-4.5 23.2-6.9 35.9-6.9 53 0 96 43 96 96l0 32c0 23.7-12.9 44.4-32 55.4l0 56.6c0 26.5-21.5 48-48 48l-32 0c-12.3 0-23.6-4.6-32.1-12.3 0-1.2 .1-2.5 .1-3.7zM416 32a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/>
                        </svg>
                        <span class="ml-3">Groups</span>
                    </Link>
                </li>
                <!-- Calendar -->
                <li>
                    <Link
                        :href="route('admin.calendar.index')" :active="route().current('admin.calendar.index')"
                        :class="[
                            'flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group',
                            isActive('admin.calendar.index') && 'bg-gray-300 dark:bg-gray-400 text-gray-900 dark:text-white'
                        ]">
                        <svg
                            aria-hidden="true"
                            class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white"
                            fill="currentColor"
                            viewBox="0 0 448 512"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 32 0c35.3 0 64 28.7 64 64l0 288c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 128C0 92.7 28.7 64 64 64l32 0 0-32c0-17.7 14.3-32 32-32zM64 240l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 368l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z"/>
                        </svg>
                        <span class="ml-3">Calendar</span>
                    </Link>
                </li>
                <!-- Games -->
                <li>
                    <Link
                        :href="route('admin.games.index')" :active="route().current('admin.games.index')"
                        :class="[
                            'flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group',
                            isActive('admin.games.index') && 'bg-gray-300 dark:bg-gray-400 text-gray-900 dark:text-white'
                        ]">
                        <svg
                            aria-hidden="true"
                            class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white"
                            fill="currentColor"
                            viewBox="0 0 512 512"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path d="M387 228.3c-4.4-2.8-7.6-7-9.2-11.9s-1.4-10.2 .5-15L411.6 118c-19.9-22.4-44.6-40.5-72.4-52.7l-69.1 57.6c-4 3.3-9 5.1-14.1 5.1s-10.2-1.8-14.1-5.1L172.8 65.3c-27.8 12.2-52.5 30.3-72.4 52.7l33.4 83.4c1.9 4.8 2.1 10.1 .5 15s-4.9 9.1-9.2 11.9L49 276.2c3 30.9 12.7 59.7 27.6 85.2l89.7-6c5.2-.3 10.3 1.1 14.5 4.2s7.2 7.4 8.4 12.5l22 87.2c14.4 3.2 29.4 4.8 44.8 4.8s30.3-1.7 44.8-4.8l22-87.2c1.3-5 4.2-9.4 8.4-12.5s9.3-4.5 14.5-4.2l89.7 6c15-25.4 24.7-54.3 27.6-85.1L387 228.3zM256 0a256 256 0 1 1 0 512 256 256 0 1 1 0-512zm62 221c8.4 6.1 11.9 16.9 8.7 26.8l-18.3 56.3c-3.2 9.9-12.4 16.6-22.8 16.6l-59.2 0c-10.4 0-19.6-6.7-22.8-16.6l-18.3-56.3c-3.2-9.9 .3-20.7 8.7-26.8l47.9-34.8c8.4-6.1 19.8-6.1 28.2 0L318 221z"/>
                        </svg>
                        <span class="ml-3">Games</span>
                    </Link>
                </li>
                <!-- Pools -->
                <li>
                    <Link
                        :href="route('admin.pools.index')" :active="route().current('admin.pools.index')"
                        :class="[
                            'flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group',
                            isActive('admin.pools.index') && 'bg-gray-300 dark:bg-gray-400 text-gray-900 dark:text-white'
                        ]">
                        <svg
                            aria-hidden="true"
                            class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white"
                            fill="currentColor"
                            viewBox="0 0 512 512"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path d="M141.4 2.3C103-8 63.5 14.8 53.3 53.2L2.5 242.7C-7.8 281.1 15 320.6 53.4 330.9l189.5 50.8c38.4 10.3 77.9-12.5 88.2-50.9l50.8-189.5c10.3-38.4-12.5-77.9-50.9-88.2L141.4 2.3zm23 205.7a32 32 0 1 1 55.4-32 32 32 0 1 1 -55.4 32zM79.2 220.3a32 32 0 1 1 32 55.4 32 32 0 1 1 -32-55.4zm185 96.4a32 32 0 1 1 -32-55.4 32 32 0 1 1 32 55.4zm9-208.4a32 32 0 1 1 32 55.4 32 32 0 1 1 -32-55.4zm-121 14.4a32 32 0 1 1 -32-55.4 32 32 0 1 1 32 55.4zM418 192L377.4 343.2c-17.2 64-83 102-147 84.9l-38.3-10.3 0 30.2c0 35.3 28.7 64 64 64l192 0c35.3 0 64-28.7 64-64l0-192c0-35.3-28.7-64-64-64L418 192z"/>
                        </svg>
                        <span class="ml-3">Pools</span>
                    </Link>
                </li>
                <!-- Rules -->
                <li>
                    <Link
                        :href="route('admin.rules.index')" :active="route().current('admin.rules.*')"
                        :class="[
                            'flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group',
                            isActive('admin.rules.*') && 'bg-gray-300 dark:bg-gray-400 text-gray-900 dark:text-white'
                        ]">
                        <svg
                            aria-hidden="true"
                            class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white"
                            fill="currentColor"
                            viewBox="0 0 512 512"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path d="M184 48H328c4.4 0 8 3.6 8 8V96H176V56c0-4.4 3.6-8 8-8zM176 144H336c53 0 96 43 96 96V432c0 17.7-14.3 32-32 32H112c-17.7 0-32-14.3-32-32V240c0-53 43-96 96-96zm72 80c-13.3 0-24 10.7-24 24v16c0 13.3 10.7 24 24 24h16c13.3 0 24-10.7 24-24V248c0-13.3-10.7-24-24-24H248zm-56 96c-13.3 0-24 10.7-24 24v16c0 13.3 10.7 24 24 24h128c13.3 0 24-10.7 24-24V344c0-13.3-10.7-24-24-24H192z"/>
                        </svg>
                        <span class="ml-3">Rules</span>
                    </Link>
                </li>
                <!-- Users -->
                <li>
                    <Link
                        :href="route('admin.users.index')"
                        :active="route().current('admin.users.index.*')"
                        :class="[
                            'flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group',
                            isActive('admin.users.index.*') && 'bg-gray-300 dark:bg-gray-400 text-gray-900 dark:text-white'
                        ]">
                        <svg
                            aria-hidden="true"
                            class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white"
                            fill="currentColor"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 640 512">
                            <path d="M320 16a104 104 0 1 1 0 208 104 104 0 1 1 0-208zM96 88a72 72 0 1 1 0 144 72 72 0 1 1 0-144zM0 416c0-70.7 57.3-128 128-128 12.8 0 25.2 1.9 36.9 5.4-32.9 36.8-52.9 85.4-52.9 138.6l0 16c0 11.4 2.4 22.2 6.7 32L32 480c-17.7 0-32-14.3-32-32l0-32zm521.3 64c4.3-9.8 6.7-20.6 6.7-32l0-16c0-53.2-20-101.8-52.9-138.6 11.7-3.5 24.1-5.4 36.9-5.4 70.7 0 128 57.3 128 128l0 32c0 17.7-14.3 32-32 32l-86.7 0zM472 160a72 72 0 1 1 144 0 72 72 0 1 1 -144 0zM160 432c0-88.4 71.6-160 160-160s160 71.6 160 160l0 16c0 17.7-14.3 32-32 32l-256 0c-17.7 0-32-14.3-32-32l0-16z"/>
                        </svg>
                        <span class="ml-3">Users</span>
                    </Link>
                </li>
                <!-- Template -->
                <li>
                    <Link
                        :href="route('admin.template')"
                        :active="route().current('admin.template.*')"
                        :class="[
                            'flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group',
                            isActive('admin.template.*') && 'bg-gray-300 dark:bg-gray-400 text-gray-900 dark:text-white'
                        ]">
                        <svg
                            aria-hidden="true"
                            class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white"
                            fill="currentColor"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 448 512">
                            <path d="M0 448c0 17.7 14.3 32 32 32s32-14.3 32-32l0-352 352 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L64 32C28.7 32 0 60.7 0 96L0 448zm128 0c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32zm128 0c0 17.7 14.3 32 32 32s32-14.3 32-32-14.3-32-32-32-32 14.3-32 32zm160 32c17.7 0 32-14.3 32-32s-14.3-32-32-32-32 14.3-32 32 14.3 32 32 32zM384 192a32 32 0 1 0 64 0 32 32 0 1 0 -64 0zm32 160a32 32 0 1 0 0-64 32 32 0 1 0 0 64z"/>
                        </svg>
                        <span class="ml-3">Template</span>
                    </Link>
                </li>
                <!-- <li>
                    <button
                    type="button"
                    class="flex items-center p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                    aria-controls="dropdown-pages"
                    data-collapse-toggle="dropdown-pages"
                    >
                    <svg
                        aria-hidden="true"
                        class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                        fill-rule="evenodd"
                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                        clip-rule="evenodd"
                        ></path>
                    </svg>
                    <span class="flex-1 ml-3 text-left whitespace-nowrap"
                        >Pages</span
                    >
                    <svg
                        aria-hidden="true"
                        class="w-6 h-6"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                        fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                        ></path>
                    </svg>
                    </button>
                    <ul id="dropdown-pages" class="hidden py-2 space-y-2">
                    <li>
                        <a
                        href="#"
                        class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                        >Settings</a
                        >
                    </li>
                    <li>
                        <a
                        href="#"
                        class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                        >Kanban</a
                        >
                    </li>
                    <li>
                        <a
                        href="#"
                        class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                        >Calendar</a
                        >
                    </li>
                    </ul>
                </li>
                <li>
                    <button
                    type="button"
                    class="flex items-center p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                    aria-controls="dropdown-sales"
                    data-collapse-toggle="dropdown-sales"
                    >
                    <svg
                        aria-hidden="true"
                        class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                        fill-rule="evenodd"
                        d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z"
                        clip-rule="evenodd"
                        ></path>
                    </svg>
                    <span class="flex-1 ml-3 text-left whitespace-nowrap"
                        >Sales</span
                    >
                    <svg
                        aria-hidden="true"
                        class="w-6 h-6"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                        fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                        ></path>
                    </svg>
                    </button>
                    <ul id="dropdown-sales" class="hidden py-2 space-y-2">
                    <li>
                        <a
                        href="#"
                        class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                        >Products</a
                        >
                    </li>
                    <li>
                        <a
                        href="#"
                        class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                        >Billing</a
                        >
                    </li>
                    <li>
                        <a
                        href="#"
                        class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                        >Invoice</a
                        >
                    </li>
                    </ul>
                </li>
                <li>
                    <a
                    href="#"
                    class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                    >
                    <svg
                        aria-hidden="true"
                        class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                        d="M8.707 7.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l2-2a1 1 0 00-1.414-1.414L11 7.586V3a1 1 0 10-2 0v4.586l-.293-.293z"
                        ></path>
                        <path
                        d="M3 5a2 2 0 012-2h1a1 1 0 010 2H5v7h2l1 2h4l1-2h2V5h-1a1 1 0 110-2h1a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5z"
                        ></path>
                    </svg>
                    <span class="flex-1 ml-3 whitespace-nowrap">Messages</span>
                    <span
                        class="inline-flex justify-center items-center w-5 h-5 text-xs font-semibold rounded-full text-primary-800 bg-primary-100 dark:bg-primary-200 dark:text-primary-800"
                    >
                        4
                    </span>
                    </a>
                </li>
                <li>
                    <button
                    type="button"
                    class="flex items-center p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                    aria-controls="dropdown-authentication"
                    data-collapse-toggle="dropdown-authentication"
                    >
                    <svg
                        aria-hidden="true"
                        class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                        fill-rule="evenodd"
                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                        clip-rule="evenodd"
                        ></path>
                    </svg>
                    <span class="flex-1 ml-3 text-left whitespace-nowrap"
                        >Authentication</span
                    >
                    <svg
                        aria-hidden="true"
                        class="w-6 h-6"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                        fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                        ></path>
                    </svg>
                    </button>
                    <ul id="dropdown-authentication" class="hidden py-2 space-y-2">
                    <li>
                        <a
                        href="#"
                        class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                        >Sign In</a
                        >
                    </li>
                    <li>
                        <a
                        href="#"
                        class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                        >Sign Up</a
                        >
                    </li>
                    <li>
                        <a
                        href="#"
                        class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                        >Forgot Password</a
                        >
                    </li>
                    </ul>
                </li> -->
            </ul>
            <ul class="pt-5 mt-5 space-y-2 border-t border-gray-200 dark:border-gray-700">
                <li>
                    <a
                    href="#"
                    class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group"
                    >
                    <svg
                        aria-hidden="true"
                        class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                        <path
                        fill-rule="evenodd"
                        d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"
                        clip-rule="evenodd"
                        ></path>
                    </svg>
                    <span class="ml-3">Docs</span>
                    </a>
                </li>
                <li>
                    <a
                    href="#"
                    class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group"
                    >
                    <svg
                        aria-hidden="true"
                        class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                        d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"
                        ></path>
                    </svg>
                    <span class="ml-3">Components</span>
                    </a>
                </li>
                <li>
                    <a
                    href="#"
                    class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg transition duration-75 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-white group"
                    >
                    <svg
                        aria-hidden="true"
                        class="flex-shrink-0 w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                        fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l-1.524-1.525a3.997 3.997 0 00.078-2.183l1.562-1.562C15.802 8.249 16 9.1 16 10zm-5.165 3.913l1.58 1.58A5.98 5.98 0 0110 16a5.976 5.976 0 01-2.516-.552l1.562-1.562a4.006 4.006 0 001.789.027zm-4.677-2.796a4.002 4.002 0 01-.041-2.08l-.08.08-1.53-1.533A5.98 5.98 0 004 10c0 .954.223 1.856.619 2.657l1.54-1.54zm1.088-6.45A5.974 5.974 0 0110 4c.954 0 1.856.223 2.657.619l-1.54 1.54a4.002 4.002 0 00-2.346.033L7.246 4.668zM12 10a2 2 0 11-4 0 2 2 0 014 0z"
                        clip-rule="evenodd"
                        ></path>
                    </svg>
                    <span class="ml-3">Help</span>
                    </a>
                </li>
            </ul>
        </div>
        <div
            class="hidden absolute bottom-0 left-0 justify-center p-4 space-x-4 w-full lg:flex bg-white dark:bg-gray-800 z-20"
        >
            <a
            href="#"
            class="inline-flex justify-center p-2 text-gray-500 rounded cursor-pointer dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-600"
            >
            <svg
                aria-hidden="true"
                class="w-6 h-6"
                fill="currentColor"
                viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z"
                ></path>
            </svg>
            </a>
            <a
            href="#"
            data-tooltip-target="tooltip-settings"
            class="inline-flex justify-center p-2 text-gray-500 rounded cursor-pointer dark:text-gray-400 dark:hover:text-white hover:text-gray-900 hover:bg-gray-100 dark:hover:bg-gray-600"
            >
            <svg
                aria-hidden="true"
                class="w-6 h-6"
                fill="currentColor"
                viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                fill-rule="evenodd"
                d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"
                clip-rule="evenodd"
                ></path>
            </svg>
            </a>
            <div
            id="tooltip-settings"
            role="tooltip"
            class="inline-block absolute invisible z-10 py-2 px-3 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm opacity-0 transition-opacity duration-300 tooltip"
            >
            Settings page
            <div class="tooltip-arrow" data-popper-arrow></div>
            </div>
            <button
            type="button"
            data-dropdown-toggle="language-dropdown"
            class="inline-flex justify-center p-2 text-gray-500 rounded cursor-pointer dark:hover:text-white dark:text-gray-400 hover:text-gray-900 hover:bg-gray-100 dark:hover:bg-gray-600"
            >
            <svg
                aria-hidden="true"
                class="h-5 w-5 rounded-full mt-0.5"
                xmlns="http://www.w3.org/2000/svg"
                xmlns:xlink="http://www.w3.org/1999/xlink"
                viewBox="0 0 3900 3900"
            >
                <path fill="#b22234" d="M0 0h7410v3900H0z" />
                <path
                d="M0 450h7410m0 600H0m0 600h7410m0 600H0m0 600h7410m0 600H0"
                stroke="#fff"
                stroke-width="300"
                />
                <path fill="#3c3b6e" d="M0 0h2964v2100H0z" />
                <g fill="#fff">
                <g id="d">
                    <g id="c">
                    <g id="e">
                        <g id="b">
                        <path
                            id="a"
                            d="M247 90l70.534 217.082-184.66-134.164h228.253L176.466 307.082z"
                        />
                        <use xlink:href="#a" y="420" />
                        <use xlink:href="#a" y="840" />
                        <use xlink:href="#a" y="1260" />
                        </g>
                        <use xlink:href="#a" y="1680" />
                    </g>
                    <use xlink:href="#b" x="247" y="210" />
                    </g>
                    <use xlink:href="#c" x="494" />
                </g>
                <use xlink:href="#d" x="988" />
                <use xlink:href="#c" x="1976" />
                <use xlink:href="#e" x="2470" />
                </g>
            </svg>
            </button>
            <!-- Dropdown -->
            <div
                class="hidden z-50 my-4 text-base list-none bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700"
                id="language-dropdown">
                <ul class="py-1" role="none">
                    <li>
                        <a
                            href="#"
                            class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100 dark:hover:text-white dark:text-gray-300 dark:hover:bg-gray-600"
                            role="menuitem"
                        >
                            <div class="inline-flex items-center">
                            <svg
                                aria-hidden="true"
                                class="h-3.5 w-3.5 rounded-full mr-2"
                                xmlns="http://www.w3.org/2000/svg"
                                id="flag-icon-css-us"
                                viewBox="0 0 512 512"
                            >
                                <g fill-rule="evenodd">
                                <g stroke-width="1pt">
                                    <path
                                    fill="#bd3d44"
                                    d="M0 0h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0z"
                                    transform="scale(3.9385)"
                                    />
                                    <path
                                    fill="#fff"
                                    d="M0 10h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0zm0 20h247v10H0z"
                                    transform="scale(3.9385)"
                                    />
                                </g>
                                <path
                                    fill="#192f5d"
                                    d="M0 0h98.8v70H0z"
                                    transform="scale(3.9385)"
                                />
                                <path
                                    fill="#fff"
                                    d="M8.2 3l1 2.8H12L9.7 7.5l.9 2.7-2.4-1.7L6 10.2l.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7L74 8.5l-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 7.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 24.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 21.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 38.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 35.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 52.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 49.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm-74.1 7l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7H65zm16.4 0l1 2.8H86l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm-74 7l.8 2.8h3l-2.4 1.7.9 2.7-2.4-1.7L6 66.2l.9-2.7-2.4-1.7h3zm16.4 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8H45l-2.4 1.7 1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9zm16.4 0l1 2.8h2.8l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h3zm16.5 0l.9 2.8h2.9l-2.3 1.7.9 2.7-2.4-1.7-2.3 1.7.9-2.7-2.4-1.7h2.9zm16.5 0l.9 2.8h2.9L92 63.5l1 2.7-2.4-1.7-2.4 1.7 1-2.7-2.4-1.7h2.9z"
                                    transform="scale(3.9385)"
                                />
                                </g>
                            </svg>
                            English (US)
                            </div>
                        </a>
                    </li>
                    <li>
                        <a
                            href="#"
                            class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100 dark:hover:text-white dark:text-gray-300 dark:hover:bg-gray-600"
                            role="menuitem"
                        >
                            <div class="inline-flex items-center">
                            <svg
                                aria-hidden="true"
                                class="h-3.5 w-3.5 rounded-full mr-2"
                                xmlns="http://www.w3.org/2000/svg"
                                id="flag-icon-css-es"
                                viewBox="0 0 512 512"
                            >
                                <!-- Spain flag: red / yellow / red -->
                                <path fill="#c60b1e" d="M0 0h512v128H0z" />
                                <path fill="#ffc400" d="M0 128h512v256H0z" />
                                <path fill="#c60b1e" d="M0 384h512v128H0z" />
                            </svg>

                            Español (ES)
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </aside>
</template>
