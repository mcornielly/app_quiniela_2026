<script setup>
import { computed } from 'vue'
import { ArrowsUpDownIcon, FunnelIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    containerClass: {
        type: String,
        default: '',
    },
    selectClass: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    icon: {
        type: String,
        default: 'filter',
    },
})

const emit = defineEmits(['update:modelValue'])

const resolvedOptions = computed(() => props.options.map((option) => {
    if (option && typeof option === 'object' && 'value' in option) {
        return {
            value: option.value,
            label: option.label ?? String(option.value),
        }
    }

    return {
        value: option,
        label: String(option),
    }
}))

const iconComponent = computed(() => (props.icon === 'sort' ? ArrowsUpDownIcon : FunnelIcon))

const onChange = (event) => {
    emit('update:modelValue', event.target.value)
}
</script>

<template>
    <div :class="['relative', containerClass]">
        <component :is="iconComponent" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <select
            :value="modelValue"
            :disabled="disabled"
            :class="[
                'block rounded-base border border-default-medium bg-white py-2.5 pl-9 pr-3 text-sm font-medium text-heading shadow-xs focus:border-brand focus:ring-brand dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
                selectClass,
            ]"
            @change="onChange"
        >
            <option v-for="option in resolvedOptions" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
    </div>
</template>
