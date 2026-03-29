<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const isSidebarOpen = ref(false);
const user = usePage().props.auth.user || { name: 'Guest' }; // Fallback for testing

const navigation = [
  { name: 'Dashboard', href: route('dashboard'), icon: 'dashboard' },
  { name: 'Mi Quiniela', href: route('predictions.index'), icon: 'edit_calendar' },
  { name: 'Partidos', href: route('matches.index'), icon: 'sports_soccer' },
  { name: 'Grupos', href: route('groups.index'), icon: 'groups' },
  { name: 'Ranking', href: route('leaderboard'), icon: 'leaderboard' },
];
</script>

<template>
  <div class="min-h-screen bg-[#0f172a] text-white font-sans">
    <nav class="fixed font-medium top-0 z-50 w-full bg-slate-900/80 backdrop-blur-md border-b border-white/10">
      <div class="px-3 py-3 lg:px-5 lg:pl-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <Link :href="route('welcome')" class="flex ml-2 md:mr-24">
              <span class="self-center text-xl font-bold sm:text-2xl bg-gradient-to-r from-blue-400 to-emerald-400 bg-clip-text text-transparent">
                WC2026 PRO
              </span>
            </Link>
          </div>
          <div class="flex items-center gap-4">
             <Link :href="route('profile.edit')" class="text-sm hover:text-blue-400 transition">{{ user.name }}</Link>
             <Link :href="route('logout')" method="post" as="button" class="bg-red-500/20 text-red-400 px-3 py-1 rounded-lg text-xs border border-red-500/50">Salir</Link>
          </div>
        </div>
      </div>
    </nav>

    <div class="flex pt-16">
      <aside class="fixed left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0 border-r border-white/5 bg-slate-900/50">
        <div class="h-full px-3 py-4 overflow-y-auto">
          <ul class="space-y-2 font-medium">
            <li v-for="item in navigation" :key="item.name">
              <Link :href="item.href" 
                :class="[$page.component.startsWith(item.name) ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-gray-400 hover:bg-white/5']"
                class="flex items-center p-3 rounded-xl transition-all group">
                <span class="material-symbols-outlined">{{ item.icon }}</span>
                <span class="ml-3">{{ item.name }}</span>
              </Link>
            </li>
          </ul>
        </div>
      </aside>

      <main class="p-4 sm:ml-64 w-full min-h-screen bg-radial-at-t from-slate-800 to-slate-950">
        <slot />
      </main>
    </div>
  </div>
</template>
