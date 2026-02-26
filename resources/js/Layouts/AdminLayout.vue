<script setup>
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Admin' },
});

const page = usePage();

const user = page.props.auth?.user;

// helper para clases active
const isActive = (name) => {
    try {
        return route().current(name);
    } catch (e) {
        return false;
    }
};

const navLinkClass = (active) =>
    [
        'flex items-center px-2 py-1.5 rounded-base group',
        active
        ? 'bg-neutral-tertiary text-fg-brand'
        : 'text-body hover:bg-neutral-tertiary hover:text-fg-brand',
    ].join(' ');

// toast state based on Inertia flash
const flash = page.props.value?.flash || {};
const toast = {
    visible: false,
    message: '',
    type: 'success',
};

if (flash.success) {
    toast.visible = true;
    toast.message = flash.success;
    toast.type = 'success';
    setTimeout(() => toast.visible = false, 4000);
} else if (flash.error) {
    toast.visible = true;
    toast.message = flash.error;
    toast.type = 'error';
    setTimeout(() => toast.visible = false, 4000);
}
</script>

<template>
    <!-- TOP NAV -->
    <nav class="fixed top-0 z-50 w-full bg-neutral-primary-soft border-b border-default">
        <div class="px-3 py-3 lg:px-5 lg:pl-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center justify-start rtl:justify-end">
            <button
                data-drawer-target="top-bar-sidebar"
                data-drawer-toggle="top-bar-sidebar"
                aria-controls="top-bar-sidebar"
                type="button"
                class="sm:hidden text-heading bg-transparent box-border border border-transparent hover:bg-neutral-secondary-medium focus:ring-4 focus:ring-neutral-tertiary font-medium leading-5 rounded-base text-sm p-2 focus:outline-none"
            >
                <span class="sr-only">Open sidebar</span>
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10"/>
                </svg>
            </button>

            <!-- Brand -->
            <Link :href="route('admin.dashboard')" class="flex ms-2 md:me-24">
                <img src="https://flowbite.com/docs/images/logo.svg" class="h-6 me-3" alt="Logo" />
                <span class="self-center text-lg font-semibold whitespace-nowrap dark:text-white">Admin</span>
            </Link>
            </div>

            <!-- User dropdown -->
            <div class="flex items-center">
            <div class="flex items-center ms-3">
                <div>
                <button
                    type="button"
                    class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600"
                    aria-expanded="false"
                    data-dropdown-toggle="dropdown-user"
                >
                    <span class="sr-only">Open user menu</span>
                    <img class="w-8 h-8 rounded-full" src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="user photo" />
                </button>
                </div>

                <div class="z-50 hidden bg-neutral-primary-medium border border-default-medium rounded-base shadow-lg w-44" id="dropdown-user">
                <div class="px-4 py-3 border-b border-default-medium" role="none">
                    <p class="text-sm font-medium text-heading" role="none">
                    {{ user?.name ?? 'Admin' }}
                    </p>
                    <p class="text-sm text-body truncate" role="none">
                    {{ user?.email ?? '' }}
                    </p>
                </div>

                <ul class="p-2 text-sm text-body font-medium" role="none">
                    <li>
                    <Link :href="route('admin.dashboard')" class="inline-flex items-center w-full p-2 hover:bg-neutral-tertiary-medium hover:text-heading rounded" role="menuitem">
                        Dashboard
                    </Link>
                    </li>

                    <li>
                    <Link :href="route('profile.edit')" class="inline-flex items-center w-full p-2 hover:bg-neutral-tertiary-medium hover:text-heading rounded" role="menuitem">
                        Profile
                    </Link>
                    </li>

                    <li>
                    <!-- Logout Breeze -->
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="inline-flex items-center w-full p-2 hover:bg-neutral-tertiary-medium hover:text-heading rounded text-left"
                        role="menuitem"
                    >
                        Sign out
                    </Link>
                    </li>
                </ul>
                </div>
            </div>
            </div>

        </div>
        </div>
    </nav>

    <!-- SIDEBAR -->
    <aside id="top-bar-sidebar" class="fixed top-0 left-0 z-40 w-64 h-full transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidebar">
        <div class="h-full px-3 py-4 overflow-y-auto bg-neutral-primary-soft border-e border-default">
        <Link :href="route('admin.dashboard')" class="flex items-center ps-2.5 mb-5">
            <img src="https://flowbite.com/docs/images/logo.svg" class="h-6 me-3" alt="Logo" />
            <span class="self-center text-lg text-heading font-semibold whitespace-nowrap">Flowbite</span>
        </Link>

        <ul class="space-y-2 font-medium">
            <li>
            <Link :href="route('admin.dashboard')" :class="navLinkClass(isActive('admin.dashboard'))">
                <svg class="w-5 h-5 transition duration-75 group-hover:text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6.025A7.5 7.5 0 1 0 17.975 14H10V6.025Z"/>
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 3c-.169 0-.334.014-.5.025V11h7.975c.011-.166.025-.331.025-.5A7.5 7.5 0 0 0 13.5 3Z"/>
                </svg>
                <span class="ms-3">Dashboard</span>
            </Link>
            </li>

            <!-- Ejemplos de secciones admin (cambia las rutas luego) -->
            <li>
            <Link :href="route('admin.tournaments.index')" :class="navLinkClass(isActive('admin.tournaments.*'))">
                <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v14M9 5v14M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
                </svg>
                <span class="flex-1 ms-3 whitespace-nowrap">Tournaments</span>
                <span class="bg-neutral-secondary-medium border border-default-medium text-heading text-xs font-medium px-1.5 py-0.5 rounded-sm">Pro</span>
            </Link>
            </li>

            <li>
            <Link :href="route('admin.teams.index')" :class="navLinkClass(isActive('admin.teams.*'))">
                <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M16 19h4a1 1 0 0 0 1-1v-1a3 3 0 0 0-3-3h-2m-2.236-4a3 3 0 1 0 0-4M3 18v-1a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Zm8-10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                </svg>
                <span class="flex-1 ms-3 whitespace-nowrap">Teams</span>
            </Link>
            </li>

            <li>
            <Link :href="route('admin.matches.index')" :class="navLinkClass(isActive('admin.matches.*'))">
                <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13h3.439a.991.991 0 0 1 .908.6 3.978 3.978 0 0 0 7.306 0 .99.99 0 0 1 .908-.6H20M4 13v6a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-6M4 13l2-9h12l2 9M9 7h6m-7 3h8"/>
                </svg>
                <span class="flex-1 ms-3 whitespace-nowrap">Matches</span>
            </Link>
            </li>

            <li>
            <Link :href="route('admin.users.index')" :class="navLinkClass(isActive('admin.users.*'))">
                <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M12 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21a7 7 0 0 1 14 0H2z"/>
                </svg>
                <span class="flex-1 ms-3 whitespace-nowrap">Users</span>
            </Link>
            </li>
            <li>
            <Link :href="route('admin.import.schedule')" :class="navLinkClass(isActive('admin.import.*'))">
                <svg class="shrink-0 w-5 h-5 transition duration-75 group-hover:text-fg-brand" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10V6a3 3 0 0 1 3-3v0a3 3 0 0 1 3 3v4m3-2 .917 11.923A1 1 0 0 1 17.92 21H6.08a1 1 0 0 1-.997-1.077L6 8h12Z"/>
                </svg>
                <span class="flex-1 ms-3 whitespace-nowrap">Import Schedule</span>
            </Link>
            </li>

        </ul>
        </div>
    </aside>

    <!-- CONTENT -->
    <div class="p-4 sm:ml-64 mt-14">
        <div class="p-4 border-1 border-default border-dashed rounded-base">
            <!-- page header / breadcrumb -->
            <div class="mb-4">
                <slot name="header">
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ title }}</h2>
                </slot>
                <slot name="breadcrumb"></slot>
            </div>
            <slot />
        </div>
    </div>

    <!-- Toast -->
    <div aria-live="polite" class="fixed inset-0 flex items-end px-4 py-6 pointer-events-none sm:p-6 z-50">
      <div class="w-full flex flex-col items-center space-y-4 sm:items-end">
        <div v-if="toast.visible" :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'" class="max-w-sm w-full text-white px-4 py-3 rounded shadow-lg pointer-events-auto">
          <div class="flex items-start">
            <div class="ml-3 flex-1">
              <p class="text-sm font-medium">{{ toast.message }}</p>
            </div>
            <div class="ml-4 flex-shrink-0">
              <button @click="toast.visible = false" class="text-white opacity-80 hover:opacity-100">✕</button>
            </div>
          </div>
        </div>
      </div>
    </div>
</template>
