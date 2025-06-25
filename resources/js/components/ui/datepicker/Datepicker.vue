<template>
  <Popover v-model:open="isOpen">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="[
          'w-full justify-start text-left font-normal',
          !modelValue && 'text-muted-foreground',
        ]"
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ displayValue }}
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0" align="start">
      <Calendar 
        v-model="internalValue" 
        :locale="locale"
        @update:model-value="handleDateSelect"
      />
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Calendar } from '@/components/ui/calendar'
import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { CalendarIcon } from 'lucide-vue-next'
import { DateValue, parseDate, getLocalTimeZone, today } from '@internationalized/date'

interface Props {
  modelValue?: string | Date | null
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

const isOpen = ref(false)
const internalValue = ref<DateValue>()

// Convertir la valeur du modèle en DateValue
const modelValueToDateValue = (value: string | Date | null): DateValue | undefined => {
  if (!value) return undefined
  
  try {
    let dateStr: string
    if (value instanceof Date) {
      dateStr = value.toISOString().split('T')[0]
    } else {
      dateStr = value
    }
    return parseDate(dateStr)
  } catch {
    return undefined
  }
}

// Convertir DateValue en string
const dateValueToString = (value: DateValue | undefined): string | null => {
  if (!value) return null
  return `${value.year}-${String(value.month).padStart(2, '0')}-${String(value.day).padStart(2, '0')}`
}

// Initialiser la valeur interne
watch(() => props.modelValue, (newValue) => {
  internalValue.value = modelValueToDateValue(newValue)
}, { immediate: true })

// Valeur d'affichage formatée
const displayValue = computed(() => {
  if (!internalValue.value) return props.placeholder
  
  const date = new Date(internalValue.value.year, internalValue.value.month - 1, internalValue.value.day)
  return date.toLocaleDateString(props.locale, {
    weekday: 'long',
    year: 'numeric',
    month: 'long', 
    day: 'numeric'
  })
})

// Gérer la sélection de date
const handleDateSelect = (value: DateValue | undefined) => {
  internalValue.value = value
  const stringValue = dateValueToString(value)
  emit('update:modelValue', stringValue)
  isOpen.value = false
}
</script> 