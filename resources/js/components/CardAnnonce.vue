<template>
    <div class="rounded-3xl bg-white p-6 shadow-md flex flex-col gap-4 max-w-md w-full">
      <!-- Header: Avatar + Name + Rating -->
      <div class="flex items-center gap-4">
        <img :src="avatar" alt="Avatar" class="h-14 w-14 rounded-full object-cover" />
        <div>
          <div class="font-bold text-lg">{{ name }}</div>

          <div class="flex items-center gap-1 text-sm text-gray-400">
            <Star class="h-4 w-4 text-yellow-400 fill-current" />
            <span class="font-semibold text-gray-700">{{ rating }}</span>
            <span class="text-gray-400">({{ reviews }} avis)</span>
          </div>
        </div>
      </div>
  
      <!-- Date, time, location -->
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div class="flex items-start gap-2">
          <CalendarClock class="h-7 w-7 text-primary bg-orange-50 rounded-md p-1" />
          <div>
            <p class="font-semibold">{{ date }}</p>
            <p class="text-gray-400">{{ time }}</p>
          </div>
        </div>
        <div class="flex items-start gap-2">
          <MapPin class="h-7 w-7 text-primary bg-orange-50 rounded-md p-1" />
          <p class="font-semibold">{{ location }}</p>
        </div>
      </div>
  
      <!-- Kids -->
      <div class="flex items-center gap-2 text-sm">
        <Users class="h-7 w-7 text-primary bg-orange-50 rounded-md p-1" />
        <p class="font-semibold">{{ childrenLabel }}</p>
      </div>
  
      <!-- Description -->
      <p class="text-sm text-gray-800">
        {{ description }}
      </p>
  
      <!-- Footer -->
      <div class="flex justify-between items-center pt-2 border-t border-gray-200">
        <p>
          <span class="font-bold text-lg">{{ rate }}€</span>
          <span class="text-gray-400">/heure</span>
        </p>
        <button class="bg-primary hover:bg-orange-500 text-white font-semibold rounded px-5 py-2 transition">
          Postuler
        </button>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import { Star, CalendarClock, MapPin, Users } from 'lucide-vue-next';
  
  defineProps({
    avatar: String,
    name: String,
    rating: Number,
    reviews: Number,
    date: String,
    time: String,
    location: String,
    childrenLabel: String,
    description: String,
    rate: Number,
  });
  </script>
  