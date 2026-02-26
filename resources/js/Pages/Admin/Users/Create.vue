<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Admin/Breadcrumb.vue';
import { route } from 'ziggy-js';
import { ref } from 'vue';

// local form state
const form = ref({
  name: '',
  email: '',
  password: '',
  is_admin: false,
});

const errors = ref({});
const processing = ref(false);

const submit = () => {
  processing.value = true;
  errors.value = {};

  router.post(route('admin.users.store'), form.value, {
    onSuccess: () => {},
    onError: (err) => { errors.value = err; },
    onFinish: () => { processing.value = false; },
  });
};
</script>

<template>
  <Head title="Admin - Create User" />
  <AdminLayout title="Users">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">Create User</h2>
    </template>
    <template #breadcrumb>
      <Breadcrumb :items="[{ title: 'Users', href: route('admin.users.index') }, { title: 'Create' }]" />
    </template>
  <div class="p-6">


    <form @submit.prevent="submit">
      <div class="mb-2">
        <label>Name</label>
        <input v-model="form.name" name="name" class="border rounded p-2 w-full" :class="{'border-red-500': errors.name}" />
        <div v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name }}</div>
      </div>

      <div class="mb-2">
        <label>Email</label>
        <input v-model="form.email" name="email" type="email" class="border rounded p-2 w-full" :class="{'border-red-500': errors.email}" />
        <div v-if="errors.email" class="text-red-500 text-sm mt-1">{{ errors.email }}</div>
      </div>

      <div class="mb-2">
        <label>Password (leave blank for auto)</label>
        <input v-model="form.password" name="password" type="password" class="border rounded p-2 w-full" />
      </div>

      <div class="mb-2">
        <label><input v-model="form.is_admin" type="checkbox" /> Admin</label>
      </div>

      <div>
        <button :disabled="processing" type="submit" class="btn">{{ processing ? 'Creating...' : 'Create' }}</button>
      </div>
    </form>
  </div>
  </AdminLayout>
</template>
