<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    team: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close', 'update'])

const form = ref({ ...props.team })

watch(() => props.team, (newTeam) => {
    form.value = { ...newTeam }
}, { deep: true })
</script>

<template>
    <Teleport to="body">
        <div v-if="show"
            class="fixed top-0 right-0 z-50 w-full h-screen max-w-xs p-4 overflow-y-auto bg-white dark:bg-gray-800 transform transition-transform duration-300 translate-x-0"
            tabindex="-1">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <h5 class="inline-flex items-center text-sm font-semibold text-gray-500 uppercase dark:text-gray-400">
                    Update Team
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
            <form @submit.prevent="$emit('update', form); $emit('close')">
                <div class="space-y-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Team Name</label>
                        <input type="text"
                            v-model="form.name"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            required>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Group</label>
                        <select v-model="form.group.name"
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
                <div class="flex justify-center w-full pb-4 mt-6 space-x-4">
                    <button type="submit"
                        class="w-full justify-center text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700">
                        Update
                    </button>
                    <button type="button" @click="$emit('close')"
                        class="w-full justify-center text-gray-900 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 border border-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Backdrop -->
        <div v-if="show" @click="$emit('close')" class="fixed inset-0 z-40 bg-gray-900 bg-opacity-50 dark:bg-opacity-80"></div>
    </Teleport>
</template>
