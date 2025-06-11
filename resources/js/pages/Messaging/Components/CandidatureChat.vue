<template>
  <div class="space-y-6">
    <!-- Messages de candidature dans le chat -->
    <div class="space-y-4">
      <!-- Message initial de candidature comme bulle de chat -->
      <div class="flex gap-3">
        <img 
          :src="otherUser.avatar || '/images/default-avatar.png'" 
          :alt="otherUser.name"
          class="w-10 h-10 rounded-full object-cover flex-shrink-0"
        />
        <div class="flex-1 max-w-2xl">
          <div class="bg-orange-50 border border-orange-200 rounded-2xl rounded-tl-sm p-4">
            <!-- En-tête candidature -->
            <div class="mb-3">
              <div class="flex items-center gap-2 mb-2">
                <span class="font-semibold text-gray-900">{{ otherUser.name }}</span>
                <span class="bg-orange-200 text-orange-800 text-xs px-3 py-1 rounded-full font-medium">
                  Candidature
                </span>
              </div>
              
              <!-- Détails de l'annonce -->
              <div class="bg-gradient-to-r from-orange-100 to-red-100 rounded-lg p-3 mb-3">
                <h4 class="text-sm font-semibold text-red-600 mb-2">{{ userRole === 'parent' ? 'Candidature pour votre annonce' : 'Votre candidature' }}</h4>
                <div class="flex items-center gap-4 text-xs text-gray-600 mb-2">
                  <span class="flex items-center gap-1">
                    <Calendar class="w-3 h-3" />
                    15 mars 2024
                  </span>
                  <span class="flex items-center gap-1">
                    <Clock class="w-3 h-3" />
                    19:00 - 23:00
                  </span>
                  <span class="flex items-center gap-1">
                    <MapPin class="w-3 h-3" />
                    Paris 16e
                  </span>
                  <span class="flex items-center gap-1">
                    <Users class="w-3 h-3" />
                    2 enfants
                  </span>
                </div>
                <div class="flex items-center justify-between">
                  <div class="text-lg font-bold text-red-600">€ {{ currentRate }}/h</div>
                  <div v-if="application.counter_rate" class="text-xs bg-orange-200 text-orange-800 px-2 py-1 rounded-full">
                    {{ getRateLabel() }}
                  </div>
                </div>
              </div>

              <!-- Actions pour parents -->
              <div v-if="userRole === 'parent' && application.status === 'pending'" class="flex gap-2 mb-3">
                <button
                  @click="handleReserve"
                  class="flex-1 bg-green-600 text-white px-3 py-1.5 rounded-md text-xs font-medium hover:bg-green-700 transition-colors flex items-center justify-center gap-1"
                >
                  <Check class="w-3 h-3" />
                  Réserver
                </button>
                <button
                  @click="handleDecline"
                  class="flex-1 bg-red-600 text-white px-3 py-1.5 rounded-md text-xs font-medium hover:bg-red-700 transition-colors flex items-center justify-center gap-1"
                >
                  <X class="w-3 h-3" />
                  Refuser
                </button>
                <button
                  @click="toggleCounterOffer"
                  class="flex-1 border border-orange-300 text-orange-700 px-3 py-1.5 rounded-md text-xs font-medium hover:bg-orange-50 transition-colors flex items-center justify-center gap-1"
                >
                  <Euro class="w-3 h-3" />
                  Contre-offre
                </button>
              </div>

              <!-- Actions pour babysitter (répondre à contre-offre du parent) -->
              <div v-if="userRole === 'babysitter' && application.status === 'counter_offered' && application.counter_rate && !isOwnCounterOffer" 
                   class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
                <div class="flex items-center gap-2 mb-2">
                  <Clock class="w-4 h-4 text-blue-600" />
                  <span class="text-sm font-medium text-blue-800">Contre-offre reçue : {{ application.counter_rate }}€/h</span>
                </div>
                <p class="text-sm text-blue-700 mb-3">
                  Le parent vous propose {{ application.counter_rate }}€/h au lieu de {{ application.proposed_rate }}€/h.
                </p>
                <div class="flex gap-2">
                  <button
                    @click="$emit('respond-counter', application.id, 'accept')"
                    class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors"
                  >
                    Accepter {{ application.counter_rate }}€/h
                  </button>
                  <button
                    @click="$emit('respond-counter', application.id, 'decline')"
                    class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors"
                  >
                    Refuser
                  </button>
                  <button
                    @click="toggleBabysitterCounterOffer"
                    class="border border-orange-300 text-orange-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-50 transition-colors"
                  >
                    Contre-proposer
                  </button>
                </div>
              </div>

              <!-- Section contre-offre parent -->
              <div v-if="showCounterOffer && userRole === 'parent' && application.status === 'pending'" 
                   class="bg-white/70 rounded-lg p-4 border border-orange-300 mb-3">
                <h4 class="font-medium text-gray-900 mb-3">Faire une contre-proposition :</h4>
                <div class="flex items-center gap-3">
                  <div class="relative">
                    <input
                      v-model="counterOfferRate"
                      type="number"
                      step="0.5"
                      min="1"
                      max="50"
                      class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                      placeholder="20"
                    />
                    <span class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">€/h</span>
                  </div>
                  <button
                    @click="submitCounterOffer"
                    :disabled="!counterOfferRate"
                    class="bg-orange-500 text-white px-6 py-2 rounded-lg font-medium hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    Proposer
                  </button>
                  <button
                    @click="showCounterOffer = false"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
                  >
                    Annuler
                  </button>
                </div>
              </div>

              <!-- Section contre-offre babysitter -->
              <div v-if="showBabysitterCounterOffer && userRole === 'babysitter'" 
                   class="bg-white/70 rounded-lg p-4 border border-blue-300 mb-3">
                <h4 class="font-medium text-gray-900 mb-3">Votre contre-proposition :</h4>
                <div class="flex items-center gap-3">
                  <div class="relative">
                    <input
                      v-model="babysitterCounterRate"
                      type="number"
                      step="0.5"
                      min="1"
                      max="50"
                      class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-center focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="20"
                    />
                    <span class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">€/h</span>
                  </div>
                  <button
                    @click="submitBabysitterCounterOffer"
                    :disabled="!babysitterCounterRate"
                    class="bg-blue-500 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    Proposer
                  </button>
                  <button
                    @click="showBabysitterCounterOffer = false"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
                  >
                    Annuler
                  </button>
                </div>
              </div>
            </div>

            <!-- Message de candidature -->
            <p class="text-gray-800 leading-relaxed mb-3">{{ application.motivation_note || 'Bonjour ! Je suis très intéressée par votre annonce. J\'ai 5 ans d\'expérience avec les enfants de cet âge et je suis disponible aux horaires demandés. J\'adore les activités créatives et je peux aider aux devoirs si besoin. Je propose 16€/h au lieu de 15€/h car j\'ai une certification premiers secours.' }}</p>
            <p class="text-xs text-gray-500 font-medium">{{ formatTime(application.created_at) }}</p>
          </div>
        </div>
      </div>

      <!-- Messages de contre-offre (pour parents) -->
      <div v-if="application.counter_rate && lastCounterOfferByParent" class="flex gap-3 justify-end">
        <div class="flex-1 flex justify-end">
          <div class="bg-blue-600 text-white rounded-2xl rounded-tr-sm p-4 max-w-md">
            <div class="flex items-center gap-2 mb-2">
              <span class="font-semibold">Vous</span>
              <span class="bg-blue-500 text-white text-xs px-3 py-1 rounded-full font-medium">
                Contre-offre : {{ application.counter_rate }}€/h
              </span>
            </div>
            <p class="leading-relaxed">{{ application.counter_message || 'Je vous propose ce tarif.' }}</p>
            <p class="text-xs text-blue-200 mt-2 font-medium">Envoyé</p>
          </div>
        </div>
        <img 
          src="/images/default-avatar.png" 
          alt="Vous"
          class="w-10 h-10 rounded-full object-cover flex-shrink-0"
        />
      </div>

      <!-- Messages de contre-offre reçue (pour babysitters) -->
      <div v-if="application.counter_rate && !lastCounterOfferByParent && userRole === 'babysitter'" class="flex gap-3">
        <img 
          :src="otherUser.avatar || '/images/default-avatar.png'" 
          :alt="otherUser.name"
          class="w-10 h-10 rounded-full object-cover flex-shrink-0"
        />
        <div class="flex-1">
          <div class="bg-blue-50 border border-blue-200 rounded-2xl rounded-tl-sm p-4 max-w-2xl">
            <div class="flex items-center gap-2 mb-3">
              <span class="font-semibold text-gray-900">{{ otherUser.name }}</span>
              <span class="bg-blue-200 text-blue-800 text-xs px-3 py-1 rounded-full font-medium">
                Contre-offre
              </span>
              <span class="bg-blue-500 text-white text-xs px-3 py-1 rounded-full font-medium">
                {{ application.counter_rate }}€/h
              </span>
            </div>
            <p class="text-gray-800 leading-relaxed">{{ application.counter_message || 'Je vous propose ce nouveau tarif.' }}</p>
            <p class="text-xs text-gray-500 mt-3 font-medium">{{ formatTime(application.counter_offered_at) }}</p>
          </div>
        </div>
      </div>

      <!-- Messages d'état -->
      <div v-if="application.status === 'accepted'" class="flex justify-center">
        <div class="bg-green-100 border border-green-300 text-green-800 px-6 py-3 rounded-full text-sm font-medium flex items-center gap-2">
          <CheckCircle class="w-4 h-4" />
          Candidature réservée ! Paiement requis pour confirmer.
        </div>
      </div>

      <div v-if="application.status === 'declined'" class="flex justify-center">
        <div class="bg-red-100 border border-red-300 text-red-800 px-6 py-3 rounded-full text-sm font-medium flex items-center gap-2">
          <XCircle class="w-4 h-4" />
          Candidature refusée
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useToast } from '@/composables/useToast'
import {
  Calendar,
  MapPin,
  Users,
  Clock,
  Check,
  X,
  Euro,
  CheckCircle,
  XCircle
} from 'lucide-vue-next'

