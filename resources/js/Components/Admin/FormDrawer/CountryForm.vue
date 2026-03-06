<script setup>

import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { notifySuccess, notifyError } from '@/Utils/notify'

const page = usePage()

const props = defineProps({
    country: Object
})

const emit = defineEmits(['close'])

const isEdit = !!props.country

const form = ref({
name: props.country?.name || '',
code: props.country?.code || '',
flag_path: props.country?.flag_path || '',

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

        router.put(
            route('admin.countries.update', props.country.id),
            form.value,
            options
        )

    } else {

        router.post(
            route('admin.countries.store'),
            form.value,
            options
        )
    }
}

</script>

<template>
  <form @submit.prevent="submit" class="space-y-4">
    
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Name
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                    </div>
                
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Code
                        </label>
                        <input
                            v-model="form.code"
                            type="text"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                    </div>
                
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Flag Path
                        </label>
                        <input
                            v-model="form.flag_path"
                            type="text"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                    </div>
                
    <div class="bottom-0 left-0 flex justify-center w-full pb-4 space-x-4 md:px-4 md:absolute">
      <button type="submit" class="text-white w-full justify-center bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
        {{ isEdit ? 'Update Country' : 'Add Country' }}
      </button>
      <button type="button" @click="emit('close')" class="inline-flex w-full justify-center text-gray-500 items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600">
        Cancel
      </button>
    </div>
  </form>
</template>
