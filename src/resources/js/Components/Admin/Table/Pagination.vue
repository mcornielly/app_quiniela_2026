<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    meta: Object
})
</script>

<template>
    <div v-if="meta" class="sticky bottom-0 right-0 items-center w-full p-4 bg-white border-t border-gray-200 sm:flex sm:justify-between dark:bg-gray-800 dark:border-gray-700">
        <!-- LEFT SIDE -->
        <div class="flex items-center mb-4 sm:mb-0">

            <!-- Previous arrow -->
            <Link
                v-if="meta.prev_page_url"
                :href="meta.prev_page_url"
                class="inline-flex justify-center p-1 text-gray-500 rounded cursor-pointer hover:text-gray-900 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white"
            >
                <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                    d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                    clip-rule="evenodd"/>
                </svg>
            </Link>

            <!-- Next arrow -->
            <Link
                v-if="meta.next_page_url"
                :href="meta.next_page_url"
                class="inline-flex justify-center p-1 mr-2 text-gray-500 rounded cursor-pointer hover:text-gray-900 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white"
            >
                <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                    clip-rule="evenodd"/>
                </svg>
            </Link>

            <!-- Showing info -->
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                Showing
                <span class="font-semibold text-gray-900 dark:text-white">
                    {{ meta.from }}-{{ meta.to }}
                </span>
                of
                <span class="font-semibold text-gray-900 dark:text-white">
                    {{ meta.total }}
                </span>
            </span>

        </div>

        <!-- RIGHT SIDE -->
        <div class="flex items-center space-x-3">

            <Link
                v-if="meta.prev_page_url"
                :href="meta.prev_page_url"
                class="inline-flex items-center justify-center flex-1 px-3 py-2 text-sm font-medium text-white rounded-lg bg-primary-700 hover:bg-primary-800"
            >
                <svg class="w-5 h-5 mr-1 -ml-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                    d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                    clip-rule="evenodd"/>
                </svg>
                Previous
            </Link>

            <Link
                v-if="meta.next_page_url"
                :href="meta.next_page_url"
                class="inline-flex items-center justify-center flex-1 px-3 py-2 text-sm font-medium text-white rounded-lg bg-primary-700 hover:bg-primary-800"
            >
                Next
                <svg class="w-5 h-5 ml-1 -mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                    clip-rule="evenodd"/>
                </svg>
            </Link>
        </div>
    </div>
</template>
