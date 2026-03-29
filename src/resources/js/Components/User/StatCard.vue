<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    helper: {
        type: String,
        default: '',
    },
    tone: {
        type: String,
        default: 'primary',
    },
})

const toneClasses = {
    primary: 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300',
    success: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
    warning: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    slate: 'bg-slate-100 text-slate-700 dark:bg-slate-700/60 dark:text-slate-200',
}
</script>

<template>
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm shadow-slate-200/60 transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                    {{ title }}
                </p>
                <p class="mt-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white">
                    {{ value }}
                </p>
            </div>

            <div :class="toneClasses[tone] || toneClasses.primary" class="inline-flex rounded-full px-3 py-1 text-xs font-semibold">
                {{ helper || 'Resumen' }}
            </div>
        </div>

        <p v-if="$slots.default" class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300">
            <slot />
        </p>
    </div>
</template>
