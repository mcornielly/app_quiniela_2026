<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { notifySuccess, notifyError } from '@/Utils/notify'
import TextInput from '@/Components/Admin/Inputs/TextInput.vue'
import ImageUpload from '@/Components/Admin/Inputs/ImageUpload.vue'


const page = usePage()
const errors = page.props.errors

const props = defineProps({
    tournament: Object
})

const emit = defineEmits(['close'])

const isEdit = !!props.tournament

const form = ref({
    name: props.tournament?.name || '',
    year: props.tournament?.year || '',
    host_countries: props.tournament?.host_countries || '',
    logo: props.tournament?.logo ?? null,
    deadline_at: props.tournament?.deadline_at || '',
    status: props.tournament?.status || '',
    type: props.tournament?.type || '',

})

const submit = () => {
    const options = {
        onSuccess: () => {
            const flash = page.props.flash

            if (flash?.success) notifySuccess(flash.success)
            if (flash?.error) notifyError(flash.error)

            emit('close')
        },

        onError: () => notifyError('Validation error')
    }

    if (isEdit) {
        router.post(
            route('admin.tournaments.update', props.tournament.id),
            {
                ...form.value,
                _method: 'put'
            },
            options
        )
    } else {
        router.post(
            route('admin.tournaments.store'),
            form.value,
            options
        )
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4 pb-24">

        <TextInput
            v-model="form.name"
            label="Name"
            :error="errors.name"
        />
        <TextInput
            v-model="form.year"
            label="Year"
        />
        <TextInput
            v-model="form.host_countries"
            label="Host Countries"
        />
        <ImageUpload
            v-model="form.logo"
            label="Logo"
            :preview="tournament?.logo"
        />
        <span v-if="errors.logo" class="text-sm text-red-600">
            {{ errors.logo }}
        </span>

        <TextInput
            v-model="form.deadline_at"
            label="Deadline At"
        />
        <TextInput
            v-model="form.status"
            label="Status"
        />
        <TextInput
            v-model="form.type"
            label="Type"
        />

        <!-- Buttons -->
        <div class="fixed bottom-0 right-0 flex justify-center w-full max-w-xs pb-4 space-x-4 md:px-4">
            <button type="submit"
                class="text-white w-full justify-center bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                {{ isEdit ? 'Update' : 'Add Tournament' }}
            </button>
            <button type="button"
                @click="emit('close')"
                class="inline-flex w-full justify-center text-gray-500 items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600">
                <svg aria-hidden="true" class="w-5 h-5 -ml-1 sm:mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Cancel
            </button>
        </div>
    </form>
</template>
