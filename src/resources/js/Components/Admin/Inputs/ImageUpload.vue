<script setup>
import { computed, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Delete, Download, Plus, ZoomIn } from '@element-plus/icons-vue'
import { imageUrl } from '@/Utils/image'

const props = defineProps({
    modelValue: {
        type: [File, String, Array, null],
        default: null,
    },
    preview: {
        type: [String, Array, null],
        default: null,
    },
    multiple: {
        type: Boolean,
        default: false,
    },
    maxFiles: {
        type: Number,
        default: 1,
    },
})

const emit = defineEmits(['update:modelValue', 'remove-preview'])

const dialogImageUrl = ref('')
const dialogVisible = ref(false)
const disabled = ref(false)
const fileList = ref([])
const selectedFiles = ref([])
const canAddMore = computed(() => {
    if (!props.multiple) return true
    return fileList.value.length < props.maxFiles
})

const normalizePreviewValues = (value) => {
    if (!value) return []

    const values = Array.isArray(value) ? value : [value]

    return values
        .map((item) => (typeof item === 'string' ? item : ''))
        .filter((item) => item !== '')
}

watch(
    () => [props.modelValue, props.preview],
    () => {
        const existingPreview = normalizePreviewValues(props.preview)
            .map((path, index) => ({
                uid: `existing-${index}`,
                name: `image-${index + 1}`,
                url: imageUrl(path) || path,
                status: 'success',
                existing: true,
                existingIndex: index,
            }))

        if (props.multiple) {
            selectedFiles.value = Array.isArray(props.modelValue)
                ? props.modelValue.filter((item) => item instanceof File)
                : []

            const selected = selectedFiles.value.map((file, index) => ({
                uid: `new-${index}`,
                name: file.name || `image-${index + 1}`,
                url: URL.createObjectURL(file),
                status: 'success',
                raw: file,
                existing: false,
            }))

            fileList.value = [...existingPreview, ...selected]
            return
        }

        const selectedSingle = props.modelValue instanceof File
            ? [{
                uid: 'new-0',
                name: props.modelValue.name || 'image',
                url: URL.createObjectURL(props.modelValue),
                status: 'success',
                raw: props.modelValue,
                existing: false,
            }]
            : []

        // In single mode, keep existing preview and selected replacement visible together.
        fileList.value = [...existingPreview.slice(0, 1), ...selectedSingle.slice(0, 1)]
    },
    { immediate: true }
)

const beforeAvatarUpload = (rawFile) => {
    const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']

    if (!allowed.includes(rawFile.type)) {
        ElMessage.error('Image must be JPG, PNG, WEBP or SVG format')
        return false
    }

    if (rawFile.size / 1024 / 1024 > 2) {
        ElMessage.error('Image size can not exceed 2MB')
        return false
    }

    return true
}

const handleChange = (uploadFile) => {
    const raw = uploadFile?.raw
    if (!raw) return

    if (!beforeAvatarUpload(raw)) {
        return
    }

    if (props.multiple) {
        const existingCount = normalizePreviewValues(props.preview).length
        const total = existingCount + selectedFiles.value.length

        if (total >= props.maxFiles) {
            ElMessage.warning(`Maximum ${props.maxFiles} images`)
            return
        }

        selectedFiles.value = [...selectedFiles.value, raw]
        emit('update:modelValue', [...selectedFiles.value])
        return
    }

    selectedFiles.value = [raw]
    emit('update:modelValue', raw)
}

const handleRemove = (file) => {
    fileList.value = fileList.value.filter((item) => item?.uid !== file?.uid)

    if (file?.existing) {
        emit('remove-preview', file.existingIndex)
        return
    }

    if (props.multiple) {
        selectedFiles.value = selectedFiles.value.filter((item) => item !== file?.raw)
        emit('update:modelValue', [...selectedFiles.value])
        return
    }

    fileList.value = []
    selectedFiles.value = []
    emit('update:modelValue', null)
}

const handlePictureCardPreview = (file) => {
    dialogImageUrl.value = file?.url || ''
    dialogVisible.value = Boolean(dialogImageUrl.value)
}

const handleDownload = (file) => {
    const url = file?.url
    if (!url) return

    const link = document.createElement('a')
    link.href = url
    link.target = '_blank'
    link.rel = 'noopener'
    link.download = (file?.name || 'image')
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
}
</script>

<template>
    <el-upload
        class="avatar-uploader"
        action="https://run.mocky.io/v3/9d059bf9-4660-45f2-925d-ce80ad6c4d15"
        :auto-upload="false"
        list-type="picture-card"
        :file-list="fileList"
        :limit="multiple ? maxFiles : undefined"
        :multiple="multiple"
        :disabled="!canAddMore"
        :on-change="handleChange"
        :before-upload="beforeAvatarUpload"
        :on-remove="handleRemove"
    >
        <el-icon v-if="canAddMore"><Plus /></el-icon>

        <template #file="{ file }">
            <div>
                <img class="el-upload-list__item-thumbnail" :src="file.url" alt="">
                <span class="el-upload-list__item-actions">
                    <span
                        class="el-upload-list__item-preview"
                        @click="handlePictureCardPreview(file)"
                    >
                        <el-icon><ZoomIn /></el-icon>
                    </span>
                    <span
                        v-if="!disabled"
                        class="el-upload-list__item-delete"
                        @click="handleDownload(file)"
                    >
                        <el-icon><Download /></el-icon>
                    </span>
                    <span
                        v-if="!disabled"
                        class="el-upload-list__item-delete"
                        @click="handleRemove(file)"
                    >
                        <el-icon><Delete /></el-icon>
                    </span>
                </span>
            </div>
        </template>
    </el-upload>

    <el-dialog v-model="dialogVisible">
        <img :src="dialogImageUrl" alt="Preview Image" class="w-full">
    </el-dialog>
</template>

<style scoped>
.avatar-uploader :deep(.el-upload--picture-card),
.avatar-uploader :deep(.el-upload-list__item) {
    width: 178px;
    height: 178px;
}

.avatar-uploader :deep(.el-upload-list--picture-card) {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.avatar-uploader :deep(.el-upload--picture-card) {
    order: -1;
}
</style>

<style>
.avatar-uploader .el-upload {
    border: 1px dashed var(--el-border-color);
    border-radius: 6px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: var(--el-transition-duration-fast);
}

.avatar-uploader .el-upload:hover {
    border-color: var(--el-color-primary);
}

.el-icon.avatar-uploader-icon {
    font-size: 28px;
    color: #8c939d;
    width: 178px;
    height: 178px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
