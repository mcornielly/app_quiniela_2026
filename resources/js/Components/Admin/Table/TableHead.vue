<script setup>

const props = defineProps({
    allSelected: Boolean,
})

const emit = defineEmits(['toggle-all'])

const toggle = (event) => {
    emit('toggle-all', event.target.checked)
}
</script>
<template>
    <thead class="bg-gray-100 dark:bg-gray-700">
        <tr>
            <th scope="col" class="p-4">
                <div class="flex items-center">
                    <input
                        ref="checkbox"
                        type="checkbox"
                        :checked="allSelected"
                        @change="toggle"
                        class="w-4 h-4 text-blue-600 accent-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600">
                </div>
            </th>

            <th scope="col"
                class="px-6 py-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                Team
            </th>

            <th scope="col"
                class="px-6 py-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                Group
            </th>

            <th scope="col"
                class="px-6 py-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                Type
            </th>

            <th scope="col"
                class="px-6 py-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                Position
            </th>

            <th scope="col"
                class="px-6 py-4 text-xs font-medium text-left text-gray-500 uppercase dark:text-gray-400">
                Actions
            </th>
        </tr>
    </thead>
</template>
