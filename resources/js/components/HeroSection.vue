<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { CheckCircle, Clock, Search, Star } from 'lucide-vue-next';
</script>

<template>
    <section class="w-full bg-orange-50 py-8 md:py-16">
        <div class="mx-auto flex max-w-7xl flex-col-reverse items-center gap-8 px-4 md:flex-row md:gap-12">
            <!-- Left: Text content -->
            <div class="flex max-w-xl flex-1 flex-col items-start justify-center">
                <span class="mb-4 rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-500">Une solution de garde en urgence</span>
                <h1 class="mb-4 text-3xl font-extrabold text-gray-900 md:text-5xl">
                    Trouvez une <span class="text-primary">babysitter</span> de confiance en quelques minutes
                </h1>
                <p class="mb-6 text-base text-gray-700 md:text-lg">
                    Miminoo vous connecte instantanément avec des babysitters vérifiées et disponibles près de chez vous, pour une garde d'enfants de
                    dernière minute ou planifiée.
                </p>
                <div class="mb-6 flex flex-col gap-3 sm:flex-row">
                    <Button class="bg-primary px-6 py-3 text-base font-semibold text-white hover:bg-orange-500">Trouver une babysitter</Button>
                    <Button variant="outline" class="border-primary text-primary px-6 py-3 text-base hover:bg-orange-100">Comment ça marche ?</Button>
                </div>
                <div class="mt-2 flex flex-wrap gap-4">
                    <div class="flex items-center gap-2 text-sm text-gray-700"><Search class="text-primary h-5 w-5" /> Recherche rapide</div>
                    <div class="flex items-center gap-2 text-sm text-gray-700"><Clock class="text-primary h-5 w-5" /> Disponibilité immédiate</div>
                    <div class="flex items-center gap-2 text-sm text-gray-700"><Star class="text-primary h-5 w-5" /> Avis des parents</div>
                </div>
            </div>
            <!-- Right: Illustration & badges -->
            <div class="flex w-full max-w-md flex-1 flex-col items-center md:items-end">
                <!-- Illustration (remplace par ton SVG ou image) -->
                <img src="/storage/hero.png" alt="Babysitter illustration" class="mb-4 w-full max-w-xs md:mb-0 md:max-w-sm" />
                <!-- Badges -->
                <div class="flex flex-col gap-2 md:absolute md:top-32 md:right-12">
                    <div
                        class="flex items-center gap-2 rounded-lg border border-green-100 bg-white px-3 py-2 text-sm font-medium text-green-600 shadow"
                    >
                        <CheckCircle class="h-5 w-5 text-green-500" /> 100% vérifié <span class="ml-1 text-gray-500">Profils certifiés</span>
                    </div>
                    <div
                        class="flex items-center gap-2 rounded-lg border border-blue-100 bg-white px-3 py-2 text-sm font-medium text-blue-600 shadow"
                    >
                        <Clock class="h-5 w-5 text-blue-500" /> En 15 minutes <span class="ml-1 text-gray-500">Réservation rapide</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
