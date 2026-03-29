<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
})
</script>

<template>
    <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 px-5 py-4 dark:border-slate-800">
            <div>
                <h2 class="text-lg font-semibold text-slate-950 dark:text-white">
                    {{ title }}
                </h2>
                <p v-if="subtitle" class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ subtitle }}
                </p>
            </div>

            <slot name="actions" />
        </div>

        <div class="p-5">
            <slot />
        </div>
    </section>
</template>
