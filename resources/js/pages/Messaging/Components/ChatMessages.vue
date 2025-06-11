<template>
  <div class="flex flex-col">
    <!-- Messages - conteneur avec scroll -->
    <div class="overflow-y-auto p-6 messages-container scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100" style="max-height: calc(100vh - 400px);">
      <div class="space-y-6 pb-8">
        <!-- Indicateur de chargement -->
        <div v-if="isLoading" class="text-center py-4">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <p class="text-sm text-gray-500 mt-2">Chargement des messages...</p>
        </div>

        <!-- Erreur de chargement -->
        <div v-else-if="error" class="text-center py-4">
          <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg inline-block">
            <p class="font-medium">Erreur lors du chargement</p>
            <p class="text-sm">{{ error }}</p>
            <button @click="loadMessages" class="mt-2 bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">
              Réessayer
            </button>
          </div>
        </div>

        <!-- Messages -->
        <template v-else>
          <!-- Message système de création -->
          <div class="text-center">
            <p class="text-xs text-gray-500 mt-1">Vous pouvez maintenant discuter</p>
          </div>

          <!-- Messages de la conversation (du plus ancien au plus récent) -->
          <div v-for="message in messages" :key="message.id" class="flex" 
               :class="isMyMessage(message) ? 'justify-end' : 'justify-start'">
            
            <!-- Message de l'autre utilisateur -->
            <div v-if="!isMyMessage(message)" class="flex gap-3 max-w-[70%]">
              <img 
                :src="getMessageSenderAvatar(message)" 
                :alt="getMessageSenderName(message)"
                class="w-8 h-8 rounded-full object-cover flex-shrink-0"
              />
              <div class="flex flex-col min-w-0 flex-1">
                <div class="bg-gray-100 text-gray-900 px-4 py-3 rounded-2xl shadow-sm message-bubble">
                  <p>{{ message.message }}</p>
                </div>
                <p class="text-xs text-gray-500 mt-2">{{ formatTime(message.created_at) }}</p>
              </div>
            </div>

            <!-- Message de l'utilisateur actuel -->
            <div v-else class="flex justify-end">
              <div class=" flex flex-col items-end">
                <div class="bg-blue-600 text-white px-4 py-3 rounded-2xl shadow-sm message-bubble">
                  <p>{{ message.message }}</p>
                </div>
                <div class="flex items-center gap-2 mt-2">
                  <p class="text-xs text-gray-500">{{ formatTime(message.created_at) }}</p>
                  <span v-if="message.read_at" class="text-xs text-blue-500 font-medium">lu</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Indicateur de frappe -->
          <div v-if="isOtherUserTyping" class="flex gap-3 max-w-xs mb-2">
            <img 
              :src="conversation.other_user?.avatar || '/images/default-avatar.png'" 
              :alt="conversation.other_user?.name || 'Utilisateur'"
              class="w-8 h-8 rounded-full object-cover flex-shrink-0"
            />
            <div class="bg-gray-100 px-4 py-3 rounded-2xl shadow-sm">
              <div class="flex space-x-1 items-center">
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
              </div>
            </div>
          </div>

          <!-- Message si pas de messages -->
          <div v-if="messages.length === 0" class="text-center py-8">
            <MessageSquare class="w-12 h-12 mx-auto mb-3 text-gray-300" />
            <p class="text-gray-500">Aucun message pour le moment</p>
            <p class="text-sm text-gray-400">Commencez la conversation !</p>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
  CheckCircle,
  AlertTriangle,
  MessageSquare
} from 'lucide-vue-next'

const props = defineProps({
  conversation: Object,
  userRole: String
})

const emit = defineEmits(['pay-deposit'])
const page = usePage()

// État local
const messages = ref([])
const isLoading = ref(false)
const error = ref(null)
const isOtherUserTyping = ref(false)
const typingTimeout = ref(null)

// Utilisateur actuel
const currentUser = computed(() => page.props.auth.user)

// Watcher pour charger les messages quand la conversation change
watch(() => props.conversation?.id, async (newConversationId, oldConversationId) => {
  // Quitter l'ancien canal
  if (oldConversationId && window.Echo) {
    window.Echo.leave(`conversation.${oldConversationId}`)
  }
  
  if (newConversationId) {
    await loadMessages()
    joinConversationChannel()
  }
}, { immediate: true })

