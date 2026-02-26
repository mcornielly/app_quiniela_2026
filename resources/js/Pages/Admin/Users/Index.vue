<script setup>
import { Head, Link } from '@inertiajs/vue3';
const props = defineProps({ users: Object });
</script>

<template>
  <Head title="Admin - Users" />
  <div class="p-6">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-xl font-semibold">Users</h1>
      <Link href="/admin/users/create" class="btn">New User</Link>
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
            <Link :href="`/admin/users/${user.id}/edit`" class="text-blue-600 mr-3">Edit</Link>
            <form :action="`/admin/users/${user.id}`" method="post" style="display:inline">
              <input type="hidden" name="_method" value="delete" />
              <input type="hidden" name="_token" :value="csrfToken" />
              <button type="submit" class="text-red-600">Delete</button>
            </form>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="mt-4">
      <!-- basic pagination links -->
      <div v-if="users.meta">
        <span>Page {{ users.meta.current_page }} / {{ users.meta.last_page }}</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  computed: {
    csrfToken() {
      return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }
  }
}
</script>