import { ElNotification, ElMessageBox, ElMessage } from 'element-plus'
import { Delete } from '@element-plus/icons-vue'
import { markRaw } from 'vue'

export function notifySuccess(message) {

    ElNotification({
        title: 'Success',
        message: message,
        type: 'success'
    })

}

export function notifyError(message) {

    ElNotification({
        title: 'Error',
        message: message,
        type: 'error'
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
