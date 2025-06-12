<template>
    <div class="rounded-2xl overflow-hidden shadow-md bg-white flex flex-col">
      <!-- Image + badge -->
      <div class="relative h-48 w-full">
        <img :src="image" :alt="`Photo de ${name}`" class="h-full w-full object-cover" />
        <span v-if="verified"
          class="absolute top-2 left-2 rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-800 shadow">
          Vérifiée
        </span>
      </div>
  
      <!-- Infos principales -->
      <div class="p-4 flex flex-col gap-3">
        <div>
  <h3 class="text-lg font-semibold text-gray-900">{{ name }}, {{ age }} ans</h3>
  <p class="text-sm text-gray-500 flex items-center">
    <MapPin class="h-4 w-4 mr-1 text-gray-400" />
    {{ city }}
  </p>
</div>


  
        <hr />
  
        <!-- Temps de réponse -->
        <div class="flex items-center text-sm text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-[#6EBBD3]" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Temps de réponse : environ {{ responseTime }}
        </div>
  
        <hr />
  
        <!-- Tarifs & Expérience -->
        <div class="flex justify-between text-sm text-gray-700">
          <div class="bg-gray-100 rounded-md p-2 w-[48%]">
            <p class="text-gray-400 text-xs">Tarif horaire</p>
            <p class="font-semibold">{{ rate }} €/h</p>
          </div>
          <div class="bg-gray-100 rounded-md p-2 w-[48%]">
            <p class="text-gray-400 text-xs">Expérience</p>
            <p class="font-semibold">{{ experience }}</p>
          </div>
        </div>
  
        <!-- Bouton -->
        <a :href="link"
           class="mt-3 inline-block rounded-lg bg-primary px-6 py-3 text-center text-sm font-semibold text-white hover:bg-primary transition">
          Voir le profil
        </a>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { MapPin } from 'lucide-vue-next'

  defineProps<{
    image: string
    name: string
    age: number
    city: string
    verified?: boolean
    responseTime: string
    rate: number
    experience: string
    link: string
  }>()
  </script>
  