<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, ref } from "vue";
import { Head} from '@inertiajs/vue3';

const title = 'Dashboard';

const props = defineProps({
    filters: { type: Object, required: true }
})


/**
 * Template-only (mock data).
 * - Resultados del día
 * - Próximos juegos
 * - Grupos (tabla por grupo)
 * - Quiniela 2026 (Top 15) + link a lista completa
 */

// --- Tournament (WC 2026 example groups from your image) ---
const tournament = ref({
  id: 1,
  name: "World Cup 2026",
  season: "2026",
  timezone: "America/New_York",
  groups: [
    { key: "A", name: "Grupo A", teams: ["México", "Korea del Sur", "Sudáfrica", "Repechaje UEFA"] },
    { key: "B", name: "Grupo B", teams: ["Canadá", "Suiza", "Qatar", "Repechaje UEFA"] },
    { key: "C", name: "Grupo C", teams: ["Brasil", "Marruecos", "Escocia", "Haití"] },
    { key: "D", name: "Grupo D", teams: ["Estados Unidos", "Australia", "Paraguay", "Repechaje UEFA"] },
    { key: "E", name: "Grupo E", teams: ["Alemania", "Ecuador", "Costa de Marfil", "Curazao"] },
    { key: "F", name: "Grupo F", teams: ["Países Bajos", "Japón", "Túnez", "Repechaje UEFA"] },
    { key: "G", name: "Grupo G", teams: ["Bélgica", "Irán", "Egipto", "Nueva Zelanda"] },
    { key: "H", name: "Grupo H", teams: ["España", "Uruguay", "Arabia Saudita", "Cabo Verde"] },
    { key: "I", name: "Grupo I", teams: ["Francia", "Senegal", "Noruega", "Repechaje"] },
    { key: "J", name: "Grupo J", teams: ["Argentina", "Austria", "Argelia", "Jordania"] },
    { key: "K", name: "Grupo K", teams: ["Portugal", "Colombia", "Uzbekistán", "Repechaje"] },
    { key: "L", name: "Grupo L", teams: ["Inglaterra", "Croacia", "Panamá", "Ghana"] },
  ],
});

// --- Mock schedule / matches (calendar) ---
const matches = ref([
  // Today results
  {
    id: 101,
    stage: "Grupos",
    group: "A",
    date: "2026-06-12",
    time: "19:00",
    home: "México",
    away: "Sudáfrica",
    status: "FT",
    scoreHome: 2,
    scoreAway: 1,
    venue: "Estadio Azteca",
  },
  {
    id: 102,
    stage: "Grupos",
    group: "B",
    date: "2026-06-12",
    time: "21:00",
    home: "Canadá",
    away: "Qatar",
    status: "FT",
    scoreHome: 1,
    scoreAway: 1,
    venue: "BC Place",
  },

  // Upcoming
  {
    id: 201,
    stage: "Grupos",
    group: "C",
    date: "2026-06-13",
    time: "18:00",
    home: "Brasil",
    away: "Escocia",
    status: "NS",
    scoreHome: null,
    scoreAway: null,
    venue: "SoFi Stadium",
  },
  {
    id: 202,
    stage: "Grupos",
    group: "D",
    date: "2026-06-13",
    time: "20:00",
    home: "Estados Unidos",
    away: "Australia",
    status: "NS",
    scoreHome: null,
    scoreAway: null,
    venue: "MetLife Stadium",
  },
  {
    id: 203,
    stage: "Grupos",
    group: "E",
    date: "2026-06-14",
    time: "17:00",
    home: "Alemania",
    away: "Ecuador",
    status: "NS",
    scoreHome: null,
    scoreAway: null,
    venue: "NRG Stadium",
  },
]);

// --- Quiniela participants (top 15) ---
const quiniela = ref({
  id: 9001,
  name: "Quiniela 2026",
  participants: Array.from({ length: 22 }).map((_, i) => ({
    id: i + 1,
    name: `Participante ${String(i + 1).padStart(2, "0")}`,
    points: Math.max(0, 42 - i * 2),
    exacts: Math.max(0, 12 - Math.floor(i / 2)),
    hits: Math.max(0, 18 - Math.floor(i / 1.5)),
    updatedAt: "hoy 3:20pm",
  })),
});

// --- UI state ---
const selectedGroup = ref("A");

