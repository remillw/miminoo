<template>
  <div 
    @click="$emit('click', conversation.id)"
    class="p-4 hover:bg-gray-50 cursor-pointer transition-colors border-l-4"
    :class="conversation.unread_count > 0 ? 'border-l-blue-500 bg-blue-50' : 'border-l-transparent'"
  >
    <div class="flex items-start gap-3">
      <!-- Avatar -->
      <div class="flex-shrink-0">
        <img 
          :src="conversation.other_user.avatar || '/images/default-avatar.png'" 
          :alt="conversation.other_user.name"
          class="w-10 h-10 rounded-full object-cover border-2 border-gray-200"
        />
      </div>

      <!-- Contenu -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between mb-1">
          <h4 class="font-medium text-gray-900 truncate">
            {{ conversation.other_user.name }}
          </h4>
          <div class="flex items-center gap-1">
            <!-- Badge non lu -->
            <span v-if="conversation.unread_count > 0"
                  class="bg-blue-500 text-white text-xs px-1.5 py-0.5 rounded-full min-w-[18px] text-center">
              {{ conversation.unread_count }}
            </span>
            <!-- Indicateur accompte -->
            <BadgeCheck v-if="conversation.deposit_paid" 
                           class="w-4 h-4 text-green-500" 
                           title="Accompte payé" />
          </div>
        </div>

        <!-- Infos annonce -->
        <div class="text-xs text-gray-500 mb-1">
          {{ conversation.ad_title }} • {{ conversation.ad_date }}
        </div>

        <!-- Tarif -->
        <div class="text-xs font-medium text-blue-600 mb-2">
          {{ conversation.rate }}€/h
        </div>

        <!-- Dernier message -->
        <p class="text-sm text-gray-600 truncate mb-1" v-html="filterSensitiveInfo(conversation.last_message)">
        </p>

        <!-- Temps -->
        <p class="text-xs text-gray-400">
          {{ conversation.last_message_at }}
        </p>
      </div>
    </div>

    <!-- Statut conversation -->
    <div v-if="conversation.status !== 'active'" class="mt-2">
      <span class="text-xs px-2 py-1 rounded-full" 
            :class="getStatusClasses(conversation.status)">
        {{ getStatusText(conversation.status) }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { BadgeCheck } from 'lucide-vue-next'

const props = defineProps({
  conversation: Object
})

defineEmits(['click'])

function filterSensitiveInfo(text) {
  if (!text) return '';
  
  // Vérifier si le paiement est effectué via props.conversation
  const isPaymentCompleted = props.conversation?.status === 'active' || props.conversation?.deposit_paid;
  
  if (isPaymentCompleted) {
    // Si le paiement est fait, pas de filtrage
    return text;
  }
  
  // Patterns pour détecter les numéros de téléphone
  const phonePatterns = [
    // Numéros français (06, 07, etc.)
    /(?:(?:0|\+33\s?)[1-9](?:[\s.-]?\d{2}){4})/g,
    // Numéros avec indicatifs internationaux
    /(?:\+\d{1,3}[\s.-]?)?(?:\d[\s.-]?){6,14}\d/g,
    // Patterns simples pour 10 chiffres consécutifs
    /\b\d{10}\b/g,
    // Numéros avec espaces ou tirets
    /\b\d{2}[\s.-]?\d{2}[\s.-]?\d{2}[\s.-]?\d{2}[\s.-]?\d{2}\b/g
  ];
  
  let filteredText = text;
  
  // Remplacer les numéros de téléphone par un message de restriction
  phonePatterns.forEach(pattern => {
    filteredText = filteredText.replace(pattern, '<span class="bg-red-100 text-red-600 px-1 py-0.5 rounded text-xs">🔒 Masqué</span>');
  });
  
  // Patterns pour détecter d'autres infos sensibles
  const emailPattern = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/g;
  filteredText = filteredText.replace(emailPattern, '<span class="bg-red-100 text-red-600 px-1 py-0.5 rounded text-xs">🔒 Email masqué</span>');
  
  return filteredText;
}

function getStatusClasses(status) {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800'
    case 'cancelled':
      return 'bg-red-100 text-red-800'
    case 'dispute':
      return 'bg-orange-100 text-orange-800'
    default:
      return 'bg-gray-100 text-gray-600'
  }
}

function getStatusText(status) {
  switch (status) {
    case 'completed':
      return 'Terminée'
    case 'cancelled':
      return 'Annulée'
    case 'dispute':
      return 'Litige'
    default:
      return status
  }
}
</script> 