<script setup>
const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
})
</script>

<template>
    <div class="relative overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-4 border-b border-gray-200 p-5 dark:border-gray-700 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ title }}</h2>
                <p v-if="description" class="mt-1.5 text-sm font-normal text-gray-500 dark:text-gray-400">
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
