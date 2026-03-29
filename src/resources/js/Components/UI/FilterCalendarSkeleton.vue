<script setup>
const props = defineProps({
    rows: {
        type: Number,
        default: 4,
    },
})
</script>

<template>
    <div class="mt-6 space-y-6" aria-live="polite" aria-busy="true">
        <section v-for="index in rows" :key="index" class="space-y-3">
            <div class="flex items-center gap-3 border-b border-slate-200 pb-2 dark:border-slate-800">
                <span class="h-9 w-9 animate-pulse rounded-lg bg-slate-200 dark:bg-slate-700" />
                <span class="h-4 w-56 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
            </div>

            <article class="animate-pulse rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/75">
                <div class="grid grid-cols-1 items-center gap-4 xl:grid-cols-[170px_1fr_180px]">
                    <div class="space-y-2">
                        <div class="h-3 w-20 rounded bg-slate-200 dark:bg-slate-700" />
                        <div class="h-8 w-14 rounded bg-slate-200 dark:bg-slate-700" />
                    </div>

                    <div class="space-y-2">
                        <div class="mx-auto h-4 w-44 rounded bg-slate-200 dark:bg-slate-700" />
                        <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">
                            <div class="h-5 w-36 justify-self-end rounded bg-slate-200 dark:bg-slate-700" />
                            <div class="h-12 w-24 rounded-xl bg-slate-200 dark:bg-slate-700" />
                            <div class="h-5 w-36 rounded bg-slate-200 dark:bg-slate-700" />
                        </div>
                    </div>

                    <div class="flex flex-col items-start gap-2 xl:items-end">
                        <div class="h-3 w-20 rounded bg-slate-200 dark:bg-slate-700" />
                        <div class="h-6 w-24 rounded-full bg-slate-200 dark:bg-slate-700" />
                    </div>
                </div>
            </article>
        </section>
    </div>
</template>
