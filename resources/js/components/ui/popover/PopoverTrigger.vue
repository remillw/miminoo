<script setup lang="ts">
import { PopoverTrigger, type PopoverTriggerProps } from 'radix-vue'

interface Props extends PopoverTriggerProps {}

const props = defineProps<Props>()
</script>

<template>
  <PopoverTrigger v-bind="props">
    <slot />
  </PopoverTrigger>
</template> 