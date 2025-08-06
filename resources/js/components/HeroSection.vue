<script setup lang="ts">
import { Clock, Search, Star } from 'lucide-vue-next';
</script>

<template>
    <section class="bg-secondary w-full py-6 sm:py-8 md:py-12 lg:py-16">
        <div class="mx-auto flex max-w-7xl flex-col-reverse items-center gap-6 px-4 sm:gap-8 md:flex-row md:gap-12">
            <!-- Left: Text content -->
            <div class="flex max-w-xl flex-1 flex-col items-center justify-center text-center md:items-start md:text-left">
                <span class="bg-primary-opacity text-primary mb-3 rounded-full px-3 py-2 text-xs font-semibold sm:mb-4"
                    >Une solution de garde en urgence</span
                >
                <h1 class="mb-3 text-2xl font-extrabold text-gray-900 sm:mb-4 sm:text-3xl md:text-4xl lg:text-5xl">
                    Trouvez une <span class="text-primary">babysitter</span> de confiance en quelques minutes
                </h1>
                <p class="mb-4 text-sm text-gray-700 sm:mb-6 sm:text-base md:text-lg">
                    La plateforme qui vous connecte instantanément avec des babysitters vérifiées et disponibles près de chez vous, pour une garde
                    d'enfants de dernière minute ou planifiée.
                </p>
                <div class="mb-4 flex w-full flex-col gap-3 sm:mb-6 sm:w-auto sm:flex-row">
                    <a
                        href="/creer-une-annonce"
                        class="bg-primary hover:bg-primary rounded-lg px-4 py-2 text-center text-sm font-semibold text-white sm:px-6 sm:py-3 sm:text-base"
                    >
                        Publier une annonce
                    </a>

                    <a
                        href="/comment-ca-marche"
                        class="border-primary text-primary rounded-lg border px-4 py-2 text-center text-sm hover:bg-orange-100 sm:px-6 sm:py-3 sm:text-base"
                    >
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
                <img
                    src="/storage/trouve-babysitter-domicile.png"
                    alt="Babysitter illustration"
                    class="mb-4 w-full max-w-xs sm:max-w-sm md:mb-0 md:max-w-md"
                />
            </div>
        </div>
    </section>
</template>
