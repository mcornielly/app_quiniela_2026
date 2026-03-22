<script setup>
import { onMounted } from 'vue'
import { initFlowbite } from 'flowbite'

const props = defineProps({
    columns: Array,
    allSelected: Boolean,
    actions: Object
})

const emit = defineEmits(['toggle-all'])

const toggle = (event) => {
    emit('toggle-all', event.target.checked)
}

onMounted(() => {
    initFlowbite()
})
</script>
<template>
    <thead class="bg-gray-100 dark:bg-gray-700">
        <tr>
            <!-- checkbox -->
            <th
                v-if="actions.checkbox"
                scope="col" class="p-4">
                <div class="flex items-center">
                    <input
                        ref="checkbox"
                        type="checkbox"
                        :checked="allSelected"
                        @change="toggle"
                        class="w-4 h-4 text-blue-600 accent-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600"
                    >
                </div>
            </th>

            <!-- columnas dinámicas -->
            <th
                v-for="column in columns"
                :key="column.key"
                scope="col"
                :class="[
                    'px-6 py-4 text-xs font-medium text-gray-500 uppercase dark:text-gray-400',
                    column.align === 'center' ? 'text-center' : 'text-left'
                ]"
            >
                <span
                    v-if="column.tooltip"
                    class="inline-flex items-center gap-1 cursor-help"
                    :data-tooltip-target="`tooltip-head-${column.key}`"
                    data-tooltip-placement="top"
                >
                    {{ column.label }}
                </span>
                <span v-else>
                    {{ column.label }}
                </span>
                <div
                    v-if="column.tooltip"
                    :id="`tooltip-head-${column.key}`"
                    role="tooltip"
                    class="absolute z-10 invisible inline-block px-3 py-2 text-xs font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700 normal-case tracking-normal"
                >
                    {{ column.tooltip }}
                    <div class="tooltip-arrow" data-popper-arrow></div>
                </div>
            </th>

            <!-- actions -->
            <th
                v-if="actions.show || actions.edit || actions.delete"
                scope="col"
                class="px-6 py-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400"
            >
                Actions
            </th>
        </tr>
    </thead>
</template>
