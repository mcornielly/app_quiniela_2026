<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/Layouts/AppLayout3.vue'
import Header from '@/Layouts/Partials/Header.vue'
import SearchBar from '@/Layouts/Partials/SearchBar.vue'
import DataTable from '@/Components/Admin/Table/DataTable.vue'
import CreateDrawer from '@/Components/Admin/Table/CreateDrawer.vue' // A crear
import EditDrawer from '@/Components/Admin/Table/EditDrawer.vue' // A crear

const title = 'Teams'

const props = defineProps({
    filters: { type: Object, required: true }
})

// Estado reactivo
const teams = ref([])
const loading = ref(false)
const searchQuery = ref('')
const showCreateDrawer = ref(false)
const selectedTeam = ref(null)

// Métodos para manejar acciones
const fetchTeams = async () => {
    loading.value = true
    try {
        // Aquí iría tu llamada API
        // const response = await axios.get('/api/teams')
        // teams.value = response.data

        // Datos de ejemplo
        teams.value = [
            { id: 1, name: 'Mexico', group: { name: 'A' }, type: 'national', group_position: 1 },
            { id: 2, name: 'Sweden', group: { name: 'A' }, type: 'national', group_position: 2 },
        ]
    } catch (error) {
        console.error('Error fetching teams:', error)
    } finally {
        loading.value = false
    }
}

// Handlers para acciones de la tabla
const handleView = (row) => {
    console.log('View:', row)
    // router.visit(`/admin/teams/${row.id}`)
}

const handleEdit = (row) => {
    selectedTeam.value = row
    // Aquí abrirías el drawer de edición
    console.log('Edit:', row)
}

const handleDelete = (row) => {
    console.log('Delete:', row)
    // Aquí abrirías el drawer de confirmación de eliminación
}

const handleSearch = (query) => {
    searchQuery.value = query
    console.log('Searching:', query)
    // Aquí iría la lógica de búsqueda
}

const handleCreate = (newTeam) => {
    console.log('Create:', newTeam)
    teams.value.push(newTeam)
    showCreateDrawer.value = false
}

const handleUpdate = (updatedTeam) => {
    console.log('Update:', updatedTeam)
    const index = teams.value.findIndex(t => t.id === updatedTeam.id)
    if (index !== -1) {
        teams.value[index] = updatedTeam
    }
    selectedTeam.value = null
}

const handleDeleteConfirm = (teamToDelete) => {
    console.log('Delete confirm:', teamToDelete)
    teams.value = teams.value.filter(t => t.id !== teamToDelete.id)
    selectedTeam.value = null
}

// Cargar datos al montar el componente
onMounted(() => {
    fetchTeams()
})
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
                            :title="title"
                        />
                        <button
                            @click="showCreateDrawer = true"
                            class="text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-primary-600 dark:hover:bg-primary-700 focus:outline-none dark:focus:ring-primary-800"
                            type="button"
                        >
                            Add new {{ title }}
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
                            <DataTable
                                :rows="teams"
                                @view="handleView"
                                @edit="handleEdit"
                                @delete="handleDelete"
                            />
                        </div>
                    </div>
                </div>

                <!-- Mensaje cuando no hay datos -->
                <div v-if="teams.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
                    No teams found.
                </div>

                <!-- Componentes de drawers -->
                <CreateDrawer
                    :show="showCreateDrawer"
                    @close="showCreateDrawer = false"
                    @create="handleCreate"
                />

                <EditDrawer
                    v-if="selectedTeam"
                    :show="!!selectedTeam"
                    :team="selectedTeam"
                    @close="selectedTeam = null"
                    @update="handleUpdate"
                />
            </div>
        </main>
    </AdminLayout>
</template>
