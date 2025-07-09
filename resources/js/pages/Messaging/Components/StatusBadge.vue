<template>
  <span :class="badgeClasses" class="text-xs font-medium px-2 py-1 rounded-full whitespace-nowrap">
    {{ badgeText }}
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { useStatusColors } from '@/composables/useStatusColors'

const props = defineProps({
  status: String,
  isExpired: Boolean
})

const { getApplicationStatusColor, getStatusText } = useStatusColors()

const badgeClasses = computed(() => {
  if (props.isExpired) {
    return 'bg-gray-100 text-gray-600'
  }
  
  return getApplicationStatusColor(props.status || '').badge
})

const badgeText = computed(() => {
  if (props.isExpired) {
    return 'Expirée'
  }
  
  return getStatusText('application', props.status || '')
})
</script> 