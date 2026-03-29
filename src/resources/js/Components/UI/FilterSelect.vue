<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { ArrowsUpDownIcon, ChevronDownIcon, FunnelIcon } from '@heroicons/vue/24/outline'

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
const rootRef = ref(null)
const isOpen = ref(false)

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
const selectedOption = computed(() => {
    const selected = resolvedOptions.value.find((option) => String(option.value) === String(props.modelValue))
    return selected ?? resolvedOptions.value[0] ?? null
})

const toggleMenu = () => {
    if (props.disabled) {
        return
    }

    isOpen.value = !isOpen.value
}

const closeMenu = () => {
    isOpen.value = false
}

const selectOption = (option) => {
    emit('update:modelValue', option.value)
    closeMenu()
}

const onClickOutside = (event) => {
    if (!rootRef.value || rootRef.value.contains(event.target)) {
        return
    }

    closeMenu()
}

const onEscape = (event) => {
    if (event.key === 'Escape') {
        closeMenu()
    }
}

onMounted(() => {
    document.addEventListener('click', onClickOutside)
    document.addEventListener('keydown', onEscape)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside)
    document.removeEventListener('keydown', onEscape)
})
</script>

<template>
    <div ref="rootRef" :class="['relative', containerClass]">
        <component :is="iconComponent" class="pointer-events-none absolute left-3 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <button
            type="button"
            :disabled="disabled"
            @click="toggleMenu"
            :class="[
                'flex w-full items-center justify-between gap-2 rounded-base border border-default-medium bg-white py-2.5 pl-9 pr-3 text-left text-sm font-medium text-heading shadow-xs transition focus:border-brand focus:ring-brand dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
                disabled ? 'cursor-not-allowed text-fg-disabled opacity-70' : 'cursor-pointer',
                selectClass,
            ]"
        >
            <span class="truncate">{{ selectedOption?.label ?? '-' }}</span>
            <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-500 transition-transform" :class="isOpen ? 'rotate-180' : ''" />
        </button>

        <div
            v-if="isOpen"
            class="absolute left-0 right-0 top-[calc(100%+2px)] z-30 overflow-hidden rounded-base border border-default-medium bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900"
        >
            <ul class="max-h-64 overflow-auto py-1">
                <li v-for="option in resolvedOptions" :key="option.value">
                    <button
                        type="button"
                        class="block w-full px-3 py-2 text-left text-sm text-heading transition hover:bg-slate-100 dark:text-slate-100 dark:hover:bg-slate-800"
                        :class="String(option.value) === String(modelValue) ? 'bg-slate-100 dark:bg-slate-800' : ''"
                        @click="selectOption(option)"
                    >
                        {{ option.label }}
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>
