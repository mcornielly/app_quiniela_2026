<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <Head title="Admin - Create User" />
  <div class="p-6">
    <h1 class="text-xl font-semibold mb-4">Create User</h1>

    <form method="post" action="/admin/users">
      <input type="hidden" name="_token" :value="csrfToken" />

      <div class="mb-2">
        <label>Name</label>
        <input name="name" class="border rounded p-2 w-full" />
      </div>

      <div class="mb-2">
        <label>Email</label>
        <input name="email" type="email" class="border rounded p-2 w-full" />
      </div>

      <div class="mb-2">
        <label>Password (leave blank for auto)</label>
        <input name="password" type="password" class="border rounded p-2 w-full" />
      </div>

      <div class="mb-2">
        <label><input type="checkbox" name="is_admin" value="1" /> Admin</label>
      </div>

      <div>
        <button type="submit" class="btn">Create</button>
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