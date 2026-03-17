<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const currentYear = computed(() => new Date().getFullYear())

const links = [
    { label: 'Inicio', href: route('dashboard') },
    { label: 'Mis quinielas', href: route('pools.index') },
    { label: 'Partidos', href: route('matches.index') },
    { label: 'Ranking', href: route('leaderboard') },
]
</script>

<template>
    <footer class="mt-10 rounded-[1.75rem] border border-slate-200/80 bg-white/90 shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900/85 dark:shadow-none">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-5 md:flex-row md:items-center md:justify-between md:px-6">
            <span class="text-sm text-slate-500 dark:text-slate-400">
                &copy; {{ currentYear }}
                <Link :href="route('dashboard')" class="font-semibold text-slate-700 transition hover:text-primary-600 dark:text-slate-200 dark:hover:text-primary-300">
                    Quiniela 2026
                </Link>
                . Todos los derechos reservados.
            </span>

            <ul class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-medium text-slate-500 dark:text-slate-400">
                <li v-for="item in links" :key="item.label">
                    <Link
                        :href="item.href"
                        class="transition hover:text-primary-600 hover:underline dark:hover:text-primary-300"
                    >
                        {{ item.label }}
                    </Link>
                </li>
            </ul>
        </div>
    </footer>
</template>
