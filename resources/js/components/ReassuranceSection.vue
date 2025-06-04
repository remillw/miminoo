<template>
    <section class="w-full py-16 px-4" :style="backgroundGradient">
      <div class="mx-auto max-w-7xl flex flex-col-reverse md:flex-row items-center justify-between gap-12">
        <!-- Colonne gauche -->
        <div class="text-white md:max-w-xl text-center md:text-left">
          <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/20 px-4 py-1 text-sm font-semibold">
            <Heart class="h-4 w-4 text-white fill-white" />
            Rejoignez la communauté Miminoo
          </div>
          <h2 class="mb-4 text-3xl font-extrabold md:text-4xl">
            Trouvez la babysitter parfaite<br />
            pour vos enfants dès aujourd’hui
          </h2>
          <p class="mb-6 text-white/90">
            Rejoignez des milliers de parents qui font confiance à Miminoo pour trouver des babysitters qualifiées et attentionnées.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
            <a href="#" class="rounded-full bg-white text-primary font-semibold px-6 py-3 shadow-md hover:bg-orange-100 transition">
              Trouver une babysitter
            </a>
            <a href="#" class="rounded-full border border-white font-semibold text-white px-6 py-3 hover:bg-white/10 transition">
              Devenir babysitter
            </a>
          </div>
        </div>
  
        <!-- Colonne droite -->
        <div class="w-full max-w-sm bg-white rounded-3xl p-6 shadow-md text-left">
          <div class="flex gap-4 mb-3">
            <img src="/storage/babysitter-test.png" alt="Julie" class="h-18 w-18 rounded-full object-cover" />
            <div>
              <p class="font-semibold text-gray-900">Julie M.</p>
              <p class="text-sm text-gray-400">Babysitter certifiée</p>
              <div class="mt-2 flex gap-1">
                <Heart v-for="i in 5" :key="i" class="h-5 w-5 text-primary fill-current" />
              </div>
            </div>
          </div>
          <blockquote class="bg-gray-100 rounded-xl p-4 text-sm text-gray-800 mb-4">
            "J'adore travailler avec Miminoo ! Je peux choisir mes horaires et rencontrer des familles formidables près de chez moi."
          </blockquote>
          <div class="flex justify-between text-sm text-gray-400">
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
  