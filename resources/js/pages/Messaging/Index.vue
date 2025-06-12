<template>
    <DashboardLayout :currentMode="currentMode">
    <div class="flex h-[calc(100vh-200px)] bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
        <!-- Sidebar conversations/candidatures -->
        <div class="w-80 bg-white border-r border-gray-200 flex flex-col flex-shrink-0">
        <!-- Header avec recherche -->
        <div class="p-4 border-b border-gray-200">
          <h1 class="text-xl font-semibold text-gray-900 mb-3">Messagerie</h1>
          <p class="text-sm text-gray-500 mb-3">{{ userRole === 'parent' ? 'Gérez vos candidatures et conversations' : 'Vos candidatures et conversations' }}</p>
          
          <!-- Barre de recherche -->
          <div class="relative">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
            <input
              type="text"
              placeholder="Rechercher..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </div>

        <!-- Liste des conversations/candidatures -->
        <div class="flex-1 overflow-y-auto w-full">
          <div v-for="conversation in conversations" :key="conversation.id" class="border-b border-gray-100 last:border-b-0">
            <div
              @click="selectConversation(conversation)"
              class="flex items-start gap-3 p-4 hover:bg-gray-50 cursor-pointer transition-all duration-200"
              :class="{ 'bg-secondary border-r-3 border-r-primary shadow-sm': isSelected(conversation) }"
            >
              <!-- Avatar avec badge statut -->
              <div class="relative flex-shrink-0">
                <img 
                  :src="conversation.other_user.avatar || '/default-avatar.svg'" 
                  :alt="conversation.other_user.name"
                  class="w-12 h-12 rounded-full object-cover ring-2 ring-gray-100"
                />
                <!-- Badge candidature -->
                <div v-if="conversation.type === 'application'" 
                     class="absolute -top-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold text-white shadow-sm"
                     :class="getApplicationBadgeClass(conversation.application?.status)">
                  {{ getApplicationBadgeIcon(conversation.application?.status) }}
                </div>
                <!-- Badge en ligne -->
                <div v-else-if="conversation.other_user.online" 
                     class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
              </div>

              <!-- Contenu -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between mb-1">
                  <div class="flex items-center gap-2 min-w-0">
                    <h4 class="font-semibold text-gray-900 truncate">{{ conversation.other_user.name }}</h4>
                    <!-- Badge candidature -->
                    <span v-if="conversation.type === 'application'" 
                          class="bg-orange-100 text-orange-700 text-xs px-2 py-1 rounded-full font-medium flex-shrink-0">
                      {{ conversation.application?.status === 'pending' ? 'Candidature' : 'Négociation' }}
                    </span>
                    <!-- Badge statut conversation -->
                    <span v-else-if="conversation.status === 'payment_required'"
                          class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded-full font-medium flex-shrink-0">
                      Paiement requis
                    </span>
                  </div>
                  <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                    <!-- Badge non lu -->
                    <span v-if="conversation.unread_count > 0"
                          class="bg-red-500 text-white text-xs px-2 py-1 rounded-full min-w-[20px] text-center font-medium">
                      {{ conversation.unread_count }}
                    </span>
                    <!-- Heure -->
                    <span class="text-xs text-gray-500 font-medium">{{ formatTimeAgo(conversation.last_message_at) }}</span>
                  </div>
                </div>
                
                <!-- Aperçu du contenu -->
                <p class="text-sm text-gray-600 leading-5 mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                  {{ conversation.last_message }}
                </p>
                
                <!-- Tarif pour candidatures -->
                <div v-if="conversation.type === 'application' && conversation.application" class="flex items-center gap-2">
                  <span class="text-sm font-semibold text-orange-600 bg-secondary px-2 py-1 rounded">
                    {{ conversation.application.proposed_rate }}€/h
                  </span>
                  <span v-if="conversation.application.counter_rate" class="text-xs text-gray-500">
                    →
                  </span>
                  <span v-if="conversation.application.counter_rate" class="text-sm font-semibold text-red-600 bg-primary-opacity px-2 py-1 rounded">
                    {{ conversation.application.counter_rate }}€/h
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- État vide -->
          <div v-if="conversations.length === 0" class="p-6 text-center text-gray-500">
            <MessagesSquare class="w-12 h-12 mx-auto mb-3 text-gray-300" />
            <p class="text-sm">{{ userRole === 'parent' ? 'Aucune candidature reçue' : 'Aucune candidature envoyée' }}</p>
          </div>
        </div>
      </div>

      <!-- Zone de chat -->
      <div class="flex-1 flex flex-col">
        <div v-if="selectedConversation" class="h-full flex flex-col">
          <!-- En-tête de chat -->
          <div class="px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <img 
                  :src="selectedConversation.other_user.avatar || '/default-avatar.svg'" 
                  :alt="selectedConversation.other_user.name"
                  class="w-10 h-10 rounded-full object-cover"
                />
                <div>
                  <h3 class="font-medium text-gray-900">{{ selectedConversation.other_user.name }}</h3>
                  <p class="text-sm text-gray-500">
                    {{ selectedConversation.type === 'application' ? 'Candidature en cours' : 'Conversation active' }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Zone de messages avec scroll - hauteur limitée -->
          <div class="flex-1 overflow-hidden bg-gray-50 flex flex-col min-h-0">
            <!-- Candidature avec chat intégré -->
            <div v-if="selectedConversation.type === 'application'" class="h-full flex flex-col">
              <!-- En-tête candidature -->
              <div class="bg-secondary border-b border-orange-200 p-4 flex-shrink-0">
                <CandidatureChat 
                  :application="selectedConversation.application" 
                  :user-role="userRole"
                  @reserve="reserveApplication"
                  @decline="archiveConversation"
                  @counter-offer="submitCounterOffer"
                  @respond-counter="respondToCounterOffer"
                  @babysitter-counter="submitBabysitterCounterOffer"
                />
              </div>
              
              <!-- Messages de chat - zone scrollable -->
              <div class="flex-1 overflow-y-auto min-h-0">
                <ChatMessages 
                  :conversation="selectedConversation"
                  :user-role="userRole"
                  ref="chatMessagesRef"
                />
              </div>
            </div>

            <!-- Conversation normale -->
            <div v-else class="h-full flex flex-col">
              <div class="flex-1 overflow-y-auto min-h-0">
                <ChatMessages 
                  :conversation="selectedConversation"
                  :user-role="userRole"
                  ref="chatMessagesRef"
                />
              </div>
            </div>
          </div>

          <!-- Zone de saisie - TOUJOURS VISIBLE -->
          <div class="border-t border-gray-200 bg-white p-4 flex-shrink-0">
            <ChatInput 
              @send="sendMessage" 
              @message-sent="onMessageSent"
              @typing="onTyping"
              :disabled="selectedConversation.status === 'payment_required' || selectedConversation.status === 'archived'" 
              :placeholder="getInputPlaceholder()"
              :conversation-id="selectedConversation.id"
            />
          </div>
        </div>

        <!-- État vide -->
        <div v-else class="flex-1 flex items-center justify-center bg-gray-50">
          <div class="text-center text-gray-500">
            <MessageSquare class="w-16 h-16 mx-auto mb-4 text-gray-300" />
            <p class="text-lg font-medium mb-2">Sélectionnez une conversation</p>
            <p class="text-sm">Choisissez une candidature ou conversation pour commencer</p>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useUserMode } from '@/composables/useUserMode'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import CandidatureChat from './Components/CandidatureChat.vue'
