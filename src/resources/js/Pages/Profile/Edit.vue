<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Breadcrumb from '@/Layouts/Partials/Breadcrumb.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const isAdmin = computed(() => Boolean(page.props.auth?.user?.is_admin));
const adminBreadcrumbItems = computed(() => ([
    { label: 'Dashboard', href: route('admin.dashboard') },
    { label: 'Profile' },
]));
</script>

<template>
    <Head title="Profile" />

    <AdminLayout v-if="isAdmin" title="Profile">
        <div class="p-4 bg-white block border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="w-full mb-1">
                <Breadcrumb :items="adminBreadcrumbItems" />
                <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Profile</h1>
            </div>
        </div>

        <div class="py-4 md:py-8 bg-gray-50 dark:bg-gray-900">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-slate-800 dark:shadow-none">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        :is-admin="true"
                        class="max-w-xl"
                    />
                </div>

                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-slate-800 dark:shadow-none">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-slate-800 dark:shadow-none">
                    <DeleteUserForm :is-admin="true" class="max-w-xl" />
                </div>
            </div>
        </div>
    </AdminLayout>

    <AuthenticatedLayout v-else>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-slate-800 dark:shadow-none">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        :is-admin="false"
                        class="max-w-xl"
                    />
                </div>

                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-slate-800 dark:shadow-none">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-slate-800 dark:shadow-none">
                    <DeleteUserForm :is-admin="false" class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
