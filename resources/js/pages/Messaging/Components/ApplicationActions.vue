<template>
  <div class="flex flex-col gap-2">
    <!-- Actions pour parent -->
    <template v-if="userRole === 'parent'">
      <!-- Marquer comme vue -->
      <button
        v-if="!application.viewed_at && application.status === 'pending'"
        @click="$emit('viewed')"
        class="text-xs text-blue-600 hover:text-blue-800 transition-colors"
      >
        Marquer comme vue
      </button>

      <!-- Actions pour candidature en attente -->
      <div v-if="application.status === 'pending' && !application.is_expired" class="flex flex-col gap-2">
        <button
          @click="$emit('accept')"
          class="bg-green-600 text-white px-3 py-1.5 rounded text-sm font-medium hover:bg-green-700 transition-colors"
          :title="`Accepter la candidature au tarif de ${application.proposed_rate}€/h`"
        >
          Accepter
        </button>
        <button
          @click="$emit('counter-offer')"
          class="bg-blue-600 text-white px-3 py-1.5 rounded text-sm font-medium hover:bg-blue-700 transition-colors"
          title="Proposer un tarif différent"
        >
          Contre-offre
        </button>
        <button
          @click="$emit('decline')"
          class="bg-red-600 text-white px-3 py-1.5 rounded text-sm font-medium hover:bg-red-700 transition-colors"
          title="Refuser cette candidature"
        >
          Refuser
        </button>
      </div>

      <!-- Actions pour contre-offre en attente -->
      <div v-if="application.status === 'counter_offered' && !application.is_expired" class="text-center">
        <p class="text-xs text-gray-600 mb-2">En attente de réponse</p>
        <div class="flex items-center justify-center">
          <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
        </div>
      </div>
    </template>

    <!-- Actions pour babysitter -->
    <template v-if="userRole === 'babysitter'">
      <!-- Répondre à une contre-offre -->
      <div v-if="application.status === 'counter_offered' && !application.is_expired" class="flex flex-col gap-2">
        <p class="text-xs text-blue-600 font-medium mb-1">Répondre à la contre-offre :</p>
        <button
          @click="$emit('respond-counter', 'accept')"
          class="bg-green-600 text-white px-3 py-1.5 rounded text-sm font-medium hover:bg-green-700 transition-colors"
          :title="`Accepter la contre-offre de ${application.counter_rate}€/h et procéder au paiement`"
        >
          Accepter {{ application.counter_rate }}€/h
        </button>
        <button
          @click="$emit('respond-counter', 'decline')"
          class="bg-red-600 text-white px-3 py-1.5 rounded text-sm font-medium hover:bg-red-700 transition-colors"
          title="Refuser la contre-offre et retourner au tarif initial"
        >
          Refuser
        </button>
      </div>

      <!-- Candidature en attente -->
      <div v-if="application.status === 'pending' && !application.is_expired" class="text-center">
        <p class="text-xs text-gray-600">En attente de réponse</p>
        <div class="flex items-center justify-center mt-1">
          <div class="animate-pulse rounded-full h-2 w-2 bg-yellow-400"></div>
        </div>
      </div>
    </template>

    <!-- États finaux -->
    <div v-if="application.status === 'accepted'" class="text-center">
      <CheckCircle class="w-5 h-5 text-green-600 mx-auto mb-1" />
      <p class="text-xs text-green-600 font-medium">Acceptée</p>
    </div>

    <div v-if="application.status === 'declined'" class="text-center">
      <XCircle class="w-5 h-5 text-red-600 mx-auto mb-1" />
      <p class="text-xs text-red-600 font-medium">Refusée</p>
    </div>

    <div v-if="application.is_expired" class="text-center">
      <Clock class="w-5 h-5 text-gray-400 mx-auto mb-1" />
      <p class="text-xs text-gray-500 font-medium">Expirée</p>
    </div>
  </div>
</template>

<script setup>
import {
  CheckCircle,
  XCircle,
  Clock
} from 'lucide-vue-next'

defineProps({
  application: Object,
  userRole: String
})

defineEmits(['viewed', 'accept', 'decline', 'counter-offer', 'respond-counter'])
</script> 