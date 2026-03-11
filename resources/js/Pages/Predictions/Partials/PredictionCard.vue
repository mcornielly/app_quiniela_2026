<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';

const props = defineProps({
    match: {
        type: Object,
        required: true
    },
    prediction: {
        type: Object,
        default: () => ({ home_score: null, away_score: null })
    }
});

const homeScore = ref(props.prediction?.home_score ?? '');
const awayScore = ref(props.prediction?.away_score ?? '');
const saving = ref(false);
const lastSaved = ref(false);

const savePrediction = debounce(async () => {
    // Only save if both scores are numeric/filled out
    if (homeScore.value === '' || awayScore.value === '') return;
    
    saving.value = true;
    try {
        await axios.post(route('predictions.store'), {
            match_id: props.match.id,
            home_score: homeScore.value,
            away_score: awayScore.value
        });
        lastSaved.value = true;
        setTimeout(() => lastSaved.value = false, 2000);
    } catch (e) {
        console.error("Error al guardar la predicción", e);
    } finally {
        saving.value = false;
    }
}, 800);

watch([homeScore, awayScore], () => savePrediction());
</script>

<template>
    <div class="group relative bg-white/5 backdrop-blur-md border border-white/10 p-5 rounded-2xl hover:border-blue-500/50 transition-all overflow-hidden">
        
        <!-- Saved indicator -->
        <div v-if="lastSaved" class="absolute top-2 right-2 flex items-center text-[10px] text-emerald-400 font-bold uppercase animate-pulse">
             ✓ Guardado
        </div>
        
        <!-- Saving indicator -->
        <div v-if="saving" class="absolute top-2 right-2 flex items-center text-[10px] text-blue-400 font-bold uppercase animate-pulse">
             Guardando...
        </div>

        <div class="flex items-center justify-between gap-4">
            <div class="flex-1 flex flex-col items-center gap-2">
                <img :src="`/flags/${match.home_team_code}.svg`" class="w-12 h-8 object-cover rounded shadow-lg shadow-black/40" :alt="match.home_team_name" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzMzMyI+PHBhdGggZD0iTTEyIDJDMi40IDIgMCAyLjQgMCAxMlMyLjQgMjIgMTIgMjJTMjIgMjEuNiAyMiAxMlMyMS42IDIgMTIgMnptMCAxOEM2LjUgMjAgMiA1LjUgMiAxMnM0LjUtMTAgMTAtMTAgMTAgNC41IDEwIDEwcy00LjUgMTAtMTAgMTB6bTAgLTJDMi41IDE4IDAgNC41IDAgMTJzMi41LTEwIDEwLTEwIDEwIDQuNSAxMCAxMHMtNC41IDEwLTEwIDEweiIgLz48L3N2Zz4='"/>
                <span class="text-sm font-bold text-center leading-tight whitespace-nowrap">{{ match.home_team_name || 'TBD' }}</span>
            </div>

            <div class="flex items-center gap-2 bg-black/40 p-2 rounded-xl border border-white/5">
                <input v-model="homeScore" type="number" min="0" 
                    class="w-12 h-12 bg-white/5 border-none rounded-lg text-center text-xl font-black focus:ring-2 focus:ring-blue-500 text-white p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                <span class="text-gray-600 font-bold text-xl">-</span>
                <input v-model="awayScore" type="number" min="0" 
                    class="w-12 h-12 bg-white/5 border-none rounded-lg text-center text-xl font-black focus:ring-2 focus:ring-blue-500 text-white p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
            </div>

            <div class="flex-1 flex flex-col items-center gap-2">
                <img :src="`/flags/${match.away_team_code}.svg`" class="w-12 h-8 object-cover rounded shadow-lg shadow-black/40" :alt="match.away_team_name" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzMzMyI+PHBhdGggZD0iTTEyIDJDMi40IDIgMCAyLjQgMCAxMlMyLjQgMjIgMTIgMjJTMjIgMjEuNiAyMiAxMlMyMS42IDIgMTIgMnptMCAxOEM2LjUgMjAgMiA1LjUgMiAxMnM0LjUtMTAgMTAtMTAgMTAgNC41IDEwIDEwcy00LjUgMTAtMTAgMTB6bTAgLTJDMi41IDE4IDAgNC41IDAgMTJzMi41LTEwIDEwLTEwIDEwIDQuNSAxMCAxMHMtNC41IDEwLTEwLTEwIDEweiIgLz48L3N2Zz4='"/>
                <span class="text-sm font-bold text-center leading-tight whitespace-nowrap">{{ match.away_team_name || 'TBD' }}</span>
            </div>
        </div>

        <div class="mt-4 flex justify-between items-center text-[10px] text-gray-500 uppercase tracking-widest border-t border-white/5 pt-3">
            <span>{{ match.venue || 'Por definir' }}</span>
            <span class="bg-white/10 px-2 py-0.5 rounded text-gray-300 italic font-medium">{{ match.date_label || 'Por definir' }}</span>
        </div>
    </div>
</template>
