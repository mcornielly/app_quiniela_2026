<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import UserDashboardLayout from '@/Layouts/UserDashboardLayout.vue'
import { imageUrl } from '@/Utils/image'

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

const statusClass = (status) => {
    if (status === 'finished') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
    if (status === 'in_progress') return 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'
    return 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
}

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
            <div class="-mt-8 flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Sede oficial</p>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white">{{ stadium?.name || 'Estadio' }}</h1>
                </div>
                <Link
                    :href="route('teams.profile')"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-300 hover:text-cyan-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                >
                    Volver a selecciones
                </Link>
            </div>

            <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/75">
                <img
                    v-if="currentImage"
                    :src="currentImage"
                    :alt="stadium?.name"
                    class="h-56 w-full object-cover sm:h-72"
                >
                <div v-else class="flex h-56 items-center justify-center bg-[linear-gradient(120deg,_#0f172a,_#1e3a8a,_#0f172a)] sm:h-72">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-200">Imagen del estadio (template)</p>
                </div>
                <div v-if="hasCarousel" class="flex items-center justify-between border-y border-slate-200 px-3 py-2 dark:border-slate-700">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-cyan-400 hover:text-cyan-700 dark:border-slate-600 dark:text-slate-200"
                        @click="goPrev"
                    >
                        Anterior
                    </button>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                        {{ carouselIndex + 1 }} / {{ galleryImages.length }}
                    </p>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-cyan-400 hover:text-cyan-700 dark:border-slate-600 dark:text-slate-200"
                        @click="goNext"
                    >
                        Siguiente
                    </button>
                </div>
                <div class="p-5 sm:p-6">
                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ stadium?.info }}</p>
                    <div class="mt-4 grid gap-2 text-xs text-slate-500 dark:text-slate-400 sm:grid-cols-2 lg:grid-cols-3">
                        <p><span class="font-semibold text-slate-700 dark:text-slate-300">Ciudad:</span> {{ stadium?.city || 'No disponible' }}</p>
                        <p><span class="font-semibold text-slate-700 dark:text-slate-300">País:</span> {{ stadium?.country || 'No disponible' }}</p>
                        <p><span class="font-semibold text-slate-700 dark:text-slate-300">Capacidad:</span> {{ capacityLabel }}</p>
                        <p><span class="font-semibold text-slate-700 dark:text-slate-300">Superficie:</span> {{ stadium?.surface || 'No disponible' }}</p>
                        <p class="sm:col-span-2 lg:col-span-3">
                            <span class="font-semibold text-slate-700 dark:text-slate-300">Dirección:</span> {{ stadium?.address || 'No disponible' }}
                        </p>
                    </div>
                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ stadium?.matches_count ?? 0 }} encuentros registrados</p>
                </div>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900/75">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Encuentros en esta sede</h2>
                <div class="mt-4 space-y-2">
                    <article
                        v-for="match in matches"
                        :key="match.id"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-900/70"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ match.stage_label }}
                                <span v-if="match.group_name"> · Grupo {{ match.group_name }}</span>
                                · {{ match.match_date_label }} {{ match.match_time_label }}
                            </p>
                            <span :class="statusClass(match.status)" class="rounded-full px-2 py-0.5 text-[10px] font-semibold">{{ match.status_label }}</span>
                        </div>
                        <div class="mt-2 grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ match.home_team.name }}</p>
                            <p class="text-base font-black text-slate-900 dark:text-white">
                                {{ Number.isInteger(match.home_score) ? match.home_score : '-' }} : {{ Number.isInteger(match.away_score) ? match.away_score : '-' }}
                            </p>
                            <p class="truncate text-right text-sm font-semibold text-slate-900 dark:text-white">{{ match.away_team.name }}</p>
                        </div>
                    </article>
                </div>
            </article>
        </section>
    </UserDashboardLayout>
</template>