const props = defineProps({
  application: Object,
  userRole: String
})

const emit = defineEmits(['reserve', 'decline', 'counter-offer', 'respond-counter', 'babysitter-counter'])

const { showSuccess, showWarning, showInfo } = useToast()
const showCounterOffer = ref(false)
const showBabysitterCounterOffer = ref(false)
const counterOfferRate = ref('')
const babysitterCounterRate = ref('')

const otherUser = computed(() => {
  return props.userRole === 'parent' ? props.application.babysitter : props.application.parent
})

const currentRate = computed(() => {
  return props.application.counter_rate || props.application.proposed_rate
})

const isOwnCounterOffer = computed(() => {
  // Pour déterminer si la dernière contre-offre vient du user actuel
  // TODO: Implémenter une logique plus robuste avec timestamps ou user_id
  return false
})

const lastCounterOfferByParent = computed(() => {
  // Pour afficher le message de contre-offre du bon côté
  // TODO: Implémenter une logique basée sur who made the last counter offer
  return props.userRole === 'parent' && props.application.counter_rate
})

function getRateLabel() {
  if (props.userRole === 'parent') {
    return 'Votre contre-offre'
  } else {
    return props.application.counter_rate > props.application.proposed_rate ? 'Contre-offre acceptée' : 'Contre-offre reçue'
  }
}

