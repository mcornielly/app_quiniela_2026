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
import StadiumForm from '@/Components/Admin/FormDrawer/StadiumForm.vue'
import ImageGalleryModal from '@/Components/Admin/Modal/ImageGalleryModal.vue'
import debounce from 'lodash/debounce'
import 'flowbite'

const title = 'Stadiums'

const props = defineProps({
    filters: Object,
    stadiums: Object,
    
})

// Column configuration (can be adjusted after generation)
const columns = [
        { key: 'api_venue_id', label: 'Api Venue Id' },
    { key: 'name', label: 'Name' },
    { key: 'city', label: 'City' },
    { key: 'country', label: 'Country' },
    { key: 'address', label: 'Address' },
    { key: 'capacity', label: 'Capacity' },
    { key: 'surface', label: 'Surface' },
    { key: 'image_url', label: 'Image Url' },

]

// Reactive state
const loading = ref(false)
const searchQuery = ref(props.filters?.search || '')
const showCreateDrawer = ref(false)
const selectedItem = ref(null)
const selectedItems = ref([])
const itemToDelete = ref(null)
const showGalleryModal = ref(false)
const galleryImages = ref([])
const galleryTitle = ref('Stadium Gallery')

const actions = {
    show: false,
    gallery: true,
    edit: true,
    delete: true
}

const handleSearch = debounce((query) => {
    router.get(
        route('admin.stadiums.index'),
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
        notifyError("No stadiums selected")
        return
    }

    try{

        await confirmDelete(`Delete ${selectedItems.value.length} stadiums?`)

        router.delete(route('admin.stadiums.bulkDelete'), {
            data: {
                ids: selectedItems.value
            },
            preserveScroll: true,
            onSuccess: () => {
                notifySuccess("Stadiums deleted")
                selectedItems.value = []
                router.reload()
            }
        })

    }catch(e){
        console.log('Delete cancelled')
    }
}

const confirmDeleteItem = (item) => {

    router.delete(route('admin.stadiums.destroy', item.id), {
        preserveScroll: true,
        onSuccess: () => {
            notifySuccess('Stadium deleted')
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

const openGallery = (item) => {
    const images = []

    if (Array.isArray(item?.image_gallery)) {
        images.push(...item.image_gallery.filter((img) => typeof img === 'string' && img.trim() !== ''))
    }

    if (typeof item?.image_url === 'string' && item.image_url.trim() !== '' && !images.includes(item.image_url)) {
        images.unshift(item.image_url)
    }

    galleryImages.value = images
    galleryTitle.value = `${item?.name || 'Stadium'} Gallery`
    showGalleryModal.value = true
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

                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full align-middle">

                        <div class="overflow-hidden shadow">

                            <DataTable
                                :rows="stadiums.data"
                                :columns="columns"
                                :actions="actions"
                                @selection-change="handleSelectionChange"
                                @gallery="openGallery"
                                @edit="openEdit"
                                @delete="openDelete"
                            />

                            <div v-if="stadiums.data.length === 0" class="py-12 text-center text-gray-500 dark:text-gray-400">
                                No stadiums found.
                            </div>

                            <Pagination :meta="stadiums" />

                        </div>

                    </div>
                </div>

                <FormDrawer
                    title="Add Stadium"
                    :show="showCreateDrawer"
                    @close="showCreateDrawer = false"
                >
                    <StadiumForm
                        
                        @saved="handleCreate"
                        @close="showCreateDrawer = false"
                    />
                </FormDrawer>

                <FormDrawer
                    title="Update Stadium"
                    :show="!!selectedItem"
                    @close="selectedItem = null"
                >
                    <StadiumForm
                        
                        :stadium="selectedItem"
                        @close="selectedItem = null"
                    />
                </FormDrawer>

                <FormDrawer
                    :show="!!itemToDelete"
                    title="Delete Stadium"
                    @close="itemToDelete = null"
                >
                    <DeleteDrawer
                        :show="!!itemToDelete"
                        entityName="Stadium"
                        :item="itemToDelete"
                        @close="itemToDelete = null"
                        @confirm="confirmDeleteItem"
                    />
                </FormDrawer>

                <ImageGalleryModal
                    :show="showGalleryModal"
                    :title="galleryTitle"
                    :images="galleryImages"
                    @close="showGalleryModal = false"
                />

            </div>

        </main>
    </AdminLayout>
</template>
