<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import MatchCard from '@/Components/Quiniela/MatchCard.vue';
import StandingsWidget from '@/Components/Quiniela/StandingsWidget.vue';

// 1. Initial State Definition 
// In a real app, this comes from an API or Props
const teams = {
    MEX: { code: 'MEX', name: 'México', flag: '🇲🇽', group: 'A' },
    CAN: { code: 'CAN', name: 'Canadá', flag: '🇨🇦', group: 'A' },
    USA: { code: 'USA', name: 'Estados Unidos', flag: '🇺🇸', group: 'A' },
    GER: { code: 'GER', name: 'Alemania', flag: '🇩🇪', group: 'A' },
    
    BRA: { code: 'BRA', name: 'Brasil', flag: '🇧🇷', group: 'B' },
    ARG: { code: 'ARG', name: 'Argentina', flag: '🇦🇷', group: 'B' },
    FRA: { code: 'FRA', name: 'Francia', flag: '🇫🇷', group: 'B' },
    ENG: { code: 'ENG', name: 'Inglaterra', flag: '🏴󠁧󠁢󠁥󠁮󠁧󠁿', group: 'B' },
    
    ESP: { code: 'ESP', name: 'España', flag: '🇪🇸', group: 'C' },
    POR: { code: 'POR', name: 'Portugal', flag: '🇵🇹', group: 'C' },
    ITA: { code: 'ITA', name: 'Italia', flag: '🇮🇹', group: 'C' },
    NED: { code: 'NED', name: 'Países Bajos', flag: '🇳🇱', group: 'C' }
};

const initialMatches = [
    // Group A
    { id: 1, group: 'A', homeTeam: teams.MEX, awayTeam: teams.CAN, date: '11 JUN 2026', venue: 'Estadio Azteca', winProbability: '60%' },
    { id: 2, group: 'A', homeTeam: teams.USA, awayTeam: teams.GER, date: '11 JUN 2026', venue: 'SoFi Stadium', winProbability: '40%' },
    { id: 3, group: 'A', homeTeam: teams.MEX, awayTeam: teams.USA, date: '15 JUN 2026', venue: 'Estadio Azteca', winProbability: '50%' },
    { id: 4, group: 'A', homeTeam: teams.CAN, awayTeam: teams.GER, date: '15 JUN 2026', venue: 'BMO Field', winProbability: '30%' },
    { id: 5, group: 'A', homeTeam: teams.GER, awayTeam: teams.MEX, date: '20 JUN 2026', venue: 'MetLife Stadium', winProbability: '55%' },
    { id: 6, group: 'A', homeTeam: teams.CAN, awayTeam: teams.USA, date: '20 JUN 2026', venue: 'BC Place', winProbability: '45%' },
    
    // Group B
    { id: 7, group: 'B', homeTeam: teams.BRA, awayTeam: teams.ARG, date: '12 JUN 2026', venue: 'Maracaná', winProbability: '50%' },
    { id: 8, group: 'B', homeTeam: teams.FRA, awayTeam: teams.ENG, date: '12 JUN 2026', venue: 'Wembley', winProbability: '55%' },
    { id: 9, group: 'B', homeTeam: teams.BRA, awayTeam: teams.FRA, date: '16 JUN 2026', venue: 'Estadio Azteca', winProbability: '48%' },
    { id: 10, group: 'B', homeTeam: teams.ARG, awayTeam: teams.ENG, date: '16 JUN 2026', venue: 'MetLife Stadium', winProbability: '52%' },
    { id: 11, group: 'B', homeTeam: teams.ENG, awayTeam: teams.BRA, date: '21 JUN 2026', venue: 'SoFi Stadium', winProbability: '45%' },
    { id: 12, group: 'B', homeTeam: teams.ARG, awayTeam: teams.FRA, date: '21 JUN 2026', venue: 'BC Place', winProbability: '50%' },
    
    // Group C
    { id: 13, group: 'C', homeTeam: teams.ESP, awayTeam: teams.POR, date: '13 JUN 2026', venue: 'Santiago Bernabéu', winProbability: '50%' },
    { id: 14, group: 'C', homeTeam: teams.ITA, awayTeam: teams.NED, date: '13 JUN 2026', venue: 'San Siro', winProbability: '50%' },
    { id: 15, group: 'C', homeTeam: teams.ESP, awayTeam: teams.ITA, date: '17 JUN 2026', venue: 'Estadio Azteca', winProbability: '55%' },
    { id: 16, group: 'C', homeTeam: teams.POR, awayTeam: teams.NED, date: '17 JUN 2026', venue: 'MetLife Stadium', winProbability: '45%' },
    { id: 17, group: 'C', homeTeam: teams.NED, awayTeam: teams.ESP, date: '22 JUN 2026', venue: 'SoFi Stadium', winProbability: '40%' },
    { id: 18, group: 'C', homeTeam: teams.POR, awayTeam: teams.ITA, date: '22 JUN 2026', venue: 'BC Place', winProbability: '50%' }
];

