<template>
    <section class="w-full py-8 px-4 sm:py-12 md:py-16" :style="backgroundGradient">
      <div class="mx-auto max-w-7xl flex flex-col-reverse md:flex-row items-center justify-between gap-6 sm:gap-8 md:gap-12">
        <!-- Colonne gauche -->
        <div class="text-white w-full md:max-w-xl text-center md:text-left">
          <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-xs font-semibold sm:mb-4 sm:px-4 sm:text-sm">
            <Heart class="h-3 w-3 text-white fill-white sm:h-4 sm:w-4" />
            Rejoignez la communauté des babysittings
          </div>
          <h2 class="mb-3 text-2xl font-extrabold sm:text-3xl md:text-4xl sm:mb-4">
            Trouvez la babysitter parfaite<br class="hidden sm:block" />
            <span class="sm:hidden"> </span>pour vos enfants dès aujourd'hui
          </h2>
          <p class="mb-4 text-sm text-white/90 sm:text-base sm:mb-6">
            Rejoignez des milliers de parents qui nous font confiance pour trouver des babysitters qualifiées et attentionnées.
          </p>
          <div class="flex flex-col gap-3 justify-center md:justify-start sm:flex-row sm:gap-4">
            <a href="/creer-une-annonce" class="rounded-full bg-white text-primary font-semibold px-4 py-2 shadow-md hover:bg-orange-100 transition text-sm sm:px-6 sm:py-3 sm:text-base">
              Trouver une babysitter
            </a>
            <a href="/devenir-babysitter" class="rounded-full border border-white font-semibold text-white px-4 py-2 hover:bg-white/10 transition text-sm sm:px-6 sm:py-3 sm:text-base">
              Devenir babysitter
            </a>
          </div>
        </div>
  
        <!-- Colonne droite -->
        <div class="w-full max-w-xs bg-white rounded-2xl p-4 shadow-md text-left sm:max-w-sm sm:rounded-3xl sm:p-6">
          <div class="flex gap-3 mb-3 sm:gap-4">
            <img src="/storage/babysitter-test.png" alt="Julie" class="h-12 w-12 rounded-full object-cover sm:h-16 sm:w-16 md:h-18 md:w-18" />
            <div class="flex-1">
              <p class="font-semibold text-gray-900 text-sm sm:text-base">Julie M.</p>
              <p class="text-xs text-gray-400 sm:text-sm">Babysitter certifiée</p>
              <div class="mt-1 flex gap-0.5 sm:mt-2 sm:gap-1">
                <Heart v-for="i in 5" :key="i" class="h-3 w-3 text-primary fill-current sm:h-4 sm:w-4 md:h-5 md:w-5" />
              </div>
            </div>
          </div>
          <blockquote class="bg-gray-100 rounded-lg p-3 text-xs text-gray-800 mb-3 sm:rounded-xl sm:p-4 sm:text-sm sm:mb-4">
            "J'adore travailler avec cette plateforme ! Je peux choisir mes horaires et rencontrer des familles formidables près de chez moi."
          </blockquote>
          <div class="flex justify-between text-xs text-gray-400 sm:text-sm">
            <div>
              <span class="font-semibold">28 heures</span><br />de garde ce mois
            </div>
            <div class="text-right">
              <span class="font-semibold">8 familles</span><br />satisfaites
            </div>
          </div>
        </div>
      </div>
    </section>
  </template>
  
  <script setup lang="ts">
  import { Heart } from 'lucide-vue-next'
  
  const backgroundGradient = {
    background: 'linear-gradient(90deg, #FF8359 0%, #FF9565 100%)'
  }
  </script>
  