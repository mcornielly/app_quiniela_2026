<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Admin/Breadcrumb.vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref, computed } from 'vue';

const props = defineProps({ users: Object });

const deletingId = ref(null);

// pagination and per-page
const perPage = ref(props.users?.meta?.per_page ?? 15);

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

function changePerPage(value) {
  perPage.value = Number(value);
  router.get(route('admin.users.index'), { page: 1, per_page: perPage.value });
}

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
        <label for="per_page" class="text-sm font-medium text-gray-700">Per page</label>
        <select id="per_page" name="per_page" class="rounded-md border-gray-300" v-model="perPage" @change="changePerPage($event.target.value)">
          <option value="10">10</option>
          <option value="15">15</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
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
      <nav aria-label="Page navigation example" class="flex items-center space-x-4">
        <ul class="flex -space-x-px text-sm">
          <li>
            <button
              @click.prevent="router.get(route('admin.users.index'), { page: meta.current_page - 1, per_page: perPage })"
              :disabled="meta.current_page <= 1"
              class="flex items-center justify-center text-body bg-neutral-secondary-medium border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading shadow-xs font-medium leading-5 rounded-s-base text-sm px-3 h-9 focus:outline-none disabled:opacity-50"
            >
              Previous
            </button>
          </li>

          <li v-for="p in pages" :key="p">
            <button
              @click.prevent="router.get(route('admin.users.index'), { page: p, per_page: perPage })"
              :aria-current="p === meta.current_page ? 'page' : null"
              :class="p === meta.current_page ? 'flex items-center justify-center text-fg-brand bg-neutral-tertiary-medium box-border border border-default-medium hover:text-fg-brand font-medium text-sm w-9 h-9 focus:outline-none' : 'flex items-center justify-center text-body bg-neutral-secondary-medium border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading shadow-xs font-medium leading-5 text-sm w-9 h-9 focus:outline-none'"
            >
              {{ p }}
            </button>
          </li>

          <li>
            <button
              @click.prevent="router.get(route('admin.users.index'), { page: meta.current_page + 1, per_page: perPage })"
              :disabled="meta.current_page >= meta.last_page"
              class="flex items-center justify-center text-body bg-neutral-secondary-medium border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading shadow-xs font-medium leading-5 rounded-e-base text-sm px-3 h-9 focus:outline-none disabled:opacity-50"
            >
              Next
            </button>
          </li>
        </ul>
      </nav>
    </div>
  </div>
  </AdminLayout>
</template>
