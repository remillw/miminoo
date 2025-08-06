<template>
    <section class="w-full bg-white py-8 sm:py-12 md:py-16">
        <div class="mx-auto max-w-7xl px-4">
            <!-- Titre -->
            <h2 class="mb-2 text-center text-xl font-bold sm:text-2xl md:text-3xl lg:text-4xl">Babysitters disponibles</h2>
            <p class="mx-auto mb-6 max-w-xl text-center text-sm text-gray-600 sm:mb-8 sm:max-w-2xl sm:text-base md:mb-10">
                Découvrez notre sélection de babysitters hautement qualifiées, prêtes à prendre soin de vos enfants.
            </p>

            <!-- Grille des cartes -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 md:gap-8 lg:grid-cols-3">
                <BabysitterCard v-for="sitter in babysitters" :key="sitter.name" v-bind="sitter" />
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import BabysitterCard from '@/components/BabysitterCard.vue';

const babysitters = [
    {
        image: '/storage/babysitter-test.png',
        name: 'Camille',
        age: 24,
        city: 'Marseille 12e',
        verified: true,
        responseTime: '5 minutes',
        rate: 15,
        experience: '3 ans',
        link: '/profil/camille',
    },
    {
        image: '/storage/babysitter-test.png',
        name: 'Laura',
        age: 24,
        city: 'Marseille 12e',
        verified: true,
        responseTime: '5 minutes',
        rate: 15,
        experience: '3 ans',
        link: '/profil/laura',
    },
    {
        image: '/storage/babysitter-test.png',
        name: 'Emilie',
        age: 24,
        city: 'Marseille 12e',
        verified: true,
        responseTime: '5 minutes',
        rate: 15,
        experience: '3 ans',
        link: '/profil/emilie',
    },
];
</script>
