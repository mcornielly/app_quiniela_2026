<script setup>

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    entityName: {
        type: String,
        default: 'item'
    },
    item: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['confirm','close'])

</script>

<template>

<Teleport to="body">

    <!-- Backdrop -->
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


    <!-- Delete Drawer -->
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
                    Delete {{ entityName }}
                </h5>

                <button
                    @click="$emit('close')"
                    class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">

                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0
                        111.414 1.414L11.414 10l4.293 4.293a1 1 0
                        01-1.414 1.414L10 11.414l-4.293
                        4.293a1 1 0
                        01-1.414-1.414L8.586 10
                        4.293 5.707a1 1 0
                        010-1.414z"
                        clip-rule="evenodd"/>
                    </svg>

                    <span class="sr-only">Close menu</span>

                </button>

            </div>


            <!-- Content -->
            <div class="flex flex-col items-center text-center">

                <svg class="w-16 h-16 mb-4 text-red-600"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0
                          11-18 0 9 9 0 0118 0z"/>

                </svg>


                <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                    Delete {{ entityName }}
                </h3>


                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">

                    Are you sure you want to delete

                    <span class="font-semibold text-gray-900 dark:text-white">
                        {{ item?.name }}
                    </span> ?

                </p>


                <div class="flex w-full space-x-3">

                    <button
                        @click="$emit('confirm', item)"
                        class="flex-1 text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-3 py-2.5 text-center dark:focus:ring-red-900">

                        Yes, I'm sure

                    </button>


                    <button
                        @click="$emit('close')"
                        class="flex-1 text-gray-900 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-primary-300 border border-gray-200 font-medium rounded-lg text-sm px-3 py-2.5 text-center dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">

                        No, cancel

                    </button>

                </div>

            </div>

        </div>

    </Transition>

</Teleport>

</template>
