<script setup>

import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { capitalize } from '@/Utils/format'
import { notifySuccess, notifyError } from '@/Utils/notify'
import { usePage } from '@inertiajs/vue3'
const page = usePage()

const props = defineProps({
    team: Object,
    groups: Array,
    types: Array
})

const emit = defineEmits(['close'])

const isEdit = !!props.team

const form = ref({
    name: props.team?.name || '',
    group_id: props.team?.group_id || '',
    type: props.team?.type || 'international',
    group_position: props.team?.group_position || 1
})

const submit = () => {

    const options = {
        onSuccess: () => {

            const flash = page.props.flash

            if (flash?.success) {
                notifySuccess(flash.success)
            }

            if (flash?.error) {
                notifyError(flash.error)
            }

            emit('close')
        },

        onError: () => {
            notifyError('Validation error')
        }
    }

    if (isEdit) {

        router.put(
            route('admin.teams.update', props.team.id),
            form.value,
            options
        )

    } else {

        router.post(
            route('admin.teams.store'),
            form.value,
            options
        )

    }

}

</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">
        <!-- Team Name -->
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Team Name
            </label>
            <input
                v-model="form.name"
                type="text"
                required
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5
                dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            >
        </div>
        <!-- Group -->
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Group
            </label>
            <select
                v-model="form.group_id"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5
                dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            >
                <option value="">Select group</option>
                <option
                    v-for="g in groups"
                    :key="g.id"
                    :value="g.id"
                >
                    {{ g.name }}
                </option>
            </select>
        </div>
        <!-- Type -->
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Type
            </label>
            <select
                v-model="form.type"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5
                dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            >
                <option
                    v-for="type in types"
                    :key="type"
                    :value="type"
                >
                    {{ capitalize(type) }}
                </option>
            </select>
        </div>
        <!-- Position -->
        <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Position
            </label>
            <input
                v-model="form.group_position"
                type="number"
                min="1"
                max="4"
                required
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5
                dark:bg-gray-700 dark:border-gray-600 dark:text-white"
            >
        </div>
        <!-- Buttons -->
        <div class="bottom-0 left-0 flex justify-center w-full pb-4 space-x-4 md:px-4 md:absolute">
            <button type="submit"
                class="text-white w-full justify-center bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                {{ isEdit ? 'Update' : 'Add Team' }}
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
