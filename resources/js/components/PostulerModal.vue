<template>
    <Dialog :open="isOpen" @openChange="onClose">
      <DialogContent class="max-w-lg p-0 overflow-hidden">
        <!-- En-tête -->
        <div class="flex items-center justify-between bg-gray-50 px-6 py-4">
          <div class="flex items-center gap-4">
            <Avatar>
              <AvatarImage :src="avatarUrl" alt="Photo famille" />
              <AvatarFallback>👪</AvatarFallback>
            </Avatar>
            <div>
              <DialogTitle class="text-lg font-semibold">Postuler à cette annonce</DialogTitle>
              <DialogDescription class="text-sm text-gray-500">Famille {{ familyName }}</DialogDescription>
            </div>
          </div>
          <button @click="onClose" class="text-gray-400 hover:text-gray-600">
            <X class="h-5 w-5"/>
          </button>
        </div>
  
        <!-- Corps de la pop-up -->
        <div class="space-y-6 px-6 py-4">
          <!-- Récapitulatif -->
          <div class="flex items-center justify-between space-x-4 bg-gray-100 rounded-md px-4 py-3 text-sm text-gray-700">
            <div class="flex items-center gap-2">
              <Calendar class="h-5 w-5 text-orange-500"/>
              <span>{{ formattedDate }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Clock class="h-5 w-5 text-orange-500"/>
              <span>{{ hours }}</span>
            </div>
            <div class="flex items-center gap-2">
              <MapPin class="h-5 w-5 text-orange-500"/>
              <span>{{ location }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Users class="h-5 w-5 text-orange-500"/>
              <span>{{ childrenCount }}</span>
            </div>
          </div>
  
          <!-- Message de présentation -->
          <div class="space-y-1">
            <Label for="message">Message de présentation</Label>
            <Textarea
              id="message"
              v-model="message"
              placeholder="Présentez-vous et expliquez pourquoi vous êtes la babysitter idéale pour cette famille…"
              :maxlength="500"
              rows="4"
            />
            <p class="text-xs text-gray-400 text-right">{{ message.length }}/500 caractères</p>
          </div>
  
          <!-- Tarif horaire -->
          <div class="space-y-1">
            <Label for="rate">Votre tarif horaire</Label>
            <div class="relative">
              <span class="absolute inset-y-0 left-3 flex items-center text-gray-500">€</span>
              <Input
                id="rate"
                v-model.number="rate"
                type="number"
                min="0"
                step="0.5"
                class="pl-8 pr-12"
              />
              <span class="absolute inset-y-0 right-3 flex items-center text-gray-500">€/h</span>
            </div>
          </div>
  
          <!-- Estimation -->
          <Card class="bg-red-50 border-0">
            <div class="px-4 py-3 flex items-center justify-between">
              <div class="text-sm text-red-700">
                Estimation pour cette garde :  
                <span class="block text-xs text-red-500">Basé sur {{ rate }}€/h × {{ duration }}h (estimation)</span>
              </div>
              <div class="text-xl font-semibold text-red-700">{{ total.toFixed(2) }}€</div>
            </div>
          </Card>
        </div>
  
        <!-- Pied de pop-up -->
        <DialogFooter class="flex justify-end gap-3 bg-gray-50 px-6 py-4">
          <Button variant="outline" @click="onClose">Annuler</Button>
          <Button :disabled="!canSubmit" @click="submit">Envoyer ma candidature</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </template>
  
  <script setup lang="ts">
  import { ref, computed, defineProps } from 'vue'
  import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
  } from '@/components/ui/dialog'
  import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
  import { Button } from '@/components/ui/button'
  import { Input } from '@/components/ui/input'
  import { Label } from '@/components/ui/label'
  import { Textarea } from '@/components/ui/textarea'
  import { Card } from '@/components/ui/card'
  import { Calendar, Clock, MapPin, Users, X } from 'lucide-vue-next'
  
  interface Props {
    isOpen: boolean
    onClose: () => void
    date: string        // ISO date
    hours: string       // ex "19:00 - 23:00"
    location: string    // ex "Paris 16e"
    childrenCount: number
    avatarUrl?: string
    familyName: string
  }
  
  const props = defineProps<Props>()
  const message = ref('')
  const rate = ref(15)
  
  // Durée estimée fixe à 4h dans ton exemple, sinon à calculer dynamiquement
  const duration = 4
  
  const total = computed(() => rate.value * duration)
  
  const canSubmit = computed(() => message.value.trim().length > 0 && rate.value > 0)
  
  const formattedDate = computed(() => {
    const d = new Date(props.date)
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
  })
  
  function submit() {
    // émettre un événement ou appeler ton API
    console.log('Candidature envoyée!', {
      message: message.value,
      rate: rate.value,
    })
    props.onClose()
  }
  </script>
  
  <style scoped>
  /* Ajustements si besoin */
  </style>
  