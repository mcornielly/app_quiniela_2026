<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Admin/Breadcrumb.vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref } from 'vue';

const props = defineProps({ users: Object });

const deletingId = ref(null);

function confirmDelete(user) {
  if (!confirm(`Delete user ${user.email}?`)) return;
  deletingId.value = user.id;

  router.delete(route('admin.users.destroy', user.id), {
    onFinish: () => { deletingId.value = null; }
  });
}
</script>

<template>
  <Head title="Admin - Users" />
  <AdminLayout title="Users">
  <div class="p-6">
    <div class="flex justify-between items-center mb-4">
      <a :href="route('admin.users.create')" class="btn">New User</a>
    </div>

    <table class="min-w-full bg-white">
      <thead>
        <tr>
          <th class="px-4 py-2">ID</th>
          <th class="px-4 py-2">Name</th>
          <th class="px-4 py-2">Email</th>
          <th class="px-4 py-2">Admin</th>
          <th class="px-4 py-2">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in users.data" :key="user.id" class="border-t">
          <td class="px-4 py-2">{{ user.id }}</td>
          <td class="px-4 py-2">{{ user.name }}</td>
          <td class="px-4 py-2">{{ user.email }}</td>
          <td class="px-4 py-2">{{ user.is_admin ? 'Yes' : 'No' }}</td>
          <td class="px-4 py-2">
            <a :href="route('admin.users.edit', user.id)" class="text-blue-600 mr-3">Edit</a>
            <button @click="confirmDelete(user)" class="text-red-600" :disabled="deletingId===user.id">
              {{ deletingId===user.id ? 'Deleting...' : 'Delete' }}
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="mt-4">
      <!-- pagination controls -->
      <div v-if="users.meta" class="flex items-center justify-between">
        <div>
          <span class="text-sm text-body">Page {{ users.meta.current_page }} of {{ users.meta.last_page }}</span>
        </div>
        <div class="flex items-center space-x-2">
          <a v-if="users.meta.current_page > 1" :href="route('admin.users.index', { page: users.meta.current_page - 1 })" class="px-3 py-1 bg-neutral-secondary-medium rounded">Previous</a>
          <a v-if="users.meta.current_page < users.meta.last_page" :href="route('admin.users.index', { page: users.meta.current_page + 1 })" class="px-3 py-1 bg-neutral-secondary-medium rounded">Next</a>
        </div>
      </div>
    </div>
  </div>
  </AdminLayout>
</template>
