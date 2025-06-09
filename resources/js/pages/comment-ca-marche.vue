<script setup lang="ts">
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import EtapesParents from '../components/EtapesParents.vue';
import Etapesbabysitter from '../components/Etapesbabysitter.vue';
import ReassuranceSection from '../components/ReassuranceSection.vue';
</script>

<template>
    <GlobalLayout>
        <div class="flex items-center justify-center bg-orange-50 px-4 py-10">
            <div class="w-full max-w-4xl text-center">
                <h1 class="mb-6 text-4xl font-bold text-gray-900 md:text-5xl">Comment fonctionne Miminoo ?</h1>

                <p class="mx-auto mb-12 max-w-2xl text-lg leading-relaxed text-gray-600 md:text-xl">
                    Découvrez comment Miminoo vous aide à trouver la babysitter idéale en toute confiance, que ce soit pour une garde d'urgence ou
                    planifiée.
                </p>

                <div class="relative flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <div class="relative order-1">
                        <button
                            class="bg-primary transform rounded-lg px-8 py-3 text-lg font-medium text-white shadow-lg transition-all duration-300 hover:scale-105 hover:bg-orange-600 hover:shadow-xl"
                        >
                            Créer une annonce
                        </button>

                        <div class="absolute -top-3 -right-3 z-10">
                            <div class="relative">
                                <div
                                    class="animate-float animate-shimmer rounded-full border border-white bg-gradient-to-r from-emerald-500 to-teal-500 px-2.5 py-1 text-xs font-medium text-white shadow-md"
                                >
                                    Sans inscription
                                </div>
                            </div>
                        </div>
                    </div>

                    <button
                        class="border-primary text-primary hover:bg-primary order-2 rounded-lg border px-8 py-3 text-lg font-medium transition-colors hover:text-white"
                    >
                        Voir les annonces en cours
                    </button>
                </div>
            </div>
        </div>
        <EtapesParents />
        <Etapesbabysitter />
        <ReassuranceSection />
    </GlobalLayout>
</template>

<style scoped>
/* Animation par box-shadow au lieu de transform pour éviter le flou */
@keyframes floatShadow {
    0%,
    100% {
        box-shadow:
            0 4px 6px -1px rgba(0, 0, 0, 0.1),
            0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    50% {
        box-shadow:
            0 8px 15px -3px rgba(0, 0, 0, 0.15),
            0 4px 6px -2px rgba(0, 0, 0, 0.1);
    }
}

@keyframes softGlow {
    0%,
    100% {
        box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.3);
    }
    50% {
        box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.2);
    }
}

@keyframes shimmer {
    0% {
        background-position: -200% center;
    }
    100% {
        background-position: 200% center;
    }
}

/* Classes d'animation sans transform */
.animate-float {
    animation: floatShadow 4s ease-in-out infinite;
}

.animate-softPulse {
    animation: softGlow 3s ease-in-out infinite;
}

.animate-shimmer {
    background: linear-gradient(
        90deg,
        rgba(16, 185, 129, 1) 0%,
        rgba(34, 197, 94, 1) 25%,
        rgba(20, 184, 166, 1) 50%,
        rgba(34, 197, 94, 1) 75%,
        rgba(16, 185, 129, 1) 100%
    );
    background-size: 200% 100%;
    animation: shimmer 3s ease-in-out infinite;
}
</style>
