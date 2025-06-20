<script setup lang="ts">
import { PopoverContent, PopoverRoot, PopoverTrigger } from 'radix-vue'
import { cn } from '@/lib/utils'

interface PopoverProps {
  class?: string
}

defineProps<PopoverProps>()
</script>

<template>
  <PopoverRoot>
    <slot />
  </PopoverRoot>
</template> 