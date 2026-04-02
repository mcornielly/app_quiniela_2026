<script setup>
import { computed } from 'vue'
import { imageUrl } from '@/Utils/image'

const props = defineProps({
    match: {
        type: Object,
        required: true,
    },
    showStatus: {
        type: Boolean,
        default: false,
    },
})

const statusClass = (status) => {
    if (status === 'finished') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
    if (status === 'in_progress') return 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'
    return 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200'
}

const metaLabel = computed(() => {
    const stage = props.match?.stage_label || 'Partido'
    const date = props.match?.match_date_label || '--'
    const time = props.match?.match_time_label || '--:--'

    return `${stage} · ${date} ${time}`
})
</script>

<template>
    <article class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-900/70">
        <div class="relative flex min-h-[22px] items-center justify-center">
            <p class="text-center text-xs text-slate-500 dark:text-slate-400">{{ metaLabel }}</p>
            <span
                v-if="showStatus"
                :class="statusClass(match.status)"
                class="absolute right-0 rounded-full px-2 py-0.5 text-[10px] font-semibold"
            >
                {{ match.status_label }}
            </span>
        </div>

        <div class="mt-2 grid grid-cols-[1fr_auto_1fr] items-center gap-2 overflow-visible">
            <div class="flex items-center gap-3 overflow-visible">
                <img
                    v-if="imageUrl(match.home_team?.shield_url || match.home_team?.flag_url)"
                    :src="imageUrl(match.home_team?.shield_url || match.home_team?.flag_url)"
                    :alt="match.home_team?.name"
                    class="relative -top-[5px] h-16 w-16 shrink-0 rounded object-contain mix-blend-multiply dark:mix-blend-normal"
                >
                <span
                    v-else
                    class="relative -top-[5px] flex h-16 w-16 shrink-0 items-center justify-center rounded bg-slate-200 text-xs font-bold dark:bg-slate-700"
                >
                    {{ match.home_team?.country_code || match.home_team?.code || '---' }}
                </span>
                <p class="text-sm font-semibold leading-tight text-slate-900 dark:text-white">
                    {{ match.home_team?.name || 'TBD' }}
                </p>
            </div>

            <p class="px-2 text-xl font-black tracking-widest text-slate-900 dark:text-white">
                {{ Number.isInteger(match.home_score) ? match.home_score : '-' }}
                :
                {{ Number.isInteger(match.away_score) ? match.away_score : '-' }}
            </p>

            <div class="flex items-center justify-end gap-3 overflow-visible">
                <p class="text-right text-sm font-semibold leading-tight text-slate-900 dark:text-white">
                    {{ match.away_team?.name || 'TBD' }}
                </p>
                <img
                    v-if="imageUrl(match.away_team?.shield_url || match.away_team?.flag_url)"
                    :src="imageUrl(match.away_team?.shield_url || match.away_team?.flag_url)"
                    :alt="match.away_team?.name"
                    class="relative -top-[5px] h-16 w-16 shrink-0 rounded object-contain mix-blend-multiply dark:mix-blend-normal"
                >
                <span
                    v-else
                    class="relative -top-[5px] flex h-16 w-16 shrink-0 items-center justify-center rounded bg-slate-200 text-xs font-bold dark:bg-slate-700"
                >
                    {{ match.away_team?.country_code || match.away_team?.code || '---' }}
                </span>
            </div>
        </div>
    </article>
</template>