const matches = ref(initialMatches);

// 2. State & Predictions
const predictions = reactive({});
initialMatches.forEach(m => {
    predictions[m.id] = { home: null, away: null };
});

const currentTab = ref('groups');

// 3. Debounce Implementation
const debouncedPredictions = ref(JSON.parse(JSON.stringify(predictions)));
let debounceTimeout = null;

const onScoreChanged = () => {
    if (debounceTimeout) clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        debouncedPredictions.value = JSON.parse(JSON.stringify(predictions));
    }, 500); // 500ms delay as requested
};

// 4. Progress Tracking
const progress = computed(() => {
    // There are 104 matches in the 2026 World Cup format
    const TOTAL_MATCHES = 104; 
    const filled = Object.values(predictions).filter(p => p.home !== null && p.away !== null).length;
    return {
        filled,
        total: TOTAL_MATCHES,
        percentage: Math.min(100, Math.round((filled / TOTAL_MATCHES) * 100))
    };
});

// 5. Standings Logic
const calculateStandings = (groupMatches, predState) => {
    const standingsMap = {};

    groupMatches.forEach(m => {
        if (!standingsMap[m.homeTeam.code]) standingsMap[m.homeTeam.code] = { team: m.homeTeam, played: 0, won: 0, drawn: 0, lost: 0, gf: 0, ga: 0, gd: 0, points: 0 };
        if (!standingsMap[m.awayTeam.code]) standingsMap[m.awayTeam.code] = { team: m.awayTeam, played: 0, won: 0, drawn: 0, lost: 0, gf: 0, ga: 0, gd: 0, points: 0 };
        
        const score = predState[m.id];
        if (score && score.home !== null && score.away !== null) {
            const hScore = Number(score.home);
            const aScore = Number(score.away);

            [m.homeTeam.code, m.awayTeam.code].forEach(code => standingsMap[code].played++);
            
            standingsMap[m.homeTeam.code].gf += hScore;
            standingsMap[m.homeTeam.code].ga += aScore;
            standingsMap[m.awayTeam.code].gf += aScore;
            standingsMap[m.awayTeam.code].ga += hScore;

            if (hScore > aScore) {
                standingsMap[m.homeTeam.code].won++;
                standingsMap[m.homeTeam.code].points += 3;
                standingsMap[m.awayTeam.code].lost++;
            } else if (hScore < aScore) {
                standingsMap[m.awayTeam.code].won++;
                standingsMap[m.awayTeam.code].points += 3;
                standingsMap[m.homeTeam.code].lost++;
            } else {
                standingsMap[m.homeTeam.code].drawn++;
                standingsMap[m.homeTeam.code].points += 1;
                standingsMap[m.awayTeam.code].drawn++;
                standingsMap[m.awayTeam.code].points += 1;
            }
        }
    });

    // 6. Recalculate GD and Sort
    Object.values(standingsMap).forEach(team => {
        team.gd = team.gf - team.ga;
    });

    return Object.values(standingsMap).sort((a, b) => {
        if (b.points !== a.points) return b.points - a.points; // Points priority
        if (b.gd !== a.gd) return b.gd - a.gd;                 // Goal Difference
        return b.gf - a.gf;                                    // Goals For
    });
};

