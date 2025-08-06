<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-yellow-50 via-white to-yellow-50 px-4">
        <div class="w-full max-w-2xl text-center">
            <!-- Animation container -->
            <div class="relative mb-8">
                <!-- Cercles de décoration avec animation -->
                <div class="absolute -top-4 -left-4 h-24 w-24 animate-pulse rounded-full bg-yellow-100 opacity-60"></div>
                <div class="absolute -right-4 -bottom-4 h-16 w-16 animate-bounce rounded-full bg-yellow-200 opacity-40"></div>

                <!-- Icône principale -->
                <div class="relative mx-auto flex h-40 w-40 items-center justify-center rounded-full border border-yellow-100 bg-white p-8 shadow-lg">
                    <div class="animate-pulse text-8xl font-bold text-yellow-500">403</div>
                </div>
            </div>

            <!-- Titre principal -->
            <h1 class="mb-4 text-4xl font-bold text-gray-900 md:text-5xl">Accès interdit</h1>

            <!-- Message d'erreur -->
            <p class="mb-8 text-lg leading-relaxed text-gray-600 md:text-xl">
                Vous n'avez pas l'autorisation d'accéder à cette page.
                <br class="hidden md:block" />
                Vérifiez vos permissions ou connectez-vous avec un autre compte.
            </p>

            <!-- Actions principales -->
            <div class="mb-8 flex flex-col justify-center gap-4 sm:flex-row">
                <Link
                    href="/"
                    class="from-primary hover:from-primary hover:to-primary inline-flex transform items-center justify-center gap-2 rounded-lg bg-gradient-to-r to-orange-400 px-8 py-3 font-medium text-white shadow-lg transition-all duration-200 hover:scale-105"
                >
                    <Home class="h-5 w-5" />
                    Retour à l'accueil
                </Link>

                <Link
                    :href="route('connexion')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-8 py-3 font-medium text-gray-700 shadow-md transition-all duration-200 hover:border-gray-300 hover:bg-gray-50"
                >
                    <LogIn class="h-5 w-5" />
                    Se connecter
                </Link>
            </div>

            <!-- Footer avec infos de contact -->
            <div class="mt-12 border-t border-gray-200 pt-8">
                <p class="mb-4 text-sm text-gray-500">Besoin d'aide avec vos permissions ?</p>
                <a
                    href="mailto:hello@trouvetababysitter.fr"
                    class="text-primary inline-flex items-center gap-2 transition-colors duration-200 hover:text-orange-700"
                >
                    <Mail class="h-4 w-4" />
                    hello@trouvetababysitter.fr
                </a>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Home, LogIn, Mail } from 'lucide-vue-next';
import { route } from 'ziggy-js';
</script>
