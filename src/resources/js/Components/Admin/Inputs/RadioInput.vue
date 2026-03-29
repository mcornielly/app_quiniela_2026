<script setup>
const props = defineProps({
    modelValue: [String, Number],
    value: [String, Number],
    label: String,
    name: String,
    id: String
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
    <div class="flex items-center">
        <input
            :id="id"
            type="radio"
            :name="name"
            :value="value"
            :checked="modelValue === value"
            @change="emit('update:modelValue', value)"
            class="w-4 h-4 border-gray-300 bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:focus:ring-primary-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600"
        >

        <label
            :for="id"
            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
        >
            {{ label }}
        </label>
    </div>
</template>
