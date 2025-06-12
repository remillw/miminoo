<template>
  <div class="space-y-4">
    <!-- En-tête candidature -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="bg-orange-200 text-orange-800 px-3 py-1 rounded-full text-sm font-medium">
          Candidature - {{ application.status === 'pending' ? 'En attente' : 'En négociation' }}
        </div>
        <div class="text-sm text-gray-600">
          Tarif proposé : <span class="font-semibold text-orange-600">{{ application.proposed_rate }}€/h</span>
          <span v-if="application.counter_rate" class="ml-2">
            → <span class="font-semibold text-blue-600">{{ application.counter_rate }}€/h</span>
          </span>
        </div>
      </div>
    </div>

    <!-- Actions principales -->
    <div class="flex items-center gap-3">
      <!-- Actions pour parents -->
      <template v-if="userRole === 'parent'">
        <button
          @click="handleReserve"
          class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors flex items-center gap-2"
        >
          <Check class="w-4 h-4" />
          Réserver {{ application.counter_rate || application.proposed_rate }}€/h
        </button>
        
        <button
          v-if="!showCounterOffer"
          @click="showCounterOffer = true"
          class="border border-orange-300 text-orange-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors flex items-center gap-2"
        >
          <Euro class="w-4 h-4" />
          Contre-offre
        </button>
        
        <button
          @click="handleDecline"
          class="border border-red-300 text-red-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-opacity transition-colors flex items-center gap-2"
        >
          <X class="w-4 h-4" />
          Refuser
        </button>
      </template>

      <!-- Actions pour babysitter -->
      <template v-if="userRole === 'babysitter' && application.status === 'counter_offered' && application.counter_rate">
        <div class="text-sm text-blue-700 bg-blue-50 px-3 py-2 rounded-lg">
          Contre-offre reçue : <span class="font-semibold">{{ application.counter_rate }}€/h</span>
        </div>
        
        <button
          @click="$emit('respond-counter', application.id, true, application.counter_rate)"
          class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors"
        >
          Accepter
        </button>
        
        <button
          @click="$emit('respond-counter', application.id, false)"
          class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors"
        >
          Refuser
        </button>
      </template>
    </div>

    <!-- Formulaire contre-offre parent -->
    <div v-if="showCounterOffer && userRole === 'parent'" 
         class="bg-secondary border border-orange-200 rounded-lg p-4">
      <h4 class="font-medium text-gray-900 mb-3">Faire une contre-proposition :</h4>
      <div class="flex items-center gap-3">
        <div class="relative">
          <input
            v-model="counterOfferRate"
            type="number"
            step="0.5"
            min="1"
            max="50"
            class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center focus:outline-none focus:ring-2 focus:ring-primary"
            placeholder="20"
          />
          <span class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">€/h</span>
        </div>
        <button
          @click="submitCounterOffer"
          :disabled="!counterOfferRate"
          class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-700 disabled:opacity-50 transition-colors"
        >
          Proposer
        </button>
        <button
          @click="showCounterOffer = false"
          class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors text-sm"
        >
          Annuler
        </button>
      </div>
    </div>

    <!-- Message de motivation -->
    <div v-if="application.motivation_note" class="bg-gray-50 rounded-lg p-3">
      <p class="text-sm text-gray-700 italic">
        "{{ application.motivation_note }}"
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  Check,
  X,
  Euro,
  Clock,
  Calendar,
  MapPin,
  Users
} from 'lucide-vue-next'

const props = defineProps({
  application: Object,
  userRole: String
})

const emit = defineEmits(['reserve', 'decline', 'counter-offer', 'respond-counter', 'babysitter-counter'])

// État local
const showCounterOffer = ref(false)
const counterOfferRate = ref('')

// Computed
const otherUser = computed(() => {
  return props.userRole === 'parent' ? props.application.babysitter : props.application.parent
})

const currentRate = computed(() => {
  return props.application.counter_rate || props.application.proposed_rate
})

// Méthodes
function handleReserve() {
  const rate = props.application.counter_rate || props.application.proposed_rate
  if (confirm(`Réserver cette candidature au tarif de ${rate}€/h ?`)) {
    emit('reserve', props.application.id, rate)
  }
}

function handleDecline() {
  if (confirm('Êtes-vous sûr de vouloir refuser cette candidature ? Elle sera archivée.')) {
    emit('decline', props.application.id)
  }
}

function submitCounterOffer() {
  if (!counterOfferRate.value) return
  
  emit('counter-offer', props.application.id, parseFloat(counterOfferRate.value))
  showCounterOffer.value = false
  counterOfferRate.value = ''
}

function formatTime(dateString) {
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', { 
      day: 'numeric', 
      month: 'long',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (err) {
    return 'Date inconnue'
  }
}
</script> 