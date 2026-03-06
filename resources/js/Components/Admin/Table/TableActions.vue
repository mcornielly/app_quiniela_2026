<script setup>

import { EyeIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    row: Object,
    entityName: String,
    actions: {
        type: Object,
        default: () => ({
            show: true,
            edit: true,
            delete: true
        })
    },
})

const emit = defineEmits(['edit','delete','view'])

</script>

<template>
    <div class="flex items-center space-x-2">

        <!-- View -->
        <button
            v-if="actions.show"
            @click="$emit('view', row)"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700"
            :title="'View ' + row.name"
        >
            <EyeIcon class="w-4 h-4" />
            <span class="sr-only">View</span>
        </button>


        <!-- EDIT -->
        <button
            v-if="actions.edit"
            @click="$emit('edit', row)"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >

            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
            </svg>

            Update

        </button>


        <!-- DELETE -->
        <button
            v-if="actions.delete"
            @click="$emit('delete', row)"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-red-700 rounded-lg hover:bg-red-800 focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900"
        >

            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>

            Delete item

        </button>

    </div>
</template>
