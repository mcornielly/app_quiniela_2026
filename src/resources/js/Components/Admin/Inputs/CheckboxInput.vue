<script setup>
const props = defineProps({
    modelValue: Boolean,
    label: String,
    id: String
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
    <div class="flex items-center">
        <input
            :id="id"
            type="checkbox"
            :checked="modelValue"
            @change="emit('update:modelValue', $event.target.checked)"
            class="w-4 h-4 border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:focus:ring-primary-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600"
        >

        <label
            :for="id"
            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
        >
            {{ label }}
        </label>
    </div>
</template>
