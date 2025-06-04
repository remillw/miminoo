<script setup lang="ts">
import { ref, computed } from 'vue'
import { Filter, Search } from 'lucide-vue-next'
import CardAnnonce from '@/components/CardAnnonce.vue'


const tarif = ref(10)
const age = ref('')
const date = ref('')
const lieu = ref('')

const annonces = ref(
  Array.from({ length: 6 }, (_, i) => ({
    id: i,
    avatar: '/storage/babysitter-test.png',
    name: 'Sophie M.',
    rating: 4.9,
    reviews: 3,
    date: '15 mars 2025',
    time: '19:00 - 23:00',
    location: 'Aix-en-Provence',
    childrenLabel: '2 enfants (3, 5 ans)',
    description: 'Maman de deux enfants, je recherche une babysitter pour la soirée, nous avons simplement prévu un restaurant.',
    rate: 15
  }))
)


const resetFilters = () => {
  tarif.value = 10
  age.value = ''
  date.value = ''
  lieu.value = ''

  
}


const progressStyle = computed(() => {
  const percent = ((tarif.value - 10) / (100 - 10)) * 100
  return {
    background: `linear-gradient(to right, #FF8359 ${percent}%, #E5E7EB ${percent}%)`
  }
})

const showFilters = ref(false)
</script>

<template>
  <div class="bg-orange-50 min-h-screen py-16 px-4">
    <div class="mx-auto max-w-7xl">
      <!-- Titre + Sous-titre -->
      <h1 class="text-center text-2xl font-bold md:text-3xl mb-2">
        Trouver votre prochain babysitting
      </h1>
      <p class="text-center text-gray-600 mb-10 max-w-xl mx-auto">
        Découvrez des opportunités de garde d'enfants qui correspondent à vos disponibilités et à vos compétences
      </p>

<!-- Barre de recherche + bouton filtre -->
<div class="flex justify-center items-center gap-2 mb-10">
  <div class="relative w-full max-w-3xl flex">
    <input
      type="text"
      placeholder="Rechercher par lieu, nom des parents, mots-clé…"
      class="w-full rounded-l-xl border border-gray-300 bg-white py-4 pl-12 pr-4 text-base placeholder-gray-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary"
    />
    <Search class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
    <button
      @click="showFilters = !showFilters"
      :aria-pressed="showFilters"
      :class="[
        'px-5 rounded-r-xl border-l-0 border border-gray-300 transition flex items-center',
        showFilters
          ? 'bg-primary border-primary'
          : 'bg-white hover:bg-gray-100'
      ]"
    >
      <Filter :class="['w-5 h-5', showFilters ? 'text-white' : 'text-gray-500']" />
    </button>
  </div>
</div>



      <!-- Bloc filtres -->
      <div
        v-if="showFilters"
        class="rounded-2xl bg-white p-6 shadow-md max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-6"
      >
        <!-- Tarif -->
        <div>
    <h3 class="font-semibold text-lg mb-6">Tarif horaire minimum</h3>
    <div class="flex items-center gap-4">
      <span class="text-sm font-semibold text-gray-900 w-10 text-right">{{ tarif }}€</span>

      <input
        type="range"
        min="10"
        max="100"
        step="5"
        v-model="tarif"
        class="w-full h-2 appearance-none rounded-full transition-all duration-150"
        :style="progressStyle"
      />

      <span class="text-sm font-semibold text-gray-900 w-10">100€</span>
    </div>
  </div>



      <!-- Âge -->
<div>
  <h3 class="font-semibold text-lg mb-3">Âge des enfants</h3>
  <div class="relative">
    <select
  v-model="age"
  class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 pr-10 text-base text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary appearance-none"
>
  <option value="">Tous les âges</option>
  <option value="<3">&lt; 3 ans</option>
  <option value="3-6">3 - 6 ans</option>
  <option value="6+">+ 6 ans</option>
</select>

    <svg
      class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
    </svg>
  </div>
</div>

<!-- Date -->
<div>
  <h3 class="font-semibold text-lg mb-3">Date</h3>
  <div class="relative">
    <input
  type="date"
  v-model="date"
  class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 pr-10 text-base text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
/>

    <svg
      class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
      />
    </svg>
  </div>
</div>


<!-- Lieu -->
<div>
  <h3 class="font-semibold text-lg mb-3">Lieu</h3>
  <div class="relative">
    <input
  type="text"
  v-model="lieu"
  placeholder="Dans quelle ville ?"
  class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2 pr-10 text-base text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary"
/>

    <svg
      class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
      stroke="currentColor"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M12 11c1.104 0 2-.896 2-2s-.896-2-2-2-2 .896-2 2 .896 2 2 2z"
      />
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"
      />
    </svg>
  </div>
</div>


        <!-- Boutons -->
        <div class="md:col-span-4 flex justify-end gap-4 pt-4">
          <button
  class="border border-gray-300 bg-white px-6 py-2 rounded-md text-sm font-semibold hover:bg-gray-100"
  @click="resetFilters"
>
  Réinitialiser
</button>

          <button
            class="bg-primary text-white px-6 py-2 rounded-md text-sm font-semibold hover:bg-orange-500"
          >
            Appliquer
          </button>
        </div>
      </div>

 <!-- Liste des annonces -->
 <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mt-10">
    <CardAnnonce
      v-for="annonce in annonces"
      :key="annonce.id"
      v-bind="annonce"
    />
  </div>

  <!-- Pagination -->
  <div class="flex justify-center mt-10 gap-2">
    <button class="w-9 h-9 flex items-center justify-center rounded-full border border-gray-300 bg-white text-gray-400 hover:bg-gray-100">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
    </button>
    <button class="w-9 h-9 rounded-full bg-primary text-white font-bold">1</button>
    <button class="w-9 h-9 flex items-center justify-center rounded-full border border-gray-300 bg-white text-gray-400 hover:bg-gray-100">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </button>
  </div>
    </div>
  </div>
</template>

<style scoped>
input[type='range'] {
  height: 8px; /* plus fin pour s’aligner au pouce */
}

input[type='range']::-webkit-slider-thumb {
  -webkit-appearance: none;
  height: 16px;
  width: 16px;
  border-radius: 50%;
  background-color: #FF8359;
  cursor: pointer;
  margin-top: 0px; /* ajusté pour centrer */
  box-shadow: 0 0 2px rgba(0, 0, 0, 0.2);
  transition: background 0.2s ease;
}

input[type='range']::-moz-range-thumb {
  height: 16px;
  width: 16px;
  border-radius: 50%;
  background-color: #FF8359;
  cursor: pointer;
  box-shadow: 0 0 2px rgba(0, 0, 0, 0.2);
}

/* Masque l'icône native du champ date sur Chrome/Safari */
input[type="date"]::-webkit-calendar-picker-indicator {
  opacity: 0;
  cursor: pointer;
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  opacity: 0; /* masque l’icône réelle du navigateur */
  width: 100%; /* étend la zone cliquable */
  height: 100%;
  cursor: pointer;
}


</style>