<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { notifyError } from '@/Utils/notify'

            import TextInput from '@/Components/Admin/Inputs/TextInput.vue'
            import NumberInput from '@/Components/Admin/Inputs/NumberInput.vue'
            import SelectInput from '@/Components/Admin/Inputs/SelectInput.vue'
            import TextareaInput from '@/Components/Admin/Inputs/TextareaInput.vue'
            import CheckboxInput from '@/Components/Admin/Inputs/CheckboxInput.vue'
            import ImageUpload from '@/Components/Admin/Inputs/ImageUpload.vue'
            

const props = defineProps({
    poolEntry: Object,
    tournaments: Array,
    users: Array,
})

const emit = defineEmits(['close'])

const isEdit = !!props.poolEntry

const poolEntryForm = ref({
    tournament_id: props.poolEntry?.tournament_id || '',
    user_id: props.poolEntry?.user_id || '',
    name: props.poolEntry?.name || '',
    status: props.poolEntry?.status || '',
    completion_percent: props.poolEntry?.completion_percent || '',
    total_points: props.poolEntry?.total_points || '',
    paid_at: props.poolEntry?.paid_at || '',
    payment_ref: props.poolEntry?.payment_ref || '',
})

const submit = () => {
    const options = {
        onSuccess: () => {
            emit('close')
        },

        onError: () => notifyError('Validation error')
    }

    if (isEdit) {
        router.put(
            route('admin.pool_entries.update', props.poolEntry.id),
            poolEntryForm.value,
            options
        )
    } else {
        router.post(
            route('admin.pool_entries.store'),
            poolEntryForm.value,
            options
        )
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">
        
                    <SelectInput
                        v-model="poolEntryForm.tournament_id"
                        label="Tournament"
                        :options="tournaments"
                    />
                    <SelectInput
                        v-model="poolEntryForm.user_id"
                        label="User"
                        :options="users"
                    />
                    <TextInput
                        v-model="poolEntryForm.name"
                        label="Name"
                    />
                    <TextInput
                        v-model="poolEntryForm.status"
                        label="Status"
                    />
                    <TextInput
                        v-model="poolEntryForm.completion_percent"
                        label="Completion Percent"
                    />
                    <TextInput
                        v-model="poolEntryForm.total_points"
                        label="Total Points"
                    />
                    <TextInput
                        v-model="poolEntryForm.paid_at"
                        label="Paid At"
                    />
                    <TextInput
                        v-model="poolEntryForm.payment_ref"
                        label="Payment Ref"
                    />

        <!-- Buttons -->
        <div class="bottom-0 left-0 flex justify-center w-full pb-4 space-x-4 md:px-4 md:absolute">
            <button type="submit"
                class="text-white w-full justify-center bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                {{ isEdit ? 'Update' : 'Add PoolEntry' }}
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
