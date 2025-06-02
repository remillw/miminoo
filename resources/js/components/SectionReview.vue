<template>
    <section class="w-full bg-orange-50 py-16">
      <div class="mx-auto max-w-5xl px-4">
        <!-- Titre + sous-titre -->
        <h2 class="text-center text-2xl font-bold md:text-3xl mb-2">
          Ce que les parents disent de nous
        </h2>
        <p class="text-center text-gray-600 mb-10">
          Découvrez les témoignages des parents qui font confiance à Miminoo pour la garde de leurs enfants.
        </p>
  
        <!-- Bloc slider -->
        <div class="relative">
          <ReviewCard v-bind="reviews[current]" />
  
          <!-- Dots -->
          <div class="mt-6 flex justify-center gap-2">
            <span
              v-for="(_, i) in reviews"
              :key="i"
              class="h-2 w-2 rounded-full"
              :class="i === current ? 'bg-primary' : 'bg-gray-300'"
            ></span>
          </div>
  
          <!-- Flèches -->
          <button
            @click="prev"
            class="absolute left-0 top-1/2 -translate-y-1/2 rounded-full bg-white p-2 shadow-md"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
  
          <button
            @click="next"
            class="absolute right-0 top-1/2 -translate-y-1/2 rounded-full bg-white p-2 shadow-md"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>
    </section>
  </template>
  
  <script setup lang="ts">
  import { ref } from 'vue'
  import ReviewCard from '@/components/ReviewCard.vue'
  
  const current = ref(0)
  
  const reviews = [
    {
      avatar: '/storage/review-parent.png',
      name: 'Sophie D.',
      role: 'Maman de 2 enfants',
      rating: 5,
      content:
        "Miminoo m'a sauvée plus d'une fois ! J'ai pu trouver une babysitter fiable en moins de 30 minutes alors que j'avais une réunion urgente. Le processus est simple et rassurant.",
    },
    {
      avatar: '/storage/review-parent.png',
      name: 'Laetitia G.',
      role: 'Maman solo à Aix',
      rating: 5,
      content:
        "Un service ultra pratique ! J’utilise Miminoo quand j’ai des imprévus pro, et je n’ai jamais été déçue. Les babysitters sont pros et ponctuelles.",
    },
    {
      avatar: '/storage/review-parent.png',
      name: 'Julien M.',
      role: 'Papa de 3 enfants',
      rating: 4,
      content:
        "C’est rassurant d’avoir un service comme celui-ci. L’interface est claire, et les profils sont bien renseignés. Mention spéciale pour la messagerie intégrée.",
    },
  ]
  
  function next() {
    current.value = (current.value + 1) % reviews.length
  }
  
  function prev() {
    current.value = (current.value - 1 + reviews.length) % reviews.length
  }
  </script>
  