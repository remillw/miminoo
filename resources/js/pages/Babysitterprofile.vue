<script setup lang="ts">
import GlobalLayout from '@/layouts/GlobalLayout.vue';

</script> 

<template>
<GlobalLayout>
        <div class="min-h-screen bg-secondary p-4">
            <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Section principale -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Header Profile -->
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-start space-x-6">
              <div class="relative">
                <img 
                  src="/storage/babysitter-test.png" 
                  alt="Sophie Martin"
                  class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg"
                >
              </div>
              <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                  <h1 class="text-2xl font-bold text-gray-900">Sophie Martin</h1>
                  <div class="flex items-center space-x-1 text-green-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-sm font-medium">Identité vérifiée</span>
                  </div>
                </div>
                <p class="text-gray-600 text-sm mb-3">26 ans | Paris 15e | 3 babysittings</p>
                <div class="flex items-center space-x-4 mb-3">
                  <div class="flex items-center space-x-1">
                    <div class="flex space-x-1">
                      <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                      </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-900">4.9</span>
                    <span class="text-sm text-gray-500">(3 avis)</span>
                  </div>
                </div>
                <div class="text-lg font-semibold text-primary">15€/heure</div>
              </div>
            </div>
          </div>
  
          <!-- À propos de moi -->
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h2 class="text-xl font-bold text-gray-900 mb-4">À propos de moi</h2>
            <p class="text-gray-700 leading-relaxed">
              Je suis Sophie, étudiante en école d'éducatrice de jeunes enfants. J'adore passer du temps 
              avec les enfants et les aider à développer leur créativité et leur curiosité. Je suis patiente, 
              attentionnée et toujours à l'écoute de leurs besoins. J'ai une expérience de plus de 3 ans 
              dans la garde d'enfants de tous âges et je suis disponible principalement les soirs et week-ends.
            </p>
          </div>
  
          <!-- Expérience -->
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Expérience</h2>
            <div class="space-y-6">
              <div class="flex space-x-4">
                <div class="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-900">Garde de 2 enfants (3 à 6 ans)</h3>
                  <p class="text-sm text-gray-500 mb-2">2024</p>
                  <p class="text-gray-700 text-sm">
                    Garde régulière deux fois par semaine. Aide aux devoirs, activités créatives, 
                    préparation des repas et coucher.
                  </p>
                </div>
              </div>
  
              <div class="flex space-x-4">
                <div class="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-900">Animatrice en centre de loisirs</h3>
                  <p class="text-sm text-gray-500 mb-2">Juin 2023 - Août 2024</p>
                  <p class="text-gray-700 text-sm">
                    Organisation d'activités ludiques et éducatives pour les groupes de 10-15 
                    enfants âgés de 4 à 8 ans.
                  </p>
                </div>
              </div>
  
              <div class="flex space-x-4">
                <div class="flex-shrink-0 w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-900">Baby-sitting occasionnel</h3>
                  <p class="text-sm text-gray-500 mb-2">2022</p>
                  <p class="text-gray-700 text-sm">
                    Garde d'enfants occasionnelle pour plusieurs familles, principalement en 
                    soirée et week-end.
                  </p>
                </div>
              </div>
            </div>
          </div>
  
          <!-- Formation -->
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Formation</h2>
            <div class="space-y-4">
              <div class="flex space-x-4">
                <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"></path>
                  </svg>
                </div>
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-900">Diplôme d'État d'Éducateur de Jeunes Enfants</h3>
                  <p class="text-sm text-gray-500 mb-1">2023 - En cours</p>
                  <p class="text-sm text-gray-600">École de Formation des Éducateurs de Jeunes Enfants</p>
                </div>
              </div>
  
              <div class="flex space-x-4">
                <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"></path>
                  </svg>
                </div>
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-900">Licence de Psychologie</h3>
                  <p class="text-sm text-gray-500 mb-1">2019 - 2022</p>
                  <p class="text-sm text-gray-600">Université Paris Descartes</p>
                </div>
              </div>
            </div>
          </div>
  
          <!-- Avis des parents -->
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-bold text-gray-900">Avis des parents</h2>
              <div class="flex items-center space-x-2">
                <div class="flex space-x-1">
                  <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                  </svg>
                </div>
                <span class="text-sm font-medium text-gray-900">4.9/5 (3)</span>
              </div>
            </div>
  
            <div class="space-y-6">
              <div class="border-b border-gray-100 pb-6">
                <div class="flex items-start space-x-3">
                  <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                    MD
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                      <h4 class="font-semibold text-gray-900">Marie D.</h4>
                      <span class="text-sm text-gray-500">15 mars 2025</span>
                    </div>
                    <div class="flex space-x-1 mb-3">
                      <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                      </svg>
                      <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                      </svg>
                      <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                      </svg>
                      <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                      </svg>
                      <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                      </svg>
                    </div>
                    <p class="text-gray-700 text-sm">
                      Sophie a gardé notre fils de 4 ans plusieurs fois et nous sommes très satisfaits. Elle est responsable et fait 
                      preuve d'une grande gentillesse. Notre fils l'adore !
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
  
        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Disponibilités -->
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Disponibilités</h3>
            <div class="space-y-3">
              <div class="flex items-center justify-between p-3 bg-secondary rounded-lg">
                <div class="flex items-center space-x-3">
                  <div class="w-2 h-2 bg-primary rounded-full"></div>
                  <span class="font-medium text-gray-900">Lundi</span>
                </div>
                <span class="text-sm text-gray-600">18h - 22h</span>
              </div>
              <div class="flex items-center justify-between p-3 bg-orange-100 rounded-lg">
                <div class="flex items-center space-x-3">
                  <div class="w-2 h-2 bg-primary rounded-full"></div>
                  <span class="font-medium text-gray-900">Samedi</span>
                </div>
                <div class="text-sm text-gray-600">
                  <div>10h - 15h</div>
                  <div>10h - 15h</div>
                </div>
              </div>
              <div class="flex items-center justify-between p-3 bg-secondary rounded-lg">
                <div class="flex items-center space-x-3">
                  <div class="w-2 h-2 bg-primary rounded-full"></div>
                  <span class="font-medium text-gray-900">Mercredi</span>
                </div>
                <span class="text-sm text-gray-600">18h - 22h</span>
              </div>
              <div class="flex items-center justify-between p-3 bg-orange-100 rounded-lg">
                <div class="flex items-center space-x-3">
                  <div class="w-2 h-2 bg-red-400 rounded-full"></div>
                  <span class="font-medium text-gray-900">Dimanche</span>
                </div>
                <div class="text-sm text-gray-600">
                  <div>10h - 15h</div>
                  <div>10h - 15h</div>
                </div>
              </div>
              <div class="flex items-center justify-between p-3 bg-secondary rounded-lg">
                <div class="flex items-center space-x-3">
                  <div class="w-2 h-2 bg-primary rounded-full"></div>
                  <span class="font-medium text-gray-900">Vendredi</span>
                </div>
                <span class="text-sm text-gray-600">18h - 22h</span>
              </div>
            </div>
          </div>
  
          <!-- Compétences -->
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Compétences</h3>
            <div class="flex flex-wrap gap-2">
              <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-sm rounded-full">Aide aux devoirs</span>
              <span class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full">Activités créatives</span>
              <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">Éveil musical</span>
              <span class="px-3 py-1 bg-purple-100 text-purple-800 text-sm rounded-full">Préparation des repas</span>
              <span class="px-3 py-1 bg-pink-100 text-pink-800 text-sm rounded-full">Premiers secours</span>
              <span class="px-3 py-1 bg-indigo-100 text-indigo-800 text-sm rounded-full">Jeux éducatifs</span>
              <span class="px-3 py-1 bg-red-100 text-red-800 text-sm rounded-full">Accompagnement bébé</span>
            </div>
          </div>
  
          <!-- Langues -->
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Langues</h3>
            <div class="space-y-3">
              <div class="flex items-center space-x-3">
                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-gray-900">Français (langue maternelle)</span>
              </div>
              <div class="flex items-center space-x-3">
                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-gray-900">Anglais (courant)</span>
              </div>
              <div class="flex items-center space-x-3">
                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-gray-900">Espagnol (intermédiaire)</span>
              </div>
            </div>
          </div>
  
          <!-- Vérifications -->
          <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Vérifications</h3>
            <div class="space-y-3">
              <div class="flex items-center space-x-3">
                <div class="w-4 h-4 rounded-full border-2 border-green-500 bg-green-500 flex items-center justify-center">
                  <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <span class="text-gray-900">Compte vérifié</span>
              </div>
              <div class="flex items-center space-x-3">
                <div class="w-4 h-4 rounded-full border-2 border-green-500 bg-green-500 flex items-center justify-center">
                  <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <span class="text-gray-900">Identité vérifiée</span>
              </div>
              <div class="flex items-center space-x-3">
                <div class="w-4 h-4 rounded-full border-2 border-green-500 bg-green-500 flex items-center justify-center">
                  <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <span class="text-gray-900">Email vérifié</span>
              </div>
              <div class="flex items-center space-x-3">
                <div class="w-4 h-4 rounded-full border-2 border-gray-300"></div>
                <span class="text-gray-500">Téléphone vérifié</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</GlobalLayout>
</template>
  
