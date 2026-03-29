<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { confirmDelete, notifySuccess, notifyError } from '@/Utils/notify'
import { singular, formatDateTime } from '@/Utils/format'
import DeleteDrawer from '@/Components/Admin/Drawer/DeleteDrawer.vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Header from '@/Layouts/Partials/Header.vue'
import SearchBar from '@/Layouts/Partials/SearchBar.vue'
import DataTable from '@/Components/Admin/Table/DataTable.vue'
import Pagination from '@/Components/Admin/Table/Pagination.vue'
import FormDrawer from '@/Components/Admin/Drawer/FormDrawer.vue'
import RuleForm from '@/Components/Admin/FormDrawer/RuleForm.vue'
import debounce from 'lodash/debounce'
import 'flowbite'

const title = 'Rules'

const props = defineProps({
    filters: Object,
    rules: Object,
        tournaments: Array,

})

// Column configuration (can be adjusted after generation)
const columns = [
        { key: 'tournament', label: 'Tournament' },
    { key: 'name', label: 'Name' },
    { key: 'tournament_starts_at', label: 'Tournament Starts', format: (v) => formatDateTime(v, 'es-VE') },
    { key: 'participation_closes_at', label: 'Participation Closes', format: (v) => formatDateTime(v, 'es-VE') },
    { key: 'exact_score_points', label: 'Exact', tooltip: 'Exact Score Points', align: 'center' },
    { key: 'correct_result_points', label: 'Result', tooltip: 'Correct Result Points', align: 'center' },
    { key: 'unpaid_after_window_action', label: 'Unpaid', tooltip: 'Unpaid After Window Action', align: 'center' },
    { key: 'active', label: 'Active', align: 'center' },

]

// Reactive state
const loading = ref(false)
const searchQuery = ref(props.filters?.search || '')
const showCreateDrawer = ref(false)
const selectedItem = ref(null)
const selectedItems = ref([])
const itemToDelete = ref(null)

const actions = {
    show: false,
    edit: true,
    delete: true
}

const handleSearch = debounce((query) => {
    router.get(
        route('admin.rules.index'),
        { search: query },
        { preserveState: true, replace: true }
    )
}, 400)

const handleToolbarAction = (action) => {
    if(action === 'deleteSelected'){
        deleteSelected()
    }
}

const deleteSelected = async () => {

    if(selectedItems.value.length === 0){
        notifyError("No rules selected")
        return
    }

    try{

        await confirmDelete(`Delete ${selectedItems.value.length} rules?`)

        router.delete(route('admin.rules.bulkDelete'), {
            data: {
                ids: selectedItems.value
            },
            preserveScroll: true,
            onSuccess: () => {
                notifySuccess("Rules deleted")
                selectedItems.value = []
                router.reload()
            }
        })

    }catch(e){
        console.log('Delete cancelled')
    }
}

const confirmDeleteItem = (item) => {

    router.delete(route('admin.rules.destroy', item.id), {
        preserveScroll: true,
        onSuccess: () => {
            notifySuccess('Rule deleted')
            itemToDelete.value = null
        }
    })

}

const handleSelectionChange = (ids) => {
    selectedItems.value = ids
}

const openEdit = (item) => {
    selectedItem.value = item
}

const openDelete = (item) => {
    itemToDelete.value = item
}

const handleCreate = () => {
    router.reload()
}

</script>

<template>
    <AdminLayout :title="title">
        <main>

            <div class="p-4 bg-white block sm:flex items-center justify-between border-b border-gray-200 lg:mt-1.5 dark:bg-gray-800 dark:border-gray-700">
                <div class="w-full mb-1">

                    <Header :title="title" />

                    <div class="items-center justify-between block sm:flex md:divide-x md:divide-gray-100 dark:divide-gray-700">

                        <SearchBar
                            :initial-query="filters?.search"
                            @search="handleSearch"
                            @action="handleToolbarAction"
                            :title="title"
                        />

                        <button
                            @click="showCreateDrawer = true"
                            class="text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800"
                            type="button"
                        >
                            Add New {{ singular(title) }}
                        </button>

                    </div>
                </div>
            </div>

            <div v-if="loading" class="flex justify-center py-12">
                <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else class="flex flex-col">

                <div class="overflow-x-auto overflow-y-visible">
                    <div class="inline-block min-w-full align-middle">

                        <div class="overflow-visible shadow">

                            <DataTable
                                :rows="rules.data"
                                :columns="columns"
                                :actions="actions"
                                @selection-change="handleSelectionChange"
                                @edit="openEdit"
                                @delete="openDelete"
                            />

                            <div v-if="rules.data.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
                                No rules found.
                            </div>

                            <Pagination :meta="rules" />

                        </div>

                    </div>
                </div>

                <FormDrawer
                    title="Add Rule"
                    :show="showCreateDrawer"
                    @close="showCreateDrawer = false"
                >
                    <RuleForm
                                :tournaments="tournaments"

                        @saved="handleCreate"
                        @close="showCreateDrawer = false"
                    />
                </FormDrawer>

                <FormDrawer
                    title="Update Rule"
                    :show="!!selectedItem"
                    @close="selectedItem = null"
                >
                    <RuleForm
                                :tournaments="tournaments"

                        :rule="selectedItem"
                        @close="selectedItem = null"
                    />
                </FormDrawer>

                <FormDrawer
                    :show="!!itemToDelete"
                    title="Delete Rule"
                    @close="itemToDelete = null"
                >
                    <DeleteDrawer
                        :show="!!itemToDelete"
                        entityName="Rule"
                        :item="itemToDelete"
                        @close="itemToDelete = null"
                        @confirm="confirmDeleteItem"
                    />
                </FormDrawer>

            </div>

        </main>
    </AdminLayout>
</template>
