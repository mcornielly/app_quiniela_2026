<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean
})

const navigation = [
    { name: 'Inicio', href: '#hero' },
    { name: 'Resultados', href: '#results' },
    { name: 'Pronósticos', href: '#predict' },
    { name: 'Posiciones', href: '#standings' },
    { name: 'Contacto', href: '#footer' }
]

// Dynamic Hero Texts based on section
const activeSection = ref('hero')
const heroTexts = {
    hero: {
        title: "LA GLORIA",
        highlight: "TE ESPERA",
        subtitle: "Vive la emoción del Mundial 2026. Predice, compite y gana."
    },
    results: {
        title: "ÚLTIMOS",
        highlight: "RESULTADOS",
        subtitle: "Repasa los marcadores recientes del torneo."
    },
    predict: {
        title: "HAZ TU",
        highlight: "PRONÓSTICO",
        subtitle: "Acierta marcadores y suma puntos a tu ranking."
    },
    live: {
        title: "PARTIDOS",
        highlight: "EN VIVO",
        subtitle: "Sigue la acción en tiempo real."
    },
    bests: {
        title: "LOS",
        highlight: "MEJORES",
        subtitle: "Estrellas destacadas del torneo."
    },
    standings: {
        title: "TABLA DE",
        highlight: "POSICIONES",
        subtitle: "Descubre quién lidera la quiniela mundial."
    },
    footer: {
        title: "ÚNETE",
        highlight: "AHORA",
        subtitle: "Sé parte de la comunidad pronosticadora."
    },
    tech: {
        title: "NUESTRA",
        highlight: "TECNOLOGÍA",
        subtitle: "Desarrollado con las mejores herramientas."
    }
}

const currentHeroText = computed(() => heroTexts[activeSection.value] || heroTexts['hero'])

// Countdown
const countdown = ref({
    days: '00',
    hours: '00',
    minutes: '00',
    seconds: '00'
})

let countdownInterval = null

const updateCountdown = () => {
    const targetDate = new Date('2026-06-11T00:00:00').getTime()
    const now = new Date().getTime()
    const distance = targetDate - now

    if (distance > 0) {
        countdown.value = {
            days: String(Math.floor(distance / (1000 * 60 * 60 * 24))).padStart(2, '0'),
            hours: String(Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0'),
            minutes: String(Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0'),
            seconds: String(Math.floor((distance % (1000 * 60)) / 1000)).padStart(2, '0')
        }
    }
}

// Particles
const particles = ref([])

const generateParticles = () => {
    for (let i = 0; i < 40; i++) {
        particles.value.push({
            id: i,
            left: Math.random() * 100 + '%',
            top: Math.random() * 100 + '%',
            size: Math.random() * 4 + 1 + 'px',
            animationDuration: Math.random() * 15 + 10 + 's',
            animationDelay: Math.random() * 5 + 's',
            opacity: Math.random() * 0.5 + 0.3
        })
    }
}

// Points Counter Animation
const pointsWon = ref(0)
const pointsLost = ref(0)

const animatePoints = () => {
    let currentWon = 0
    let currentLost = 0
    const interval = setInterval(() => {
        if (currentWon < 12) pointsWon.value = ++currentWon
        if (currentLost < 4) pointsLost.value = ++currentLost
        if (currentWon >= 12 && currentLost >= 4) clearInterval(interval)
    }, 100)
}

onMounted(() => {
    updateCountdown()
    countdownInterval = setInterval(updateCountdown, 1000)
    generateParticles()

    // Intersection Observer for Scroll Reveal & Active Section
    const scrollObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show-reveal')
                if (entry.target.id === 'predict') {
                    animatePoints()
                }
            }
        })
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' })

    document.querySelectorAll('.reveal').forEach(el => scrollObserver.observe(el))

    // Section Observer for Hero Text
    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.intersectionRatio > 0.4) {
                activeSection.value = entry.target.id
            }
        })
    }, { threshold: [0.4, 0.6] })

    document.querySelectorAll('section[id], footer[id]').forEach(el => sectionObserver.observe(el))
})

onUnmounted(() => {
    if (countdownInterval) clearInterval(countdownInterval)
})