// Helpers
const today = "2026-06-12";

const todayResults = computed(() => matches.value.filter((m) => m.date === today && m.status === "FT"));
const upcoming = computed(() => matches.value.filter((m) => m.status !== "FT").slice(0, 6));
const top15 = computed(() =>
  [...quiniela.value.participants].sort((a, b) => b.points - a.points).slice(0, 15)
);

// Basic group standings mock (template). Later you will replace with backend-calculated standings.
const standingsByGroup = computed(() => {
  const base = {};
  tournament.value.groups.forEach((g) => {
    base[g.key] = g.teams.map((t, idx) => ({
      team: t,
      played: 1,
      won: idx === 0 ? 1 : 0,
      draw: idx === 1 ? 1 : 0,
      lost: idx > 1 ? 1 : 0,
      gf: 2 - Math.min(idx, 2),
      ga: 1 + Math.min(idx, 2),
      gd: (2 - Math.min(idx, 2)) - (1 + Math.min(idx, 2)),
      pts: idx === 0 ? 3 : idx === 1 ? 1 : 0,
    }));
  });
  return base;
});

const selectedStandings = computed(() => standingsByGroup.value[selectedGroup.value] || []);

const groupMatches = computed(() =>
  matches.value.filter((m) => m.group === selectedGroup.value).slice(0, 8)
);

const badgeByStatus = (status) => {
  if (status === "FT") return "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300";
  if (status === "LIVE") return "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300";
  return "bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300";
};

const statusLabel = (status) => {
  if (status === "FT") return "Final";
  if (status === "LIVE") return "En vivo";
  return "Próximo";
};

</script>

<template>
  <AdminLayout :title="title">
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Header -->
      <div class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
        <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                Dashboard — {{ quiniela.name }}
              </h1>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ tournament.name }} · Temporada {{ tournament.season }} · Zona: {{ tournament.timezone }}
              </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <Link
                href="#"
                class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-700"
              >
                Ver calendario
              </Link>

              <Link
                href="#"
                class="inline-flex items-center rounded-lg bg-blue-700 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
              >
                Ir a mi quiniela
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Content wrapper -->
      <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-4 py-6 sm:px-6 lg:grid-cols-12 lg:px-8">
        <!-- LEFT -->
        <div class="space-y-6 lg:col-span-7">
          <!-- CARD -->
          <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-base font-semibold text-gray-900 dark:text-white">Sección izquierda 1</h2>
              <span class="text-xs text-gray-500 dark:text-gray-400">Texto</span>
            </div>

            <!-- SLOT: tu contenido -->
            <div class="rounded-lg border border-dashed border-gray-200 p-6 text-center dark:border-gray-700">
              <p class="text-sm text-gray-500 dark:text-gray-400">Contenido aquí…</p>
            </div>
          </div>

          <!-- CARD -->
          <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-base font-semibold text-gray-900 dark:text-white">Sección izquierda 2</h2>
              <Link href="#" class="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">
                Acción
              </Link>
            </div>

            <!-- SLOT: tu contenido -->
            <div class="rounded-lg border border-dashed border-gray-200 p-6 text-center dark:border-gray-700">
              <p class="text-sm text-gray-500 dark:text-gray-400">Contenido aquí…</p>
            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <div class="space-y-6 lg:col-span-5">
          <!-- CARD -->
          <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-base font-semibold text-gray-900 dark:text-white">Sección derecha 1</h2>
              <Link href="#" class="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">
                Acción
              </Link>
            </div>

            <!-- SLOT: tu contenido -->
            <div class="rounded-lg border border-dashed border-gray-200 p-6 text-center dark:border-gray-700">
              <p class="text-sm text-gray-500 dark:text-gray-400">Contenido aquí…</p>
            </div>
          </div>

          <!-- CARD -->
          <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-base font-semibold text-gray-900 dark:text-white">Sección derecha 2</h2>
              <Link href="#" class="text-sm font-medium text-blue-700 hover:underline dark:text-blue-400">
                Acción
              </Link>
            </div>

            <!-- SLOT: tu contenido -->
            <div class="rounded-lg border border-dashed border-gray-200 p-6 text-center dark:border-gray-700">
              <p class="text-sm text-gray-500 dark:text-gray-400">Contenido aquí…</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
