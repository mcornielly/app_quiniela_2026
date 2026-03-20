<script setup>
import { computed } from 'vue'

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    variant: {
        type: String,
        default: 'default',
    },
    titleIcon: {
        type: Object,
        default: null,
    },
})

const isUserDashboard = computed(() => props.variant === 'user-dashboard')

const wrapperClass = computed(() => {
    if (isUserDashboard.value) {
        return 'relative overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/85'
    }

    return 'relative overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800'
})

const headerClass = computed(() => {
    if (isUserDashboard.value) {
        return 'flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-slate-800 sm:flex-row sm:items-start sm:justify-between'
    }

    return 'flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-gray-700 sm:flex-row sm:items-start sm:justify-between'
})

const descriptionClass = computed(() => {
    if (isUserDashboard.value) {
        return 'mt-1.5 text-sm font-normal text-gray-500 dark:text-slate-400'
    }

    return 'mt-1.5 text-sm font-normal text-gray-500 dark:text-gray-400'
})
</script>

<template>
    <div :class="wrapperClass">
        <div :class="headerClass">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-medium text-gray-900 dark:text-white">
                    <svg
                        v-if="titleIcon?.path"
                        :viewBox="titleIcon.viewBox || '0 0 24 24'"
                        class="h-4 w-4 shrink-0 fill-current text-cyan-500 dark:text-cyan-400"
                        aria-hidden="true"
                    >
                        <path :d="titleIcon.path" />
                    </svg>
                    <span>{{ title }}</span>
                </h2>
                <p v-if="description" :class="descriptionClass">
                    {{ description }}
                </p>
            </div>
            <div v-if="$slots.actions" class="flex items-center gap-2 self-start">
                <slot name="actions" />
            </div>
        </div>

        <slot />
    </div>
</template>