const results = [
    { team1: 'Bra', team2: 'Esp', s1: 3, s2: 1 },
    { team1: 'Arg', team2: 'Fra', s1: 2, s2: 2 },
    { team1: 'Ale', team2: 'Ita', s1: 1, s2: 0 }
]

const liveMatches = [
    { team1: 'México', team2: 'USA', s1: 2, s2: 1, min: '76\'' },
    { team1: 'Inglaterra', team2: 'Holanda', s1: 3, s2: 2, min: '82\'' }
]

const standings = [
    { pos: 1, name: 'Real Madrid', points: 156, trend: 'up' },
    { pos: 2, name: 'Barcelona Elite', points: 142, trend: 'same' },
    { pos: 3, name: 'Bayern Fans', points: 138, trend: 'up' },
    { pos: 4, name: 'Boca Juniors AR', points: 130, trend: 'down' },
    { pos: 5, name: 'River Plate Club', points: 125, trend: 'up' }
]

const techStack = [
    { name: 'PHP', icon: 'php' },
    { name: 'Laravel', icon: 'laravel' },
    { name: 'Vue.js', icon: 'vue' },
    { name: 'Flowbite', icon: 'flowbite' },
    { name: 'Inertia', icon: 'inertia' }
]

// Toggle Mobile Menu
const isMobileMenuOpen = ref(false)
const toggleMenu = () => isMobileMenuOpen.value = !isMobileMenuOpen.value
</script>

