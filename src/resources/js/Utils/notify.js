import { ElNotification, ElMessageBox, ElMessage } from 'element-plus'
import { Delete } from '@element-plus/icons-vue'
import { markRaw } from 'vue'

const notificationClass = () => {
    const isDark = document?.documentElement?.classList?.contains('dark')
    return isDark ? 'app-notify app-notify--dark' : 'app-notify app-notify--light'
}

const notificationQueue = []
let isProcessingQueue = false

const processQueue = async () => {
    if (isProcessingQueue || notificationQueue.length === 0) return
    isProcessingQueue = true

    while (notificationQueue.length > 0) {
        const { title, message, type } = notificationQueue.shift()

        ElNotification({
            title,
            message,
            type,
            customClass: notificationClass(),
        })

        // delay for next notification to avoid overlap/simultaneous pop
        await new Promise(resolve => setTimeout(resolve, 300))
    }

    isProcessingQueue = false
}

export function notifySuccess(message) {
    notificationQueue.push({ title: 'Success', message, type: 'success' })
    processQueue()
}

export function notifyError(message) {
    notificationQueue.push({ title: 'Error', message, type: 'error' })
    processQueue()
}

export function notifyInfo(message) {
    notificationQueue.push({ title: 'Info', message, type: 'info' })
    processQueue()
}

export function notifyWarning(message, options = {}) {

    ElMessage({
        message,
        type: 'warning',
        showClose: true,
        grouping: true,
        ...options,
    })

}

export function confirmDelete(message) {

    return ElMessageBox.confirm(
        message,
        'Warning',
        {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning',
            icon: markRaw(Delete)
        }
    ).then(() => {

        ElMessage({
            type: 'success',
            message: 'Delete confirmed'
        })

        return true

    })

}

export async function confirmAction({
    message,
    title = 'Confirmar',
    confirmButtonText = 'Aceptar',
    cancelButtonText = 'Cancelar',
    type = 'warning',
} = {}) {
    try {
        await ElMessageBox.confirm(message, title, {
            confirmButtonText,
            cancelButtonText,
            type,
        })

        return true
    } catch (error) {
        return false
    }
}
