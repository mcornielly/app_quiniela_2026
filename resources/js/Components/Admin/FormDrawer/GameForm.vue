<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { notifySuccess, notifyError } from '@/Utils/notify'
import TextInput from '@/Components/Admin/Inputs/TextInput.vue'
import NumberInput from '@/Components/Admin/Inputs/NumberInput.vue'
import SelectInput from '@/Components/Admin/Inputs/SelectInput.vue'
import TextareaInput from '@/Components/Admin/Inputs/TextareaInput.vue'
import CheckboxInput from '@/Components/Admin/Inputs/CheckboxInput.vue'
import ImageUpload from '@/Components/Admin/Inputs/ImageUpload.vue'


const page = usePage()

const props = defineProps({
    game: Object,
    tournaments: Array,
    teams: Array
})

const emit = defineEmits(['close'])

const isEdit = !!props.game

const form = ref({
    tournament_id: props.game?.tournament_id ?? '',
    match_number: props.game?.match_number ?? '',
    home_team_id: props.game?.home_team_id ?? '',
    away_team_id: props.game?.away_team_id ?? '',
    home_slot: props.game?.home_slot ?? '',
    away_slot: props.game?.away_slot ?? '',
    home_score: props.game?.home_score ?? '',
    away_score: props.game?.away_score ?? '',
    winner_team_id: props.game?.winner_team_id ?? '',
    stage: props.game?.stage ?? '',
    venue: props.game?.venue ?? '',
    match_date: props.game?.match_date_input ?? '',
    match_time: props.game?.match_time_input ?? '',

})

const submit = () => {

    const options = {
        onSuccess: () => {
            const flash = page.props.flash

            // if (flash?.success) notifySuccess(flash.success)
            if (flash?.error) notifyError(flash.error)

            emit('close')
        },
        onError: () => notifyError('Validation error')
    }

    // si se están editando resultados
    if (isEdit && form.value.home_score !== '' && form.value.away_score !== '') {

        router.post(
            route('admin.games.result.update', props.game.id),
            {
                winner_team_id: form.value.winner_team_id,
                home_score: form.value.home_score,
                away_score: form.value.away_score
            },
            options
        )

    } else {

        if (isEdit) {
            router.put(
                route('admin.games.update', props.game.id),
                form.value,
                options
            )
        } else {
            router.post(
                route('admin.games.store'),
                form.value,
                options
            )
        }

    }
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4 pb-24">

        <SelectInput
            v-model="form.tournament_id"
            label="Tournament"
            :options="tournaments"
        />
        <TextInput
            v-model="form.match_number"
            label="Match Number"
        />
        <SelectInput
            v-model="form.home_team_id"
            label="Home Team"
            :options="teams"
        />
        <SelectInput
            v-model="form.away_team_id"
            label="Away Team"
            :options="teams"
        />
        <TextInput
            v-model="form.home_score"
            label="Home Score"
        />
        <TextInput
            v-model="form.away_score"
            label="Away Score"
        />
        <TextInput
            v-model="form.venue"
            label="Venue"
        />
        <TextInput
            v-model="form.match_date"
            label="Match Date"
            type="date"
        />
        <TextInput
            v-model="form.match_time"
            label="Match Time"
        />
        <!-- <SelectInput
            v-model="form.winner_team_id"
            label="Winner"
            :options="teams"
        /> -->
        <!-- <TextInput
            v-model="form.stage"
            label="Stage"
        /> -->

        <!-- Buttons -->
        <div class="fixed bottom-0 right-0 flex justify-center w-full max-w-xs pb-4 space-x-4 md:px-4">
        <!-- <div class="bottom-0 left-0 flex justify-center w-full pb-4 space-x-4 md:px-4 md:absolute"> -->
            <button type="submit"
                class="text-white w-full justify-center bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                {{ isEdit ? 'Update' : 'Add Game' }}
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
