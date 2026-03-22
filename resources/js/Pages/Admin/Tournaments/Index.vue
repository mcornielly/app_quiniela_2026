<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { confirmDelete, notifySuccess, notifyError } from '@/Utils/notify'
import { singular } from '@/Utils/format'
import DeleteDrawer from '@/Components/Admin/Drawer/DeleteDrawer.vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Header from '@/Layouts/Partials/Header.vue'
import SearchBar from '@/Layouts/Partials/SearchBar.vue'
import DataTable from '@/Components/Admin/Table/DataTable.vue'
import Pagination from '@/Components/Admin/Table/Pagination.vue'
import FormDrawer from '@/Components/Admin/Drawer/FormDrawer.vue'
import TournamentForm from '@/Components/Admin/FormDrawer/TournamentForm.vue'
import debounce from 'lodash/debounce'
import 'flowbite'

const title = 'Tournaments'

const props = defineProps({
    filters: Object,
    tournaments: Object
})

// Column configuration (can be adjusted after generation)
const columns = [
    { key: 'name', label: 'Name' },
    { key: 'year', label: 'Year' },
    { key: 'host_countries', label: 'Host Countries' },
    { key: 'logo', label: 'Logo' },
    { key: 'deadline_at', label: 'Deadline At' },
    { key: 'status', label: 'Status' },
    { key: 'type', label: 'Type' },

]

// Reactive state
const loading = ref(false)
const searchQuery = ref(props.filters?.search || '')
const showCreateDrawer = ref(false)
const selectedItem = ref(null)
const selectedItems = ref([])
const itemToDelete = ref(null)

const actions = {
    show: true,
    edit: true,
    delete: true,
    new: true,
    checkbox : true,
}

const handleSearch = debounce((query) => {
    router.get(
        route('admin.tournaments.index'),
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
        notifyError("No tournaments selected")
        return
    }

    try{

        await confirmDelete(`Delete ${selectedItems.value.length} tournaments?`)

        router.delete(route('admin.tournaments.bulkDelete'), {
            data: {
                ids: selectedItems.value
            },
            preserveScroll: true,
            onSuccess: () => {
                notifySuccess("Tournaments deleted")
                selectedItems.value = []
                router.reload()
            }
        })

    }catch(e){
        console.log('Delete cancelled')
    }
}

const confirmDeleteItem = (item) => {

    router.delete(route('admin.tournaments.destroy', item.id), {
        preserveScroll: true,
        onSuccess: () => {
            notifySuccess('Tournament deleted')
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

const openParticipants = (item) => {
    router.visit(route('admin.tournaments.participants.index', item.id))
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
                            v-if = "actions.new"
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

                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full align-middle">

                        <div class="overflow-hidden shadow">

                            <DataTable
                                :rows="tournaments.data"
                                :columns="columns"
                                :actions="actions"
                                @selection-change="handleSelectionChange"
                                @view="openParticipants"
                                @edit="openEdit"
                                @delete="openDelete"
                            />

                            <div v-if="tournaments.data.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
                                No tournaments found.
                            </div>

                            <Pagination :meta="tournaments" />

                        </div>

                    </div>
                </div>

                <FormDrawer
                    title="Add Tournament"
                    :show="showCreateDrawer"
                    @close="showCreateDrawer = false"
                >
                    <TournamentForm
                        @saved="handleCreate"
                        @close="showCreateDrawer = false"
                    />
                </FormDrawer>

                <FormDrawer
                    title="Update Tournament"
                    :show="!!selectedItem"
                    @close="selectedItem = null"
                >
                    <TournamentForm
                        :tournament="selectedItem"
                        @close="selectedItem = null"
                    />
                </FormDrawer>

                <FormDrawer
                    :show="!!itemToDelete"
                    title="Delete Tournament"
                    @close="itemToDelete = null"
                >
                    <DeleteDrawer
                        :show="!!itemToDelete"
                        entityName="Tournament"
                        :item="itemToDelete"
                        @close="itemToDelete = null"
                        @confirm="confirmDeleteItem"
                    />
                </FormDrawer>

            </div>

        </main>
    </AdminLayout>
</template>
