<script setup>
import { ref, watch, computed } from 'vue'
import TableHead from './TableHead.vue'
import TableRow from './TableRow.vue'
import 'flowbite'

const props = defineProps({
    rows: Array,
    columns: Array,
    actions: Object
})

const emit = defineEmits(['selection-change', 'edit', 'delete'])

const selected = ref([])

// 👇 toggle select all
const toggleSelectAll = (checked) => {
    if (checked) {
        selected.value = props.rows.map(row => row.id)
    } else {
        selected.value = []
    }
    emit('selection-change', selected.value)
}

// 👇 toggle individual
const toggleRow = (id) => {
    if (selected.value.includes(id)) {
        selected.value = selected.value.filter(i => i !== id)
    } else {
        selected.value = [...selected.value, id]
    }
    emit('selection-change', selected.value)
}

const allSelected = computed(() => {
    return selected.value.length > 0
})

watch(() => props.rows, () => {
    selected.value = []
})

</script>

<template>
    <div class="flex flex-col">
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <table class="min-w-full divide-y divide-gray-200 table-fixed dark:divide-gray-600">
                    <TableHead
                        :columns="columns"
                        :allSelected="allSelected"
                        @toggle-all="toggleSelectAll"
                    />
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                        <TableRow
                            v-for="row in rows"
                            :key="row.id"
                            :row="row"
                            :columns="columns"
                            :actions="actions"
                            :selected="selected.includes(row.id)"
                            @toggle="toggleRow"
                            @edit="$emit('edit',$event)"
                            @delete="$emit('delete',$event)"
                        />
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
