<script setup>
import { ref } from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['close', 'create'])

const form = ref({
    name: '',
    group: 'A',
    type: 'national',
    group_position: 1
})
</script>

<template>
    <Teleport to="body">
        <!-- Backdrop con transición -->
        <Transition
            enter-active-class="transition-opacity duration-300 ease-in-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300 ease-in-out"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show"
                @click="$emit('close')"
                class="fixed inset-0 z-[60] bg-gray-900 bg-opacity-50 dark:bg-opacity-80">
            </div>
        </Transition>

        <!-- Drawer con transición -->
        <Transition
            enter-active-class="transform transition-transform duration-300 ease-in-out"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transform transition-transform duration-300 ease-in-out"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div v-if="show"
                class="fixed top-0 right-0 z-[70] w-full h-screen max-w-xs p-4 overflow-y-auto bg-white shadow-xl dark:bg-gray-800"
                tabindex="-1">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <h5 class="inline-flex items-center text-sm font-semibold text-gray-500 uppercase dark:text-gray-400">
                        Add New Team
                    </h5>
                    <button @click="$emit('close')"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        <span class="sr-only">Close menu</span>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="$emit('create', { ...form, id: Date.now() }); $emit('close')">
                    <div class="space-y-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Team Name</label>
                            <input type="text"
                                v-model="form.name"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                required>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Group</label>
                            <select v-model="form.group"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option v-for="g in 'ABCDEFGHIJKL'" :key="g" :value="g">Group {{ g }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Type</label>
                            <select v-model="form.type"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="national">National</option>
                                <option value="club">Club</option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Position</label>
                            <input type="number"
                                v-model="form.group_position"
                                min="1" max="4"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                required>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="bottom-0 left-0 flex justify-center w-full pb-4 space-x-4 md:px-4 md:absolute">
                        <button type="submit"
                            class="text-white w-full justify-center bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                            Add product
                        </button>
                        <button type="button" @click="$emit('close')"
                            class="inline-flex w-full justify-center text-gray-500 items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600">
                            <svg aria-hidden="true" class="w-5 h-5 -ml-1 sm:mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </Transition>
    </Teleport>
</template>
