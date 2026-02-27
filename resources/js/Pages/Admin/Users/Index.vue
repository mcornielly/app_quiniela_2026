<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Admin/Breadcrumb.vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref, computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({ users: Object });

const deletingId = ref(null);

// pagination and per-page
const perPage = ref(props.users?.per_page ?? 15);

function changePerPage(value) {
  perPage.value = Number(value);
  router.get(props.users.path, { page: 1, per_page: perPage.value }, {
    preserveScroll: true,
    preserveState: true,
    replace: true,
  });
}

// safe meta computed (fallback when props.users.meta is undefined)
const meta = computed(() => {
  return props.users?.meta ?? {
    current_page: 1,
    last_page: 1,
    per_page: perPage.value,
    total: props.users?.data ? props.users.data.length : 0,
  };
});

const pages = computed(() => {
  const last = meta.value.last_page || 1;
  const current = meta.value.current_page || 1;
  const result = [];

  if (last <= 7) {
    for (let i = 1; i <= last; i++) result.push(i);
    return result;
  }

  let start = Math.max(current - 2, 1);
  let end = Math.min(start + 4, last);
  if (end - start < 4) {
    start = Math.max(end - 4, 1);
  }
  for (let i = start; i <= end; i++) result.push(i);
  return result;
});

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
      <div class="flex items-center space-x-2">
        <label for="per_page" class="sr-only">Per page</label>
        <select
          id="per_page"
          name="per_page"
          class="block w-32 px-3 py-2.5 bg-neutral-secondary-medium border border-default-medium text-heading text-sm leading-4 rounded-base focus:ring-brand focus:border-brand shadow-xs placeholder:text-body"
          v-model="perPage"
          @change="changePerPage($event.target.value)"
        >
          <option value="10">10 per page</option>
          <option value="15">15 per page</option>
          <option value="25">25 per page</option>
          <option value="50">50 per page</option>
          <option value="100">100 per page</option>
        </select>
      </div>
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
      <Pagination :paginator="users" />
    </div>
  </div>
  </AdminLayout>
</template>