<template>
    <Head title="Mundial 2026 Quiniela | El Futuro del Fútbol" />

    <div class="app-theme text-white font-body overflow-x-hidden selection:bg-neon-blue selection:text-white">
        <!-- PARTICLES LAYER -->
        <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
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

        <!-- NAVBAR -->
        <header class="fixed top-0 w-full z-50 glass-nav border-b border-light-white transition-all duration-300">
            <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
                <a href="#hero" class="flex items-center gap-3 group relative">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-neon-blue to-neon-violet shadow-neon flex items-center justify-center">
                        <span class="text-white font-bold text-xs">26</span>
                    </div>
                    <span class="font-heading font-black text-xl tracking-wider text-white group-hover:text-neon-blue transition-colors">
                        QUINIELA<span class="text-neon-blue">PRO</span>
                    </span>
                </a>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex gap-8 items-center bg-black/20 px-6 py-2 rounded-full border border-white/5 backdrop-blur-md">
                    <a
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        class="font-medium text-sm text-gray-300 hover:text-white hover:text-shadow-neon transition-all"
                    >
                        {{ item.name }}
                    </a>
                </nav>

                <div class="hidden md:flex items-center gap-4">
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="text-sm font-semibold text-gray-300 hover:text-white transition-colors"
                    >
                        Iniciar sesión
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="btn-neon text-sm py-2 px-5"
                    >
                        Regístrate
                    </Link>
                </div>

                <!-- Mobile Menu Toggle -->
                <button @click="toggleMenu" class="md:hidden text-white focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>

            <!-- Mobile Nav -->
            <div v-if="isMobileMenuOpen" class="md:hidden glass-panel border-t border-white/10 p-4 absolute w-full top-20 left-0 flex flex-col gap-4">
                <a
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    @click="isMobileMenuOpen = false"
                    class="font-medium text-base text-gray-300 hover:text-neon-blue transition-colors block p-2"
                >
                    {{ item.name }}
                </a>
                <div class="h-px bg-white/10 my-2"></div>
                <Link :href="route('login')" class="block p-2 text-gray-300">Iniciar sesión</Link>
                <Link :href="route('register')" class="btn-neon text-center mt-2 p-2">Regístrate</Link>
            </div>
        </header>

        <!-- HERO BANNER -->
        <section id="hero" class="relative min-h-screen flex items-center pt-20 z-10 overflow-hidden isolate">
            <!-- Hero Background Image & Overlays -->
            <div class="absolute inset-0 z-[-2]">
                <img src="/hero_bg_football.png" alt="Hero Background" class="w-full h-full object-cover object-center scale-105 animate-slow-zoom" />
            </div>

            <!-- Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-r from-bg-base/95 via-bg-base/80 to-transparent z-[-1]"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-bg-base via-transparent to-transparent z-[-1]"></div>
            <div class="absolute inset-0 bg-pattern opacity-10 mix-blend-overlay z-[-1]"></div>

            <div class="max-w-7xl mx-auto px-6 w-full relative">
                <div class="max-w-2xl reveal slide-right delay-100">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full glass-panel border border-neon-blue/30 mb-6 group">
                        <span class="w-2 h-2 rounded-full bg-neon-blue animate-pulse"></span>
                        <span class="text-xs font-semibold text-neon-blue tracking-widest uppercase">Mundial 2026</span>
                    </div>

                    <h1 class="text-5xl sm:text-7xl font-black font-heading leading-[1.1] tracking-tight mb-6 transition-all duration-500 min-h-[160px]">
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-400 block">{{ currentHeroText.title }}</span>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-neon-blue to-neon-violet text-shadow-glow block">
                            {{ currentHeroText.highlight }}
                        </span>
                    </h1>

                    <p class="text-lg md:text-xl text-gray-300 mb-10 font-light max-w-lg transition-all duration-500">
                        {{ currentHeroText.subtitle }}
                    </p>

                    <div class="flex flex-wrap gap-4">
                        <a href="#predict" class="btn-neon text-lg py-3 px-8 group">
                            <span class="group-hover:scale-105 transition-transform inline-block">Comienza tu quiniela</span>
                        </a>

                        <a href="#live" class="glass-btn text-lg py-3 px-8 flex items-center gap-2">
                            <span>Ver en vivo</span>
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Overlay Decorative Elements -->
            <div class="absolute bottom-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-neon-violet to-transparent opacity-50"></div>
        </section>

        <!-- COUNTDOWN STRIP -->
        <div class="relative z-20 max-w-5xl mx-auto px-6 -mt-16 sm:-mt-24 mb-20 reveal slide-up">
            <div class="glass-panel border border-white/10 rounded-2xl p-6 sm:p-8 shadow-[0_0_50px_rgba(67,97,238,0.15)] backdrop-blur-xl relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-neon-blue to-neon-violet"></div>
                <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                    <div class="text-center md:text-left">
                        <h3 class="font-heading font-bold text-2xl text-white mb-2">El Mundial comienza en:</h3>
                        <p class="text-gray-400 text-sm">Norteamérica 2026 - Prepárate para la historia</p>
                    </div>

                    <div class="flex gap-4 sm:gap-6">
                        <div class="countdown-box">
                            <span class="number">{{ countdown.days }}</span>
                            <span class="label">Días</span>
                        </div>
                        <div class="text-3xl font-bold text-gray-500 self-start mt-2">:</div>
                        <div class="countdown-box">
                            <span class="number">{{ countdown.hours }}</span>
                            <span class="label">Horas</span>
                        </div>
                        <div class="text-3xl font-bold text-gray-500 self-start mt-2">:</div>
                        <div class="countdown-box">
                            <span class="number">{{ countdown.minutes }}</span>
                            <span class="label">Min</span>
                        </div>
                        <div class="text-3xl font-bold text-gray-500 self-start mt-2">:</div>
                        <div class="countdown-box">
                            <span class="number">{{ countdown.seconds }}</span>
                            <span class="label">Seg</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN CONTENT WRAPPER -->
        <main class="relative z-10">
            <!-- LATEST RESULTS -->
            <section id="results" class="py-24 px-6">
                <div class="max-w-7xl mx-auto">
                    <div class="text-center mb-16 reveal slide-up">
                        <h2 class="section-title">Últimos <span class="text-neon-blue">Resultados</span></h2>
                        <p class="text-gray-400 mt-4 max-w-2xl mx-auto">Los marcadores más recientes de los encuentros y amistosos previos.</p>
                    </div>

                    <div class="grid md:grid-cols-3 gap-6">
                        <div
                            v-for="(match, i) in results"
                            :key="i"
                            class="glass-card result-card reveal slide-up"
                            :style="{ animationDelay: (i * 100) + 'ms' }"
                        >
                            <div class="flex justify-between items-center mb-4 text-sm text-gray-400 font-medium">
                                <span>Amistoso Intl.</span>
                                <span class="bg-white/5 px-2 py-1 rounded-md">Finalizado</span>
                            </div>

                            <div class="flex items-center justify-between mt-6">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-12 h-12 rounded-full bg-gray-800 flex items-center justify-center font-bold font-heading shadow-inner">
                                        {{ match.team1.substring(0,3).toUpperCase() }}
                                    </div>
                                    <span class="font-semibold">{{ match.team1 }}</span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="text-3xl font-black text-white">{{ match.s1 }}</span>
                                    <span class="text-gray-500">-</span>
                                    <span class="text-3xl font-black text-white">{{ match.s2 }}</span>
                                </div>

                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-12 h-12 rounded-full bg-gray-800 flex items-center justify-center font-bold font-heading shadow-inner">
                                        {{ match.team2.substring(0,3).toUpperCase() }}
                                    </div>
                                    <span class="font-semibold">{{ match.team2 }}</span>
                                </div>
                            </div>

                            <div class="mt-6 text-center">
                                <span class="text-xs uppercase tracking-wider text-neon-blue font-semibold">Resultado</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- PREDICT MODULE -->
            <section id="predict" class="py-24 px-6 relative">
                <div class="absolute inset-0 bg-gradient-to-b from-transparent via-neon-violet/5 to-transparent skew-y-3 transform -z-10"></div>

                <div class="max-w-6xl mx-auto">
                    <div class="grid lg:grid-cols-2 gap-16 items-center">
                        <div class="reveal slide-right">
                            <h2 class="section-title text-left mb-6">Haz tu <span class="text-neon-violet">Pronóstico</span></h2>
                            <p class="text-gray-300 mb-6 text-lg">
                                Participa en el Duelo de Predicción. Acierta los resultados exactos o al ganador y acumula puntos. El resultado de tu ranking es la suma de los puntos ganados y perdidos en las últimas 5 partidas.
                            </p>

                            <div class="glass-panel p-6 rounded-xl border border-neon-violet/20 mb-8 max-w-sm relative overflow-hidden group">
                                <div class="absolute inset-0 bg-neon-violet/5 group-hover:bg-neon-violet/10 transition-colors"></div>
                                <div class="flex justify-between items-center relative z-10">
                                    <div class="text-center flex-1 border-r border-white/10">
                                        <p class="text-sm text-gray-400 mb-1">Puntos Ganados</p>
                                        <p class="text-4xl font-black text-neon-blue">{{ pointsWon }}</p>
                                    </div>
                                    <div class="text-center flex-1">
                                        <p class="text-sm text-gray-400 mb-1">Puntos Perdidos</p>
                                        <p class="text-4xl font-black text-red-400">{{ pointsLost }}</p>
                                    </div>
                                </div>
                            </div>

                            <a href="#" class="btn-neon inline-flex px-8 py-4 text-lg">Empezar a pronosticar</a>
                        </div>

                        <div class="relative reveal slide-left">
                            <!-- Abstract 3D/Glass visual representation -->
                            <div class="aspect-square relative flex items-center justify-center">
                                <div class="absolute inset-0 bg-gradient-to-tr from-neon-blue/20 to-neon-violet/20 rounded-full blur-3xl animate-pulse"></div>
                                <div class="glass-panel w-64 h-80 rounded-2xl border border-white/20 absolute transform -rotate-12 shadow-2xl backdrop-blur-2xl flex flex-col items-center justify-center z-10 transition-transform duration-500 hover:-translate-y-4 hover:rotate-[-5deg]">
                                    <div class="w-16 h-1 rounded-full bg-white/20 mb-8"></div>
                                    <div class="text-center w-full px-6">
                                        <div class="flex justify-between items-center w-full mb-4">
                                            <div class="w-10 h-10 rounded-full bg-blue-600/50"></div>
                                            <div class="text-2xl font-bold">2 - 0</div>
                                            <div class="w-10 h-10 rounded-full bg-red-600/50"></div>
                                        </div>
                                        <div class="h-2 w-full bg-white/10 rounded-full mb-2"></div>
                                        <div class="h-2 w-3/4 bg-white/10 rounded-full mx-auto"></div>
                                    </div>
                                    <div class="mt-8 px-6 py-2 rounded-full bg-neon-blue/20 text-neon-blue text-sm border border-neon-blue/30 font-bold">+3 Puntos</div>
                                </div>
                                <div class="glass-panel w-56 h-72 rounded-2xl border border-white/10 absolute transform rotate-12 translate-x-12 translate-y-12 shadow-xl opacity-60 z-0"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- LIVE MATCHES -->
            <section id="live" class="py-24 px-6">
                <div class="max-w-5xl mx-auto">
                    <div class="text-center mb-16 reveal slide-up flex flex-col items-center">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-red-500/10 border border-red-500/30 text-red-400 font-bold text-sm tracking-widest mb-4">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-ping"></span> EN VIVO
                        </div>
                        <h2 class="section-title">Partidos <span class="text-white">en Directo</span></h2>
                    </div>

                    <div class="glass-panel overflow-hidden border border-white/10 rounded-2xl reveal slide-up">
                        <table class="w-full text-left border-collapse min-w-[500px]">
                            <thead>
                                <tr class="bg-white/5 border-b border-white/10 text-xs uppercase tracking-wider text-gray-400">
                                    <th class="p-4 font-semibold">Partido</th>
                                    <th class="p-4 font-semibold text-center">Tiempo</th>
                                    <th class="p-4 font-semibold text-center">Goles</th>
                                    <th class="p-4 font-semibold text-right">Detalles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(match, i) in liveMatches" :key="i" class="border-b border-white/5 hover:bg-white/5 transition-colors group">
                                    <td class="p-4">
                                        <div class="flex items-center gap-4">
                                            <span class="font-bold flex-1 text-right">{{ match.team1 }}</span>
                                            <div class="flex items-center gap-2 bg-black/40 px-3 py-1.5 rounded-lg border border-white/5 group-hover:border-neon-blue/40 transition-colors">
                                                <span class="text-xl font-bold">{{ match.s1 }}</span>
                                                <span class="text-gray-500">-</span>
                                                <span class="text-xl font-bold">{{ match.s2 }}</span>
                                            </div>
                                            <span class="font-bold flex-1">{{ match.team2 }}</span>
                                        </div>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="text-red-400 font-bold animate-pulse">{{ match.min }}</span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="text-gray-300 font-medium">{{ match.s1 + match.s2 }} Goles</span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <button class="text-neon-blue hover:text-white transition-colors text-sm font-semibold">Ver Resumen</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- BEST OF TOURNAMENT -->
            <section id="bests" class="py-24 px-6">
                <div class="max-w-6xl mx-auto">
                    <div class="text-center mb-16 reveal slide-up">
                        <h2 class="section-title">Los Mejores del <span class="bg-clip-text text-transparent bg-gradient-to-r from-neon-blue to-neon-violet">Torneo</span></h2>
                    </div>

                    <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                        <!-- MVP Card -->
                        <div class="glass-panel p-1 rounded-2xl bg-gradient-to-br from-neon-blue/40 to-transparent hover:from-neon-blue/60 transition-all duration-300 reveal slide-left">
                            <div class="bg-[#100b1a] p-8 rounded-[14px] h-full flex items-center gap-6 relative overflow-hidden group">
                                <div class="absolute -right-10 -top-10 w-40 h-40 bg-neon-blue/20 blur-3xl rounded-full group-hover:bg-neon-blue/30 transition-colors"></div>
                                <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-gray-800 to-gray-600 border-2 border-neon-blue flex-shrink-0 flex items-center justify-center font-black text-4xl shadow-[0_0_20px_rgba(0,212,255,0.4)]">
                                    M
                                </div>
                                <div class="relative z-10">
                                    <span class="text-neon-blue text-sm uppercase tracking-wider font-bold mb-1 block">Mejor Jugador</span>
                                    <h3 class="text-3xl font-heading font-black mb-2">Messi</h3>
                                    <p class="text-gray-400 text-sm">Argentina • 4 Goles • 3 Asist.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Best Defender Card -->
                        <div class="glass-panel p-1 rounded-2xl bg-gradient-to-br from-neon-violet/40 to-transparent hover:from-neon-violet/60 transition-all duration-300 reveal slide-right delay-100">
                            <div class="bg-[#100b1a] p-8 rounded-[14px] h-full flex items-center gap-6 relative overflow-hidden group">
                                <div class="absolute -right-10 -top-10 w-40 h-40 bg-neon-violet/20 blur-3xl rounded-full group-hover:bg-neon-violet/30 transition-colors"></div>
                                <div class="w-24 h-24 rounded-full bg-gradient-to-tr from-gray-800 to-gray-600 border-2 border-neon-violet flex-shrink-0 flex items-center justify-center font-black text-4xl shadow-[0_0_20px_rgba(67,97,238,0.4)]">
                                    N
                                </div>
                                <div class="relative z-10">
                                    <span class="text-neon-violet text-sm uppercase tracking-wider font-bold mb-1 block">Mejor Defensa</span>
                                    <h3 class="text-3xl font-heading font-black mb-2">Neymar</h3>
                                    <p class="text-gray-400 text-sm">Brasil • 20 Recuperaciones</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- STANDINGS -->
            <section id="standings" class="py-24 px-6 relative">
                <div class="absolute inset-0 bg-pattern opacity-5 mix-blend-overlay z-0 pointer-events-none"></div>

                <div class="max-w-4xl mx-auto relative z-10">
                    <div class="flex justify-between items-end mb-10 reveal slide-up">
                        <h2 class="section-title mb-0">Tabla de <span class="text-white">Posiciones</span></h2>
                        <button class="text-sm font-semibold text-neon-blue hover:text-white transition-colors pb-2 border-b border-neon-blue/30 hover:border-white">Ver completa</button>
                    </div>

                    <div class="glass-panel rounded-2xl border border-white/10 overflow-hidden reveal slide-up">
                        <div class="p-6 bg-white/5 border-b border-white/10 flex gap-4">
                            <button class="px-4 py-2 rounded-lg bg-neon-blue text-black font-bold text-sm">Global</button>
                            <button class="px-4 py-2 rounded-lg text-gray-400 hover:text-white font-medium text-sm transition-colors">Amigos</button>
                        </div>

                        <table class="w-full text-left collapse-table">
                            <thead>
                                <tr class="text-xs uppercase tracking-wider text-gray-500 border-b border-white/5">
                                    <th class="py-4 px-6 font-semibold w-16 text-center">Pos</th>
                                    <th class="py-4 px-6 font-semibold">Jugador / Equipo</th>
                                    <th class="py-4 px-6 font-semibold text-right">Pts</th>
                                    <th class="py-4 px-6 font-semibold w-16 text-center">Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, i) in standings"
                                    :key="i"
                                    class="border-b border-white/5 hover:bg-white/5 transition-all group"
                                    :class="{'bg-[#4361EE]/10': i === 0}"
                                >
                                    <td class="py-4 px-6 text-center font-bold text-lg" :class="{'text-yellow-400': i === 0, 'text-gray-300': i > 0}">
                                        {{ row.pos }}
                                    </td>
                                    <td class="py-4 px-6 font-semibold group-hover:text-neon-blue transition-colors">
                                        {{ row.name }}
                                    </td>
                                    <td class="py-4 px-6 text-right font-black text-white">
                                        {{ row.points }}
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <svg v-if="row.trend === 'up'" class="w-5 h-5 text-green-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                        <svg v-if="row.trend === 'down'" class="w-5 h-5 text-red-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                        </svg>
                                        <svg v-if="row.trend === 'same'" class="w-5 h-5 text-gray-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>
                                        </svg>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- TECH STACK -->
            <section id="tech" class="py-20 px-6 border-t border-white/5 relative overflow-hidden">
                <div class="absolute right-0 top-1/2 -translate-y-1/2 w-96 h-96 bg-neon-blue/5 rounded-full blur-[100px] pointer-events-none"></div>

                <div class="max-w-5xl mx-auto text-center reveal slide-up">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-8">Desarrollado con</h3>

                    <div class="flex flex-wrap justify-center gap-8 md:gap-16 items-center opacity-70 grayscale hover:grayscale-0 transition-all duration-700">
                        <div v-for="tech in techStack" :key="tech.name" class="group relative cursor-help">
                            <!-- Icon Placeholder (Initials or SVG) -->
                            <div class="w-16 h-16 rounded-xl border border-white/10 flex items-center justify-center text-xl font-bold bg-white/5 group-hover:bg-white/10 group-hover:border-neon-blue/50 group-hover:text-neon-blue transition-all group-hover:-translate-y-2 group-hover:shadow-[0_10px_20px_rgba(0,212,255,0.2)]">
                                {{ tech.name.substring(0,2) }}
                            </div>
                            <!-- Tooltip -->
                            <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-black text-xs px-3 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap border border-white/10">
                                {{ tech.name }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- FOOTER -->
        <footer id="footer" class="relative pt-24 pb-10 px-6 border-t border-white/10 overflow-hidden">
            <!-- Footer geometric waves/lines -->
            <div class="absolute bottom-0 left-0 w-full h-full opacity-20 pointer-events-none z-0" style="background: radial-gradient(100% 100% at 50% 100%, #4361EE 0%, transparent 100%);"></div>
            <div class="absolute bottom-0 w-full flex justify-center opacity-50 z-0">
                <div class="w-full max-w-7xl h-px bg-gradient-to-r from-transparent via-white to-transparent"></div>
            </div>

            <div class="max-w-7xl mx-auto relative z-10 grid md:grid-cols-4 gap-12 mb-16">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-neon-blue to-neon-violet flex items-center justify-center">
                            <span class="text-white font-bold text-xs">26</span>
                        </div>
                        <span class="font-heading font-black text-xl tracking-wider text-white">
                            QUINIELA<span class="text-neon-blue">PRO</span>
                        </span>
                    </div>
                    <p class="text-gray-400 text-sm max-w-sm leading-relaxed mb-6">
                        La plataforma definitiva para vivir la pasión del Mundial 2026. Predice, compite y demuestra que eres el mejor estratega del fútbol mundial.
                    </p>
                    <div class="flex gap-4">
                        <!-- Social icons placeholders -->
                        <a href="#" class="w-10 h-10 rounded-full border border-white/10 flex items-center justify-center text-gray-400 hover:text-white hover:border-neon-blue hover:bg-neon-blue/10 transition-colors">X</a>
                        <a href="#" class="w-10 h-10 rounded-full border border-white/10 flex items-center justify-center text-gray-400 hover:text-white hover:border-neon-violet hover:bg-neon-violet/10 transition-colors">IG</a>
                        <a href="#" class="w-10 h-10 rounded-full border border-white/10 flex items-center justify-center text-gray-400 hover:text-white hover:border-neon-blue hover:bg-neon-blue/10 transition-colors">FB</a>
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-white mb-6 uppercase tracking-wider text-sm">Enlaces Rápidos</h4>
                    <ul class="space-y-3 text-sm text-gray-400">
                        <li><a href="#hero" class="hover:text-neon-blue transition-colors">Inicio</a></li>
                        <li><a href="#results" class="hover:text-neon-blue transition-colors">Resultados</a></li>
                        <li><a href="#predict" class="hover:text-neon-blue transition-colors">Pronósticos</a></li>
                        <li><a href="#standings" class="hover:text-neon-blue transition-colors">Ranking</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-white mb-6 uppercase tracking-wider text-sm">Legal & Ayuda</h4>
                    <ul class="space-y-3 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-neon-blue transition-colors">Términos y Condiciones</a></li>
                        <li><a href="#" class="hover:text-neon-blue transition-colors">Política de Privacidad</a></li>
                        <li><a href="#" class="hover:text-neon-blue transition-colors">Preguntas Frecuentes</a></li>
                        <li><a href="#" class="hover:text-neon-blue transition-colors">Contacto</a></li>
                    </ul>
                </div>
            </div>

            <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center pt-8 border-t border-white/10 text-xs text-gray-500 relative z-10">
                <p>© 2026 Quiniela Mundial. Todos los derechos reservados.</p>
                <p class="mt-2 md:mt-0">Diseñado con ❤️ para fanáticos del fútbol.</p>
            </div>
        </footer>
    </div>
</template>

<style>
/* CSS Reset + Google Fonts embedded inside standard CSS */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@700;800;900&display=swap');

:root {
    --color-dark: #0f0c1b;
    --color-purple: #2D1B3A;
    --color-violet: #1A0F2E;
    --color-neon-blue: #00D4FF;
    --color-neon-violet: #4361EE;
    --color-glass: rgba(15, 12, 27, 0.6);
    --color-glass-border: rgba(255, 255, 255, 0.08);
}

.font-body {
    font-family: 'Inter', sans-serif;
}

.font-heading {
    font-family: 'Montserrat', sans-serif;
}

.bg-brand-dark {
    background-color: var(--color-dark);
}

.bg-bg-base {
    background-color: #0b0714;
}

.app-theme {
    background-color: #0b0714;
    min-height: 100vh;
}

/* Utilities */
.text-neon-blue {
    color: var(--color-neon-blue);
}

.bg-neon-blue {
    background-color: var(--color-neon-blue);
}

.text-neon-violet {
    color: var(--color-neon-violet);
}

.border-neon-blue {
    border-color: var(--color-neon-blue);
}

.text-shadow-glow {
    text-shadow: 0 0 20px rgba(0, 212, 255, 0.4);
}

.text-shadow-neon {
    text-shadow: 0 0 10px rgba(0, 212, 255, 0.6);
}

.shadow-neon {
    box-shadow: 0 0 15px rgba(0, 212, 255, 0.5), inset 0 0 10px rgba(255, 255, 255, 0.2);
}

.bg-pattern {
    background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
    background-size: 50px 50px;
}

/* Glassmorphism */
.glass-nav {
    background: rgba(11, 7, 20, 0.7);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

.glass-panel {
    background: rgba(255, 255, 255, 0.02);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid var(--color-glass-border);
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
}

.glass-card {
    background: linear-gradient(145deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.01) 100%);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 16px;
    padding: 24px;
    transition: all 0.3s ease;
}

.glass-card:hover {
    transform: translateY(-5px);
    border-color: rgba(0, 212, 255, 0.3);
    box-shadow: 0 10px 40px rgba(0, 212, 255, 0.1);
    background: linear-gradient(145deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.02) 100%);
}

