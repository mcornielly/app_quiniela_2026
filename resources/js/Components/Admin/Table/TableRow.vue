<script setup>
import TableActions from './TableActions.vue'

const props = defineProps({
    row: {
        type: Object,
        required: true,
    },
    actions: {
        type: Object,
        default: () => ({
            show: true,
            edit: true,
            delete: true
        })
    },
    selected: Boolean
})

const emit = defineEmits(['toggle','edit','delete'])

const toggle = () => {
    emit('toggle', props.row.id)
}
</script>

<template>
    <tr class="border-b hover:bg-gray-100 dark:hover:bg-gray-700 dark:border-gray-700">
        <td class="w-4 p-4">
            <div class="flex items-center">
                <input
                    :checked="selected"
                    @change="toggle"
                    type="checkbox"
                    class="w-4 h-4 text-blue-600 accent-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600">
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
            <TableActions
                :row="row"
                :actions="actions"
                @edit="$emit('edit', $event)"
                @delete="$emit('delete', $event)"
            />
        </td>
    </tr>
</template>
