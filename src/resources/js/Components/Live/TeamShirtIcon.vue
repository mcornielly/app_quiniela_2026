<script setup>
import { computed } from 'vue'

const props = defineProps({
    color: {
        type: String,
        default: '#22d3ee',
    },
    number: {
        type: [String, Number],
        default: null,
    },
    size: {
        type: Number,
        default: 34,
    },
    textColor: {
        type: String,
        default: '#0f172a',
    },
})

const iconStyle = computed(() => ({
    width: `${props.size}px`,
    height: `${props.size}px`,
}))
const numberStyle = computed(() => ({
    color: props.textColor,
    fontSize: `${Math.max(10, Math.round(props.size * 0.32))}px`,
}))

const numberLabel = computed(() => {
    if (props.number === null || props.number === undefined || props.number === '') {
        return ''
    }

    return String(props.number)
})
</script>

<template>
    <span class="shirt-icon" :style="iconStyle" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" class="shirt-svg">
            <path
                :fill="color"
                d="M320.2 112c44.2 0 80-35.8 80-80l53.5 0c17 0 33.3 6.7 45.3 18.7L617.6 169.4c12.5 12.5 12.5 32.8 0 45.3l-50.7 50.7c-12.5 12.5-32.8 12.5-45.3 0l-41.4-41.4 0 224c0 35.3-28.7 64-64 64l-192 0c-35.3 0-64-28.7-64-64l0-224-41.4 41.4c-12.5 12.5-32.8 12.5-45.3 0L22.9 214.6c-12.5-12.5-12.5-32.8 0-45.3L141.5 50.7c12-12 28.3-18.7 45.3-18.7l53.5 0c0 44.2 35.8 80 80 80z"
            />
            <path
                fill="rgba(15,23,42,0.16)"
                d="M240 32c0 44.2 35.8 80 80 80s80-35.8 80-80h-31c0 27.1-21.9 49-49 49s-49-21.9-49-49h-31z"
            />
        </svg>

        <span v-if="numberLabel" class="shirt-number" :style="numberStyle">
            {{ numberLabel }}
        </span>
    </span>
</template>

<style scoped>
.shirt-icon {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.shirt-svg {
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 1px 2px rgba(15, 23, 42, 0.28));
}

.shirt-number {
    position: absolute;
    top: 44%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 10px;
    font-weight: 800;
    line-height: 1;
    font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
}
</style>
