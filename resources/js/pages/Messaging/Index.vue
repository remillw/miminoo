<template>
    <DashboardLayout :currentMode="currentMode">
    <div class="max-w-6xl mx-auto px-4 py-6">
      <div class="flex h-[calc(100vh-200px)] min-h-[600px] bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
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
        <div class="flex-1 overflow-y-auto">
          <div v-for="conversation in conversations" :key="conversation.id" class="border-b border-gray-100 last:border-b-0">
            <div
              @click="selectConversation(conversation)"
              class="flex items-start gap-3 p-4 hover:bg-gray-50 cursor-pointer transition-all duration-200"
              :class="{ 'bg-orange-50 border-r-3 border-r-orange-500 shadow-sm': isSelected(conversation) }"
            >
              <!-- Avatar avec badge statut -->
              <div class="relative flex-shrink-0">
                <img 
                  :src="conversation.other_user.avatar || '/images/default-avatar.png'" 
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
                  <span class="text-sm font-semibold text-orange-600 bg-orange-50 px-2 py-1 rounded">
                    {{ conversation.application.proposed_rate }}€/h
                  </span>
                  <span v-if="conversation.application.counter_rate" class="text-xs text-gray-500">
                    →
                  </span>
                  <span v-if="conversation.application.counter_rate" class="text-sm font-semibold text-red-600 bg-red-50 px-2 py-1 rounded">
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
        <div v-if="selectedConversation" class="flex-1 flex flex-col">
          <!-- En-tête de chat -->
          <div class="px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <img 
                  :src="selectedConversation.other_user.avatar || '/images/default-avatar.png'" 
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

          <!-- Zone de messages avec scroll -->
          <div class="flex-1 overflow-y-auto bg-gray-50">
            <!-- Candidature -->
            <div v-if="selectedConversation.type === 'application'" class="h-full flex flex-col">
              <div class="flex-1 overflow-y-auto p-6">
                <CandidatureChat 
                  :application="selectedConversation.application" 
                  :user-role="userRole"
                  @reserve="reserveApplication"
                  @decline="declineApplication"
                  @counter-offer="submitCounterOffer"
                  @respond-counter="respondToCounterOffer"
                  @babysitter-counter="submitBabysitterCounterOffer"
                />
              </div>
            </div>

            <!-- Conversation -->
            <div v-else class="h-full flex flex-col">
              <div class="flex-1 overflow-y-auto p-6">
                <ChatMessages 
                  :conversation="selectedConversation"
                  :user-role="userRole"
                />
              </div>
            </div>
          </div>

          <!-- Zone de saisie -->
          <div class="border-t border-gray-200 bg-white p-4 flex-shrink-0">
            <ChatInput 
              @send="sendMessage" 
              :disabled="selectedConversation.type === 'application' || selectedConversation.status === 'payment_required'" 
              :placeholder="getInputPlaceholder()"
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
})

// État local
const selectedConversation = ref(null)

// Helpers
function selectConversation(conversation) {
  selectedConversation.value = conversation
  
  // Marquer comme vue automatiquement pour les candidatures
  if (conversation.type === 'application' && props.userRole === 'parent' && !conversation.application?.viewed_at) {
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
  
  if (selectedConversation.value.type === 'application') {
    return 'Utilisez les boutons ci-dessus pour répondre à la candidature'
  }
  
  if (selectedConversation.value.status === 'payment_required') {
    return 'Effectuez le paiement pour débloquer la conversation'
  }
  
  return 'Écrivez votre message...'
}

// Actions candidatures
function reserveApplication(application) {
  if (confirm('Réserver cette candidature ? Le processus de paiement sera lancé.')) {
    const finalRate = application.counter_rate || application.proposed_rate
    
    router.post(route('applications.reserve', application.id), {
      final_rate: finalRate
    }, {
      onSuccess: (page) => {
        // Rediriger vers la page de paiement ou recharger
        if (page.props.payment_required) {
          // TODO: Rediriger vers page de paiement
          alert('Redirection vers le paiement...')
        }
        router.reload()
      }
    })
  }
}

function declineApplication(applicationId) {
  if (confirm('Êtes-vous sûr de vouloir refuser cette candidature ? Elle sera archivée définitivement.')) {
    router.post(route('applications.decline', applicationId), {}, {
      onSuccess: () => {
        selectedConversation.value = null
        router.reload()
      }
    })
  }
}

function respondToCounterOffer(applicationId, response) {
  const message = response === 'accept' 
    ? 'Accepter cette contre-offre ? La candidature sera réservée.' 
    : 'Refuser cette contre-offre ? Vous pourrez continuer à négocier.'
    
  if (confirm(message)) {
    router.post(route('applications.respond-counter', applicationId), {
      response: response
    }, {
      onSuccess: (page) => {
        if (response === 'accept' && page.props.payment_required) {
          // TODO: Rediriger vers page de paiement
          alert('Redirection vers le paiement...')
        }
        router.reload()
      }
    })
  }
}

// Contre-offre
function submitCounterOffer(applicationId, data) {
  router.post(route('applications.counter-offer', applicationId), data, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      router.reload()
    }
  })
}

function submitBabysitterCounterOffer(applicationId, data) {
  router.post(route('applications.babysitter-counter', applicationId), data, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      router.reload()
    }
  })
}

function sendMessage(message) {
  // TODO: Implémenter l'envoi de message
  console.log('Envoi message:', message)
}
</script> 