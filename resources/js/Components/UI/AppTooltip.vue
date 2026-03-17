<script setup>
const props = defineProps({
    text: {
        type: String,
        default: '',
    },
    placement: {
        type: String,
        default: 'top',
    },
    tooltipClass: {
        type: String,
        default: '',
    },
    arrowClass: {
        type: String,
        default: '',
    },
})

const placementClasses = {
    top: 'bottom-full left-1/2 mb-3 -translate-x-1/2',
    bottom: 'top-full left-1/2 mt-3 -translate-x-1/2',
    left: 'right-full top-1/2 mr-3 -translate-y-1/2',
    right: 'left-full top-1/2 ml-3 -translate-y-1/2',
}

const arrowClasses = {
    top: 'left-1/2 top-full -translate-x-1/2 -translate-y-1/2',
    bottom: 'left-1/2 bottom-full -translate-x-1/2 translate-y-1/2',
    left: 'left-full top-1/2 -translate-x-1/2 -translate-y-1/2',
    right: 'right-full top-1/2 translate-x-1/2 -translate-y-1/2',
}
</script>

<template>
    <div class="group relative inline-flex">
        <div class="inline-flex">
            <slot />
        </div>

        <div
            role="tooltip"
            class="pointer-events-none absolute z-20 invisible inline-block max-w-[220px] whitespace-normal break-words rounded-lg bg-slate-800 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-sm transition-opacity duration-300 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 sm:max-w-[320px] sm:whitespace-nowrap"
            :class="[placementClasses[placement] || placementClasses.top, tooltipClass]"
        >
            <slot name="content">
                {{ text }}
            </slot>
            <div
                class="absolute h-2.5 w-2.5 rotate-45 bg-slate-800"
                :class="[arrowClasses[placement] || arrowClasses.top, arrowClass]"
                data-popper-arrow
            />
        </div>
    </div>
</template>