// Fonctions
async function loadMessages() {
  if (!props.conversation?.id) {
    return
  }

  isLoading.value = true
  error.value = null

  try {
    const response = await fetch(route('conversations.messages', { conversation: props.conversation.id }), {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (response.ok) {
      const data = await response.json()
      messages.value = data.messages || []
      
      // Scroll vers le bas après chargement
      await nextTick()
      scrollToBottom()
    } else {
      const errorData = await response.json()
      error.value = errorData.message || 'Erreur lors du chargement des messages'
    }
  } catch (err) {
    error.value = 'Erreur de connexion'
  } finally {
    isLoading.value = false
  }
}

function isMyMessage(message) {
  return message.sender_id === currentUser.value?.id
}

function getMessageSenderAvatar(message) {
  // Si c'est mon message, utiliser mon avatar
  if (isMyMessage(message)) {
    return currentUser.value?.avatar || '/default-avatar.svg'
  }
  // Sinon, utiliser l'avatar de l'expéditeur ou l'autre utilisateur
  return message.sender?.avatar || props.conversation?.other_user?.avatar || '/default-avatar.svg'
}

function getMessageSenderName(message) {
  if (message.sender?.name) {
    return message.sender.name
  }
  return props.conversation?.other_user?.name || 'Utilisateur'
}

function formatTime(dateString) {
  try {
    const date = new Date(dateString)
    return date.toLocaleTimeString('fr-FR', { 
      hour: '2-digit', 
      minute: '2-digit' 
    })
  } catch (err) {
    return '00:00'
  }
}

function scrollToBottom() {
  nextTick(() => {
    setTimeout(() => {
      const container = document.querySelector('.messages-container')
      if (container) {
        container.scrollTop = container.scrollHeight + 100
      }
    }, 200)
  })
}

function joinConversationChannel() {
  if (!props.conversation?.id || !window.Echo) {
    console.warn('⚠️ Pas de conversation ID ou Echo non disponible')
    return
  }

  console.log('🔗 Connexion au canal conversation:', props.conversation.id)
  console.log('🔗 Echo state:', window.Echo.connector?.pusher?.connection?.state)
  
  const channel = window.Echo.private(`conversation.${props.conversation.id}`)
  
  console.log('🔗 Canal créé:', channel)
  
  channel.listen('.message.sent', (e) => {
    console.log('📨 🎉 NOUVEAU MESSAGE REÇU EN TEMPS RÉEL:', e)
    console.log('📨 🔍 Structure complète de l\'événement:', JSON.stringify(e, null, 2))
    console.log('📨 Message sender_id:', e.message?.sender_id, 'type:', typeof e.message?.sender_id)
    console.log('📨 User actuel:', currentUser.value?.id, 'type:', typeof currentUser.value?.id)
    
    // Comparaison en convertissant les deux en string pour éviter les problèmes de type
    const messageSenderId = String(e.message?.sender_id)
    const currentUserId = String(currentUser.value?.id)
    const isMyMessage = messageSenderId === currentUserId
    
    console.log('📨 Comparaison:', messageSenderId, '===', currentUserId, '→', isMyMessage)
    
    // Ne pas ajouter notre propre message (déjà ajouté localement)
    if (!isMyMessage) {
      console.log('📨 ✅ Ajout du message de l\'autre utilisateur à la liste:', messages.value.length, '→', messages.value.length + 1)
      console.log('📨 ✅ Message à ajouter:', e.message)
      
      messages.value.push(e.message)
      
      // Scroll vers le bas
      nextTick(() => {
        scrollToBottom()
      })
      
      console.log('📨 ✅ Messages après ajout:', messages.value.length)
    } else {
      console.log('📨 ⏭️ Ignoré: c\'est mon propre message')
    }
  })
  
  // Écouter les événements "en train d'écrire"
  channel.listenForWhisper('typing', (e) => {
    if (parseInt(e.user_id) !== parseInt(currentUser.value?.id)) {
      isOtherUserTyping.value = true
      clearTimeout(typingTimeout.value)
      typingTimeout.value = setTimeout(() => {
        isOtherUserTyping.value = false
      }, 3000)
    }
  })
  
  // Écouter les événements "arrêt d'écriture"
  channel.listenForWhisper('stop-typing', (e) => {
    if (parseInt(e.user_id) !== parseInt(currentUser.value?.id)) {
      isOtherUserTyping.value = false
      clearTimeout(typingTimeout.value)
    }
  })
  
  // Écouter les événements de messages lus
  channel.listen('.messages.read', (e) => {
    console.log('👁️ Messages marqués comme lus:', e)
    
    // Marquer mes messages comme lus si c'est l'autre utilisateur qui les a lus
    if (parseInt(e.read_by) !== parseInt(currentUser.value?.id)) {
      let updatedCount = 0
      messages.value.forEach(message => {
        if (message.sender_id === currentUser.value?.id && !message.read_at) {
          message.read_at = e.read_at
          updatedCount++
        }
      })
      console.log('👁️ Messages mis à jour avec statut lu:', updatedCount)
    } else {
      console.log('👁️ Événement lu ignoré (même utilisateur)')
    }
  })
  
  // Debug des événements de connexion
  channel.subscribed(() => {
    console.log('✅ 🎊 CONNECTÉ AU CANAL DE CONVERSATION:', props.conversation.id)
    console.log('✅ 🎊 URL du canal:', `conversation.${props.conversation.id}`)
  })
  
  channel.error((error) => {
    console.error('❌ 🚨 ERREUR CONNEXION CANAL:', error)
    console.error('❌ 🚨 Détails erreur:', JSON.stringify(error, null, 2))
  })
}

// Exposer la fonction pour recharger depuis le parent
defineExpose({
  reloadMessages: loadMessages,
  addMessageLocally: (message) => {
    messages.value.push(message)
    nextTick(scrollToBottom)
  },
  sendTypingEvent: () => {
    if (window.Echo && props.conversation?.id) {
      window.Echo.private(`conversation.${props.conversation.id}`)
        .whisper('typing', { user_id: currentUser.value?.id })
    }
  },
  sendStopTypingEvent: () => {
    if (window.Echo && props.conversation?.id) {
      window.Echo.private(`conversation.${props.conversation.id}`)
        .whisper('stop-typing', { user_id: currentUser.value?.id })
    }
  }
})

// Nettoyer lors de la destruction
onUnmounted(() => {
  if (props.conversation?.id && window.Echo) {
    window.Echo.leave(`conversation.${props.conversation.id}`)
  }
  if (typingTimeout.value) {
    clearTimeout(typingTimeout.value)
  }
})
</script>

<style scoped>
/* Styles pour la messagerie */
.message-bubble {
  word-wrap: break-word;
  overflow-wrap: break-word;
  hyphens: none;
  -webkit-hyphens: none;
  -moz-hyphens: none;
  -ms-hyphens: none;
  max-width: 100%;
  min-width: 0;
}

/* Éviter la coupure de mots courts */
.message-bubble p {
  word-wrap: break-word;
  overflow-wrap: break-word;
  white-space: pre-wrap;
  margin: 0;
  line-height: 1.4;
}
</style> 