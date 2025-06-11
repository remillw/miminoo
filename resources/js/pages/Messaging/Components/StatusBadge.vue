<template>
  <span :class="badgeClasses" class="text-xs font-medium px-2 py-1 rounded-full whitespace-nowrap">
    {{ badgeText }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: String,
  isExpired: Boolean
})

const badgeClasses = computed(() => {
  if (props.isExpired) {
    return 'bg-gray-100 text-gray-600'
  }
  
  switch (props.status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'counter_offered':
      return 'bg-blue-100 text-blue-800'
    case 'accepted':
      return 'bg-green-100 text-green-800'
    case 'declined':
      return 'bg-red-100 text-red-800'
    case 'expired':
      return 'bg-gray-100 text-gray-600'
    default:
      return 'bg-gray-100 text-gray-600'
  }
})

const badgeText = computed(() => {
  if (props.isExpired) {
    return 'Expirée'
  }
  
  switch (props.status) {
    case 'pending':
      return 'En attente'
    case 'counter_offered':
      return 'Contre-offre'
    case 'accepted':
      return 'Acceptée'
    case 'declined':
      return 'Refusée'
    case 'expired':
      return 'Expirée'
    default:
      return 'Inconnu'
  }
})
</script> 