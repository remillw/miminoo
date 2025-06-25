<template>
    <div class="rounded-3xl bg-white p-6 shadow-md flex flex-col gap-4 max-w-md w-full">
      <!-- Header: Avatar + Name + Rating -->
      <div class="flex items-center gap-4">
        <img :src="avatar" alt="Avatar" class="h-14 w-14 rounded-full object-cover" />
        <div>
          <div class="font-bold text-lg">{{ name }}</div>

          <div class="flex items-center gap-1 text-sm text-gray-400">
            <Star class="h-4 w-4 text-yellow-400 fill-current" />
            <span class="font-semibold text-gray-700">{{ rating }}</span>
            <span class="text-gray-400">({{ reviews }} avis)</span>
          </div>
        </div>
      </div>
  
      <!-- Date, time, location -->
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div class="flex items-start gap-2">
          <CalendarClock class="h-7 w-7 text-primary bg-secondary rounded-md p-1" />
          <div>
            <p class="font-semibold">{{ date }}</p>
            <p class="text-gray-400">{{ time }}</p>
          </div>
        </div>
        <div class="flex items-start gap-2">
          <MapPin class="h-7 w-7 text-primary bg-secondary rounded-md p-1" />
          <div>
            <p class="font-semibold">
              {{ city }}<span class="text-gray-500">, {{ postalCode }}</span>
            </p>
            <!-- Affichage de la distance si disponible -->
            <p v-if="distance !== null" class="text-xs text-gray-500 flex items-center gap-1">
              <span>📍 {{ distance }} km</span>
            </p>
          </div>
        </div>
      </div>
  
      <!-- Kids -->
      <div class="flex items-center gap-2 text-sm">
        <Users class="h-7 w-7 text-primary bg-secondary rounded-md p-1" />
        <p class="font-semibold">{{ childrenLabel }}</p>
      </div>
  
      <!-- Description -->
      <p class="text-sm text-gray-800">
        {{ description }}
      </p>
  
      <!-- Footer -->
      <div class="flex justify-between items-center pt-2 border-t border-gray-200">
        <p>
          <span class="font-bold text-lg">{{ rate }}€</span>
          <span class="text-gray-400">/heure</span>
        </p>
        
        <!-- Bouton Postuler ou message si c'est sa propre annonce -->
        <button 
          v-if="!isOwnAnnouncement"
          @click="isModalOpen = true" 
          class="bg-primary hover:bg-primary text-white font-semibold rounded px-5 py-2 transition"
        >
          Postuler
        </button>
        
        <div v-else class="text-sm text-gray-500 font-medium px-3 py-2 bg-gray-100 rounded">
          Votre annonce
        </div>
      </div>

      <!-- Modal de candidature -->
      <PostulerModal
        :is-open="isModalOpen"
        :on-close="() => isModalOpen = false"
        :announcement-id="id"
        :date="rawDate"
        :hours="time"
        :location="`${city}, ${postalCode}`"
        :children-count="childrenCount"
        :avatar-url="avatar"
        :family-name="name"
        :requested-rate="rate"
        :additional-info="additionalInfo"
        :start-time="startTime"
        :end-time="endTime"
      />
    </div>
  </template>
  
  <script setup lang="ts">
  import { ref, computed } from 'vue';
  import { Star, CalendarClock, MapPin, Users } from 'lucide-vue-next';
  import { usePage } from '@inertiajs/vue3';
  import PostulerModal from './PostulerModal.vue';
  
  const props = defineProps({
    id: Number,
    parentId: Number, // ID du parent propriétaire de l'annonce
    avatar: String,
    name: String,
    rating: Number,
    reviews: Number,
    date: String,
    rawDate: String, // Date au format ISO pour le modal
    time: String,
    startTime: String, // Heure de début pour calcul de durée
    endTime: String, // Heure de fin pour calcul de durée
    postalCode: String,
    city: String,
    childrenLabel: String,
    childrenCount: Number,
    description: String,
    rate: Number,
    distance: {
      type: Number,
      default: null
    },
    latitude: Number,
    longitude: Number,
    additionalInfo: {
      type: String,
      default: null
    },
  });

  const isModalOpen = ref(false);
  
  // Accès à l'utilisateur connecté
  const page = usePage();
  const user = computed(() => page.props.auth?.user);
  
  // Vérifier si c'est la propre annonce de l'utilisateur
  const isOwnAnnouncement = computed(() => {
    return user.value && props.parentId === user.value.id;
  });
  </script>
  