const groups = computed(() => {
    return [...new Set(matches.value.map(m => m.group))].sort();
});

const groupedMatches = computed(() => {
    const map = {};
    matches.value.forEach(m => {
        if (!map[m.group]) map[m.group] = [];
        map[m.group].push(m);
    });
    return map;
});

const standings = computed(() => {
    const res = {};
    groups.value.forEach(g => {
        const groupMatches = groupedMatches.value[g];
        res[g] = calculateStandings(groupMatches, debouncedPredictions.value);
    });
    return res;
});

</script>

<template>
    <AppLayout>
        <Head title="La Quiniela Definitiva 2026" />

        <div class="h-full bg-[#050505]/50 rounded-2xl overflow-hidden backdrop-blur-xl border border-white/5 text-white font-sans selection:bg-[#0057FF] selection:text-white">
            
        <!-- Top Progress Tracker (Fixed) -->
        <div class="sticky top-0 z-50 bg-[#050505]/80 backdrop-blur-xl border-b border-white/10 shadow-2xl">
            <div class="max-w-7xl mx-auto px-4 md:px-8 py-4 flex flex-col md:flex-row items-center justify-between gap-4">
                
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-[#0057FF] to-[#00F5FF] flex items-center justify-center font-black italic shadow-[0_0_15px_rgba(0,87,255,0.5)]">
                        W
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-black font-['Inter'] tracking-tight">
                            LA QUINIELA <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#0057FF] to-[#00F5FF]">DEFINITIVA</span>
                        </h1>
                        <p class="text-[10px] md:text-xs text-gray-400 font-medium tracking-widest uppercase">Mundial 2026 &bull; Elite Predictor</p>
                    </div>
                </div>

                <div class="w-full md:w-1/3 max-w-sm">
                    <div class="flex justify-between text-sm font-medium mb-1.5 px-1">
                        <span class="text-gray-300">Progreso global</span>
                        <div class="font-bold">
                            <span class="text-white">{{ progress.filled }}</span>
                            <span class="text-gray-500 mx-1">/</span>
                            <span class="text-gray-500">{{ progress.total }}</span>
                        </div>
                    </div>
                    <div class="w-full bg-white/5 rounded-full h-2.5 overflow-hidden border border-white/5">
                        <div 
                            class="h-full rounded-full transition-all duration-700 ease-out bg-gradient-to-r from-[#0057FF] via-[#00F5FF] to-emerald-400 relative overflow-hidden" 
                            :style="{ width: `${progress.percentage}%` }"
                        >
                            <div class="absolute inset-0 bg-white/20 w-full h-full animate-[shimmer_2s_infinite] -translate-x-full"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="max-w-7xl mx-auto px-4 md:px-8 mt-8">
            <nav class="flex space-x-1 md:space-x-4 border-b border-white/10" aria-label="Tabs">
                <button 
                    @click="currentTab = 'groups'"
                    :class="[
                        currentTab === 'groups' ? 'border-[#0057FF] text-white bg-white/5' : 'border-transparent text-gray-400 hover:text-gray-200 hover:bg-white/5', 
                        'px-4 md:px-6 py-3 border-b-2 font-bold text-sm md:text-base transition-all duration-300 rounded-t-lg'
                    ]"
                >
                    Fase de Grupos
                </button>
                <button 
                    @click="currentTab = 'bracket'"
                    :class="[
                        currentTab === 'bracket' ? 'border-[#0057FF] text-white bg-white/5' : 'border-transparent text-gray-400 hover:text-gray-200 hover:bg-white/5', 
                        'px-4 md:px-6 py-3 border-b-2 font-bold text-sm md:text-base transition-all duration-300 rounded-t-lg flex items-center space-x-2'
                    ]"
                >
                    <span>Eliminatorias</span>
                    <span class="px-2 py-0.5 rounded-full bg-gradient-to-r from-[#FF0000]/20 to-[#FF0000]/10 border border-[#FF0000]/30 text-[10px] text-[#FF0000] tracking-widest uppercase">Live Bracket</span>
                </button>
            </nav>
        </div>

        <main class="max-w-7xl mx-auto px-4 md:px-8 py-8 pb-32">
            
            <!-- GROUP STAGE VIEW -->
            <div v-if="currentTab === 'groups'" class="animate-[fadeIn_0.5s_ease-out]">
                
                <!-- Header Info -->
                <div class="mb-8 flex items-center justify-between bg-white/5 border border-[#0057FF]/30 p-4 rounded-xl backdrop-blur-sm">
                    <p class="text-sm text-gray-300">
                        <strong class="text-[#00F5FF]">Tip:</strong> Los dos primeros de cada grupo y los 8 mejores terceros viajarán automáticamente a la Ronda de 32 basándose en tus predicciones.
                    </p>
                </div>

                <!-- Dynamic Grid: 1 col (Móvil), 2 cols (Tablet), 3 cols (Desktop) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-8">
                    
                    <template v-for="groupName in groups" :key="groupName">
                        <div class="space-y-4">
                            <!-- Reactive Mini-table -->
                            <StandingsWidget 
                                :groupName="groupName" 
                                :standings="standings[groupName] || []" 
                            />
                            
                            <!-- Spacing -->
                            <div class="h-2"></div>
                            
                            <!-- Match Cards -->
                            <div class="space-y-4">
                                <MatchCard 
                                    v-for="match in groupedMatches[groupName]" 
                                    :key="match.id" 
                                    :match="match"
                                    v-model="predictions[match.id]"
                                    @score-changed="onScoreChanged"
                                />
                            </div>
                        </div>
                    </template>

                </div>
            </div>

            <!-- KNOCKOUT VIEW (Bracket placeholder) -->
            <div v-else-if="currentTab === 'bracket'" class="flex items-center justify-center min-h-[50vh] animate-[fadeIn_0.5s_ease-out]">
                <div class="text-center bg-white/5 border border-white/10 rounded-2xl p-12 max-w-xl backdrop-blur-xl relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-b from-[#0057FF]/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
                    
                    <div class="relative z-10">
                        <div class="w-20 h-20 mx-auto bg-[#0057FF]/20 rounded-full flex items-center justify-center border border-[#0057FF]/50 mb-6 shadow-[0_0_30px_rgba(0,87,255,0.3)]">
                            <svg class="w-10 h-10 text-[#00F5FF]" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                        </div>
                        
                        <h2 class="text-3xl font-black font-['Inter'] mb-3 tracking-tight">Ronda de 32</h2>
                        <p class="text-gray-400 mb-8 leading-relaxed">
                            El bracket se conectará automáticamente. Completa la fase de grupos para ver cómo las posiciones 1 y 2, junto con los 8 mejores terceros, viajan hacia la final.
                        </p>
                        
                        <button @click="currentTab = 'groups'" class="px-8 py-3 bg-[#0057FF] hover:bg-[#0057FF]/80 text-white rounded-xl font-bold transition-all duration-300 border border-[#00F5FF]/30 hover:border-[#00F5FF] shadow-[0_0_15px_rgba(0,87,255,0.4)] hover:shadow-[0_0_25px_rgba(0,245,255,0.5)] transform hover:-translate-y-1">
                            Completar Grupos
                        </button>
                    </div>
                </div>
            </div>

        </main>
    </div>
    </AppLayout>
</template>

<style>
@keyframes shimmer {
    100% {
        transform: translateX(100%);
    }
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
/* Optional: Adding some global scrollbar styling for the dark theme */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
::-webkit-scrollbar-track {
    background: #050505; 
}
::-webkit-scrollbar-thumb {
    background: #333; 
    border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
    background: #0057FF; 
}
</style>
