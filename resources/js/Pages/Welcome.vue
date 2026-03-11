<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean
})

const navigation = [
    { name: 'Quiniela', href: '#quiniela' },
    { name: 'Resultados', href: '#results' },
    { name: 'En Vivo', href: '#live' },
    { name: 'Ranking', href: '#ranking' }
]

const slides = [
    {
        title: "EL FÚTBOL",
        highlight: "EN TU",
        end: "PANTALLA",
        text: "Predice resultados del Mundial 2026, sigue partidos en vivo y compite con tus amigos.",
        button: "Ver Partidos",
        section: "live"
    },
    {
        title: "CREA TU",
        highlight: "QUINIELA",
        end: "MUNDIAL",
        text: "Participa en la quiniela más emocionante del Mundial 2026.",
        button: "Comenzar",
        section: "quiniela"
    }
]

const currentSlide = ref(0)

onMounted(() => {

    setInterval(() => {
        currentSlide.value =
            (currentSlide.value + 1) % slides.length
    }, 5000)

    const observer = new IntersectionObserver((entries)=>{
        entries.forEach(entry=>{
            if(entry.isIntersecting){
                entry.target.classList.add('show')
            }
        })
    })

    document.querySelectorAll('.reveal')
        .forEach(el => observer.observe(el))
})

const scrollToSection = () => {
    const section = slides[currentSlide.value].section
    document.getElementById(section)
        ?.scrollIntoView({ behavior: "smooth" })
}
</script>

<template>

<Head title="WC2026 PRO" />

<div class="bg-gray-950 text-white">

<!-- NAVBAR -->
<header class="fixed top-0 left-0 w-full z-50 backdrop-blur-md bg-gray-900/60">
<nav class="flex items-center justify-between px-6 py-4 max-w-7xl mx-auto">

<div class="flex items-center gap-2">
<div class="w-6 h-6 bg-green-400 rounded-full"></div>
<span class="font-black text-lg">
WC2026<span class="text-green-400">PRO</span>
</span>
</div>

<div class="hidden lg:flex gap-8 text-sm">
<a v-for="item in navigation" :key="item.name" :href="item.href">
{{ item.name }}
</a>
</div>

<div class="flex gap-4">
<Link :href="route('login')" class="text-sm">Login</Link>
<Link :href="route('register')" class="bg-green-500 text-black px-4 py-2 rounded-md text-sm">
Registrarse
</Link>
</div>

</nav>
</header>

<!-- HERO -->
<section class="relative min-h-screen flex items-center pt-[80px]">

<div class="absolute inset-0">

<img
src="/hero_bg_color.jpeg"
class="w-full h-full object-cover"
/>

</div>

<div class="absolute inset-0 bg-gradient-to-r
from-gray-950 via-gray-950/70 to-transparent"></div>

<div class="relative z-10 max-w-7xl mx-auto px-6">

<h1 class="text-5xl md:text-7xl font-extrabold leading-tight reveal">

{{ slides[currentSlide].title }}

<br>

<span class="text-green-400">
{{ slides[currentSlide].highlight }}
</span>

<br>

{{ slides[currentSlide].end }}

</h1>

<p class="mt-6 text-gray-300 max-w-xl reveal">
{{ slides[currentSlide].text }}
</p>

<div class="mt-8 flex gap-4 reveal">

<Link
:href="route('register')"
class="bg-green-500 text-black px-6 py-3 font-bold rounded-md">
Crear Quiniela
</Link>

<button
@click="scrollToSection"
class="border border-gray-500 px-6 py-3 rounded-md">
{{ slides[currentSlide].button }}
</button>

</div>

</div>

</section>

<!-- QUINIELA -->
<section id="quiniela" class="py-32 px-6 section-bg">

<div class="max-w-6xl mx-auto text-center">

<h2 class="text-4xl font-bold mb-6 reveal">
Crea tu Quiniela
</h2>

<p class="text-gray-400 mb-12 reveal">
Predice los 104 partidos del Mundial 2026
</p>

<div class="grid md:grid-cols-3 gap-8">

<div class="card reveal">
<h3>Predicciones</h3>
<p>Selecciona los resultados de cada partido.</p>
</div>

<div class="card reveal">
<h3>Puntos</h3>
<p>Gana puntos por resultados correctos.</p>
</div>

<div class="card reveal">
<h3>Ranking</h3>
<p>Compite con amigos y jugadores globales.</p>
</div>

</div>

</div>

</section>

<!-- RESULTADOS -->
<section id="results" class="py-32 px-6">

<div class="max-w-6xl mx-auto">

<h2 class="text-4xl font-bold text-center mb-12 reveal">
Últimos Resultados
</h2>

<div class="grid md:grid-cols-3 gap-6">

<div class="match-card reveal">
Brazil <span>3 - 1</span> Spain
</div>

<div class="match-card reveal">
Argentina <span>2 - 2</span> France
</div>

<div class="match-card reveal">
Germany <span>1 - 0</span> Italy
</div>

</div>

</div>

</section>

<!-- LIVE -->
<section id="live" class="py-32 px-6 section-bg">

<div class="max-w-6xl mx-auto">

<h2 class="text-4xl font-bold text-center mb-12 reveal">
Partidos en Vivo
</h2>

<div class="grid md:grid-cols-2 gap-8">

<div class="live-card reveal">

<div class="flex justify-between">
<span>Brazil</span>
<span class="text-green-400">2</span>
</div>

<div class="flex justify-between">
<span>Germany</span>
<span>1</span>
</div>

<p class="text-sm text-gray-400 mt-3">
Minuto 76
</p>

</div>

<div class="live-card reveal">

<div class="flex justify-between">
<span>Argentina</span>
<span>1</span>
</div>

<div class="flex justify-between">
<span>France</span>
<span>1</span>
</div>

<p class="text-sm text-gray-400 mt-3">
Minuto 52
</p>

</div>

</div>

</div>

</section>

<!-- RANKING -->
<section id="ranking" class="py-32 px-6">

<div class="max-w-5xl mx-auto">

<h2 class="text-4xl font-bold text-center mb-12 reveal">
Ranking Global
</h2>

<table class="w-full reveal">

<thead>
<tr class="border-b border-gray-700">
<th class="py-4 text-left">Pos</th>
<th class="py-4 text-left">Jugador</th>
<th class="py-4 text-left">Puntos</th>
</tr>
</thead>

<tbody>

<tr class="border-b border-gray-800">
<td>1</td>
<td>Carlos Rivera</td>
<td class="text-green-400">124</td>
</tr>

<tr class="border-b border-gray-800">
<td>2</td>
<td>Laura Gomez</td>
<td>118</td>
</tr>

<tr class="border-b border-gray-800">
<td>3</td>
<td>Miguel Torres</td>
<td>111</td>
</tr>

</tbody>

</table>

</div>

</section>

</div>

</template>

<style scoped>

.section-bg{
background:
linear-gradient(135deg,#020617,#020617),
repeating-linear-gradient(
45deg,
rgba(0,255,170,0.05) 0px,
rgba(0,255,170,0.05) 1px,
transparent 1px,
transparent 20px
);
}

.card{
background:#111827;
padding:30px;
border-radius:12px;
}

.match-card{
background:#111827;
padding:20px;
border-radius:10px;
display:flex;
justify-content:space-between;
}

.live-card{
background:#111827;
padding:25px;
border-radius:12px;
}

.reveal{
opacity:0;
transform:translateY(40px);
transition:all .8s ease;
}

.reveal.show{
opacity:1;
transform:translateY(0);
}

</style>
