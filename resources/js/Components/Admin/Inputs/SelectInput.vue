<script setup>
const props = defineProps({
    modelValue: [String, Number],
    label: String,
    options: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
    <div>
        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            {{ label }}
        </label>

        <select
            :value="modelValue"
            @change="emit('update:modelValue', $event.target.value)"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
        >
            <option value="">Select {{ label }}</option>

            <option
                v-for="option in options"
                :key="option.id"
                :value="option.id"
            >
                {{ option.name }}
            </option>
        </select>
    </div>
</template>
