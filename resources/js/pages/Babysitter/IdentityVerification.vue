<template>
  <DashboardLayout :currentMode="'babysitter'" title="Vérification d'identité">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6 lg:p-8">
            <h1 class="text-2xl font-medium text-gray-900 mb-6">
              Vérification d'identité
            </h1>

            <!-- Statut actuel -->
            <div v-if="verificationStatus" class="mb-8">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div v-if="verificationStatus === 'verified'" class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                  </div>
                  <div v-else-if="verificationStatus === 'processing'" class="h-8 w-8 rounded-full bg-yellow-100 flex items-center justify-center">
                    <svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                  </div>
                  <div v-else class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </div>
                </div>
                <div>
                  <p class="text-lg font-medium text-gray-900">
                    Statut : {{ getStatusLabel(verificationStatus) }}
                  </p>
                  <p class="text-sm text-gray-500">
                    {{ getStatusDescription(verificationStatus) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Vérification déjà complétée -->
            <div v-if="verificationStatus === 'verified'" class="bg-green-50 border border-green-200 rounded-md p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-green-800">
                    Vérification complétée
                  </h3>
                  <div class="mt-2 text-sm text-green-700">
                    <p>Votre identité a été vérifiée avec succès. Vous pouvez maintenant recevoir des paiements.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Session en cours de traitement -->
            <div v-else-if="verificationStatus === 'processing'" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-yellow-800">
                    Vérification en cours
                  </h3>
                  <div class="mt-2 text-sm text-yellow-700">
                    <p>Votre vérification d'identité est en cours de traitement. Nous vous notifierons dès qu'elle sera complétée.</p>
                  </div>
                  <div class="mt-4">
                    <button 
                      @click="checkStatus"
                      :disabled="loading"
                      class="text-sm bg-yellow-200 hover:bg-yellow-300 text-yellow-800 font-medium py-1 px-3 rounded disabled:opacity-50"
                    >
                      {{ loading ? 'Vérification...' : 'Vérifier le statut' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Besoin de vérification -->
            <div v-else>
              <!-- Informations supplémentaires - Upload de carte d'identité -->
              <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">
                      Informations supplémentaires
                    </h3>
                    <div class="mt-2 text-sm text-blue-700">
                      <p>Vous pouvez uploader votre carte d'identité maintenant ou passer par Stripe Identity.</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Section Upload de carte d'identité -->
              <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                  <svg class="inline-block mr-2 h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                  </svg>
                  Upload de carte d'identité
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                  <!-- Recto -->
                  <div class="space-y-3">
                    <label class="block text-sm font-medium text-gray-700">Carte d'identité (recto)</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                      <input 
                        type="file" 
                        id="identity-front" 
                        class="hidden" 
                        accept="image/*,.pdf"
                        @change="handleDocumentUpload($event, 'front')"
                      />
                      <div v-if="!uploadedDocuments.front">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <label for="identity-front" class="cursor-pointer">
                          <span class="text-sm font-medium text-blue-600 hover:text-blue-500">
                            Cliquez pour uploader
                          </span>
                          <span class="text-sm text-gray-500"> ou glissez-déposez</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-2">PNG, JPG, PDF jusqu'à 10MB</p>
                      </div>
                      <div v-else class="space-y-2">
                        <svg class="mx-auto h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <p class="text-sm font-medium text-gray-900">{{ uploadedDocuments.front.name }}</p>
                        <p class="text-xs text-gray-500">{{ Math.round(uploadedDocuments.front.size / 1024) }} KB</p>
                        <button 
                          type="button"
                          @click="removeDocument('front')"
                          class="text-xs text-red-600 hover:text-red-800 underline"
                        >
                          Supprimer
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Verso -->
                  <div class="space-y-3">
                    <label class="block text-sm font-medium text-gray-700">Carte d'identité (verso - optionnel)</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                      <input 
                        type="file" 
                        id="identity-back" 
                        class="hidden" 
                        accept="image/*,.pdf"
                        @change="handleDocumentUpload($event, 'back')"
                      />
                      <div v-if="!uploadedDocuments.back">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <label for="identity-back" class="cursor-pointer">
                          <span class="text-sm font-medium text-blue-600 hover:text-blue-500">
                            Cliquez pour uploader
                          </span>
                          <span class="text-sm text-gray-500"> ou glissez-déposez</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-2">PNG, JPG, PDF jusqu'à 10MB</p>
                      </div>
                      <div v-else class="space-y-2">
                        <svg class="mx-auto h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <p class="text-sm font-medium text-gray-900">{{ uploadedDocuments.back.name }}</p>
                        <p class="text-xs text-gray-500">{{ Math.round(uploadedDocuments.back.size / 1024) }} KB</p>
                        <button 
                          type="button"
                          @click="removeDocument('back')"
                          class="text-xs text-red-600 hover:text-red-800 underline"
                        >
                          Supprimer
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Bouton pour envoyer les documents -->
                <div class="flex justify-center mb-4" v-if="uploadedDocuments.front">
                  <button 
                    @click="uploadDocuments" 
                    :disabled="uploading"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                  >
                    <svg v-if="uploading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ uploading ? 'Envoi en cours...' : 'Envoyer les documents' }}
                  </button>
                </div>

                <!-- Informations sur les documents acceptés -->
                <div class="bg-gray-50 rounded-lg p-4">
                  <h4 class="font-medium text-gray-900 mb-2">Types de documents acceptés</h4>
                  <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Carte d'identité française ou européenne</li>
                    <li>• Passeport en cours de validité</li>
                    <li>• Permis de conduire français</li>
                    <li>• Carte de séjour (pour les non-européens)</li>
                  </ul>
                </div>
              </div>

              <div class="text-center mb-6">
                <div class="relative">
                  <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                  </div>
                  <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">ou</span>
                  </div>
                </div>
              </div>

              <div class="space-y-6">
                <div>
                  <h3 class="text-lg font-medium text-gray-900 mb-4">Comment ça fonctionne</h3>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                      <div class="mx-auto h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center mb-3">
                        <span class="text-xl font-bold text-indigo-600">1</span>
                      </div>
                      <h4 class="font-medium text-gray-900">Cliquez sur "Commencer"</h4>
                      <p class="text-sm text-gray-600 mt-1">Nous vous redirigerons vers Stripe Identity</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                      <div class="mx-auto h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center mb-3">
                        <span class="text-xl font-bold text-indigo-600">2</span>
                      </div>
                      <h4 class="font-medium text-gray-900">Photographiez votre pièce d'identité</h4>
                      <p class="text-sm text-gray-600 mt-1">Carte d'identité, passeport ou permis de conduire</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                      <div class="mx-auto h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center mb-3">
                        <span class="text-xl font-bold text-indigo-600">3</span>
                      </div>
                      <h4 class="font-medium text-gray-900">Prenez un selfie</h4>
                      <p class="text-sm text-gray-600 mt-1">Pour vérifier que c'est bien vous</p>
                    </div>
                  </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                  <div class="flex justify-center">
                    <button 
                      @click="startVerification" 
                      :disabled="loading"
                      class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                    >
                      <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      {{ loading ? 'Création en cours...' : 'Commencer la vérification' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const props = defineProps({
  verificationStatus: String,
  accountDetails: Object,
  identitySession: Object,
  stripePublishableKey: String
})

const loading = ref(false)
const uploading = ref(false)
const uploadedDocuments = ref({ front: null, back: null })

const getStatusLabel = (status) => {
  switch (status) {
    case 'verified': return 'Vérifié'
    case 'processing': return 'En cours de traitement'
    case 'requires_input': return 'Action requise'
    case 'canceled': return 'Annulé'
    default: return 'Non vérifié'
  }
}

const getStatusDescription = (status) => {
  switch (status) {
    case 'verified': return 'Votre identité a été vérifiée avec succès'
    case 'processing': return 'Nous vérifions actuellement votre identité'
    case 'requires_input': return 'Des informations supplémentaires sont requises'
    case 'canceled': return 'La vérification a été annulée'
    default: return 'Votre identité n\'a pas encore été vérifiée'
  }
}

const startVerification = async () => {
  loading.value = true
  
  try {
    const response = await fetch(route('stripe.identity.create-session'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    
    const data = await response.json()
    
    if (data.success && data.session.url) {
      // Rediriger vers Stripe Identity
      window.location.href = data.session.url
    } else {
      alert('Erreur lors de la création de la session de vérification')
    }
  } catch (error) {
    console.error('Erreur:', error)
    alert('Une erreur s\'est produite')
  } finally {
    loading.value = false
  }
}

const checkStatus = async () => {
  loading.value = true
  
  try {
    const response = await fetch(route('stripe.identity.status'))
    const data = await response.json()
    
    if (data.success) {
      // Recharger la page pour mettre à jour le statut
      router.reload({ only: ['verificationStatus', 'identitySession'] })
    }
  } catch (error) {
    console.error('Erreur:', error)
  } finally {
    loading.value = false
  }
}

// Gestion de l'upload de documents
const handleDocumentUpload = (event, type) => {
  const input = event.target
  const file = input.files?.[0]
  
  if (file) {
    // Vérifier le type de fichier
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf']
    if (!allowedTypes.includes(file.type)) {
      alert('Type de fichier invalide. Seuls les fichiers JPEG, PNG et PDF sont acceptés.')
      return
    }
    
    // Vérifier la taille (max 10MB)
    if (file.size > 10 * 1024 * 1024) {
      alert('Fichier trop volumineux. La taille maximale est de 10MB.')
      return
    }
    
    uploadedDocuments.value[type] = file
  }
}

const removeDocument = (type) => {
  uploadedDocuments.value[type] = null
}

const uploadDocuments = async () => {
  if (!uploadedDocuments.value.front) {
    alert('Veuillez sélectionner au moins le recto de votre carte d\'identité.')
    return
  }
  
  uploading.value = true
  
  try {
    const formData = new FormData()
    formData.append('identity_document_front', uploadedDocuments.value.front)
    if (uploadedDocuments.value.back) {
      formData.append('identity_document_back', uploadedDocuments.value.back)
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    const response = await fetch('/stripe/upload-identity-documents', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken || '',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      body: formData
    })
    
    const result = await response.json()
    
    if (response.ok && result.success) {
      alert('✅ Documents uploadés avec succès !')
      // Recharger la page pour mettre à jour le statut
      router.reload()
    } else {
      throw new Error(result.error || 'Erreur lors de l\'upload des documents')
    }
  } catch (error) {
    console.error('Erreur upload:', error)
    alert('❌ Erreur lors de l\'upload des documents: ' + error.message)
  } finally {
    uploading.value = false
  }
}
</script>