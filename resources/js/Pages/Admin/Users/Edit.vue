<script setup>
import { Head } from '@inertiajs/vue3';
const props = defineProps({ user: Object });
</script>

<template>
  <Head title="Admin - Edit User" />
  <div class="p-6">
    <h1 class="text-xl font-semibold mb-4">Edit User</h1>

    <form :action="`/admin/users/${user.id}`" method="post">
      <input type="hidden" name="_token" :value="csrfToken" />
      <input type="hidden" name="_method" value="patch" />

      <div class="mb-2">
        <label>Name</label>
        <input name="name" :value="user.name" class="border rounded p-2 w-full" />
      </div>

      <div class="mb-2">
        <label>Email</label>
        <input name="email" type="email" :value="user.email" class="border rounded p-2 w-full" />
      </div>

      <div class="mb-2">
        <label>Password (leave blank to keep current)</label>
        <input name="password" type="password" class="border rounded p-2 w-full" />
      </div>

      <div class="mb-2">
        <label><input type="checkbox" name="is_admin" :checked="user.is_admin" value="1" /> Admin</label>
      </div>

      <div>
        <button type="submit" class="btn">Save</button>
      </div>
    </form>
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