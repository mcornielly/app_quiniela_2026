<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'
import { imageUrl } from '@/Utils/image'
import FixtureMatchCard from '@/Components/Quiniela/FixtureMatchCard.vue'

const props = defineProps({
    tournament: { type: Object, default: null },
    stadium: { type: Object, default: null },
    matches: { type: Array, default: () => [] },
})

const page = usePage()
const favoriteTeamTheme = computed(() => page.props.auth?.user?.favorite_team_theme ?? null)
const tickerThemes = {
    neutral: {
        tickerClass: 'border-t border-slate-300/70 bg-[linear-gradient(to_right,_#cfd6df_0%,_#e4e8ee_45%,_#f4f6f9_100%)] text-slate-900',
    },
}
const activeTickerTheme = computed(() => ({
    ...tickerThemes.neutral,
    ...(favoriteTeamTheme.value ?? {}),
}))

const capacityLabel = computed(() => {
    const value = Number(props.stadium?.capacity)
    if (!Number.isFinite(value) || value <= 0) return 'No disponible'
    return new Intl.NumberFormat('es-ES').format(value)
})

const carouselIndex = ref(0)

const galleryImages = computed(() => {
    const list = Array.isArray(props.stadium?.image_gallery) ? props.stadium.image_gallery : []
    const normalized = list
        .map((item) => imageUrl(item) || item)
        .filter((item) => typeof item === 'string' && item !== '')

    const cover = imageUrl(props.stadium?.image_url)
    if (cover && !normalized.includes(cover)) {
        normalized.unshift(cover)
    }

    return normalized
})

const currentImage = computed(() => galleryImages.value[carouselIndex.value] ?? null)
const hasCarousel = computed(() => galleryImages.value.length > 1)
let carouselTimer = null

const goNext = () => {
    if (!galleryImages.value.length) return
    carouselIndex.value = (carouselIndex.value + 1) % galleryImages.value.length
}

const goPrev = () => {
    if (!galleryImages.value.length) return
    carouselIndex.value = (carouselIndex.value - 1 + galleryImages.value.length) % galleryImages.value.length
}

const selectImage = (index) => {
    carouselIndex.value = index
}

watch(
    () => galleryImages.value.length,
    () => {
        carouselIndex.value = 0
    }
)

const startCarousel = () => {
    if (carouselTimer || !hasCarousel.value) return
    carouselTimer = setInterval(() => {
        goNext()
    }, 5000)
}

const stopCarousel = () => {
    if (!carouselTimer) return
    clearInterval(carouselTimer)
    carouselTimer = null
}

watch(hasCarousel, (enabled) => {
    if (enabled) {
        startCarousel()
        return
    }

    stopCarousel()
})

onMounted(() => {
    startCarousel()
})

onBeforeUnmount(() => {
    stopCarousel()
})
</script>

