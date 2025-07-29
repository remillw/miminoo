<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <div class="mx-auto h-24 w-24 rounded-full bg-red-100 flex items-center justify-center">
          <svg class="h-16 w-16 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </div>
        <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
          Vérification non complétée
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          La vérification de votre identité n'a pas pu être finalisée
        </p>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <div class="space-y-6">
          <div class="text-center">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              Que s'est-il passé ?
            </h3>
            <p class="text-gray-600 mb-6">
              La vérification de votre identité n'a pas pu être complétée. 
              Cela peut arriver pour différentes raisons.
            </p>
            
            <div v-if="error" class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm text-yellow-800">
                    <strong>Détail :</strong> {{ formatError(error) }}
                  </p>
                </div>
              </div>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
              <div class="text-sm text-blue-800">
                <p class="font-medium mb-2">Conseils pour réussir la vérification :</p>
                <ul class="text-left space-y-1">
                  <li>• Assurez-vous que votre document d'identité est valide</li>
                  <li>• Vérifiez que la photo est nette et bien éclairée</li>
                  <li>• Évitez les reflets ou les ombres sur le document</li>
                  <li>• Utilisez un environnement bien éclairé</li>
                </ul>
              </div>
            </div>
            
            <div class="space-y-3" v-if="canRetry">
              <Link 
                :href="retryUrl" 
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Recommencer la vérification
              </Link>
              
              <Link 
                href="/babysitter/dashboard" 
                class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Retour au dashboard
              </Link>
              
              <div class="text-center">
                <Link 
                  href="/contact" 
                  class="text-sm text-indigo-600 hover:text-indigo-500"
                >
                  Contacter le support
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  error: Object,
  canRetry: Boolean,
  retryUrl: String
})

const formatError = (error) => {
  if (!error) return 'Erreur inconnue'
  
  if (typeof error === 'string') {
    return error
  }
  
  if (error.code) {
    switch (error.code) {
      case 'document_unverified_other':
        return 'Document non reconnu ou de mauvaise qualité'
      case 'document_expired':
        return 'Document expiré'
      case 'document_type_not_supported':
        return 'Type de document non supporté'
      case 'document_failed_copy':
        return 'Le document semble être une copie'
      case 'document_fraudulent':
        return 'Document suspect détecté'
      default:
        return error.code
    }
  }
  
  return 'Erreur lors de la vérification'
}
</script>