import ChatMessages from './Components/ChatMessages.vue'
import ChatInput from './Components/ChatInput.vue'
import {
  MessageSquare,
  MessagesSquare,
  Search
} from 'lucide-vue-next'

const props = defineProps({
  conversations: Array,
  userRole: String,
  hasParentRole: Boolean,
  hasBabysitterRole: Boolean,
  requestedMode: String
})

const { currentMode, initializeMode } = useUserMode()

// Initialiser le mode au montage du composant
onMounted(() => {
  initializeMode(
    props.hasParentRole,
    props.hasBabysitterRole,
    props.requestedMode
  )
  
  // Debug Echo
  console.log('🔧 Echo disponible:', !!window.Echo)
  console.log('🔧 Echo connector:', window.Echo?.connector?.name)
  console.log('🔧 Echo state:', window.Echo?.connector?.pusher?.connection?.state)
})

// Refs
const selectedConversation = ref(null)
const isLoading = ref(true)
const chatMessagesRef = ref(null)

// Utiliser les conversations des props
const conversations = computed(() => props.conversations || [])

// Helpers
function selectConversation(conversation) {
  console.log('🔄 Changement de conversation:', conversation.id, conversation.type)
  selectedConversation.value = conversation
  
  // Marquer comme vue automatiquement pour les candidatures
  if (conversation.type === 'application' && props.userRole === 'parent' && !conversation.application?.viewed_at) {
    console.log('👁️ Marquage candidature comme vue:', conversation.application.id)
    router.patch(route('applications.mark-viewed', conversation.application.id), {}, {
      preserveState: true,
      preserveScroll: true
    })
  }
}

function isSelected(conversation) {
  return selectedConversation.value?.id === conversation.id
}

function formatTimeAgo(dateString) {
  if (!dateString) return 'Maintenant'
  
  try {
    const date = new Date(dateString)
    const now = new Date()
    
    // Vérifier si la date est valide
    if (isNaN(date.getTime())) {
      return 'Maintenant'
    }
    
    const diffInMinutes = Math.floor((now - date) / (1000 * 60))
    
    if (diffInMinutes < 1) return 'À l\'instant'
    if (diffInMinutes < 60) return `${diffInMinutes} min`
    if (diffInMinutes < 1440) {
      const hours = Math.floor(diffInMinutes / 60)
      return `${hours}h`
    }
    const days = Math.floor(diffInMinutes / 1440)
    return `${days}j`
  } catch (error) {
    return 'Maintenant'
  }
}

function getApplicationBadgeClass(status) {
  switch (status) {
    case 'pending': return 'bg-yellow-500'
    case 'counter_offered': return 'bg-blue-500'
    case 'accepted': return 'bg-green-500'
    case 'declined': return 'bg-red-500'
    default: return 'bg-gray-500'
  }
}

