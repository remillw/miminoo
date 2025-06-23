<template>
  <div class="flex items-end" :class="mobile ? 'gap-2' : 'gap-3'">
    <!-- Zone de saisie -->
    <div class="flex-1 relative">
      <textarea
        v-model="message"
        @keydown="handleKeydown"
        @input="handleInput"
        :placeholder="placeholder"
        :disabled="disabled"
        rows="1"
        class="w-full border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
        :class="mobile ? 'px-3 py-2 text-sm' : 'px-4 py-3'"
        :style="{ height: textareaHeight }"
        ref="textarea"
      ></textarea>
      
      <!-- Indicateur de caractères -->
      <div v-if="message.length > 450" class="absolute bottom-1 right-3 text-xs"
           :class="message.length > 500 ? 'text-red-500' : 'text-gray-400'">
        {{ message.length }}/500
      </div>
    </div>

    <!-- Boutons d'action -->
    <div class="flex" :class="mobile ? 'gap-1' : 'gap-2'">
      <!-- Bouton envoi -->
      <button
        @click="sendMessage"
        :disabled="!canSend || isSending"
        class="bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        :class="mobile ? 'p-2' : 'p-3'"
        :title="isSending ? 'Envoi en cours...' : 'Envoyer le message'"
      >
        <div v-if="isSending" class="animate-spin rounded-full border-b-2 border-white" :class="mobile ? 'h-3 w-3' : 'h-4 w-4'"></div>
        <Send v-else :class="mobile ? 'w-4 h-4' : 'w-5 h-5'" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue'
import { Send } from 'lucide-vue-next'

const props = defineProps({
  disabled: {
    type: Boolean,
    default: false
  },
  placeholder: {
    type: String,
    default: 'Écrivez votre message...'
  },
  conversationId: {
    type: Number,
    required: false
  },
  currentUserId: {
    type: Number,
    required: false
  },
  mobile: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['send', 'typing', 'message-sent', 'message-sent-optimistic', 'message-confirmed', 'message-failed'])

const message = ref('')
const textarea = ref(null)
const textareaHeight = ref('auto')
const isTyping = ref(false)
const typingTimeout = ref(null)
const isSending = ref(false)

const canSend = computed(() => {
  return message.value.trim().length > 0 && 
         message.value.length <= 500 && 
         !props.disabled && 
         !isSending.value
})

// Auto-resize du textarea
watch(message, async () => {
  if (textarea.value) {
    textarea.value.style.height = 'auto'
    const scrollHeight = textarea.value.scrollHeight
    const maxHeight = 120 // 5 lignes environ
    textareaHeight.value = Math.min(scrollHeight, maxHeight) + 'px'
  }
  
  // Gestion de l'indicateur de frappe
  handleTypingIndicator()
})

async function sendMessage() {
  if (!canSend.value || !props.conversationId) {
    return
  }

  const messageText = message.value.trim()
  isSending.value = true

  // 🚀 AFFICHAGE OPTIMISTE - Créer un message temporaire immédiatement
  const optimisticMessage = {
    id: `temp-${Date.now()}`, // ID temporaire
    message: messageText,
    sender_id: props.currentUserId || 3, // ID de l'utilisateur actuel (fallback temporaire)
    conversation_id: props.conversationId,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    read_at: null,
    type: 'user',
    status: 'sending' // Statut temporaire
  }

  // Vider le champ de saisie IMMÉDIATEMENT
  message.value = ''
  textareaHeight.value = 'auto'
  stopTypingIndicator()

  // 🚀 AFFICHER LE MESSAGE IMMÉDIATEMENT (optimistic UI)
  console.log('🚀 Affichage optimiste du message:', optimisticMessage)
  emit('message-sent-optimistic', optimisticMessage)

  // Focus sur le textarea pour continuer à taper
  if (textarea.value) {
    textarea.value.focus()
  }

  try {
    // 📡 ENVOYER AU SERVEUR EN ARRIÈRE-PLAN
    const response = await fetch(route('conversations.send-message', { conversation: props.conversationId }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ message: messageText })
    })

    if (response.ok) {
      const data = await response.json()
      
      // ✅ CONFIRMER L'ENVOI - Remplacer le message temporaire par le vrai
      console.log('✅ Message confirmé par le serveur:', data.message)
      emit('message-confirmed', { 
        tempId: optimisticMessage.id, 
        realMessage: data.message 
      })
      
    } else {
      const errorData = await response.json()
      
      // ❌ ÉCHEC - Marquer le message comme échoué
      console.error('❌ Échec envoi message:', errorData)
      emit('message-failed', { 
        tempId: optimisticMessage.id, 
        error: errorData.error || 'Erreur lors de l\'envoi du message' 
      })
    }
  } catch (error) {
    console.error('❌ Erreur connexion:', error)
    
    // ❌ ÉCHEC - Marquer le message comme échoué
    emit('message-failed', { 
      tempId: optimisticMessage.id, 
      error: 'Erreur de connexion lors de l\'envoi du message' 
    })
  } finally {
    isSending.value = false
  }
}

function handleKeydown(event) {
  // Envoyer avec Entrée (sans Shift)
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    sendMessage()
  }
}

function handleInput() {
  // Cette fonction est appelée à chaque changement du contenu
  // Le watcher de `message` s'occupe déjà du reste
}

function handleTypingIndicator() {
  if (!isTyping.value && message.value.length > 0) {
    isTyping.value = true
    emit('typing', true)
  }
  
  // Annuler le timer précédent
  if (typingTimeout.value) {
    clearTimeout(typingTimeout.value)
  }
  
  // Arrêter l'indicateur après 2 secondes d'inactivité
  typingTimeout.value = setTimeout(() => {
    stopTypingIndicator()
  }, 2000)
}

function stopTypingIndicator() {
  if (isTyping.value) {
    isTyping.value = false
    emit('typing', false)
  }
  
  if (typingTimeout.value) {
    clearTimeout(typingTimeout.value)
    typingTimeout.value = null
  }
}

// Nettoyer les timeouts lors de la destruction du composant
import { onUnmounted } from 'vue'
onUnmounted(() => {
  if (typingTimeout.value) {
    clearTimeout(typingTimeout.value)
  }
})
</script> 