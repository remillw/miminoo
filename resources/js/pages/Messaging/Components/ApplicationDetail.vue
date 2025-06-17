<template>
  <div class="max-w-2xl mx-auto">
    <!-- En-tête candidature -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
      <div class="flex items-start justify-between mb-4">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ application.ad_title }}</h3>
          <p class="text-sm text-gray-600 flex items-center gap-1 mt-1">
            <Calendar class="w-4 h-4" />
            {{ application.ad_date }}
          </p>
        </div>
        <StatusBadge :status="application.status" :is-expired="application.is_expired" />
      </div>

      <!-- Informations de la babysitter/parent -->
      <div class="flex items-center gap-4 mb-4 p-4 bg-gray-50 rounded-lg">
        <img 
          :src="otherUser.avatar || '/images/default-avatar.png'" 
          :alt="otherUser.name"
          class="w-16 h-16 rounded-full object-cover border-2 border-gray-200"
        />
        <div class="flex-1">
          <h4 class="font-medium text-gray-900">{{ otherUser.name }}</h4>
          <p v-if="userRole === 'parent' && otherUser.experience" class="text-sm text-gray-600">
            {{ otherUser.experience }} {{ otherUser.experience === 1 ? 'an' : 'ans' }} d'expérience
          </p>
        </div>
      </div>

      <!-- Tarif proposé -->
      <div class="mb-4">
        <h4 class="font-medium text-gray-900 mb-2">Tarif proposé</h4>
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
          <div class="flex items-center justify-between">
            <span class="text-lg font-semibold text-blue-900">{{ application.proposed_rate }}€/h</span>
            <span class="text-sm text-blue-600">Tarif horaire</span>
          </div>
        </div>
      </div>

      <!-- Contre-offre si applicable -->
      <div v-if="application.counter_rate" class="mb-4">
        <h4 class="font-medium text-gray-900 mb-2">Contre-offre du parent</h4>
        <div class="bg-secondary rounded-lg p-4 border border-orange-200">
          <div class="flex items-center justify-between mb-2">
            <span class="text-lg font-semibold text-orange-900">{{ application.counter_rate }}€/h</span>
            <span class="text-sm text-primary">Nouveau tarif proposé</span>
          </div>
          <p v-if="application.counter_message" class="text-sm text-orange-800">
            <strong>Message :</strong> {{ application.counter_message }}
          </p>
        </div>
      </div>

      <!-- Message de motivation -->
      <div v-if="application.motivation_note" class="mb-4">
        <h4 class="font-medium text-gray-900 mb-2">Message de motivation</h4>
        <div class="bg-gray-50 rounded-lg p-4">
          <p class="text-gray-700 whitespace-pre-wrap">{{ application.motivation_note }}</p>
        </div>
      </div>

      <!-- Timer d'expiration -->
      <div v-if="application.time_remaining && !application.is_expired" 
           class="mb-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-center gap-2 text-yellow-800">
          <Clock class="w-5 h-5" />
          <span class="font-medium">Expire dans {{ application.time_remaining }}</span>
        </div>
      </div>

      <!-- Actions pour babysitter (répondre à contre-offre) -->
      <div v-if="userRole === 'babysitter' && application.status === 'counter_offered' && !application.is_expired"
           class="border-t pt-4">
        <h4 class="font-medium text-gray-900 mb-3">Répondre à la contre-offre</h4>
        <div class="flex gap-3">
          <button
            @click="$emit('respond-counter', application.id, 'accept')"
            class="flex-1 bg-green-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-green-700 transition-colors"
          >
            Accepter {{ application.counter_rate }}€/h
          </button>
          <button
            @click="$emit('respond-counter', application.id, 'decline')"
            class="flex-1 bg-red-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-red-700 transition-colors"
          >
            Refuser la contre-offre
          </button>
        </div>
        <p class="text-xs text-gray-500 mt-2 text-center">
          Cette offre expire dans {{ application.time_remaining }}
        </p>
      </div>

      <!-- Message d'attente pour parent -->
      <div v-if="userRole === 'parent' && application.status === 'counter_offered' && !application.is_expired"
           class="border-t pt-4 text-center">
        <div class="inline-flex items-center gap-2 text-blue-600">
          <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
          <span class="text-sm font-medium">En attente de la réponse de la babysitter...</span>
        </div>
        <p class="text-xs text-gray-500 mt-1">
          Réponse attendue avant {{ application.time_remaining }}
        </p>
      </div>

      <!-- État final -->
      <div v-if="application.status === 'accepted'" class="border-t pt-4 text-center">
        <div class="inline-flex items-center gap-2 text-green-600 mb-2">
          <CheckCircle class="w-5 h-5" />
          <span class="font-medium">Candidature acceptée</span>
        </div>
        <p class="text-sm text-gray-600">Une conversation a été créée pour organiser les détails.</p>
      </div>

      <div v-if="application.status === 'declined'" class="border-t pt-4 text-center">
        <div class="inline-flex items-center gap-2 text-red-600 mb-2">
          <XCircle class="w-5 h-5" />
          <span class="font-medium">Candidature refusée</span>
        </div>
      </div>

      <div v-if="application.is_expired" class="border-t pt-4 text-center">
        <div class="inline-flex items-center gap-2 text-gray-500 mb-2">
          <Clock class="w-5 h-5" />
          <span class="font-medium">Candidature expirée</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import StatusBadge from './StatusBadge.vue'
import {
  Calendar,
  Clock,
  CheckCircle,
  XCircle
} from 'lucide-vue-next'

const props = defineProps({
  application: Object,
  userRole: String
})

const emit = defineEmits(['accept', 'decline', 'respond-counter'])

const otherUser = computed(() => {
  return props.userRole === 'parent' ? props.application.babysitter : props.application.parent
})
</script> 