<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Faire une contre-offre</h3>
      
      <div class="mb-4">
        <p class="text-sm text-gray-600 mb-2">
          {{ application.babysitter.name }} propose {{ application.proposed_rate }}€/h
        </p>
        
        <div class="bg-gray-50 rounded-lg p-3">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Tarif actuel :</span>
            <span class="font-medium">{{ application.proposed_rate }}€/h</span>
          </div>
        </div>
      </div>

      <form @submit.prevent="submit">
        <div class="mb-4">
          <label for="counter_rate" class="block text-sm font-medium text-gray-700 mb-2">
            Votre contre-proposition
          </label>
          <div class="relative">
            <input
              id="counter_rate"
              v-model="form.counter_rate"
              type="number"
              step="0.5"
              min="1"
              max="50"
              class="block w-full rounded-lg border-gray-300 pr-12 focus:border-blue-500 focus:ring-blue-500"
              placeholder="15"
              required
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
              <span class="text-gray-500 text-sm">€/h</span>
            </div>
          </div>
        </div>

        <div class="mb-6">
          <label for="counter_message" class="block text-sm font-medium text-gray-700 mb-2">
            Message (optionnel)
          </label>
          <textarea
            id="counter_message"
            v-model="form.counter_message"
            rows="3"
            class="block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
            placeholder="Expliquez votre proposition..."
          ></textarea>
        </div>

        <div class="flex gap-3">
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
          >
            Annuler
          </button>
          <button
            type="submit"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            :disabled="!form.counter_rate"
          >
            Envoyer l'offre
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'

const props = defineProps({
  application: Object
})

const emit = defineEmits(['close', 'submit'])

const form = reactive({
  counter_rate: '',
  counter_message: ''
})

function submit() {
  emit('submit', {
    counter_rate: parseFloat(form.counter_rate),
    counter_message: form.counter_message
  })
}
</script> 