<template>
  <div class="flex flex-col h-full">
    <!-- Messages -->
    <div class="flex-1 space-y-4">
      <!-- Message système de création -->
      <div class="text-center">
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded-lg inline-block text-sm">
          <CheckCircle class="w-4 h-4 inline mr-1" />
          Conversation créée - Candidature acceptée
        </div>
        <p class="text-xs text-gray-500 mt-1">Tarif convenu : {{ conversation.rate }}€/h</p>
      </div>

      <!-- Messages de la conversation -->
      <div v-for="message in messages" :key="message.id" class="flex" 
           :class="message.sender_type === userRole ? 'justify-end' : 'justify-start'">
        
        <!-- Message de l'autre utilisateur -->
        <div v-if="message.sender_type !== userRole" class="flex gap-3 max-w-xs lg:max-w-md">
          <img 
            :src="conversation.other_user.avatar || '/images/default-avatar.png'" 
            :alt="conversation.other_user.name"
            class="w-8 h-8 rounded-full object-cover flex-shrink-0"
          />
          <div>
            <div class="bg-gray-200 text-gray-900 px-4 py-2 rounded-lg">
              <p>{{ message.message }}</p>
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ formatTime(message.created_at) }}</p>
          </div>
        </div>

        <!-- Message de l'utilisateur actuel -->
        <div v-else class="flex gap-3 max-w-xs lg:max-w-md">
          <div class="text-right">
            <div class="bg-blue-600 text-white px-4 py-2 rounded-lg">
              <p>{{ message.message }}</p>
            </div>
            <div class="flex items-center justify-end gap-1 mt-1">
              <p class="text-xs text-gray-500">{{ formatTime(message.created_at) }}</p>
              <div v-if="message.read_at" class="flex">
                <Check class="w-3 h-3 text-blue-500" />
                <Check class="w-3 h-3 text-blue-500 -ml-1" />
              </div>
              <div v-else>
                <Check class="w-3 h-3 text-gray-400" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Message d'accompte -->
      <div v-if="!conversation.deposit_paid" class="text-center">
        <div class="bg-yellow-100 border border-yellow-300 text-yellow-800 px-4 py-3 rounded-lg inline-block max-w-md">
          <div class="flex items-center gap-2 mb-2">
            <AlertTriangle class="w-5 h-5" />
            <span class="font-medium">Accompte requis</span>
          </div>
          <p class="text-sm">
            Pour finaliser la réservation, un accompte de <strong>{{ depositAmount }}€</strong> 
            (1h de babysitting + 2€ de frais) doit être payé.
          </p>
          <button 
            v-if="userRole === 'parent'"
            class="mt-3 bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-yellow-700"
            @click="$emit('pay-deposit')"
          >
            Payer l'accompte
          </button>
        </div>
      </div>

      <!-- Indicateur de frappe -->
      <div v-if="isOtherUserTyping" class="flex gap-3 max-w-xs">
        <img 
          :src="conversation.other_user.avatar || '/images/default-avatar.png'" 
          :alt="conversation.other_user.name"
          class="w-8 h-8 rounded-full object-cover flex-shrink-0"
        />
        <div class="bg-gray-200 px-4 py-2 rounded-lg">
          <div class="flex space-x-1">
            <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce"></div>
            <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
            <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  CheckCircle,
  Check,
  AlertTriangle
} from 'lucide-vue-next'

const props = defineProps({
  conversation: Object,
  userRole: String
})

const emit = defineEmits(['pay-deposit'])

// Messages simulés pour l'instant
const messages = computed(() => [
  {
    id: 1,
    message: "Bonjour ! Merci d'avoir accepté ma candidature. J'ai hâte de m'occuper de vos enfants !",
    sender_type: 'babysitter',
    created_at: '2024-01-16T10:30:00Z',
    read_at: '2024-01-16T10:31:00Z'
  },
  {
    id: 2,
    message: "Parfait ! Pouvez-vous me confirmer l'adresse exacte et me donner quelques détails sur les enfants ?",
    sender_type: 'parent',
    created_at: '2024-01-16T10:32:00Z',
    read_at: null
  }
])

const isOtherUserTyping = computed(() => false) // TODO: Implémenter avec WebSocket

const depositAmount = computed(() => {
  return parseFloat(props.conversation.rate) + 2
})

function formatTime(dateString) {
  const date = new Date(dateString)
  return date.toLocaleTimeString('fr-FR', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}
</script> 