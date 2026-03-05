<script setup>
import TableActions from './TableActions.vue'

defineProps({
    row: {
        type: Object,
        required: true
    }
})
</script>

<template>
    <tr class="border-b hover:bg-gray-100 dark:hover:bg-gray-700 dark:border-gray-700">
        <td class="w-4 p-4">
            <div class="flex items-center">
                <input type="checkbox"
                    class="w-4 h-4 border-gray-300 rounded bg-gray-50
                    focus:ring-3 focus:ring-primary-300
                    dark:focus:ring-primary-600
                    dark:ring-offset-gray-800
                    dark:bg-gray-700
                    dark:border-gray-600">
            </div>
        </td>

        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
            {{ row.name }}
        </td>

        <td class="px-6 py-4 text-gray-900 whitespace-nowrap dark:text-white">
            {{ row.group?.name || '—' }}
        </td>

        <td class="px-6 py-4 text-gray-900 whitespace-nowrap dark:text-white">
            <span class="capitalize">{{ row.type || '—' }}</span>
        </td>

        <td class="px-6 py-4 text-gray-900 whitespace-nowrap dark:text-white">
            {{ row.group_position || '—' }}
        </td>

        <td class="px-6 py-4 space-x-2 whitespace-nowrap">
            <TableActions :row="row" />
        </td>
    </tr>
</template>
