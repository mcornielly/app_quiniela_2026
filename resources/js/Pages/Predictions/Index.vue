<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PredictionCard from './Partials/PredictionCard.vue';
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    matches: {
        type: Array,
        default: () => []
    }, // Array completo de los 104 partidos
    initialPredictions: {
        type: Object,
        default: () => ({})
    }
});

const activeTab = ref('GRUPOS');
const phases = ['GRUPOS', 'R32', 'OCTAVOS', 'CUARTOS', 'SEMIS', 'FINAL'];

// Agrupación de partidos por fase
const filteredMatches = computed(() => {
    if (activeTab.value === 'GRUPOS') {
        return props.matches.filter(m => m.phase === 'Group');
    }
    return props.matches.filter(m => m.phase === activeTab.value);
});

// Tracker de Progreso
const totalPredicted = computed(() => {
    return Object.keys(props.initialPredictions).length;
});
const progressPercent = computed(() => (totalPredicted.value / 104) * 100);

</script>

<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="bg-white/5 backdrop-blur-xl p-6 rounded-3xl border border-white/10">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-black italic uppercase tracking-tighter text-white">Mi Quiniela Total</h1>
                        <p class="text-gray-400">Predice todos los resultados del mundial 2026</p>
                    </div>
                    <div class="w-full md:w-64 text-right">
                        <div class="flex justify-between text-xs mb-1 uppercase font-bold text-blue-400">
                            <span>Progreso Total</span>
                            <span>{{ totalPredicted }} / 104</span>
                        </div>
                        <div class="w-full bg-white/10 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full transition-all duration-500" :style="{ width: progressPercent + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex overflow-x-auto gap-2 p-1 bg-black/20 rounded-2xl no-scrollbar">
                <button v-for="phase in phases" :key="phase"
                    @click="activeTab = phase"
                    :class="[activeTab === phase ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20' : 'text-gray-500 hover:text-gray-300']"
                    class="px-6 py-2 rounded-xl text-sm font-bold transition-all uppercase whitespace-nowrap">
                    {{ phase }}
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <PredictionCard 
                    v-for="match in filteredMatches" 
                    :key="match.id" 
                    :match="match"
                    :prediction="initialPredictions[match.id]"
                />
            </div>
        </div>
    </AppLayout>
</template>
