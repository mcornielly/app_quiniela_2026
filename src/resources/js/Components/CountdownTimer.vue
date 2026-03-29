<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    targetDate: {
        type: String,
        default: '2026-06-11T00:00:00'
    },
    compact: {
        type: Boolean,
        default: false
    }
})

const countdown = ref({ days: '00', hours: '00', minutes: '00', seconds: '00' })
let countdownInterval = null

const updateCountdown = () => {
    const targetDate = new Date(props.targetDate).getTime()
    const now = new Date().getTime()
    const distance = targetDate - now

    if (distance > 0) {
        countdown.value = {
            days:    String(Math.floor(distance / (1000 * 60 * 60 * 24))).padStart(2, '0'),
            hours:   String(Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0'),
            minutes: String(Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0'),
            seconds: String(Math.floor((distance % (1000 * 60)) / 1000)).padStart(2, '0')
        }
    } else {
        countdown.value = { days: '00', hours: '00', minutes: '00', seconds: '00' }
    }
}

onMounted(() => {
    updateCountdown()
    countdownInterval = setInterval(updateCountdown, 1000)
})

onUnmounted(() => {
    if (countdownInterval) clearInterval(countdownInterval)
})
</script>

<template>
    <div
        class="countdown-widget glass-panel border border-white/10 rounded-2xl shadow-[0_0_30px_rgba(67,97,238,0.15)] flex flex-row items-center gap-3 sm:gap-4 overflow-hidden"
        :class="compact ? 'p-1.5 px-3' : 'p-2 px-4 sm:p-3 sm:pr-5'"
    >
        <!-- Badge Mundial 2026 + Faltan -->
        <div class="flex items-center gap-2 sm:gap-3 border-r border-white/10 pr-2 sm:pr-4">
            <div class="inline-flex items-center gap-1.5 sm:gap-2 px-2 sm:px-3 py-1 sm:py-1.5 rounded-full bg-neon-blue/10 border border-neon-blue/30 whitespace-nowrap">
                <span class="w-1.5 sm:w-2 h-1.5 sm:h-2 rounded-full bg-neon-blue animate-pulse"></span>
                <span class="text-[10px] sm:text-xs font-bold text-neon-blue tracking-widest uppercase">Mundial 2026</span>
            </div>
            <span class="text-[10px] sm:text-xs uppercase tracking-widest font-bold text-white hidden md:block mt-0.5">Faltan:</span>
        </div>

        <!-- Dígitos -->
        <div class="flex gap-2 sm:gap-3 items-center">
            <div class="countdown-unit" :class="compact ? 'compact' : ''">
                <span class="unit-number">{{ countdown.days }}</span>
                <span class="unit-label">Días</span>
            </div>
            <span class="separator">:</span>
            <div class="countdown-unit" :class="compact ? 'compact' : ''">
                <span class="unit-number">{{ countdown.hours }}</span>
                <span class="unit-label">Hrs</span>
            </div>
            <span class="separator">:</span>
            <div class="countdown-unit" :class="compact ? 'compact' : ''">
                <span class="unit-number">{{ countdown.minutes }}</span>
                <span class="unit-label">Min</span>
            </div>
            <span class="separator">:</span>
            <div class="countdown-unit" :class="compact ? 'compact' : ''">
                <span class="unit-number">{{ countdown.seconds }}</span>
                <span class="unit-label">Seg</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.countdown-unit {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.4);
    border-radius: 8px;
    padding: 8px;
    min-width: 48px;
    border: 1px solid rgba(255,255,255,0.06);
}

.countdown-unit.compact {
    padding: 5px 6px;
    min-width: 40px;
}

.unit-number {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.25rem;
    font-weight: 900;
    color: #00D4FF;
    line-height: 1;
    letter-spacing: 0.02em;
}

.unit-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(255,255,255,0.45);
    margin-top: 3px;
}

.separator {
    font-size: 1.1rem;
    font-weight: 700;
    color: rgba(255,255,255,0.3);
    margin-bottom: 12px;
}
</style>
