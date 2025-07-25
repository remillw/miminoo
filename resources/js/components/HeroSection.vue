<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { CheckCircle, Clock, Search, Star } from 'lucide-vue-next';
</script>

<template>
    <section class="w-full bg-secondary py-6 sm:py-8 md:py-12 lg:py-16">
        <div class="mx-auto flex max-w-7xl flex-col-reverse items-center gap-6 px-4 sm:gap-8 md:flex-row md:gap-12">
            <!-- Left: Text content -->
            <div class="flex max-w-xl flex-1 flex-col items-center justify-center text-center md:items-start md:text-left">
                <span class="mb-3 rounded-full bg-primary-opacity px-3 py-2 text-xs font-semibold text-primary sm:mb-4">Une solution de garde en urgence</span>
                <h1 class="mb-3 text-2xl font-extrabold text-gray-900 sm:text-3xl md:text-4xl lg:text-5xl sm:mb-4">
                    Trouvez une <span class="text-primary">babysitter</span> de confiance en quelques minutes
                </h1>
                <p class="mb-4 text-sm text-gray-700 sm:text-base md:text-lg sm:mb-6">
                    La plateforme qui vous connecte instantanément avec des babysitters vérifiées et disponibles près de chez vous, pour une garde d'enfants de
                    dernière minute ou planifiée.
                </p>
                <div class="mb-4 flex w-full flex-col gap-3 sm:mb-6 sm:flex-row sm:w-auto">
  <a href="/annonces" class="bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary text-center sm:px-6 sm:py-3 sm:text-base rounded-lg">
    Trouver une babysitter
  </a>
  
  <a href="/comment-ca-marche" class="border border-primary text-primary px-4 py-2 text-sm hover:bg-orange-100 text-center sm:px-6 sm:py-3 sm:text-base rounded-lg">
    Comment ça marche ?
  </a>
</div>

                <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:gap-4 md:gap-6">
  <div class="flex items-center justify-center gap-2 text-xs text-gray-800 sm:justify-start sm:gap-3 sm:text-sm">
    <div class="rounded-lg bg-white p-1.5 shadow-sm sm:p-2">
      <Search class="text-primary h-3 w-3 sm:h-4 sm:w-4" />
    </div>
    <span>Recherche rapide</span>
  </div>

  <div class="flex items-center justify-center gap-2 text-xs text-gray-800 sm:justify-start sm:gap-3 sm:text-sm">
    <div class="rounded-lg bg-white p-1.5 shadow-sm sm:p-2">
      <Clock class="text-primary h-3 w-3 sm:h-4 sm:w-4" />
    </div>
    <span>Disponibilité immédiate</span>
  </div>

  <div class="flex items-center justify-center gap-2 text-xs text-gray-800 sm:justify-start sm:gap-3 sm:text-sm">
    <div class="rounded-lg bg-white p-1.5 shadow-sm sm:p-2">
      <Star class="text-primary h-3 w-3 sm:h-4 sm:w-4" />
    </div>
    <span>Avis des parents</span>
  </div>
</div>

            </div>
          <!-- Right: Illustration -->
<div class="flex flex-1 items-center justify-center">
  <img src="/storage/trouve-babysitter-domicile.png"
       alt="Babysitter illustration"
       class="mb-4 w-full max-w-xs sm:max-w-sm md:mb-0 md:max-w-md" />
</div>

        </div>
    </section>
</template>
