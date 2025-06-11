<template>
  <div class="p-6 hover:bg-gray-50 transition-colors">
    <div class="flex items-start justify-between">
      <div class="flex items-start gap-4 flex-1">
        <!-- Avatar -->
        <div class="flex-shrink-0">
          <img 
            :src="otherUser.avatar || '/images/default-avatar.png'" 
            :alt="otherUser.name"
            class="w-12 h-12 rounded-full object-cover border-2 border-gray-200"
          />
        </div>

        <!-- Contenu principal -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-3 mb-2">
            <h3 class="font-semibold text-gray-900 truncate">{{ otherUser.name }}</h3>
            
            <!-- Badge statut -->
            <StatusBadge :status="application.status" :is-expired="application.is_expired" />
            
            <!-- Badge non-lu (parent uniquement) -->
            <span v-if="userRole === 'parent' && !application.viewed_at && application.status === 'pending'"
                  class="bg-red-500 text-white text-xs px-2 py-1 rounded-full animate-pulse">
              Nouveau
            </span>
          </div>

          <!-- Infos annonce -->
          <div class="text-sm text-gray-600 mb-3">
            <p class="font-medium">{{ application.ad_title }}</p>
            <p class="flex items-center gap-1 mt-1">
              <Calendar class="w-4 h-4" />
              {{ application.ad_date }}
            </p>
          </div>

          <!-- Message motivation -->
          <div v-if="application.motivation_note" class="bg-gray-50 rounded-lg p-3 mb-3">
            <p class="text-sm text-gray-700">{{ application.motivation_note }}</p>
          </div>

          <!-- Tarif proposé -->
          <div class="flex items-center gap-4 mb-3">
            <span class="text-sm font-medium text-gray-900">
              Tarif proposé : {{ application.proposed_rate }}€/h
            </span>
            <span v-if="userRole === 'parent' && application.babysitter.experience" 
                  class="text-xs text-gray-500">
              {{ application.babysitter.experience }} {{ application.babysitter.experience === 1 ? 'an' : 'ans' }} d'expérience
            </span>
          </div>

          <!-- Contre-offre -->
          <div v-if="application.counter_rate" class="bg-blue-50 rounded-lg p-3 mb-3 border border-blue-200">
            <div class="flex items-center gap-2 mb-2">
              <RotateCcw class="w-4 h-4 text-blue-600" />
              <span class="text-sm font-medium text-blue-900">Contre-offre : {{ application.counter_rate }}€/h</span>
            </div>
            <p v-if="application.counter_message" class="text-sm text-blue-800">
              {{ application.counter_message }}
            </p>
          </div>

          <!-- Timer expiration -->
          <div v-if="application.time_remaining && !application.is_expired" 
               class="flex items-center gap-2 text-xs text-orange-600 mb-3">
            <Clock class="w-4 h-4" />
            <span>Expire dans {{ application.time_remaining }}</span>
          </div>

          <!-- Date -->
          <p class="text-xs text-gray-500">{{ application.created_at }}</p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex-shrink-0 ml-4">
        <ApplicationActions
          :application="application"
          :user-role="userRole"
          @viewed="$emit('viewed', application.id)"
          @accept="handleAccept"
          @decline="$emit('decline', application.id)"
          @counter-offer="$emit('counter-offer', application)"
          @respond-counter="$emit('respond-counter', application.id, $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import StatusBadge from './StatusBadge.vue'
import ApplicationActions from './ApplicationActions.vue'
import {
  Calendar,
  Clock,
  RotateCcw
} from 'lucide-vue-next'

const props = defineProps({
  application: Object,
  userRole: String
})

const emit = defineEmits(['viewed', 'accept', 'decline', 'counter-offer', 'respond-counter'])

const otherUser = computed(() => {
  return props.userRole === 'parent' ? props.application.babysitter : props.application.parent
})

function handleAccept() {
  // Si contre-offre, accepter avec le tarif de contre-offre
  const finalRate = props.application.counter_rate || props.application.proposed_rate
  emit('accept', props.application, finalRate)
}
</script> 