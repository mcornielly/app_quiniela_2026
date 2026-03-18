<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '4xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['close'])
const dialog = ref()
const showSlot = ref(props.show)

watch(
    () => props.show,
    () => {
        if (props.show) {
            document.body.style.overflow = 'hidden'
            showSlot.value = true
            dialog.value?.showModal()
            return
        }

        document.body.style.overflow = ''

        window.setTimeout(() => {
            dialog.value?.close()
            showSlot.value = false
        }, 200)
    },
)

const close = () => {
    if (props.closeable) {
        emit('close')
    }
}

const closeOnEscape = (event) => {
    if (event.key === 'Escape' && props.show) {
        event.preventDefault()
        close()
    }
}

onMounted(() => document.addEventListener('keydown', closeOnEscape))

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape)
    document.body.style.overflow = ''
})

const maxWidthClass = computed(() => ({
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
    '5xl': 'max-w-5xl',
}[props.maxWidth] ?? 'max-w-4xl'))
</script>

<template>
    <dialog
        ref="dialog"
        class="z-50 m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent"
    >
        <div class="fixed inset-0 z-50 flex min-h-full items-center justify-center overflow-y-auto overflow-x-hidden p-4 md:inset-0 flowbite-modal-scroll">
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <button
                    v-show="show"
                    type="button"
                    class="fixed inset-0 bg-slate-950/55 backdrop-blur-[2px]"
                    aria-label="Close modal overlay"
                    @click="close"
                />
            </Transition>

            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div
                    v-show="show"
                    class="relative z-10 my-8 w-full max-h-full"
                    :class="maxWidthClass"
                >
                    <div class="relative rounded-[1.75rem] border border-slate-200/80 bg-white p-4 shadow-sm md:p-6 dark:border-slate-800 dark:bg-slate-900">
                        <slot v-if="showSlot" />
                    </div>
                </div>
            </Transition>
        </div>
    </dialog>
</template>

<style scoped>
.flowbite-modal-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.7) transparent;
}

.flowbite-modal-scroll::-webkit-scrollbar {
    width: 10px;
}

.flowbite-modal-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.flowbite-modal-scroll::-webkit-scrollbar-thumb {
    background-color: rgba(148, 163, 184, 0.7);
    border-radius: 9999px;
    border: 2px solid transparent;
    background-clip: content-box;
}
</style>
