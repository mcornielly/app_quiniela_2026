<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { capitalize } from '@/Utils/format'
import { notifyError } from '@/Utils/notify'

const props = defineProps({
    team: Object,
    groups: Array,
    types: Array,
    countries: { type: Array, default: () => [] }
})

const emit = defineEmits(['close'])

const isEdit = !!props.team

// Country combobox state
const countryQuery   = ref('')
const showDropdown   = ref(false)

const form = ref({
    name:           props.team?.name || '',
    country_id:     props.team?.country_id || null,
    group_id:       props.team?.group_id || '',
    type:           props.team?.type || 'international',
    group_position: props.team?.group_position || 1
})

// Pre-fill the search input with the currently linked country name
onMounted(() => {
    if (props.team?.country_id && props.countries.length) {
        const found = props.countries.find(c => c.id === props.team.country_id)
        if (found) countryQuery.value = found.name
    }
})

// Filtered country list based on typed text
const filteredCountries = computed(() => {
    const q = countryQuery.value.toLowerCase().trim()
    if (!q) return props.countries
    return props.countries.filter(c =>
        c.name.toLowerCase().includes(q) ||
        (c.code && c.code.toLowerCase().includes(q))
    )
})

// Currently selected country object (for the flag preview)
const selectedCountry = computed(() =>
    form.value.country_id
        ? props.countries.find(c => c.id === form.value.country_id) ?? null
        : null
)

const getFlagUrl = (country) => {
    if (!country) return null
    if (country.api_flag_url) return country.api_flag_url
    if (country.flag_path)    return `/storage/${country.flag_path}`
    return null
}

const pickCountry = (country) => {
    form.value.country_id = country.id
    form.value.name       = country.name
    countryQuery.value    = country.name
    showDropdown.value    = false
}

const onBlur = () => {
    // small delay so click on list item fires first
    setTimeout(() => { showDropdown.value = false }, 150)
}

const submit = () => {

    const options = {
        onSuccess: () => {
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
        <!-- Country / Team Name — searchable select with flag preview -->
        <div class="relative">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                Team Name
            </label>

            <!-- Input wrapper with flag icon -->
            <div class="relative flex items-center">
                <!-- Flag preview inside the input -->
                <span class="pointer-events-none absolute left-2.5 flex items-center">
                    <img
                        v-if="selectedCountry"
                        :src="getFlagUrl(selectedCountry)"
                        :alt="selectedCountry.name"
                        class="h-4 w-6 rounded-[2px] object-cover shadow-sm"
                    />
                    <svg v-else class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>

                <input
                    v-model="countryQuery"
                    type="text"
                    placeholder="Search country…"
                    autocomplete="off"
                    @focus="showDropdown = true"
                    @blur="onBlur"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg
                           focus:ring-primary-600 focus:border-primary-600 block w-full py-2.5 pr-2.5
                           dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    :class="selectedCountry ? 'pl-10' : 'pl-8'"
                />
            </div>

            <!-- Dropdown list -->
            <ul
                v-if="showDropdown && filteredCountries.length"
                class="absolute z-50 mt-1 w-full max-h-52 overflow-y-auto
                       rounded-lg border border-gray-200 bg-white shadow-lg
                       dark:border-gray-700 dark:bg-gray-800"
            >
                <li
                    v-for="country in filteredCountries"
                    :key="country.id"
                    @mousedown.prevent="pickCountry(country)"
                    class="flex cursor-pointer items-center gap-3 px-3 py-2 text-sm
                           hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <img
                        :src="getFlagUrl(country)"
                        :alt="country.name"
                        class="h-4 w-6 shrink-0 rounded-[2px] object-cover shadow-sm"
                    />
                    <span class="flex-1 text-gray-900 dark:text-white">{{ country.name }}</span>
                    <span class="text-xs uppercase text-gray-400">{{ country.code }}</span>
                </li>
            </ul>
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
