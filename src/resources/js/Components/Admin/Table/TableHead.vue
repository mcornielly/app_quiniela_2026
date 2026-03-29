<script setup>
import AppTooltip from '@/Components/UI/AppTooltip.vue'

const props = defineProps({
    columns: Array,
    allSelected: Boolean,
    actions: Object
})

const emit = defineEmits(['toggle-all'])

const toggle = (event) => {
    emit('toggle-all', event.target.checked)
}
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
                <AppTooltip
                    v-if="column.tooltip"
                    :text="column.tooltip"
                    placement="bottom"
                    tooltip-class="max-w-none whitespace-nowrap"
                >
                    <span class="inline-flex items-center cursor-default">
                        {{ column.label }}
                    </span>
                </AppTooltip>
                <span v-else>
                    {{ column.label }}
                </span>
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
