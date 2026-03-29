<script setup>
const props = defineProps({
    rows: {
        type: Number,
        default: 4,
    },
})
</script>

<template>
    <div class="mt-4 space-y-3" aria-live="polite" aria-busy="true">
        <article
            v-for="index in rows"
            :key="index"
            class="animate-pulse rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/75"
        >
            <div class="grid grid-cols-1 items-center gap-4 lg:grid-cols-[170px_1fr_88px]">
                <div class="space-y-2">
                    <div class="h-3 w-20 rounded bg-slate-200 dark:bg-slate-700" />
                    <div class="h-3 w-24 rounded bg-slate-200 dark:bg-slate-700" />
                </div>

                <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">
                    <div class="flex justify-end">
                        <div class="h-5 w-40 rounded bg-slate-200 dark:bg-slate-700" />
                    </div>
                    <div class="h-12 w-24 rounded-xl bg-slate-200 dark:bg-slate-700" />
                    <div class="h-5 w-40 rounded bg-slate-200 dark:bg-slate-700" />
                </div>

                <div class="flex justify-end">
                    <div class="h-6 w-20 rounded-full bg-slate-200 dark:bg-slate-700" />
                </div>
            </div>
        </article>
    </div>
</template>
