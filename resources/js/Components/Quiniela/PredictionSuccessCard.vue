<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    entry: {
        type: Object,
        required: true,
    },
})

const confettiPieces = Array.from({ length: 24 }, (_, index) => ({
    id: index,
    left: `${(index % 8) * 12 + 6}%`,
    delay: `${(index % 6) * 120}ms`,
    duration: `${2200 + (index % 5) * 180}ms`,
    colorClass: [
        'bg-cyan-300',
        'bg-emerald-300',
        'bg-amber-300',
        'bg-sky-300',
    ][index % 4],
}))
</script>

<template>
    <div class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/75 px-4 backdrop-blur-sm">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <span
                v-for="piece in confettiPieces"
                :key="piece.id"
                :class="piece.colorClass"
                class="absolute top-0 h-3 w-2 rounded-full opacity-90 animate-[confetti-fall_var(--duration)_ease-in_forwards]"
                :style="{ left: piece.left, animationDelay: piece.delay, '--duration': piece.duration }"
            />
        </div>

        <div class="relative w-full max-w-2xl overflow-hidden rounded-[2rem] border border-cyan-300/20 bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.16),_transparent_35%),linear-gradient(180deg,_rgba(15,23,42,0.98),_rgba(2,6,23,0.98))] p-8 shadow-2xl shadow-cyan-950/40">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-300 via-emerald-300 to-amber-300" />

            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-200/90">
                    Quiniela registrada
                </p>
                <h2 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl">
                    Tu quiniela ya quedo lista
                </h2>
                <p class="mt-3 text-sm leading-6 text-slate-300 sm:text-base">
                    Registro <span class="font-bold text-white">#{{ entry.registration_number }}</span>
                    generado con exito para {{ entry.tournament_name }}.
                </p>
            </div>

            <div class="mt-8 grid gap-4 rounded-3xl border border-white/10 bg-white/5 p-5 sm:grid-cols-3">
                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Numero unico</p>
                    <p class="mt-2 text-2xl font-black text-white">#{{ entry.registration_number }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Estado</p>
                    <p class="mt-2 text-2xl font-black capitalize text-emerald-300">{{ entry.status }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Completado</p>
                    <p class="mt-2 text-2xl font-black text-cyan-300">{{ entry.completion_percent }}%</p>
                </div>
            </div>

            <div class="mt-6 rounded-3xl border border-amber-300/20 bg-amber-300/10 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-200/80">Tu campeon pronosticado</p>
                <p class="mt-2 text-2xl font-black text-white">
                    {{ entry.champion_name || 'Por definirse en la final de tu quiniela' }}
                </p>
                <p class="mt-2 text-sm text-slate-300">
                    Guarda este numero de registro para identificar esta quiniela entre todas tus participaciones.
                </p>
            </div>

            <div class="mt-8 flex justify-center">
                <Link
                    :href="route('pools.index')"
                    class="inline-flex items-center justify-center rounded-2xl border border-cyan-300/20 bg-cyan-400/10 px-6 py-3 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-400/20"
                >
                    Continuar
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes confetti-fall {
    0% {
        transform: translate3d(0, -20px, 0) rotate(0deg);
        opacity: 0;
    }

    15% {
        opacity: 1;
    }

    100% {
        transform: translate3d(0, 105vh, 0) rotate(540deg);
        opacity: 0;
    }
}
</style>