function formatTime(dateString) {
  if (!dateString) return 'Il y a quelques minutes'
  
  try {
    const date = new Date(dateString)
    const now = new Date()
    
    // Vérifier si la date est valide
    if (isNaN(date.getTime())) {
      return 'Il y a quelques minutes'
    }
    
    const diffInMinutes = Math.floor((now - date) / (1000 * 60))
    
    if (diffInMinutes < 1) return 'À l\'instant'
    if (diffInMinutes < 60) return `Il y a ${diffInMinutes} min`
    if (diffInMinutes < 1440) {
      const hours = Math.floor(diffInMinutes / 60)
      return `Il y a ${hours}h`
    }
    const days = Math.floor(diffInMinutes / 1440)
    return `Il y a ${days} jour${days > 1 ? 's' : ''}`
  } catch (error) {
    console.error('Erreur formatTime:', error)
    return 'Il y a quelques minutes'
  }
}

function toggleCounterOffer() {
  showCounterOffer.value = !showCounterOffer.value
  if (showCounterOffer.value) {
    counterOfferRate.value = ''
  }
}

function toggleBabysitterCounterOffer() {
  showBabysitterCounterOffer.value = !showBabysitterCounterOffer.value
  if (showBabysitterCounterOffer.value) {
    babysitterCounterRate.value = ''
  }
}

function handleReserve() {
  if (confirm("Réserver cette candidature ? Le processus de paiement sera lancé.")) {
    emit('reserve', props.application)
    showSuccess("Candidature réservée !", "Procédez au paiement pour confirmer la réservation.")
  }
}

function handleDecline() {
  if (confirm("Êtes-vous sûr de vouloir refuser cette candidature ? Elle sera archivée définitivement.")) {
    emit('decline', props.application.id)
    showInfo("Candidature refusée", "La conversation a été archivée.")
  }
}

function submitCounterOffer() {
  if (counterOfferRate.value) {
    emit('counter-offer', props.application.id, {
      counter_rate: parseFloat(counterOfferRate.value),
      counter_message: ''
    })
    showCounterOffer.value = false
    counterOfferRate.value = ''
    
    showSuccess("Contre-offre envoyée !", `Votre proposition de ${counterOfferRate.value}€/h a été envoyée.`)
  }
}

function submitBabysitterCounterOffer() {
  if (babysitterCounterRate.value) {
    emit('babysitter-counter', props.application.id, {
      counter_rate: parseFloat(babysitterCounterRate.value),
      counter_message: ''
    })
    showBabysitterCounterOffer.value = false
    babysitterCounterRate.value = ''
    
    showSuccess("Contre-proposition envoyée !", `Votre proposition de ${babysitterCounterRate.value}€/h a été envoyée au parent.`)
  }
}
</script> 