function getApplicationBadgeIcon(status) {
  switch (status) {
    case 'pending': return '⏳'
    case 'counter_offered': return '↩'
    case 'accepted': return '✓'
    case 'declined': return '✗'
    default: return '?'
  }
}

function getInputPlaceholder() {
  if (!selectedConversation.value) return 'Écrivez votre message...'
  
  if (selectedConversation.value.status === 'payment_required') {
    return 'Effectuez le paiement pour débloquer la conversation'
  }
  
  if (selectedConversation.value.status === 'archived') {
    return 'Cette conversation est archivée'
  }
  
  return 'Écrivez votre message...'
}

// Actions candidatures
function reserveApplication(applicationId, finalRate = null) {
  console.log('📝 Réservation candidature:', applicationId, finalRate)
  router.post(route('applications.reserve', applicationId), {
    final_rate: finalRate
  }, {
    preserveState: true,
    onSuccess: (page) => {
      console.log('✅ Candidature réservée avec succès')
      // Recharger les conversations
      router.get(route('messaging.index'))
    },
    onError: (errors) => {
      console.error('❌ Erreur réservation candidature:', errors)
    }
  })
}

function archiveConversation(applicationId) {
  console.log('❌ Archivage conversation:', selectedConversation.value?.id)
  
  if (!selectedConversation.value) {
    console.error('❌ Aucune conversation sélectionnée pour archivage')
    return
  }

  if (confirm('Êtes-vous sûr de vouloir archiver cette conversation ? Elle ne sera plus visible dans votre messagerie.')) {
    router.patch(route('conversations.archive', selectedConversation.value.id), {}, {
      preserveState: true,
      onSuccess: () => {
        console.log('✅ Conversation archivée avec succès')
        // Réinitialiser la conversation sélectionnée
        selectedConversation.value = null
        // Recharger les conversations
        router.get(route('messaging.index'))
      },
      onError: (errors) => {
        console.error('❌ Erreur archivage conversation:', errors)
      }
    })
  }
}

function submitCounterOffer(applicationId, counterRate, counterMessage = null) {
  console.log('🔄 Contre-offre parent:', applicationId, counterRate, counterMessage)
  router.post(route('applications.counter', applicationId), {
    counter_rate: counterRate,
    counter_message: counterMessage
  }, {
    preserveState: true,
    onSuccess: () => {
      console.log('✅ Contre-offre envoyée avec succès')
      // Recharger les conversations
      router.get(route('messaging.index'))
    },
    onError: (errors) => {
      console.error('❌ Erreur contre-offre:', errors)
    }
  })
}

function respondToCounterOffer(applicationId, accept, finalRate = null) {
  console.log('🔄 Réponse contre-offre:', applicationId, accept, finalRate)
  router.post(route('applications.respond-counter', applicationId), {
    accept: accept,
    final_rate: finalRate
  }, {
    preserveState: true,
    onSuccess: () => {
      console.log('✅ Réponse contre-offre envoyée avec succès')
      // Recharger les conversations
      router.get(route('messaging.index'))
    },
    onError: (errors) => {
      console.error('❌ Erreur réponse contre-offre:', errors)
    }
  })
}

function submitBabysitterCounterOffer(applicationId, counterRate, counterMessage = null) {
  console.log('🔄 Contre-offre babysitter:', applicationId, counterRate, counterMessage)
  router.post(route('applications.babysitter-counter', applicationId), {
    counter_rate: counterRate,
    counter_message: counterMessage
  }, {
    preserveState: true,
    onSuccess: () => {
      console.log('✅ Contre-offre babysitter envoyée avec succès')
      // Recharger les conversations
      router.get(route('messaging.index'))
    },
    onError: (errors) => {
      console.error('❌ Erreur contre-offre babysitter:', errors)
    }
  })
}

// Actions messages
function sendMessage(message) {
  console.log('📤 Envoi message (deprecated):', message)
  // Cette fonction est dépréciée, on utilise maintenant onMessageSent
}

function onMessageSent(message) {
  // Ajouter le message localement via une référence au composant ChatMessages
  if (chatMessagesRef.value) {
    chatMessagesRef.value.addMessageLocally(message)
  }
  
  // Mettre à jour le dernier message dans la sidebar
  if (selectedConversation.value) {
    selectedConversation.value.last_message = message.message
    selectedConversation.value.last_message_at = message.created_at
    selectedConversation.value.last_message_by = message.sender_id
    
    // Remonter cette conversation en haut de la liste
    const conversations = props.conversations
    const currentIndex = conversations.findIndex(c => c.id === selectedConversation.value.id)
    if (currentIndex > 0) {
      // Déplacer la conversation vers le haut
      const currentConv = conversations.splice(currentIndex, 1)[0]
      conversations.unshift(currentConv)
    }
  }
}

function onTyping(isTyping) {
  // Envoyer les événements de frappe via WebSocket
  if (chatMessagesRef.value) {
    if (isTyping) {
      chatMessagesRef.value.sendTypingEvent()
    } else {
      chatMessagesRef.value.sendStopTypingEvent()
    }
  }
}
</script> 