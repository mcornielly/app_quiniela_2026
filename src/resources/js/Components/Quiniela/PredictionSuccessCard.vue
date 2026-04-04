<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { launchPoolEntrySuccessConfetti, resetConfetti } from '@/Utils/confetti'
import { formatRegistrationNumber } from '@/Utils/format'

defineProps({
    poolEntry: {
        type: Object,
        required: true,
    },
})

const statusLabel = (status) => {
    const labels = {
        complete: 'Completado',
    }

    return labels[status] ?? status
}

const backConfettiCanvas = ref(null)
const frontConfettiCanvas = ref(null)

const launchConfetti = () => {
    launchPoolEntrySuccessConfetti({
        backCanvas: backConfettiCanvas.value,
        frontCanvas: frontConfettiCanvas.value,
    })
}

onMounted(() => {
    launchConfetti()
})

onBeforeUnmount(() => {
    resetConfetti()
})
</script>

<template>
    <div class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-100/80 px-4 backdrop-blur-sm dark:bg-slate-950/75">
        <canvas
            ref="backConfettiCanvas"
            class="pointer-events-none fixed inset-0 h-full w-full"
            aria-hidden="true"
        />

        <div class="relative z-[72] w-full max-w-2xl overflow-hidden rounded-[2rem] border border-cyan-300/40 bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.18),_transparent_40%),linear-gradient(180deg,_#f8fdff,_#e8f6ff)] p-8 shadow-2xl shadow-cyan-500/20 dark:border-cyan-300/20 dark:bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.16),_transparent_35%),linear-gradient(180deg,_rgba(15,23,42,0.98),_rgba(2,6,23,0.98))] dark:shadow-cyan-950/40">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-300 via-emerald-300 to-amber-300" />

            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-700 dark:text-cyan-200/90">
                    Quiniela registrada
                </p>
                <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                    Tu quiniela ya quedo lista
                </h2>
                <p class="mt-3 text-sm leading-6 text-slate-600 sm:text-base dark:text-slate-300">
                    Registro <span class="font-semibold text-slate-700 dark:text-slate-300">{{ formatRegistrationNumber(poolEntry.registrationNumber) }}</span>
                    generado con exito para {{ poolEntry.tournamentName }}.
                </p>
            </div>

            <div class="mt-8 grid gap-4 rounded-3xl border border-cyan-300/35 bg-white/70 p-5 sm:grid-cols-3 dark:border-white/10 dark:bg-white/5">
                <div class="rounded-2xl border border-cyan-200/60 bg-white/90 p-4 text-center dark:border-white/10 dark:bg-slate-950/60">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Numero unico</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-slate-300">{{ formatRegistrationNumber(poolEntry.registrationNumber) }}</p>
                </div>
                <div class="rounded-2xl border border-cyan-200/60 bg-white/90 p-4 text-center dark:border-white/10 dark:bg-slate-950/60">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Estado</p>
                    <p class="mt-2 text-2xl font-black text-emerald-500 dark:text-emerald-300">{{ statusLabel(poolEntry.status) }}</p>
                </div>
                <div class="rounded-2xl border border-cyan-200/60 bg-white/90 p-4 text-center dark:border-white/10 dark:bg-slate-950/60">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Completado</p>
                    <p class="mt-2 text-2xl font-black text-cyan-600 dark:text-cyan-300">{{ poolEntry.completionPercent }}%</p>
                </div>
            </div>

            <div class="mt-6 rounded-3xl border border-amber-400/45 bg-amber-50/75 p-5 text-center dark:border-amber-300/20 dark:bg-amber-300/10">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-200/80">Tu campeon pronosticado</p>
                <p class="mt-2 text-2xl font-black uppercase tracking-[0.14em] text-slate-900 dark:text-white">
                    {{ poolEntry.predictedChampionName || 'Por definirse en la final de tu quiniela' }}
                </p>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    Guarda este numero de registro para identificar esta quiniela entre todas tus participaciones.
                </p>
            </div>

            <div class="mt-8 flex justify-center">
                <Link
                    :href="route('pools.index')"
                    class="inline-flex items-center justify-center rounded-2xl border border-cyan-400/45 bg-cyan-500/15 px-6 py-3 text-sm font-semibold text-cyan-800 transition hover:bg-cyan-500/25 dark:border-cyan-300/20 dark:bg-cyan-400/10 dark:text-cyan-100 dark:hover:bg-cyan-400/20"
                >
                    Continuar
                </Link>
            </div>
        </div>

        <canvas
            ref="frontConfettiCanvas"
            class="pointer-events-none fixed inset-0 z-[73] h-full w-full"
            aria-hidden="true"
        />
    </div>
</template>
