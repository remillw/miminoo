<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { CheckCircle, Clock, Search, Star } from 'lucide-vue-next';
</script>

<template>
    <section class="w-full bg-orange-50 py-8 md:py-16">
        <div class="mx-auto flex max-w-7xl flex-col-reverse items-center gap-8 px-4 md:flex-row md:gap-12">
            <!-- Left: Text content -->
            <div class="flex max-w-xl flex-1 flex-col items-start justify-center">
                <span class="mb-4 rounded-full primaryopacity px-3 py-2 text-xs font-semibold text-primary">Une solution de garde en urgence</span>
                <h1 class="mb-4 text-3xl font-extrabold text-gray-900 md:text-5xl">
                    Trouvez une <span class="text-primary">babysitter</span> de confiance en quelques minutes
                </h1>
                <p class="mb-6 text-base text-gray-700 md:text-lg">
                    Miminoo vous connecte instantanément avec des babysitters vérifiées et disponibles près de chez vous, pour une garde d'enfants de
                    dernière minute ou planifiée.
                </p>
                <div class="mb-6 flex flex-col gap-3 sm:flex-row">
  <a href="/trouver-une-babysitter" class="bg-primary px-6 py-3 text-base font-semibold text-white hover:bg-orange-500 text-center">
    Trouver une babysitter
  </a>
  
  <a href="/comment-ca-marche" class="border border-primary text-primary px-6 py-3 text-base hover:bg-orange-100 text-center">
    Comment ça marche ?
  </a>
</div>

                <div class="mt-2 flex flex-wrap gap-6">
  <div class="flex items-center gap-3 text-sm text-gray-800">
    <div class="rounded-lg bg-white p-2 shadow-sm">
      <Search class="text-primary h-4 w-4" />
    </div>
    <span>Recherche rapide</span>
  </div>

  <div class="flex items-center gap-3 text-sm text-gray-800">
    <div class="rounded-lg bg-white p-2 shadow-sm">
      <Clock class="text-primary h-4 w-4" />
    </div>
    <span>Disponibilité immédiate</span>
  </div>

  <div class="flex items-center gap-3 text-sm text-gray-800">
    <div class="rounded-lg bg-white p-2 shadow-sm">
      <Star class="text-primary h-4 w-4" />
    </div>
    <span>Avis des parents</span>
  </div>
</div>

            </div>
            <!-- Right: Illustration & badges -->
          <!-- Right: Illustration -->
<div class="flex flex-1 items-center justify-center">
  <img src="/storage/babysitter-domicile-miminoo.png"
       alt="Babysitter illustration"
       class="mb-4 max-w-md md:mb-0" />
</div>

        </div>
    </section>
</template>
