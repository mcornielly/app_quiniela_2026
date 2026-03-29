<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const particles = ref([]);
const generateParticles = () => {
    for (let i = 0; i < 25; i++) {
        particles.value.push({
            id: i,
            left: Math.random() * 100 + '%',
            top: Math.random() * 100 + '%',
            size: Math.random() * 3 + 1 + 'px',
            animationDuration: Math.random() * 12 + 8 + 's',
            animationDelay: Math.random() * 5 + 's',
            opacity: Math.random() * 0.4 + 0.2
        });
    }
};

onMounted(() => {
    generateParticles();
});
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[#0b0714] selection:bg-neon-blue selection:text-white relative overflow-hidden font-body">
        <!-- Floating Neon Particles -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden z-0">
            <div
                v-for="p in particles"
                :key="p.id"
                class="particle"
                :style="{
                    left: p.left,
                    top: p.top,
                    width: p.size,
                    height: p.size,
                    animationDuration: p.animationDuration,
                    animationDelay: p.animationDelay,
                    opacity: p.opacity
                }"
            ></div>
        </div>

        <!-- Background Elements -->
        <div class="absolute inset-0 bg-pattern opacity-5 pointer-events-none"></div>
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-neon-blue/10 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-neon-violet/10 rounded-full blur-[120px] pointer-events-none"></div>

        <div class="relative z-10 flex flex-col items-center w-full px-4 py-4">
            <Link href="/" class="group flex flex-col items-center mb-6">
                <div class="logo-ball-container w-16 h-16 rounded-full flex items-center justify-center relative mb-1 transition-transform duration-500 group-hover:scale-110">
                    <div class="absolute inset-0 rounded-full animate-pulse-slow" style="background: radial-gradient(circle, rgba(0,212,255,0.4) 0%, rgba(0,212,255,0) 70%); filter: blur(8px);"></div>
                    <img
                        src="/balon.png"
                        alt="Logo Quiniela"
                        class="w-12 h-12 object-contain relative z-10 transition-all duration-300"
                        style="filter: drop-shadow(0 0 12px rgba(0,212,255,0.8));"
                    />
                </div>
                <span class="font-heading font-black text-4xl tracking-tighter text-white">
                    QUINIELA<span class="text-neon-blue">PRO</span>
                </span>
            </Link>

            <div class="w-full sm:max-w-md px-8 py-8 glass-panel rounded-[2rem] border border-white/10 shadow-2xl relative overflow-hidden group">
                <!-- Inner glow effect -->
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-neon-blue/5 rounded-full blur-3xl pointer-events-none group-hover:bg-neon-blue/10 transition-colors duration-700"></div>
                
                <slot />
            </div>

            <div class="mt-8 text-center">
                <p class="text-gray-500 text-xs font-medium uppercase tracking-widest">
                    Mundial 2026 <span class="text-neon-blue mx-1">•</span> Predicciones Elite
                </p>
            </div>
        </div>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@800;900&display=swap');

:root {
    --color-neon-blue: #00D4FF;
    --color-neon-violet: #4361EE;
}

.font-body { font-family: 'Inter', sans-serif; }
.font-heading { font-family: 'Montserrat', sans-serif; }

.bg-pattern {
    background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
    background-size: 40px 40px;
}

.glass-panel {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

/* Particles */
.particle {
    position: absolute;
    background: var(--color-neon-blue);
    border-radius: 50%;
    box-shadow: 0 0 10px var(--color-neon-blue);
    animation: float_auth linear infinite;
}

@keyframes float_auth {
    0% { transform: translateY(0) rotate(0deg); }
    100% { transform: translateY(-100vh) rotate(360deg); }
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.3; transform: scale(0.95); }
    50% { opacity: 0.6; transform: scale(1.05); }
}

.animate-pulse-slow {
    animation: pulse-slow 4s ease-in-out infinite;
}

.text-neon-blue { color: var(--color-neon-blue); }
</style>

