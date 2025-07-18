<template>
    <div class="min-h-screen bg-gradient-to-br from-yellow-50 via-white to-yellow-50 flex items-center justify-center px-4">
        <div class="max-w-2xl w-full text-center">
            <!-- Animation container -->
            <div class="relative mb-8">
                <!-- Cercles de décoration avec animation -->
                <div class="absolute -top-4 -left-4 w-24 h-24 bg-yellow-100 rounded-full opacity-60 animate-pulse"></div>
                <div class="absolute -bottom-4 -right-4 w-16 h-16 bg-yellow-200 rounded-full opacity-40 animate-bounce"></div>
                
                <!-- Icône principale -->
                <div class="relative bg-white rounded-full p-8 shadow-lg border border-yellow-100 mx-auto w-40 h-40 flex items-center justify-center">
                    <div class="text-8xl font-bold text-yellow-500 animate-pulse">403</div>
                </div>
            </div>

            <!-- Titre principal -->
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                Accès interdit
            </h1>
            
            <!-- Message d'erreur -->
            <p class="text-lg md:text-xl text-gray-600 mb-8 leading-relaxed">
                Vous n'avez pas l'autorisation d'accéder à cette page.
                <br class="hidden md:block">
                Vérifiez vos permissions ou connectez-vous avec un autre compte.
            </p>
            
            <!-- Actions principales -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                <Link 
                    href="/"
                    class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-primary to-orange-400 text-white px-8 py-3 rounded-lg font-medium shadow-lg hover:from-primary hover:to-primary transition-all duration-200 transform hover:scale-105"
                >
                    <Home class="w-5 h-5" />
                    Retour à l'accueil
                </Link>
                
                <Link 
                    :href="route('connexion')"
                    class="inline-flex items-center justify-center gap-2 bg-white text-gray-700 px-8 py-3 rounded-lg font-medium shadow-md border border-gray-200 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200"
                >
                    <LogIn class="w-5 h-5" />
                    Se connecter
                </Link>
            </div>

            <!-- Footer avec infos de contact -->
            <div class="mt-12 pt-8 border-t border-gray-200">
                <p class="text-gray-500 text-sm mb-4">
                    Besoin d'aide avec vos permissions ?
                </p>
                <a 
                    href="mailto:hello@trouvetababysitter.fr" 
                    class="inline-flex items-center gap-2 text-primary hover:text-orange-700 transition-colors duration-200"
                >
                    <Mail class="w-4 h-4" />
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