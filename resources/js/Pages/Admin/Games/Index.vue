<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { confirmDelete, notifySuccess, notifyError } from '@/Utils/notify'
import { formatDate } from '@/Utils/format'
import { singular } from '@/Utils/format'
import DeleteDrawer from '@/Components/Admin/Drawer/DeleteDrawer.vue'
import AdminLayout from '@/Layouts/AppLayout3.vue'
import Header from '@/Layouts/Partials/Header.vue'
import SearchBar from '@/Layouts/Partials/SearchBar.vue'
import DataTable from '@/Components/Admin/Table/DataTable.vue'
import Pagination from '@/Components/Admin/Table/Pagination.vue'
import FormDrawer from '@/Components/Admin/Drawer/FormDrawer.vue'
import GameForm from '@/Components/Admin/FormDrawer/GameForm.vue'
import debounce from 'lodash/debounce'
import 'flowbite'

const title = 'Games'

const props = defineProps({
    filters: Object,
    games: Object,
    tournaments: Array,
    teams: Array
})

// Column configuration (can be adjusted after generation)
const columns = [
    // { key: 'tournament', label: 'Tournament' },
    { key: 'match_number', label: 'Match Number' },
    { key: 'group_name', label: 'Group' },
    { key: 'stage', label: 'Stage' },
    { key: 'home_team', label: 'Home Team' },
    { key: 'away_team', label: 'Away Team' },
    // { key: 'home_slot', label: 'Home Slot' },
    // { key: 'away_slot', label: 'Away Slot' },
    // { key: 'home_score', label: 'Home Score' },
    // { key: 'away_score', label: 'Away Score' },
    // { key: 'winner_team_id', label: 'Winner Team Id' },
    { key: 'venue', label: 'Venue' },
    { key: 'match_date', label: 'Match Date', format: formatDate },
    { key: 'match_time', label: 'Match Time' },

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
    delete: false,
    new: false,
    checkbox : false,
}

const handleSearch = debounce((query) => {
    router.get(
        route('admin.games.index'),
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
        notifyError("No games selected")
        return
    }

    try{

        await confirmDelete(`Delete ${selectedItems.value.length} games?`)

        router.delete(route('admin.games.bulkDelete'), {
            data: {
                ids: selectedItems.value
            },
            preserveScroll: true,
            onSuccess: () => {
                notifySuccess("Games deleted")
                selectedItems.value = []
                router.reload()
            }
        })

    }catch(e){
        console.log('Delete cancelled')
    }
}

const confirmDeleteItem = (item) => {

    router.delete(route('admin.games.destroy', item.id), {
        preserveScroll: true,
        onSuccess: () => {
            notifySuccess('Game deleted')
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
                            v-if="actions.new"
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
                                :rows="games.data"
                                :columns="columns"
                                :actions="actions"
                                @selection-change="handleSelectionChange"
                                @edit="openEdit"
                                @delete="openDelete"
                            />

                            <div v-if="games.data.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
                                No games found.
                            </div>

                            <Pagination :meta="games" />

                        </div>

                    </div>
                </div>

                <FormDrawer
                    title="Add Game"
                    :show="showCreateDrawer"
                    @close="showCreateDrawer = false"
                >
                    <GameForm
                        :tournaments="tournaments"
                        :teams="teams"
                        @saved="handleCreate"
                        @close="showCreateDrawer = false"
                    />
                </FormDrawer>

                <FormDrawer
                    title="Update Game"
                    :show="!!selectedItem"
                    @close="selectedItem = null"
                >
                    <GameForm
                        :tournaments="tournaments"
                        :teams="teams"
                        :game="selectedItem"
                        @close="selectedItem = null"
                    />
                </FormDrawer>

                <FormDrawer
                    :show="!!itemToDelete"
                    title="Delete Game"
                    @close="itemToDelete = null"
                >
                    <DeleteDrawer
                        :show="!!itemToDelete"
                        entityName="Game"
                        :item="itemToDelete"
                        @close="itemToDelete = null"
                        @confirm="confirmDeleteItem"
                    />
                </FormDrawer>

            </div>

        </main>
    </AdminLayout>
</template>
