<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Breadcrumb from '@/Components/Admin/Breadcrumb.vue';
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ref } from 'vue';

const props = defineProps({
  user: Object
});

// Estados locales para el formulario
const form = ref({
  name: props.user.name,
  email: props.user.email,
  password: '',
  is_admin: props.user.is_admin || false
});

const errors = ref({});
const processing = ref(false);

const submit = () => {
  processing.value = true;
  errors.value = {};

  router.patch(route('admin.users.update', props.user.id), form.value, {
    onSuccess: () => {
      // Opcional: redirigir o mostrar mensaje
    },
    onError: (err) => {
      errors.value = err;
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};
</script>

<template>
  <Head title="Admin - Edit User" />
  <AdminLayout title="Users">
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit User</h2>
    </template>
    <template #breadcrumb>
      <Breadcrumb :items="[{ title: 'Users', href: route('admin.users.index') }, { title: 'Edit' }]" />
    </template>

  <div class="p-6">


    <form @submit.prevent="submit">
      <!-- Nombre -->
      <div class="mb-4">
        <label class="block mb-1">Name</label>
        <input
          v-model="form.name"
          type="text"
          class="border rounded p-2 w-full"
          :class="{ 'border-red-500': errors.name }"
        />
        <div v-if="errors.name" class="text-red-500 text-sm mt-1">
          {{ errors.name }}
        </div>
      </div>

      <!-- Email -->
      <div class="mb-4">
        <label class="block mb-1">Email</label>
        <input
          v-model="form.email"
          type="email"
          class="border rounded p-2 w-full"
          :class="{ 'border-red-500': errors.email }"
        />
        <div v-if="errors.email" class="text-red-500 text-sm mt-1">
          {{ errors.email }}
        </div>
      </div>

      <!-- Password -->
      <div class="mb-4">
        <label class="block mb-1">Password (leave blank to keep current)</label>
        <input
          v-model="form.password"
          type="password"
          class="border rounded p-2 w-full"
        />
      </div>

      <!-- Is Admin -->
      <div class="mb-4">
        <label class="flex items-center">
          <input
            v-model="form.is_admin"
            type="checkbox"
            class="mr-2"
          />
          Admin
        </label>
      </div>

      <!-- Botón Submit -->
      <div>
        <button
          type="submit"
          class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 disabled:opacity-50"
          :disabled="processing"
        >
          {{ processing ? 'Saving...' : 'Save' }}
        </button>
      </div>
    </form>
  </div>
  </AdminLayout>
</template>
