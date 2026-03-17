<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import confetti from 'canvas-confetti'

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
let backConfettiInstance = null
let frontConfettiInstance = null

const launchConfetti = () => {
    if (!backConfettiCanvas.value || !frontConfettiCanvas.value) {
        return
    }

    backConfettiInstance = confetti.create(backConfettiCanvas.value, {
        resize: true,
        useWorker: true,
    })

    frontConfettiInstance = confetti.create(frontConfettiCanvas.value, {
        resize: true,
        useWorker: true,
    })

    const backBursts = [
        { particleCount: 120, spread: 72, startVelocity: 50, origin: { x: 0.5, y: 0.2 } },
        { particleCount: 70, spread: 58, startVelocity: 42, origin: { x: 0.18, y: 0.15 } },
        { particleCount: 70, spread: 58, startVelocity: 42, origin: { x: 0.82, y: 0.15 } },
        { particleCount: 48, spread: 110, startVelocity: 34, decay: 0.92, scalar: 0.9, origin: { x: 0.5, y: 0.28 } },
    ]

    const frontBursts = [
        { particleCount: 42, spread: 66, startVelocity: 38, scalar: 1.05, origin: { x: 0.32, y: 0.24 } },
        { particleCount: 42, spread: 66, startVelocity: 38, scalar: 1.05, origin: { x: 0.68, y: 0.24 } },
        { particleCount: 28, spread: 92, startVelocity: 28, decay: 0.9, scalar: 1.1, origin: { x: 0.5, y: 0.18 } },
    ]

    backBursts.forEach((burst, index) => {
        window.setTimeout(() => {
            backConfettiInstance?.({
                ...burst,
                ticks: 260,
                gravity: 1.02,
                colors: ['#67e8f9', '#6ee7b7', '#fde047', '#7dd3fc', '#f9a8d4', '#c4b5fd'],
            })
        }, index * 180)
    })

    frontBursts.forEach((burst, index) => {
        window.setTimeout(() => {
            frontConfettiInstance?.({
                ...burst,
                ticks: 220,
                gravity: 0.98,
                drift: index === 1 ? -0.15 : 0.15,
                colors: ['#ffffff', '#67e8f9', '#fde047', '#f9a8d4'],
            })
        }, 120 + (index * 220))
    })
}

onMounted(() => {
    launchConfetti()
})

onBeforeUnmount(() => {
    confetti.reset()
})
</script>

<template>
    <div class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/75 px-4 backdrop-blur-sm">
        <canvas
            ref="backConfettiCanvas"
            class="pointer-events-none fixed inset-0 h-full w-full"
            aria-hidden="true"
        />

        <div class="relative z-[72] w-full max-w-2xl overflow-hidden rounded-[2rem] border border-cyan-300/20 bg-[radial-gradient(circle_at_top,_rgba(34,211,238,0.16),_transparent_35%),linear-gradient(180deg,_rgba(15,23,42,0.98),_rgba(2,6,23,0.98))] p-8 shadow-2xl shadow-cyan-950/40">
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-300 via-emerald-300 to-amber-300" />

            <div class="text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-200/90">
                    Quiniela registrada
                </p>
                <h2 class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl">
                    Tu quiniela ya quedo lista
                </h2>
                <p class="mt-3 text-sm leading-6 text-slate-300 sm:text-base">
                    Registro <span class="font-bold text-white">#{{ poolEntry.registrationNumber }}</span>
                    generado con exito para {{ poolEntry.tournamentName }}.
                </p>
            </div>

            <div class="mt-8 grid gap-4 rounded-3xl border border-white/10 bg-white/5 p-5 sm:grid-cols-3">
                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 text-center">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Numero unico</p>
                    <p class="mt-2 text-2xl font-black text-white">#{{ poolEntry.registrationNumber }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 text-center">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Estado</p>
                    <p class="mt-2 text-2xl font-black text-emerald-300">{{ statusLabel(poolEntry.status) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 text-center">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Completado</p>
                    <p class="mt-2 text-2xl font-black text-cyan-300">{{ poolEntry.completionPercent }}%</p>
                </div>
            </div>

            <div class="mt-6 rounded-3xl border border-amber-300/20 bg-amber-300/10 p-5 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-200/80">Tu campeon pronosticado</p>
                <p class="mt-2 text-2xl font-black uppercase tracking-[0.14em] text-white">
                    {{ poolEntry.predictedChampionName || 'Por definirse en la final de tu quiniela' }}
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

        <canvas
            ref="frontConfettiCanvas"
            class="pointer-events-none fixed inset-0 z-[73] h-full w-full"
            aria-hidden="true"
        />
    </div>
</template>
