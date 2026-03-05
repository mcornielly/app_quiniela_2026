import { ElNotification } from 'element-plus'

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
