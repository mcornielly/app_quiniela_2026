<script setup>
import { computed, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { imageUrl } from '@/Utils/image'

const props = defineProps({
    modelValue: {
        type: [File, Array, null],
        default: null,
    },
    preview: {
        type: [String, Array],
        default: () => [],
    },
    multiple: {
        type: Boolean,
        default: false,
    },
    maxFiles: {
        type: Number,
        default: 8,
    },
    maxSizeMb: {
        type: Number,
        default: 2,
    },
    accept: {
        type: String,
        default: 'image/png,image/jpeg,image/webp,image/svg+xml',
    },
})

const emit = defineEmits(['update:modelValue', 'remove-preview'])

const selectedFiles = ref([])

watch(
    () => props.modelValue,
    (value) => {
        if (!value) {
            selectedFiles.value = []
            return
        }

        if (Array.isArray(value)) {
            selectedFiles.value = value.filter((item) => item instanceof File)
            return
        }

        selectedFiles.value = value instanceof File ? [value] : []
    },
    { immediate: true }
)

const existingPreview = computed(() => {
    const values = Array.isArray(props.preview) ? props.preview : (props.preview ? [props.preview] : [])

    return values
        .map((value) => (typeof value === 'string' ? value : ''))
        .filter((value) => value !== '')
})

const previewItems = computed(() => {
    const existing = existingPreview.value.map((path, index) => ({
        id: `existing-${index}`,
        src: imageUrl(path) ?? path,
        type: 'existing',
        index,
    }))

    const selected = selectedFiles.value.map((file, index) => ({
        id: `new-${index}`,
        src: URL.createObjectURL(file),
        type: 'new',
        index,
    }))

    if (!props.multiple) {
        return selected.length ? selected : existing.slice(0, 1)
    }

    return [...existing, ...selected]
})

const totalCount = computed(() => previewItems.value.length)

const beforeUpload = (file) => {
    const allowed = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp']

    if (!allowed.includes(file.type)) {
        ElMessage.error('Image must be JPG, PNG, SVG or WEBP')
        return false
    }

    if (file.size / 1024 / 1024 > props.maxSizeMb) {
        ElMessage.error(`Image size must be less than ${props.maxSizeMb}MB`)
        return false
    }

    return true
}

const emitModel = () => {
    if (props.multiple) {
        emit('update:modelValue', [...selectedFiles.value])
        return
    }

    emit('update:modelValue', selectedFiles.value[0] ?? null)
}

const handleChange = (uploadFile) => {
    const raw = uploadFile?.raw
    if (!raw || !beforeUpload(raw)) {
        return
    }

    if (props.multiple) {
        const total = existingPreview.value.length + selectedFiles.value.length
        if (total >= props.maxFiles) {
            ElMessage.warning(`Maximum ${props.maxFiles} images`)
            return
        }

        selectedFiles.value = [...selectedFiles.value, raw]
    } else {
        selectedFiles.value = [raw]
    }

    emitModel()
}

const removeImage = (item) => {
    if (item.type === 'existing') {
        emit('remove-preview', item.index)
        return
    }

    selectedFiles.value = selectedFiles.value.filter((_, idx) => idx !== item.index)
    emitModel()
}
</script>

<template>
    <div class="space-y-3">
        <el-upload
            class="w-full"
            :show-file-list="false"
            :auto-upload="false"
            :multiple="multiple"
            :accept="accept"
            :on-change="handleChange"
        >
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-dashed border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-primary-500 hover:text-primary-600 dark:border-slate-600 dark:text-slate-200"
            >
                <el-icon><Plus /></el-icon>
                {{ multiple ? 'Add images' : 'Select image' }}
            </button>
        </el-upload>

        <div v-if="previewItems.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <article
                v-for="item in previewItems"
                :key="item.id"
                class="group relative overflow-hidden rounded-lg border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800"
            >
                <img
                    :src="item.src"
                    alt=""
                    class="h-24 w-full object-cover"
                >
                <button
                    type="button"
                    class="absolute right-1 top-1 rounded bg-black/70 px-1.5 py-0.5 text-[11px] font-semibold text-white opacity-0 transition group-hover:opacity-100"
                    @click="removeImage(item)"
                >
                    Remove
                </button>
            </article>
        </div>

        <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ totalCount }} / {{ maxFiles }} images
        </p>
    </div>
</template>
