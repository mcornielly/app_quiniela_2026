<script setup>
const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Select date',
    },
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
    <div class="relative max-w-sm">
        <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
            <svg
                class="h-4 w-4 text-slate-400"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                fill="none"
                viewBox="0 0 24 24"
            >
                <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"
                />
            </svg>
        </div>
        <input
            :value="modelValue"
            type="date"
            class="block w-full rounded-xl border border-slate-300 bg-white py-2.5 pe-3 ps-10 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-primary-400 focus:ring-primary-300/40 dark:border-white/10 dark:bg-slate-800/80 dark:text-white dark:placeholder:text-slate-400 dark:focus:border-cyan-400 dark:focus:ring-cyan-400/30"
            :placeholder="placeholder"
            @input="emit('update:modelValue', $event.target.value)"
        >
    </div>
</template>
