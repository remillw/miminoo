<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle class="flex items-center space-x-2">
          <component :is="getIcon()" :class="getIconClasses()" class="h-5 w-5" />
          <span>{{ title }}</span>
        </DialogTitle>
        <DialogDescription v-if="description">
          {{ description }}
        </DialogDescription>
      </DialogHeader>
      
      <div v-if="details" class="mt-4 p-3 bg-gray-50 rounded-md">
        <p class="text-sm text-gray-600">{{ details }}</p>
      </div>
      
      <div class="flex items-center justify-end space-x-2 mt-6">
        <Button 
          variant="outline" 
          @click="onCancel"
          :disabled="loading"
        >
          {{ cancelText }}
        </Button>
        <Button 
          :variant="confirmVariant" 
          @click="onConfirm"
          :disabled="loading"
        >
          <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
          {{ confirmText }}
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { AlertTriangle, Trash2, CheckCircle, Info, Loader2 } from 'lucide-vue-next'

interface Props {
  open: boolean
  title: string
  description?: string
  details?: string
  type?: 'danger' | 'warning' | 'info' | 'success'
  confirmText?: string
  cancelText?: string
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'danger',
  confirmText: 'Confirmer',
  cancelText: 'Annuler',
  loading: false
})

interface Emits {
  (e: 'update:open', value: boolean): void
  (e: 'confirm'): void
  (e: 'cancel'): void
}

const emit = defineEmits<Emits>()

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value)
})

const confirmVariant = computed(() => {
  switch (props.type) {
    case 'danger':
      return 'destructive'
    case 'warning':
      return 'outline'
    case 'success':
      return 'default'
    case 'info':
      return 'default'
    default:
      return 'destructive'
  }
})

const getIcon = () => {
  switch (props.type) {
    case 'danger':
      return Trash2
    case 'warning':
      return AlertTriangle
    case 'success':
      return CheckCircle
    case 'info':
      return Info
    default:
      return AlertTriangle
  }
}

const getIconClasses = () => {
  switch (props.type) {
    case 'danger':
      return 'text-red-600'
    case 'warning':
      return 'text-yellow-600'
    case 'success':
      return 'text-green-600'
    case 'info':
      return 'text-blue-600'
    default:
      return 'text-red-600'
  }
}

const onConfirm = () => {
  emit('confirm')
}

const onCancel = () => {
  emit('cancel')
  isOpen.value = false
}
</script> 