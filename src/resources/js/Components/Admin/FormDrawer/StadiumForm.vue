<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { notifySuccess, notifyError } from '@/Utils/notify'

import TextInput from '@/Components/Admin/Inputs/TextInput.vue'
import NumberInput from '@/Components/Admin/Inputs/NumberInput.vue'
import ImageUpload from '@/Components/Admin/Inputs/ImageUpload.vue'

const page = usePage()

const props = defineProps({
    stadium: Object,
})

const emit = defineEmits(['close'])

const isEdit = !!props.stadium
const initialGallery = (() => {
    const gallery = Array.isArray(props.stadium?.image_gallery)
        ? props.stadium.image_gallery.filter((item) => typeof item === 'string' && item.trim() !== '')
        : []

    const cover = props.stadium?.image_url
    if (typeof cover === 'string' && cover.trim() !== '' && !gallery.includes(cover)) {
        gallery.unshift(cover)
    }

    return gallery
})()

const form = ref({
    name: props.stadium?.name || '',
    city: props.stadium?.city || '',
    country: props.stadium?.country || '',
    address: props.stadium?.address || '',
    capacity: props.stadium?.capacity || '',
    surface: props.stadium?.surface || '',
    image_url: props.stadium?.image_url || '',
    cover_image_file: null,
    gallery_existing: [...initialGallery],
    gallery_images: [],
})

const submit = () => {
    const firstExisting = form.value.gallery_existing[0] || ''
    form.value.image_url = firstExisting

    const options = {
        onSuccess: (page) => {
            // AdminLayout already renders flash notifications globally.
            // Keep only a fallback when flash is not present.
            const flash = page?.props?.flash
            if (!flash?.success && !flash?.error) {
                notifySuccess(isEdit ? 'Stadium updated successfully' : 'Stadium created successfully')
            }
            emit('close')
        },

        onError: () => notifyError('Validation error'),
        onException: () => notifyError('Communication error while saving'),
    }

    if (isEdit) {
        router.post(
            route('admin.stadiums.update', props.stadium.id),
            {
                ...form.value,
                _method: 'put',
            },
            {
                ...options,
                forceFormData: true,
            }
        )
    } else {
        router.post(
            route('admin.stadiums.store'),
            form.value,
            {
                ...options,
                forceFormData: true,
            }
        )
    }
}

const handleRemoveGalleryPreview = (index) => {
    if (typeof index !== 'number') return
    form.value.gallery_existing = form.value.gallery_existing.filter((_, i) => i !== index)
}

</script>

<template>
    <form @submit.prevent="submit" class="space-y-4 pb-24">
        <TextInput
            v-model="form.name"
            label="Name"
        />
        <TextInput
            v-model="form.city"
            label="City"
        />
        <TextInput
            v-model="form.country"
            label="Country"
        />
        <TextInput
            v-model="form.address"
            label="Address"
        />
        <NumberInput
            v-model="form.capacity"
            label="Capacity"
        />
        <TextInput
            v-model="form.surface"
            label="Surface"
        />

        <div class="space-y-2">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Stadium Gallery</p>
            <ImageUpload
                v-model="form.gallery_images"
                :preview="form.gallery_existing"
                :multiple="true"
                :max-files="10"
                @remove-preview="handleRemoveGalleryPreview"
            />
        </div>

        <!-- Buttons -->
        <div class="fixed bottom-0 right-0 flex justify-center w-full max-w-xs pb-4 space-x-4 md:px-4">
            <button type="submit"
                class="text-white w-full justify-center bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                {{ isEdit ? 'Update' : 'Add Stadium' }}
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
