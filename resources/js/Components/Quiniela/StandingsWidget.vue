<script setup>
const props = defineProps({
    groupName: {
        type: String,
        required: true
    },
    standings: {
        type: Array,
        required: true
    }
});
</script>

<template>
    <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-xl overflow-hidden p-0 w-full transition-all duration-300 hover:border-white/20">
        <div class="bg-black/40 px-4 py-3 border-b border-white/10 flex justify-between items-center">
            <h3 class="text-white font-bold font-['Inter'] tracking-widest text-sm">GRUPO {{ groupName }}</h3>
            <span class="text-xs text-gray-500 font-medium tracking-wide">PTS</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-300">
                <thead class="text-[10px] text-gray-400 bg-black/20 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="px-3 py-2 text-center w-8">#</th>
                        <th scope="col" class="px-2 py-2 w-full">Equipo</th>
                        <th scope="col" class="px-2 py-2 text-center" title="Partidos Jugados">PJ</th>
                        <th scope="col" class="px-2 py-2 text-center" title="Diferencia de Goles">DG</th>
                        <th scope="col" class="px-3 py-2 text-center text-[#00F5FF]">Pts</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, index) in standings" :key="row.team.code" 
                        class="border-b border-white/5 last:border-0 hover:bg-white/10 transition-colors duration-200"
                        :class="{'bg-[#0057FF]/10': index < 2}">
                        <td class="px-3 py-2.5 font-bold text-center" :class="index < 2 ? 'text-[#00F5FF]' : 'text-gray-500'">
                            {{ index + 1 }}
                        </td>
                        <td class="px-2 py-2.5 font-bold text-white flex items-center space-x-2">
                            <span class="text-lg drop-shadow-sm">{{ row.team.flag }}</span>
                            <span class="tracking-wide">{{ row.team.code }}</span>
                        </td>
                        <td class="px-2 py-2.5 text-center text-gray-400">{{ row.played }}</td>
                        <td class="px-2 py-2.5 text-center font-medium" :class="row.gd > 0 ? 'text-emerald-400' : (row.gd < 0 ? 'text-[#FF0000]' : 'text-gray-400')">
                            {{ row.gd > 0 ? '+' : '' }}{{ row.gd }}
                        </td>
                        <td class="px-3 py-2.5 text-center font-black text-lg text-[#00F5FF]">{{ row.points }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="py-2 text-[10px] text-gray-500 bg-black/40 uppercase tracking-widest text-center border-t border-white/5">
            Top 2 avanza a Ronda de 32
        </div>
    </div>
</template>