/* Buttons */
.btn-neon {
    background: transparent;
    border: 2px solid var(--color-neon-blue);
    color: white;
    font-weight: 600;
    border-radius: 8px;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    z-index: 1;
    text-align: center;
}

.btn-neon::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(0, 212, 255, 0.4), transparent);
    transition: all 0.4s ease;
    z-index: -1;
}

.btn-neon:hover {
    box-shadow: 0 0 20px rgba(0, 212, 255, 0.6);
    background-color: rgba(0, 212, 255, 0.1);
}

.btn-neon:hover::before {
    left: 100%;
}

.btn-neon:active {
    transform: scale(0.96);
}

.glass-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.glass-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
}

/* Typography styles */
.section-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 2.5rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: -0.02em;
    margin-bottom: 1.5rem;
}

@media (min-width: 768px) {
    .section-title {
        font-size: 3.5rem;
    }
}

/* Countdown specific */
.countdown-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 12px;
    padding: 12px 16px;
    min-width: 70px;
}

.countdown-box .number {
    font-family: 'Montserrat', sans-serif;
    font-size: 2rem;
    font-weight: 900;
    color: var(--color-neon-blue);
    text-shadow: 0 0 15px rgba(0, 212, 255, 0.5);
    line-height: 1;
    margin-bottom: 4px;
}

.countdown-box .label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #9ca3af;
    font-weight: 600;
}

/* Animations Scroll Reveal */
.reveal {
    opacity: 0;
    will-change: transform, opacity;
}

.reveal.slide-up {
    transform: translateY(50px);
}

.reveal.slide-left {
    transform: translateX(50px);
}

.reveal.slide-right {
    transform: translateX(-50px);
}

.reveal.show-reveal {
    opacity: 1;
    transform: translate(0, 0);
    transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

.delay-100 {
    transition-delay: 100ms;
}

.delay-200 {
    transition-delay: 200ms;
}

.delay-300 {
    transition-delay: 300ms;
}

/* Particles */
.particle {
    position: absolute;
    background: var(--color-neon-blue);
    border-radius: 50%;
    box-shadow: 0 0 10px var(--color-neon-blue);
    animation: float linear infinite;
}

@keyframes float {
    0% {
        transform: translateY(0) rotate(0deg);
    }
    100% {
        transform: translateY(-100vh) rotate(360deg);
    }
}

@keyframes slow-zoom {
    0% {
        transform: scale(1);
    }
    100% {
        transform: scale(1.1);
    }
}

.animate-slow-zoom {
    animation: slow-zoom 20s infinite alternate ease-in-out;
}
</style>
