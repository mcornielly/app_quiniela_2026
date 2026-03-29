<script setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'

const props = defineProps({
    modelValue: {
        type: [File, String],
        default: null
    },
    preview: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])

const imageUrl = ref(props.preview ?? props.modelValue ?? '')

const beforeUpload = (file) => {
    const allowed = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp']

    if (!allowed.includes(file.type)) {
        ElMessage.error('Image must be JPG, PNG, SVG or WEBP')
        return false
    }

    if (file.size / 1024 / 1024 > 2) {
        ElMessage.error('Image size must be less than 2MB')
        return false
    }

    return true
}

const handleUpload = (file) => {
    if (!beforeUpload(file.raw)) return

    imageUrl.value = URL.createObjectURL(file.raw)

    emit('update:modelValue', file.raw)
}
</script>

<template>
    <el-upload
        class="avatar-uploader"
        :show-file-list="false"
        :auto-upload="false"
        :on-change="handleUpload"
    >
        <img
            v-if="imageUrl"
            :src="imageUrl.startsWith('http') || imageUrl.startsWith('blob')
            ? imageUrl
            : '/storage/' + imageUrl"
            class="avatar"
        />

        <el-icon v-else class="avatar-uploader-icon">
            <Plus />
        </el-icon>
    </el-upload>
</template>

<style scoped>
.avatar-uploader .avatar {
    width: 120px;
    height: 120px;
    object-fit: contain;
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

.avatar-uploader-icon {
    font-size: 28px;
    color: #8c939d;
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
