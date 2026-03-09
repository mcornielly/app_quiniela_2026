<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { confirmDelete, notifySuccess, notifyError } from '@/Utils/notify'
import { singular } from '@/Utils/format'
import DeleteDrawer from '@/Components/Admin/Drawer/DeleteDrawer.vue'
import AdminLayout from '@/Layouts/AppLayout3.vue'
import Header from '@/Layouts/Partials/Header.vue'
import SearchBar from '@/Layouts/Partials/SearchBar.vue'
import DataTable from '@/Components/Admin/Table/DataTable.vue'
import Pagination from '@/Components/Admin/Table/Pagination.vue'
import FormDrawer from '@/Components/Admin/Drawer/FormDrawer.vue'
import TeamForm from '@/Components/Admin/FormDrawer/TeamForm.vue'
import debounce from 'lodash/debounce'
import 'flowbite'

const title = 'Teams'

const props = defineProps({
    filters: Object,
    teams: Object,
    groups: Array,
    types: Array
})

// Definir columnas de la tabla
const columns = [
    { key: 'name', label: 'TEAM' },
    { key: 'group', label: 'GROUP' },
    { key: 'type', label: 'TYPE' },
    { key: 'group_position', label: 'POSITION' }
]

// Estado reactivo
const loading = ref(false)
const searchQuery = ref(props.filters?.search || '')
const showCreateDrawer = ref(false)
const selectedTeam = ref(null)
const selectedTeams = ref([])
const teamToDelete = ref(null)

// Acciones para la tabla
const actions = {
    show: false,
    edit: true,
    delete: true,
    new: true,
    checkbox : true,
}

// Función para manejar la búsqueda con debounce
const handleSearch = debounce((query) => {
    router.get(
        route('admin.teams.index'),
        { search: query },
        { preserveState: true, replace: true }
    )

}, 400)

// Funciones para manejar acciones de crear, editar y eliminar
const handleToolbarAction = (action) => {
    if(action === 'deleteSelected'){
        console.log('toolbar action:', action)
        deleteSelected()
    }
}

// Eliminar equipos seleccionados
const deleteSelected = async () => {
    if(selectedTeams.value.length === 0){
        notifyError("No teams selected")
        return
    }
    try{
        await confirmDelete(`Delete ${selectedTeams.value.length} teams?`)
        router.delete(route('admin.teams.bulkDelete'), {
            data: {
                ids: selectedTeams.value
            },
            preserveScroll: true,
            onSuccess: () => {
                notifySuccess("Teams deleted")
                selectedTeams.value = []
                router.reload() //
            }
        })
    }catch(e){
        console.log('Delete cancelled')
        // cancelado
    }
}

// Eliminar un solo equipo
const confirmDeleteTeam = (team) => {

    router.delete(route('admin.teams.destroy', team.id), {
        preserveScroll: true,
        onSuccess: () => {
            notifySuccess('Team deleted')
            teamToDelete.value = null
        }
    })

}

const handleSelectionChange = (ids) => {
    selectedTeams.value = ids
}

const openEdit = (team) => {
    selectedTeam.value = team
}

const openDelete = (team) => {
    teamToDelete.value = team
}

const handleCreate = () => {
    router.reload()
}

</script>

<template>
    <AdminLayout :title="title">
        <main>
            <!-- Header con título y botón de crear -->
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

            <!-- Indicador de carga -->
            <div v-if="loading" class="flex justify-center py-12">
                <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <!-- Tabla de datos -->
            <div v-else class="flex flex-col">
                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full align-middle">
                        <div class="overflow-hidden shadow">
                            <!-- DataTable -->
                            <DataTable
                                :rows="teams.data"
                                :columns="columns"
                                :actions="actions"
                                @selection-change="handleSelectionChange"
                                @edit="openEdit"
                                @delete="openDelete"
                            />
                            <!-- Mensaje cuando no hay datos -->
                            <div v-if="teams.data.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
                                No teams found.
                            </div>

                            <Pagination :meta="teams" />
                        </div>
                    </div>
                </div>

                <!-- Drawer para crear nuevo equipo -->
                <FormDrawer
                    title="Add Teams"
                    :show="showCreateDrawer"
                    @close="showCreateDrawer = false"
                >

                    <TeamForm
                        :groups="groups"
                        :types="types"
                        @saved="handleCreate"
                        @close="showCreateDrawer = false"
                    />
                </FormDrawer>

                <!-- Drawer para actualizar equipo -->
                <FormDrawer
                    title="Update Team"
                    :show="!!selectedTeam"
                    @close="selectedTeam = null"
                >
                    <TeamForm
                        :team="selectedTeam"
                        :groups="groups"
                        :types="types"
                        @close="selectedTeam = null"
                    />
                </FormDrawer>

                <!-- Drawer para confirmar eliminación de equipo -->
                <FormDrawer
                    :show="!!teamToDelete"
                    title="Delete Team"
                    @close="teamToDelete = null"
                >
                    <DeleteDrawer
                        :show="!!teamToDelete"
                        entityName="Team"
                        :item="teamToDelete"
                        @close="teamToDelete = null"
                        @confirm="confirmDeleteTeam"
                    />
                </FormDrawer>
            </div>
        </main>
    </AdminLayout>
</template>
