<template>
    <div class="from-secondary to-secondary flex min-h-screen items-center justify-center bg-gradient-to-br via-white px-4">
        <div class="w-full max-w-2xl text-center">
            <!-- Animation container -->
            <div class="relative mb-8">
                <!-- Cercles de décoration avec animation -->
                <div class="absolute -top-4 -left-4 h-24 w-24 animate-pulse rounded-full bg-orange-100 opacity-60"></div>
                <div class="absolute -right-4 -bottom-4 h-16 w-16 animate-bounce rounded-full bg-orange-200 opacity-40"></div>

                <!-- Icône principale -->
                <div class="relative mx-auto flex h-40 w-40 items-center justify-center rounded-full border border-orange-100 bg-white p-8 shadow-lg">
                    <div class="text-primary animate-pulse text-8xl font-bold">404</div>
                </div>
            </div>

            <!-- Titre principal -->
            <h1 class="mb-4 text-4xl font-bold text-gray-900 md:text-5xl">Oops ! Page introuvable</h1>

            <!-- Sous-titre avec un peu d'humour -->
            <p class="mb-8 text-lg leading-relaxed text-gray-600 md:text-xl">
                Il semblerait que notre babysitter ait perdu cette page quelque part...
                <br class="hidden md:block" />
                Ne vous inquiétez pas, on va vous aider à retrouver votre chemin !
            </p>

            <!-- Suggestions d'actions -->
            <div class="mb-8 space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Card suggestion 1 -->
                    <div
                        class="rounded-xl border border-orange-100 bg-white p-6 shadow-sm transition-all duration-200 hover:border-orange-200 hover:shadow-md"
                    >
                        <div class="mb-3 flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100">
                                <Home class="text-primary h-4 w-4" />
                            </div>
                            <h3 class="font-semibold text-gray-900">Retour à l'accueil</h3>
                        </div>
                        <p class="mb-3 text-sm text-gray-600">Découvrez toutes nos annonces de babysitting</p>
                        <Link href="/" class="text-primary text-sm font-medium transition-colors duration-200 hover:text-orange-700">
                            Aller à l'accueil →
                        </Link>
                    </div>

                    <!-- Card suggestion 2 -->
                    <div
                        class="rounded-xl border border-orange-100 bg-white p-6 shadow-sm transition-all duration-200 hover:border-orange-200 hover:shadow-md"
                    >
                        <div class="mb-3 flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100">
                                <Search class="text-primary h-4 w-4" />
                            </div>
                            <h3 class="font-semibold text-gray-900">Voir les annonces</h3>
                        </div>
                        <p class="mb-3 text-sm text-gray-600">Trouvez la mission parfaite près de chez vous</p>
                        <Link
                            :href="route('announcements.index')"
                            class="text-primary text-sm font-medium transition-colors duration-200 hover:text-orange-700"
                        >
                            Parcourir les annonces →
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Actions principales -->
            <div class="flex flex-col justify-center gap-4 sm:flex-row">
                <Link
                    href="/"
                    class="from-primary hover:from-primary hover:to-primary inline-flex transform items-center justify-center gap-2 rounded-lg bg-gradient-to-r to-orange-400 px-8 py-3 font-medium text-white shadow-lg transition-all duration-200 hover:scale-105"
                >
                    <Home class="h-5 w-5" />
                    Retour à l'accueil
                </Link>

                <button
                    @click="goBack"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-8 py-3 font-medium text-gray-700 shadow-md transition-all duration-200 hover:border-gray-300 hover:bg-gray-50"
                >
                    <ArrowLeft class="h-5 w-5" />
                    Page précédente
                </button>
            </div>

            <!-- Footer avec infos de contact ou aide -->
            <div class="mt-12 border-t border-gray-200 pt-8">
                <p class="mb-4 text-sm text-gray-500">Vous pensez qu'il s'agit d'une erreur ou vous avez besoin d'aide ?</p>
                <div class="flex flex-col items-center justify-center gap-4 text-sm sm:flex-row">
                    <a
                        href="mailto:hello@trouvetababysitter.fr"
                        class="text-primary flex items-center gap-2 transition-colors duration-200 hover:text-orange-700"
                    >
                        <Mail class="h-4 w-4" />
                        hello@trouvetababysitter.fr
                    </a>
                    <span class="hidden text-gray-300 sm:block">•</span>
                    <Link href="/comment-ca-marche" class="text-primary flex items-center gap-2 transition-colors duration-200 hover:text-orange-700">
                        <HelpCircle class="h-4 w-4" />
                        Comment ça marche ?
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, HelpCircle, Home, Mail, Search } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/';
    }
};
</script>

<style scoped>
@keyframes float {
    0%,
    100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}
</style>
