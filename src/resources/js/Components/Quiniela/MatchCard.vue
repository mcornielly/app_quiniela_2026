<script setup>
import { computed } from 'vue';

const props = defineProps({
    match: {
        type: Object,
        required: true,
    },
    modelValue: {
        type: Object,
        default: () => ({ home: null, away: null })
    }
});

const emit = defineEmits(['update:modelValue', 'score-changed']);

const homeScore = computed({
    get: () => props.modelValue.home,
    set: (val) => {
        emit('update:modelValue', { ...props.modelValue, home: val === '' ? null : Number(val) });
        emit('score-changed');
    }
});

const awayScore = computed({
    get: () => props.modelValue.away,
    set: (val) => {
        emit('update:modelValue', { ...props.modelValue, away: val === '' ? null : Number(val) });
        emit('score-changed');
    }
});
</script>

<template>
    <div class="relative overflow-hidden rounded-xl bg-white/5 backdrop-blur-xl border border-white/10 p-4 transition-all duration-300 hover:border-white/20 hover:bg-white/10 group">
        <!-- Live indicator -->
        <div v-if="match.status === 'live'" class="absolute top-2 left-2 flex items-center space-x-1">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FF0000] opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-[#FF0000]"></span>
            </span>
            <span class="text-[#FF0000] text-xs font-bold tracking-widest uppercase">Live</span>
        </div>

        <!-- Info -->
        <div class="text-center text-xs text-gray-400 font-sans tracking-tight mb-3">
            Grupo {{ match.group }} &bull; {{ match.date }}
        </div>

        <div class="flex items-center justify-between">
            <!-- Home Team -->
            <div class="flex flex-col items-center w-1/3">
                <span class="text-3xl mb-1 drop-shadow-md">{{ match.homeTeam.flag }}</span>
                <span class="font-bold text-lg text-white font-['Inter']">{{ match.homeTeam.code }}</span>
            </div>

            <!-- Inputs -->
            <div class="flex items-center justify-center space-x-2 w-1/3">
                <input 
                    type="number" 
                    v-model="homeScore"
                    min="0"
                    max="20"
                    class="w-12 h-14 bg-black/40 border border-white/10 rounded-lg text-center text-2xl font-bold text-white focus:ring-[#0057FF] focus:border-[#0057FF] [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none transition-colors duration-200"
                    placeholder="-"
                />
                <span class="text-gray-500 font-bold">:</span>
                <input 
                    type="number" 
                    v-model="awayScore"
                    min="0"
                    max="20"
                    class="w-12 h-14 bg-black/40 border border-white/10 rounded-lg text-center text-2xl font-bold text-white focus:ring-[#0057FF] focus:border-[#0057FF] [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none transition-colors duration-200"
                    placeholder="-"
                />
            </div>

            <!-- Away Team -->
            <div class="flex flex-col items-center w-1/3">
                <span class="text-3xl mb-1 drop-shadow-md">{{ match.awayTeam.flag }}</span>
                <span class="font-bold text-lg text-white font-['Inter']">{{ match.awayTeam.code }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-4 pt-3 border-t border-white/5 flex justify-between items-center text-xs text-gray-400">
            <div>
                <span class="text-[#00F5FF]">{{ match.winProbability || '50%' }}</span> prob. victoria
            </div>
            <div class="flex items-center">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                {{ match.venue }}
            </div>
        </div>
    </div>
</template>
