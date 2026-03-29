<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: true,
    },
    ticker: {
        type: String,
        default: '',
    },
    tickerClass: {
        type: String,
        default: '',
    },
    useScrollFade: {
        type: Boolean,
        default: true,
    },
    scrollThreshold: {
        type: Number,
        default: 16,
    },
    initialOpacityClass: {
        type: String,
        default: 'opacity-100',
    },
    scrolledOpacityClass: {
        type: String,
        default: 'opacity-55',
    },
    transitionClass: {
        type: String,
        default: 'transition-opacity duration-300',
    },
    containerClass: {
        type: String,
        default: 'mx-auto max-w-7xl px-4 py-2 sm:px-6 lg:px-8',
    },
    defaultContentClass: {
        type: String,
        default: 'h-12 w-full',
    },
})

const hasScrolled = ref(false)

const tickerOpacityClass = computed(() => {
    if (!props.useScrollFade) {
        return props.initialOpacityClass
    }

    return hasScrolled.value ? props.scrolledOpacityClass : props.initialOpacityClass
})

const handleScroll = () => {
    hasScrolled.value = window.scrollY > props.scrollThreshold
}

onMounted(() => {
    if (!props.useScrollFade) {
        return
    }

    window.addEventListener('scroll', handleScroll, { passive: true })
    handleScroll()
})

onBeforeUnmount(() => {
    if (!props.useScrollFade) {
        return
    }

    window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
    <div
        v-if="props.show"
        :class="[props.tickerClass, tickerOpacityClass, props.transitionClass]"
    >
        <div :class="props.containerClass">
            <slot>
                <div v-if="props.ticker" class="flex items-center gap-3">
                    <p class="text-sm font-medium tracking-wide text-slate-100">
                        {{ props.ticker }}
                    </p>
                </div>
                <div v-else :class="props.defaultContentClass" aria-hidden="true" />
            </slot>
        </div>
    </div>
</template>
