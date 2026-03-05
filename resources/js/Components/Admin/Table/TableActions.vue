<script setup>
import { PencilIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline'
import { ref, reactive, watch } from 'vue'

const props = defineProps({
    row: {
        type: Object,
        required: true
    },
    entityName: {
        type: String,
        default: 'team'
    }
})

const emit = defineEmits(['edit', 'delete', 'view'])

const showEditDrawer = ref(false)
const showDeleteDrawer = ref(false)

/*
|--------------------------------------------------------------------------
| Form local (NO modificar props directamente)
|--------------------------------------------------------------------------
*/

const form = reactive({
    name: '',
    group_id: '',
    type: '',
    group_position: ''
})

watch(
    () => props.row,
    (team) => {
        if (!team) return

        form.name = team.name
        form.group_id = team.group_id
        form.type = team.type
        form.group_position = team.group_position
    },
    { immediate: true }
)

function submitEdit() {
    emit('edit', { ...form, id: props.row.id })
    showEditDrawer.value = false
}
</script>

<template>
    <div class="flex items-center space-x-2">
        <!-- View Button -->
        <button @click="$emit('view', row)"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700"
            :title="'View ' + row.name">
            <EyeIcon class="w-4 h-4" />
            <span class="sr-only">View</span>
        </button>

        <!-- Edit Button - Abre el drawer -->
        <button @click="showEditDrawer = true"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white rounded-lg bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
            </svg>
            Update
        </button>

        <!-- Delete Button - Abre el drawer -->
        <button @click="showDeleteDrawer = true"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-red-700 rounded-lg hover:bg-red-800 focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            Delete item
        </button>
    </div>

    <!-- Drawers con transiciones suaves -->
    <Teleport to="body">
        <!-- Backdrop unificado -->
        <Transition
            enter-active-class="transition-opacity duration-300 ease-in-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300 ease-in-out"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showEditDrawer || showDeleteDrawer"
                @click="showEditDrawer = false; showDeleteDrawer = false"
                class="fixed inset-0 z-[60] bg-gray-900 bg-opacity-50 dark:bg-opacity-80">
            </div>
        </Transition>

        <!-- Edit Drawer -->
        <Transition
            enter-active-class="transform transition-transform duration-300 ease-in-out"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition-transform duration-300 ease-in-out"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div v-if="showEditDrawer"
                class="fixed top-0 right-0 z-[70] w-full h-screen max-w-xs p-4 overflow-y-auto bg-white shadow-xl dark:bg-gray-800"
                tabindex="-1">
                <!-- Header del drawer -->
                <div class="flex items-center justify-between mb-6">
                    <h5 class="inline-flex items-center text-sm font-semibold text-gray-500 uppercase dark:text-gray-400">
                        Update {{ entityName }}
                    </h5>
                    <button @click="showEditDrawer = false"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        <span class="sr-only">Close menu</span>
                    </button>
                </div>

                <!-- Formulario de edición -->
                <form @submit.prevent="submitEdit">
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Team Name</label>
                            <input type="text"
                                id="name"
                                v-model="form.name"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                        </div>

                        <div>
                            <label for="group" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Group</label>
                            <select id="group" v-model="form.group_id"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                <option v-for="g in 'ABCDEFGHIJKL'" :key="g" :value="g">Group {{ g }}</option>
                            </select>
                        </div>

                        <div>
                            <label for="type" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Type</label>
                            <select id="type" v-model="form.type"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                <option value="national">National</option>
                                <option value="club">Club</option>
                            </select>
                        </div>

                        <div>
                            <label for="position" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Position</label>
                            <input type="number"
                                id="position"
                                v-model="form.group_position"
                                min="1" max="4"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="bottom-0 left-0 flex justify-center w-full pb-4 mt-4 space-x-4 sm:absolute sm:px-4 sm:mt-0">
                        <button type="submit" class="w-full justify-center text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                            Update
                        </button>
                        <button type="button" @click="showDeleteDrawer = true"
                            class="w-full justify-center text-red-600 inline-flex items-center hover:text-white border border-red-600 hover:bg-red-600 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:border-red-500 dark:text-red-500 dark:hover:text-white dark:hover:bg-red-600 dark:focus:ring-red-900">
                            <svg aria-hidden="true" class="w-5 h-5 mr-1 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </Transition>

        <!-- Delete Drawer -->
        <Transition
            enter-active-class="transform transition-transform duration-300 ease-in-out"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition-transform duration-300 ease-in-out"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div v-if="showDeleteDrawer"
                class="fixed top-0 right-0 z-[70] w-full h-screen max-w-xs p-4 overflow-y-auto bg-white shadow-xl dark:bg-gray-800"
                tabindex="-1">
                <div class="flex items-center justify-between mb-6">
                    <h5 class="inline-flex items-center text-sm font-semibold text-gray-500 uppercase dark:text-gray-400">Delete {{ entityName }}</h5>
                    <button @click="showDeleteDrawer = false"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        <span class="sr-only">Close menu</span>
                    </button>
                </div>

                <div class="flex flex-col items-center text-center">
                    <svg class="w-16 h-16 mb-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>

                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                        Delete {{ entityName }}
                    </h3>

                    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                        Are you sure you want to delete <span class="font-semibold text-gray-900 dark:text-white">{{ row.name }}</span>?
                    </p>

                    <div class="flex w-full space-x-3">
                        <button @click="$emit('delete', row); showDeleteDrawer = false"
                            class="flex-1 text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-3 py-2.5 text-center dark:focus:ring-red-900">
                            Yes, I'm sure
                        </button>
                        <button @click="showDeleteDrawer = false"
                            class="flex-1 text-gray-900 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 border border-gray-200 font-medium rounded-lg text-sm px-3 py-2.5 text-center dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            No, cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
