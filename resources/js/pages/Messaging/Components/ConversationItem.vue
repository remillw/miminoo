<template>
  <div 
    @click="$emit('click', item)"
    class="p-3 hover:bg-gray-50 cursor-pointer transition-colors border-l-4"
    :class="[
      active ? 'bg-blue-50 border-l-blue-500' : 'border-l-transparent',
      hasUnreadBadge ? 'bg-secondary' : ''
    ]"
  >
    <div class="flex items-start gap-3">
      <!-- Avatar -->
      <div class="flex-shrink-0 relative">
        <img 
          :src="otherUser.avatar || '/images/default-avatar.png'" 
          :alt="otherUser.name"
          class="w-10 h-10 rounded-full object-cover border-2 border-gray-200"
        />
        <!-- Badge status pour candidatures -->
        <div v-if="type === 'application'" class="absolute -top-1 -right-1">
          <div class="w-4 h-4 rounded-full flex items-center justify-center text-xs font-bold text-white"
               :class="getStatusBadgeClass(item.status)">
            {{ getStatusIcon(item.status) }}
          </div>
        </div>
      </div>

      <!-- Contenu -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between mb-1">
          <h4 class="font-medium text-gray-900 truncate">
            {{ otherUser.name }}
          </h4>
          <div class="flex items-center gap-1">
            <!-- Badge non lu -->
            <span v-if="unreadCount > 0"
                  class="bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full min-w-[18px] text-center">
              {{ unreadCount }}
            </span>
            <!-- Badge nouveau (candidature) -->
            <span v-if="type === 'application' && isNew"
                  class="bg-primary text-white text-xs px-2 py-0.5 rounded-full animate-pulse">
              Nouveau
            </span>
          </div>
        </div>

        <!-- Titre annonce -->
        <div class="text-xs text-gray-500 mb-1 truncate">
          {{ item.ad_title }}
        </div>

        <!-- Info spécifique au type -->
        <div v-if="type === 'application'" class="mb-2">
          <!-- Tarif proposé -->
          <div class="text-sm font-medium text-blue-600 mb-1">
            {{ item.proposed_rate }}€/h
            <span v-if="item.counter_rate" class="text-primary">
              → {{ item.counter_rate }}€/h
            </span>
          </div>
          
          <!-- Message de motivation (tronqué) -->
          <p v-if="item.motivation_note" class="text-xs text-gray-600 truncate">
            {{ item.motivation_note }}
          </p>
        </div>

        <div v-else class="mb-2">
          <!-- Dernier message -->
          <p class="text-sm text-gray-600 truncate" v-html="filterSensitiveInfo(item.last_message)">
          </p>
        </div>

        <!-- Temps et actions rapides -->
        <div class="flex items-center justify-between">
          <p class="text-xs text-gray-400">
            {{ getTimeDisplay() }}
          </p>
          
          <!-- Actions rapides pour candidatures (parent) -->
          <div v-if="type === 'application' && userRole === 'parent' && item.status === 'pending'" 
               class="flex gap-1">
            <button
              @click.stop="$emit('quick-action', 'accept', item)"
              class="bg-green-600 text-white px-2 py-1 rounded text-xs hover:bg-green-700"
            >
              ✓
            </button>
            <button
              @click.stop="$emit('quick-action', 'decline', item)"
              class="bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700"
            >
              ✗
            </button>
          </div>

          <!-- Actions rapides pour contre-offres (babysitter) -->
          <div v-if="type === 'application' && userRole === 'babysitter' && item.status === 'counter_offered'" 
               class="flex gap-1">
            <button
              @click.stop="$emit('quick-action', 'accept-counter', item)"
              class="bg-green-600 text-white px-2 py-1 rounded text-xs hover:bg-green-700"
            >
              ✓
            </button>
            <button
              @click.stop="$emit('quick-action', 'decline-counter', item)"
              class="bg-red-600 text-white px-2 py-1 rounded text-xs hover:bg-red-700"
            >
              ✗
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  item: Object,
  type: String, // 'application' ou 'conversation'
  userRole: String,
  active: Boolean
})

const emit = defineEmits(['click', 'quick-action'])

const otherUser = computed(() => {
  if (props.type === 'application') {
    return props.userRole === 'parent' ? props.item.babysitter : props.item.parent
  } else {
    return props.item.other_user
  }
})

const unreadCount = computed(() => {
  if (props.type === 'application') {
    return props.userRole === 'parent' && !props.item.viewed_at ? 1 : 0
  } else {
    return props.item.unread_count || 0
  }
})

const isNew = computed(() => {
  return props.type === 'application' && 
         props.userRole === 'parent' && 
         props.item.status === 'pending' && 
         !props.item.viewed_at
})

const hasUnreadBadge = computed(() => {
  return props.type === 'application' && isNew.value
})

function filterSensitiveInfo(text) {
  if (!text) return '';
  
  // Vérifier si le paiement est effectué
  const isPaymentCompleted = props.item?.status === 'active' || props.item?.deposit_paid;
  
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

function getStatusBadgeClass(status) {
  switch (status) {
    case 'pending':
      return 'bg-yellow-500'
    case 'counter_offered':
      return 'bg-blue-500'
    case 'accepted':
      return 'bg-green-500'
    case 'declined':
      return 'bg-red-500'
    default:
      return 'bg-gray-500'
  }
}

function getStatusIcon(status) {
  switch (status) {
    case 'pending':
      return '⏳'
    case 'counter_offered':
      return '↩'
    case 'accepted':
      return '✓'
    case 'declined':
      return '✗'
    default:
      return '?'
  }
}

function getTimeDisplay() {
  if (props.type === 'application') {
    return props.item.created_at
  } else {
    return props.item.last_message_at
  }
}
</script> 