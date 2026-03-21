<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Datepicker } from 'flowbite'

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

const inputRef = ref(null)
let datepicker = null
let syncingFromModel = false

const isoToDisplay = (iso) => {
    if (!iso) {
        return ''
    }

    const [year, month, day] = String(iso).split('-')
    if (!year || !month || !day) {
        return ''
    }

    return `${day}/${month}/${year}`
}

const displayToIso = (display) => {
    if (!display) {
        return ''
    }

    const [day, month, year] = String(display).split('/')
    if (!year || !month || !day) {
        return ''
    }

    return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`
}

const syncFromPicker = () => {
    if (!datepicker || syncingFromModel) {
        return
    }

    const selected = datepicker.getDate()
    const displayValue = Array.isArray(selected) ? selected[0] : selected
    const iso = displayToIso(displayValue)

    if (iso !== props.modelValue) {
        emit('update:modelValue', iso)
    }
}

const syncPickerFromModel = (value) => {
    if (!datepicker) {
        return
    }

    syncingFromModel = true

    const displayValue = isoToDisplay(value)
    datepicker.setDate(displayValue || '')

    syncingFromModel = false
}

const handleInput = () => {
    if (!inputRef.value?.value) {
        emit('update:modelValue', '')
    }
}

const hidePicker = () => {
    if (datepicker?.active) {
        datepicker.hide()
    }
}

const handleWindowScroll = () => {
    hidePicker()
}

const handleWindowResize = () => {
    hidePicker()
}

const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        hidePicker()
    }
}

const handleDocumentMouseDown = (event) => {
    if (!datepicker || !inputRef.value) {
        return
    }

    const target = event.target
    const pickerElement = datepicker.pickerElement

    if (inputRef.value.contains(target)) {
        return
    }

    if (pickerElement && pickerElement.contains(target)) {
        return
    }

    hidePicker()
}

onMounted(() => {
    if (!inputRef.value) {
        return
    }

    datepicker = new Datepicker(inputRef.value, {
        autohide: true,
        format: 'dd/mm/yyyy',
        buttons: false,
    })

    syncPickerFromModel(props.modelValue)

    inputRef.value.addEventListener('changeDate', syncFromPicker)
    inputRef.value.addEventListener('input', handleInput)
    window.addEventListener('scroll', handleWindowScroll, true)
    window.addEventListener('resize', handleWindowResize)
    document.addEventListener('keydown', handleKeydown)
    document.addEventListener('mousedown', handleDocumentMouseDown)
})

watch(() => props.modelValue, (value) => {
    syncPickerFromModel(value)
})

onBeforeUnmount(() => {
    if (inputRef.value) {
        inputRef.value.removeEventListener('changeDate', syncFromPicker)
        inputRef.value.removeEventListener('input', handleInput)
    }

    window.removeEventListener('scroll', handleWindowScroll, true)
    window.removeEventListener('resize', handleWindowResize)
    document.removeEventListener('keydown', handleKeydown)
    document.removeEventListener('mousedown', handleDocumentMouseDown)

    if (datepicker) {
        datepicker.destroy()
        datepicker = null
    }
})
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
            ref="inputRef"
            type="text"
            class="block w-full rounded-xl border border-slate-300 bg-white py-2.5 pe-3 ps-10 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-primary-400 focus:ring-primary-300/40 dark:border-white/10 dark:bg-slate-800/80 dark:text-white dark:placeholder:text-slate-400 dark:focus:border-cyan-400 dark:focus:ring-cyan-400/30"
            :placeholder="placeholder"
        >
    </div>
</template>

