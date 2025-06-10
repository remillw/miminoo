<template>
    <Dialog :open="isOpen" @update:open="onClose">
      <DialogContent class="max-w-md p-0 overflow-hidden rounded-2xl">
        <!-- En-tête avec photo de profil -->
        <div class="px-8 py-6 bg-gradient-to-br from-orange-50 to-white">
          <div class="flex items-center gap-4 mb-4">
            <div class="relative">
              <img 
                :src="props.avatarUrl || '/default-avatar.png'" 
                :alt="'Photo de la famille ' + familyName"
                class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-sm"
              />
              <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-green-100 rounded-full border-2 border-white flex items-center justify-center">
                <User class="w-3 h-3 text-green-600" />
              </div>
            </div>
            <div>
              <DialogTitle class="text-2xl font-semibold text-gray-900">
                Postuler à cette annonce
              </DialogTitle>
              <DialogDescription class="mt-1 text-base text-gray-600 flex items-center gap-1">
                <Users class="w-4 h-4" />
                Famille {{ familyName }}
              </DialogDescription>
            </div>
          </div>
        </div>
  
        <!-- Corps avec espacement amélioré -->
        <div class="px-8 py-6 space-y-8">
          <!-- Message d'erreur -->
          <div v-if="error" class="p-4 rounded-xl bg-red-50 border border-red-200">
            <p class="text-red-700 flex items-center gap-2">
              <AlertCircle class="w-4 h-4" />
              {{ error }}
            </p>
          </div>

          <!-- Message de succès -->
          <div v-if="success" class="p-4 rounded-xl bg-green-50 border border-green-200">
            <p class="text-green-700 flex items-center gap-2">
              <CheckCircle class="w-4 h-4" />
              {{ success }}
            </p>
          </div>

          <!-- Récapitulatif en cards modernes -->
          <div class="grid grid-cols-2 gap-4">
            <div class="p-4 rounded-xl bg-orange-50/50 transition-all hover:bg-orange-50">
              <div class="flex items-center gap-2 text-gray-600 text-sm mb-2">
                <Calendar class="w-4 h-4" />
                <span>Date</span>
              </div>
              <div class="font-medium text-gray-900">{{ formattedDate }}</div>
            </div>
            <div class="p-4 rounded-xl bg-orange-50/50 transition-all hover:bg-orange-50">
              <div class="flex items-center gap-2 text-gray-600 text-sm mb-2">
                <Clock class="w-4 h-4" />
                <span>Horaires</span>
              </div>
              <div class="font-medium text-gray-900">{{ hours }}</div>
            </div>
            <div class="p-4 rounded-xl bg-orange-50/50 transition-all hover:bg-orange-50">
              <div class="flex items-center gap-2 text-gray-600 text-sm mb-2">
                <MapPin class="w-4 h-4" />
                <span>Lieu</span>
              </div>
              <div class="font-medium text-gray-900">{{ location }}</div>
            </div>
            <div class="p-4 rounded-xl bg-orange-50/50 transition-all hover:bg-orange-50">
              <div class="flex items-center gap-2 text-gray-600 text-sm mb-2">
                <Baby class="w-4 h-4" />
                <span>Enfants</span>
              </div>
              <div class="font-medium text-gray-900">{{ childrenCount }}</div>
            </div>
          </div>
  
          <!-- Message de présentation avec style moderne -->
          <div class="space-y-3">
            <Label for="message" class="text-base font-medium text-gray-700 flex items-center gap-2">
              <MessageSquare class="w-4 h-4" />
              Votre message pour la famille
            </Label>
            <Textarea
              id="message"
              v-model="message"
              placeholder="Présentez-vous et expliquez pourquoi vous êtes la babysitter idéale pour cette famille…"
              :maxlength="500"
              rows="4"
              :disabled="isLoading || success"
              class="resize-none rounded-xl border-gray-200 focus:border-orange-300 focus:ring-orange-100 transition-all"
            />
            <p class="text-sm text-gray-400 text-right">{{ message.length }}/500 caractères</p>
          </div>
  
          <!-- Tarif horaire avec style moderne -->
          <div class="space-y-3">
            <Label for="rate" class="text-base font-medium text-gray-700 flex items-center justify-between">
              <span class="flex items-center gap-2">
                <Euro class="w-4 h-4" />
                Votre tarif horaire
              </span>
              <span class="text-sm font-normal text-gray-500">Tarif demandé : {{ props.requestedRate }}€/h</span>
            </Label>
            <div class="relative">
              <span class="absolute inset-y-0 left-4 flex items-center text-gray-500">€</span>
              <Input
                id="rate"
                v-model.number="rate"
                type="number"
                min="0"
                step="0.5"
                :disabled="isLoading || success"
                class="pl-9 pr-12 rounded-xl border-gray-200 focus:border-orange-300 focus:ring-orange-100 transition-all text-lg"
              />
              <span class="absolute inset-y-0 right-4 flex items-center text-gray-500">/h</span>
            </div>
          </div>

          <!-- Message de contre-proposition avec style moderne -->
          <div
            v-if="isCounterProposal"
            class="p-4 rounded-xl bg-blue-50/70 border border-blue-100/50 transition-all"
          >
            <p class="text-blue-700 flex items-center gap-2">
              <Info class="w-4 h-4" />
              <span class="flex-1">Votre proposition : {{ rate }}€/h</span>
              <span class="text-blue-500 text-sm">Initial : {{ props.requestedRate }}€/h</span>
            </p>
          </div>

          <!-- Estimation avec style moderne -->
          <div class="p-5 rounded-xl bg-gradient-to-br from-gray-50 to-white border shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-base font-medium text-gray-900 flex items-center gap-2">
                  <Calculator class="w-4 h-4" />
                  Estimation totale
                </div>
                <div class="text-sm text-gray-500 mt-1">
                  {{ effectiveRate }}€/h × {{ duration }}h
                </div>
              </div>
              <div class="text-2xl font-semibold text-gray-900">
                {{ (effectiveRate * duration).toFixed(2) }}€
              </div>
            </div>
          </div>
        </div>
  
        <!-- Pied de pop-up avec style moderne -->
        <div class="flex gap-3 px-8 py-6 bg-gradient-to-br from-gray-50 to-white border-t">
          <Button 
            variant="outline" 
            @click="closeModal"
            :disabled="isLoading"
            class="flex-1 rounded-xl border-gray-200 hover:bg-gray-50 transition-all duration-200 flex items-center justify-center gap-2"
          >
            <X class="w-4 h-4" />
            {{ success ? 'Fermer' : 'Annuler' }}
          </Button>
          
          <Button 
            v-if="!success"
            :disabled="!canSubmit || isLoading" 
            @click="submit"
            class="flex-1 rounded-xl bg-gradient-to-r from-orange-500 to-orange-400 hover:from-orange-600 hover:to-orange-500 text-white border-0 transition-all duration-200 disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <Loader v-if="isLoading" class="w-4 h-4 animate-spin" />
            <Send v-else class="w-4 h-4" />
            {{ isLoading ? 'Envoi...' : 'Envoyer ma candidature' }}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  </template>
  
  <script setup lang="ts">
  import { ref, computed } from 'vue'
  import { router } from '@inertiajs/vue3'
  import { route } from 'ziggy-js'
  import {
    Dialog,
    DialogContent,
    DialogTitle,
    DialogDescription,
  } from '@/components/ui/dialog'
  import { Button } from '@/components/ui/button'
  import { Input } from '@/components/ui/input'
  import { Label } from '@/components/ui/label'
  import { Textarea } from '@/components/ui/textarea'
  import {
    User,
    Users,
    Calendar,
    Clock,
    MapPin,
    Baby,
    MessageSquare,
    Euro,
    Info,
    Calculator,
    X,
    Send,
    AlertCircle,
    CheckCircle,
    Loader
  } from 'lucide-vue-next'

  interface Props {
    isOpen: boolean
    onClose: () => void
    announcementId: number
    date: string
    hours: string
    location: string
    childrenCount: number
    avatarUrl?: string
    familyName: string
    requestedRate: number
  }

  const props = defineProps<Props>()
  const message = ref('')
  const rate = ref(props.requestedRate)
  const duration = 4
  const isLoading = ref(false)
  const error = ref('')
  const success = ref('')

  const isCounterProposal = computed(() => rate.value !== props.requestedRate)
  const effectiveRate = computed(() => rate.value)

  const total = computed(() => effectiveRate.value * duration)
  const canSubmit = computed(() => message.value.trim().length > 0 && rate.value > 0)

  const formattedDate = computed(() => {
    const d = new Date(props.date)
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
  })

  const closeModal = () => {
    // Reset des données
    message.value = ''
    rate.value = props.requestedRate
    error.value = ''
    success.value = ''
    isLoading.value = false
    
    props.onClose()
  }

  async function submit() {
    if (!canSubmit.value || isLoading.value) return

    isLoading.value = true
    error.value = ''

    try {
      const response = await fetch(route('announcements.apply', { announcement: props.announcementId }), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          message: message.value.trim(),
          proposed_rate: rate.value
        })
      })

      const data = await response.json()

      if (response.ok) {
        success.value = data.message || 'Candidature envoyée avec succès !'
        
        // Optionnel: rediriger après un délai
        setTimeout(() => {
          closeModal()
        }, 2000)
      } else {
        error.value = data.error || 'Une erreur est survenue lors de l\'envoi de votre candidature'
      }
    } catch (err) {
      console.error('Erreur lors de l\'envoi de la candidature:', err)
      error.value = 'Une erreur réseau est survenue. Veuillez réessayer.'
    } finally {
      isLoading.value = false
    }
  }
  </script>