<template>
    <Head title="Detalle de estadio" />

    <UserDashboardLayout
        title="Detalle de estadio"
        description="Template de sede con encuentros programados y datos basicos."
        :ticker-class="[activeTickerTheme.tickerClass, activeTickerTheme.decorationClass || ''].join(' ')"
    >
        <template #ticker><div class="h-12 w-full" aria-hidden="true" /></template>
        <template #headerContent><div class="hidden" /></template>

        <section class="space-y-6">
            <div class="-mt-8">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Mundial {{ tournament?.year ?? '' }}</p>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white">{{ tournament?.name || 'FIFA World Cup' }}</h1>
            </div>

            <div class="border-b border-slate-200 dark:border-slate-800">
                <ul class="-mb-px flex flex-wrap text-center text-sm font-medium text-slate-600 dark:text-slate-300">
                    <li class="me-2">
                        <Link
                            :href="route('teams.profile')"
                            class="group inline-flex items-center justify-center rounded-t-xl border-b px-4 py-4 leading-none transition border-transparent hover:border-primary-300 hover:text-primary-700 dark:hover:border-primary-500 dark:hover:text-primary-300"
                        >
                            <span class="me-2 inline-flex h-6 w-6 shrink-0 items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" class="h-6 w-6 fill-current" aria-hidden="true">
                                    <path d="M320 16a104 104 0 1 1 0 208 104 104 0 1 1 0-208zM96 88a72 72 0 1 1 0 144 72 72 0 1 1 0-144zM0 416c0-70.7 57.3-128 128-128 12.8 0 25.2 1.9 36.9 5.4-32.9 36.8-52.9 85.4-52.9 138.6l0 16c0 11.4 2.4 22.2 6.7 32L32 480c-17.7 0-32-14.3-32-32l0-32zm521.3 64c4.3-9.8 6.7-20.6 6.7-32l0-16c0-53.2-20-101.8-52.9-138.6 11.7-3.5 24.1-5.4 36.9-5.4 70.7 0 128 57.3 128 128l0 32c0 17.7-14.3 32-32 32l-86.7 0zM472 160a72 72 0 1 1 144 0 72 72 0 1 1 -144 0zM160 432c0-88.4 71.6-160 160-160s160 71.6 160 160l0 16c0 17.7-14.3 32-32 32l-256 0c-17.7 0-32-14.3-32-32l0-16z"/>
                                </svg>
                            </span>
                            <span class="text-xs font-semibold uppercase tracking-[0.2em]">Equipos</span>
                        </Link>
                    </li>
                    <li class="me-2">
                        <span
                            class="group inline-flex items-center justify-center rounded-t-xl border-b px-4 py-4 leading-none transition border-primary-500 text-primary-700 dark:border-primary-400 dark:text-primary-300"
                        >
                            <img
                                src="/noun-stadium.png"
                                alt=""
                                aria-hidden="true"
                                class="me-2 h-6 w-6 shrink-0 object-contain opacity-95"
                            >
                            <span class="text-xs font-semibold uppercase tracking-[0.2em]">Estadios</span>
                        </span>
                    </li>
                    <li class="me-2">
                        <Link
                            :href="route('groups.index')"
                            class="group inline-flex items-center justify-center rounded-t-xl border-b px-4 py-4 leading-none transition border-transparent hover:border-primary-300 hover:text-primary-700 dark:hover:border-primary-500 dark:hover:text-primary-300"
                        >
                            <span class="me-2 inline-flex h-6 w-6 shrink-0 items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="h-5 w-5 fill-current" aria-hidden="true">
                                    <path d="M512 48c0-11.1-5.7-21.4-15.2-27.2s-21.2-6.4-31.1-1.4L349.5 77.5 170.1 17.6c-8.1-2.7-16.8-2.1-24.4 1.7l-128 64C6.8 88.8 0 99.9 0 112L0 464c0 11.1 5.7 21.4 15.2 27.2s21.2 6.4 31.1 1.4l116.1-58.1 179.4 59.8c8.1 2.7 16.8 2.1 24.4-1.7l128-64c10.8-5.4 17.7-16.5 17.7-28.6l0-352zM192 376.9l0-284.5 128 42.7 0 284.5-128-42.7z"/>
                                </svg>
                            </span>
                            <span class="text-xs font-semibold uppercase tracking-[0.2em]">Roadmap</span>
                        </Link>
                    </li>
                </ul>
            </div>

            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Sede oficial</p>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white">{{ stadium?.name || 'Estadio' }}</h1>
                </div>
                <Link
                    :href="route('teams.profile')"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-300 hover:text-cyan-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                >
                    Volver a estadios
                </Link>
            </div>

            <div id="stadium-carousel" class="relative w-full" data-carousel="slide">
                <div class="relative h-56 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 md:h-96 dark:border-slate-700 dark:bg-slate-950">
                    <Transition name="carousel-fade" mode="out-in">
                        <img
                            v-if="currentImage"
                            :key="currentImage"
                            :src="currentImage"
                            :alt="stadium?.name"
                            class="absolute left-1/2 top-1/2 block h-full w-full -translate-x-1/2 -translate-y-1/2 object-cover"
                        >
                    </Transition>
                    <div v-if="!currentImage" class="absolute inset-0 flex items-center justify-center bg-[linear-gradient(120deg,_#0f172a,_#1e3a8a,_#0f172a)]">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-200">Sin imagen del estadio</p>
                    </div>

                    <div v-if="hasCarousel" class="absolute bottom-5 left-1/2 z-30 flex -translate-x-1/2 space-x-3 rtl:space-x-reverse">
                        <button
                            v-for="(img, index) in galleryImages"
                            :key="`dot-${img}-${index}`"
                            type="button"
                            class="h-3 w-3 rounded-base transition"
                            :class="index === carouselIndex ? 'bg-white' : 'bg-white/40 hover:bg-white/70'"
                            :aria-label="`Slide ${index + 1}`"
                            @click="selectImage(index)"
                        />
                    </div>

                    <button
                        v-if="hasCarousel"
                        type="button"
                        class="group absolute start-0 top-0 z-30 flex h-full cursor-pointer items-center justify-center px-4 focus:outline-none"
                        @click="goPrev"
                    >
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-base bg-white/30 group-hover:bg-white/50 group-focus:ring-4 group-focus:ring-white dark:bg-gray-800/30 dark:group-hover:bg-gray-800/60 dark:group-focus:ring-gray-800/70">
                            <svg class="h-5 w-5 text-white rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"/></svg>
                            <span class="sr-only">Previous</span>
                        </span>
                    </button>
                    <button
                        v-if="hasCarousel"
                        type="button"
                        class="group absolute end-0 top-0 z-30 flex h-full cursor-pointer items-center justify-center px-4 focus:outline-none"
                        @click="goNext"
                    >
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-base bg-white/30 group-hover:bg-white/50 group-focus:ring-4 group-focus:ring-white dark:bg-gray-800/30 dark:group-hover:bg-gray-800/60 dark:group-focus:ring-gray-800/70">
                            <svg class="h-5 w-5 text-white rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                            <span class="sr-only">Next</span>
                        </span>
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                    <p class="text-left">Ciudad: {{ stadium?.city || 'No disponible' }}</p>
                    <p class="text-center">País: {{ stadium?.country || 'No disponible' }}</p>
                    <p class="text-right">Capacidad: {{ capacityLabel }}</p>
                </div>
                <div class="grid grid-cols-3 gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <p class="col-span-1 font-semibold uppercase tracking-[0.2em]"><span class="text-slate-700 dark:text-slate-300">Superficie:</span> {{ stadium?.surface || 'No disponible' }}</p>
                    <p class="col-span-1 row-start-2">
                        <span class="font-semibold uppercase tracking-[0.2em] text-slate-700 dark:text-slate-300">Dirección:</span> {{ stadium?.address || 'No disponible' }}
                    </p>
                    <p class="col-span-3">{{ stadium?.matches_count ?? 0 }} encuentros registrados</p>
                    <div class="col-span-3 border-b border-slate-200 dark:border-slate-700" />
                </div>
            </div>

            <section class="space-y-2">
                <h2 class="text-xl font-black text-slate-900 dark:text-white">Encuentros en esta sede</h2>
                <FixtureMatchCard
                    v-for="match in matches"
                    :key="match.id"
                    :match="match"
                    :show-status="true"
                />
                <p
                    v-if="!matches.length"
                    class="rounded-xl border border-dashed border-slate-300 px-3 py-4 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400"
                >
                    No hay encuentros programados en esta sede.
                </p>
            </section>
        </section>
    </UserDashboardLayout>
</template>

<style scoped>
.carousel-fade-enter-active,
.carousel-fade-leave-active {
    transition: opacity 260ms ease;
}

.carousel-fade-enter-from,
.carousel-fade-leave-to {
    opacity: 0;
}
</style>
