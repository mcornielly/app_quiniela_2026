<script setup>
import { computed, ref, watch } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { imageUrl } from '@/Utils/image'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Gallery',
    },
    images: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['close'])

const currentIndex = ref(0)
const direction = ref('next')

const normalizedImages = computed(() => {
    return (props.images || [])
        .map((item) => {
            if (typeof item !== 'string') return null
            const value = item.trim()
            if (!value) return null
            return imageUrl(value) || value
        })
        .filter(Boolean)
})

const currentImage = computed(() => normalizedImages.value[currentIndex.value] || null)
const hasImages = computed(() => normalizedImages.value.length > 0)
const hasNavigation = computed(() => normalizedImages.value.length > 1)

const prev = () => {
    if (!hasNavigation.value) return
    direction.value = 'prev'
    currentIndex.value = (currentIndex.value - 1 + normalizedImages.value.length) % normalizedImages.value.length
}

const next = () => {
    if (!hasNavigation.value) return
    direction.value = 'next'
    currentIndex.value = (currentIndex.value + 1) % normalizedImages.value.length
}

const selectImage = (index) => {
    direction.value = index >= currentIndex.value ? 'next' : 'prev'
    currentIndex.value = index
}

watch(
    () => [props.show, normalizedImages.value.length],
    () => {
        currentIndex.value = 0
    }
)
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-[90] bg-slate-900/70 backdrop-blur-[1px]"
                @click="$emit('close')"
            />
        </Transition>

        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <section
                v-if="show"
                class="fixed inset-0 z-[95] flex items-center justify-center p-4 sm:p-8"
            >
                <article
                    class="w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                    @click.stop
                >
                    <header class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ title }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ normalizedImages.length }} imagen{{ normalizedImages.length === 1 ? '' : 'es' }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg border border-slate-300 p-1.5 text-slate-600 hover:bg-red-100 hover:text-red-600 hover:border-red-300 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-red-900/30 dark:hover:text-red-400 dark:hover:border-red-500/40"
                            @click="$emit('close')"
                        >
                            <XMarkIcon class="h-5 w-5" />
                        </button>
                    </header>

                    <div class="relative flex h-[56vh] items-center justify-center bg-slate-100 dark:bg-slate-950">
                        <Transition :name="direction === 'next' ? 'gallery-slide-next' : 'gallery-slide-prev'" mode="out-in">
                            <img
                                v-if="hasImages"
                                :key="currentImage"
                                :src="currentImage"
                                alt="Stadium image"
                                class="h-full w-full object-contain"
                            >
                        </Transition>
                        <p v-if="!hasImages" class="text-sm text-slate-500 dark:text-slate-400">No hay imagenes disponibles</p>

                        <button
                            v-if="hasNavigation"
                            type="button"
                            class="absolute left-3 rounded-full border border-slate-300 bg-white/90 p-2 text-slate-700 hover:bg-white dark:border-slate-600 dark:bg-slate-900/80 dark:text-slate-100"
                            @click="prev"
                        >
                            <ChevronLeftIcon class="h-5 w-5" />
                        </button>
                        <button
                            v-if="hasNavigation"
                            type="button"
                            class="absolute right-3 rounded-full border border-slate-300 bg-white/90 p-2 text-slate-700 hover:bg-white dark:border-slate-600 dark:bg-slate-900/80 dark:text-slate-100"
                            @click="next"
                        >
                            <ChevronRightIcon class="h-5 w-5" />
                        </button>
                    </div>

                    <footer class="border-t border-slate-200 dark:border-slate-700">
                        <div class="px-4 pt-3">
                            <div class="mb-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400">
                            {{ hasImages ? `${currentIndex + 1} / ${normalizedImages.length}` : '0 / 0' }}
                            </div>
                            <div class="flex gap-2 overflow-x-auto pb-3">
                            <button
                                v-for="(img, index) in normalizedImages"
                                :key="`${img}-${index}`"
                                type="button"
                                :class="[
                                    'h-16 w-24 shrink-0 overflow-hidden rounded-lg border-2 transition',
                                    index === currentIndex
                                        ? 'border-primary-500'
                                        : 'border-transparent opacity-80 hover:opacity-100'
                                ]"
                                @click="selectImage(index)"
                            >
                                <img :src="img" alt="thumb" class="h-full w-full object-cover">
                            </button>
                            </div>
                        </div>
                        <div class="w-full border-t border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/40">
                            <div class="flex w-full justify-end">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white dark:focus:ring-gray-600"
                                @click="$emit('close')"
                            >
                                <svg aria-hidden="true" class="mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                                Cancel
                            </button>
                            </div>
                        </div>
                    </footer>
                </article>
            </section>
        </Transition>
    </Teleport>
</template>

<style scoped>
.gallery-slide-next-enter-active,
.gallery-slide-next-leave-active,
.gallery-slide-prev-enter-active,
.gallery-slide-prev-leave-active {
    transition: opacity 220ms ease, transform 220ms ease;
}

.gallery-slide-next-enter-from,
.gallery-slide-prev-leave-to {
    opacity: 0;
    transform: translateX(16px);
}

.gallery-slide-next-leave-to,
.gallery-slide-prev-enter-from {
    opacity: 0;
    transform: translateX(-16px);
}
</style>
