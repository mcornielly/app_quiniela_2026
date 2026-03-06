<script setup>

const props = defineProps({
    show: Boolean,
    title: String
})

const emit = defineEmits(['close'])

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


        <!-- Drawer -->
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

                    <h5 class="text-sm font-semibold uppercase text-gray-500 dark:text-gray-400">
                        {{ title }}
                    </h5>

                    <button @click="$emit('close')">
                        ✕
                    </button>

                </div>

                <!-- FORM SLOT -->
                <slot />

            </div>
        </Transition>

    </Teleport>
</template>
