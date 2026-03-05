<script setup>
import TableHead from './TableHead.vue'
import TableRow from './TableRow.vue'

defineProps({
    rows: Array
})
</script>

<template>
    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                    <TableHead />
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                        <TableRow
                            v-for="row in rows"
                            :key="row.id"
                            :row="row"
                        />
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
