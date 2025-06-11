<template>
  <div class="flex items-end gap-3">
    <!-- Zone de saisie -->
    <div class="flex-1 relative">
      <textarea
        v-model="message"
        @keydown="handleKeydown"
        @input="handleInput"
        placeholder="Écrivez votre message..."
        rows="1"
        class="w-full px-4 py-3 border border-gray-300 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
    <div class="flex gap-2">
      <!-- Bouton pièce jointe -->
      <button
        type="button"
        class="p-3 text-gray-400 hover:text-gray-600 transition-colors"
        title="Ajouter une pièce jointe"
      >
        <Paperclip class="w-5 h-5" />
      </button>

      <!-- Bouton envoi -->
      <button
        @click="sendMessage"
        :disabled="!canSend"
        class="bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        title="Envoyer le message"
      >
        <Send class="w-5 h-5" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue'
import { Send, Paperclip } from 'lucide-vue-next'

const emit = defineEmits(['send', 'typing'])

const message = ref('')
const textarea = ref(null)
const textareaHeight = ref('auto')
const isTyping = ref(false)
const typingTimeout = ref(null)

const canSend = computed(() => {
  return message.value.trim().length > 0 && message.value.length <= 500
})

// Auto-resize du textarea
watch(message, async () => {
  await nextTick()
  if (textarea.value) {
    textarea.value.style.height = 'auto'
    const scrollHeight = textarea.value.scrollHeight
    const maxHeight = 120 // 5 lignes environ
    textareaHeight.value = Math.min(scrollHeight, maxHeight) + 'px'
  }
})

function handleKeydown(event) {
  // Envoyer avec Ctrl/Cmd + Enter
  if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
    event.preventDefault()
    sendMessage()
  }
  // Empêcher les nouvelles lignes simples si on veut
  else if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    sendMessage()
  }
}

function handleInput() {
  // Gestion de l'indicateur de frappe
  if (!isTyping.value) {
    isTyping.value = true
    emit('typing', true)
  }

  // Reset du timeout
  if (typingTimeout.value) {
    clearTimeout(typingTimeout.value)
  }

  // Arrêter l'indicateur après 1 seconde d'inactivité
  typingTimeout.value = setTimeout(() => {
    isTyping.value = false
    emit('typing', false)
  }, 1000)
}

function sendMessage() {
  if (!canSend.value) return

  const messageToSend = message.value.trim()
  
  // Émettre le message
  emit('send', {
    message: messageToSend,
    type: 'text'
  })

  // Réinitialiser
  message.value = ''
  textareaHeight.value = 'auto'
  
  // Arrêter l'indicateur de frappe
  if (isTyping.value) {
    isTyping.value = false
    emit('typing', false)
  }
  
  // Focus sur le textarea
  nextTick(() => {
    if (textarea.value) {
      textarea.value.focus()
    }
  })
}
</script> 