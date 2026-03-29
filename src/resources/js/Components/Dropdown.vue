<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    width: {
        type: String,
        default: '48',
    },
    contentClasses: {
        type: String,
        default: 'py-1 bg-white rounded-md',
    },
    panelClasses: {
        type: String,
        default: '',
    },
});

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

const dropdown = ref(null);

const closeOnClickOutside = (e) => {
    if (open.value && dropdown.value && !dropdown.value.contains(e.target)) {
        open.value = false;
    }
};

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
    document.addEventListener('mousedown', closeOnClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.removeEventListener('mousedown', closeOnClickOutside);
});

const widthClass = computed(() => {
    const value = props.width.toString();

    return ({
        48: 'w-48',
        80: 'w-80',
        96: 'w-[min(24rem,calc(100vw-1rem))]',
        112: 'w-[min(28rem,calc(100vw-1rem))]',
    }[value] ?? value);
});

const alignmentClasses = computed(() => {
    if (props.align === 'left') {
        return 'origin-top-left left-0';
    } else if (props.align === 'center') {
        return 'origin-top left-1/2 -translate-x-1/2';
    } else if (props.align === 'right') {
        return 'origin-top-right right-0';
    } else {
        return 'origin-top';
    }
});

const open = ref(false);
</script>

<template>
    <div ref="dropdown" class="relative">
        <div @click.stop="open = !open">
            <slot name="trigger" />
        </div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="open"
                class="absolute top-full z-50 mt-3 max-w-[calc(100vw-1rem)] shadow-lg"
                :class="[widthClass, alignmentClasses, panelClasses]"
                style="display: none"
                @click="open = false"
            >
                <div
                    class="ring-1 ring-black ring-opacity-5"
                    :class="contentClasses"
                >
                    <slot name="content" />
                </div>
            </div>
        </Transition>
    </div>
</template>
