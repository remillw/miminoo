<template>
  <div class="relative flex flex-col items-center justify-between gap-6 rounded-3xl bg-white p-8 shadow-md md:flex-row min-h-[340px]">
    <!-- Colonne gauche : Avatar, nom, info, étoiles -->
    <div class="flex flex-col items-center text-center md:w-1/3">
      <img
        :src="avatar"
        :alt="`Photo de ${name}`"
        class="mb-4 h-20 w-20 rounded-full object-cover"
      />
      <h3 class="text-lg font-semibold text-gray-900">{{ name }}</h3>
      <p class="text-sm text-gray-500">{{ role }}</p>
      <div class="mt-2 flex gap-1">
        <template v-for="i in 5" :key="i">
          <svg
            v-if="i <= rating"
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4 text-yellow-400"
            fill="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              d="M12 .587l3.668 7.431L24 9.753l-6 5.843 1.417 8.264L12 19.771l-7.417 4.089L6 15.596 0 9.753l8.332-1.735z"
            />
          </svg>
          <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4 text-gray-300"
            fill="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              d="M12 .587l3.668 7.431L24 9.753l-6 5.843 1.417 8.264L12 19.771l-7.417 4.089L6 15.596 0 9.753l8.332-1.735z"
            />
          </svg>
        </template>
      </div>
    </div>

    <!-- Colonne droite : texte -->
    <div class="md:w-2/3 text-center md:text-left text-gray-800 text-base leading-relaxed">
      “{{ content }}”
    </div>

    <!-- Slot de navigation (flèches + dots) -->
    <slot name="navigation" />
  </div>
</template>

<script setup lang="ts">
defineProps<{
  avatar: string
  name: string
  role: string
  rating: number
  content: string
}>()
</script>
