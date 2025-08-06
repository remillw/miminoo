<template>
    <div class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-md">
        <!-- Image + badge -->
        <div class="relative h-48 w-full">
            <img :src="image" :alt="`Photo de ${name}`" class="h-full w-full object-cover" />
            <span v-if="verified" class="absolute top-2 left-2 rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-800 shadow">
                Vérifiée
            </span>
        </div>

        <!-- Infos principales -->
        <div class="flex flex-col gap-3 p-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ name }}, {{ age }} ans</h3>
                <p class="flex items-center text-sm text-gray-500">
                    <MapPin class="mr-1 h-4 w-4 text-gray-400" />
                    {{ city }}
                </p>
            </div>

            <hr />

            <!-- Temps de réponse -->
            <div class="flex items-center text-sm text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4 text-[#6EBBD3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Temps de réponse : environ {{ responseTime }}
            </div>

            <hr />

            <!-- Tarifs & Expérience -->
            <div class="flex justify-between text-sm text-gray-700">
                <div class="w-[48%] rounded-md bg-gray-100 p-2">
                    <p class="text-xs text-gray-400">Tarif horaire</p>
                    <p class="font-semibold">{{ rate }} €/h</p>
                </div>
                <div class="w-[48%] rounded-md bg-gray-100 p-2">
                    <p class="text-xs text-gray-400">Expérience</p>
                    <p class="font-semibold">{{ experience }}</p>
                </div>
            </div>

            <!-- Bouton -->
            <a
                :href="link"
                class="bg-primary hover:bg-primary mt-3 inline-block rounded-lg px-6 py-3 text-center text-sm font-semibold text-white transition"
            >
                Voir le profil
            </a>
        </div>
    </div>
</template>

<script setup lang="ts">
import { MapPin } from 'lucide-vue-next';

defineProps<{
    image: string;
    name: string;
    age: number;
    city: string;
    verified?: boolean;
    responseTime: string;
    rate: number;
    experience: string;
    link: string;
}>();
</script>
