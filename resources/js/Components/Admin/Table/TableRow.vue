<script setup>
import TableActions from './TableActions.vue'
import { imageUrl } from '@/Utils/image'
import { formatDate } from '@/Utils/format'

const props = defineProps({
    row: {
        type: Object,
        required: true,
    },
    columns: Array,
    actions: Object,
    selected: Boolean
})

const emit = defineEmits(['toggle', 'edit', 'delete'])

const toggle = () => {
    emit('toggle', props.row.id)
}

const imageFields = [
    'flag',
    'flag_path',
    'logo',
    'image',
    'avatar',
    'photo'
]

const dateFields = [
    'match_date',
    'created_at',
    'updated_at'
]

const isDateField = (key) => dateFields.includes(key)

const isImageField = (key) => imageFields.includes(key)
</script>

<template>
    <tr class="border-b hover:bg-gray-100 dark:hover:bg-gray-700 dark:border-gray-700">
        <!-- checkbox -->
        <td class="w-4 p-4">
            <div class="flex items-center">
                <input
                    :checked="selected"
                    @change="toggle"
                    type="checkbox"
                    class="w-4 h-4 text-blue-600 accent-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600"
                >
            </div>
        </td>

        <!-- 🔁 LOOP COLUMNAS -->
        <td
            v-for="column in columns"
            :key="column.key"
            class="px-6 py-4 text-gray-900 whitespace-nowrap dark:text-white"
        >

            <!-- 🖼 IMAGE FIELDS -->
            <img
                v-if="isImageField(column.key) && row[column.key]"
                :src="imageUrl(row[column.key])"
                class="w-8 h-8 object-contain rounded"
            />

            <!-- 🔗 RELATION OBJECT -->
            <span
                v-else-if="typeof row[column.key] === 'object' && row[column.key] !== null"
            >
                {{ row[column.key].name ?? '—' }}
            </span>

            <!-- 📅 DATE FIELD -->
            <span v-else-if="isDateField(column.key)">
                {{ formatDate(row[column.key]) }}
            </span>

            <!-- 🧾 NORMAL FIELD -->
            <span v-else>
                {{ row[column.key] ?? '—' }}
            </span>

        </td>

        <!-- actions -->
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
