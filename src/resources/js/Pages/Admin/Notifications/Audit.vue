<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Header from '@/Layouts/Partials/Header.vue'

defineProps({
    auditItems: {
        type: Array,
        default: () => [],
    },
})

const formatDateTime = (value) => {
    if (!value) {
        return '-'
    }

    const date = new Date(value)
    if (Number.isNaN(date.getTime())) {
        return '-'
    }

    return date.toLocaleString('es-VE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    })
}
</script>

<template>
    <AdminLayout title="Auditoria de notificaciones">
        <main class="p-4">
            <div class="mb-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <Header title="Auditoria de notificaciones" />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Registro historico de actividad del buzon admin. "Limpiar" solo oculta en el inbox, no elimina estos registros.
                </p>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Usuario</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Accion</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Quiniela</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Creada</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Leida</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Oculta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="item in auditItems" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                    <p class="font-semibold">{{ item.userName || 'Usuario' }}</p>
                                    <p class="text-xs text-gray-500">{{ item.userEmail || '-' }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                    {{ item.messageSuffix || '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                    <p>{{ item.poolEntryName || '-' }}</p>
                                    <p class="text-xs text-gray-500">{{ item.registrationNumber || '-' }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ formatDateTime(item.createdAt) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ formatDateTime(item.readAt) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ formatDateTime(item.hiddenAt) }}</td>
                            </tr>
                            <tr v-if="auditItems.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No hay registros de notificaciones.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </AdminLayout>
</template>
