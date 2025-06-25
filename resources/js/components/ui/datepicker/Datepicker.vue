<template>
  <div class="relative">
    <Input
      type="date"
      :value="modelValue"
      @input="handleInput"
      :min="minDate"
      :placeholder="placeholder"
      class="w-full pl-10 cursor-pointer hover:border-primary transition-colors focus:border-primary datepicker-input"
    />
    <CalendarIcon 
      class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400 pointer-events-none z-10" 
    />
  </div>
</template>

<script setup lang="ts">
import { Input } from '@/components/ui/input'
import { CalendarIcon } from 'lucide-vue-next'

interface Props {
  modelValue?: string | null
  placeholder?: string
  locale?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Sélectionner une date',
  locale: 'fr-FR'
})

const emit = defineEmits<{
  'update:modelValue': [value: string | null]
}>()

// Date minimum (aujourd'hui)
const minDate = new Date().toISOString().split('T')[0]

// Gérer le changement de valeur
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value || null)
}
</script>

<style scoped>
/* Masquer l'icône de calendrier natif pour éviter les doublons */
.datepicker-input::-webkit-calendar-picker-indicator {
  display: none;
  -webkit-appearance: none;
}

.datepicker-input::-moz-calendar-picker-indicator {
  display: none;
}

/* Styles pour Firefox */
.datepicker-input::-moz-focus-inner {
  border: 0;
}
</style> 