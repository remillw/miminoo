<template>
    <div class="min-h-screen bg-gradient-to-br from-secondary via-white to-secondary flex items-center justify-center px-4">
        <div class="max-w-2xl w-full text-center">
            <!-- Animation container -->
            <div class="relative mb-8">
                <!-- Cercles de décoration avec animation -->
                <div class="absolute -top-4 -left-4 w-24 h-24 bg-orange-100 rounded-full opacity-60 animate-pulse"></div>
                <div class="absolute -bottom-4 -right-4 w-16 h-16 bg-orange-200 rounded-full opacity-40 animate-bounce"></div>
                
                <!-- Icône principale -->
                <div class="relative bg-white rounded-full p-8 shadow-lg border border-orange-100 mx-auto w-40 h-40 flex items-center justify-center">
                    <div class="text-8xl font-bold text-primary animate-pulse">404</div>
                </div>
            </div>

            <!-- Titre principal -->
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                Oops ! Page introuvable
            </h1>
            
            <!-- Sous-titre avec un peu d'humour -->
            <p class="text-lg md:text-xl text-gray-600 mb-8 leading-relaxed">
                Il semblerait que notre babysitter ait perdu cette page quelque part... 
                <br class="hidden md:block">
                Ne vous inquiétez pas, on va vous aider à retrouver votre chemin !
            </p>
            
            <!-- Suggestions d'actions -->
            <div class="space-y-4 mb-8">
                <div class="grid md:grid-cols-2 gap-4">
                    <!-- Card suggestion 1 -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-orange-100 hover:shadow-md transition-all duration-200 hover:border-orange-200">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                <Home class="w-4 h-4 text-primary" />
                            </div>
                            <h3 class="font-semibold text-gray-900">Retour à l'accueil</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-3">Découvrez toutes nos annonces de babysitting</p>
                        <Link 
                            :href="route('home')" 
                            class="text-primary hover:text-orange-700 text-sm font-medium transition-colors duration-200"
                        >
                            Aller à l'accueil →
                        </Link>
                    </div>

                    <!-- Card suggestion 2 -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-orange-100 hover:shadow-md transition-all duration-200 hover:border-orange-200">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                                <Search class="w-4 h-4 text-primary" />
                            </div>
                            <h3 class="font-semibold text-gray-900">Voir les annonces</h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-3">Trouvez la mission parfaite près de chez vous</p>
                        <Link 
                            :href="route('announcements.index')" 
                            class="text-primary hover:text-orange-700 text-sm font-medium transition-colors duration-200"
                        >
                            Parcourir les annonces →
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Actions principales -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <Link 
                    :href="route('home')"
                    class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-primary to-orange-400 text-white px-8 py-3 rounded-lg font-medium shadow-lg hover:from-primary hover:to-primary transition-all duration-200 transform hover:scale-105"
                >
                    <Home class="w-5 h-5" />
                    Retour à l'accueil
                </Link>
                
                <button 
                    @click="goBack"
                    class="inline-flex items-center justify-center gap-2 bg-white text-gray-700 px-8 py-3 rounded-lg font-medium shadow-md border border-gray-200 hover:bg-gray-50 hover:border-gray-300 transition-all duration-200"
                >
                    <ArrowLeft class="w-5 h-5" />
                    Page précédente
                </button>
            </div>

            <!-- Footer avec infos de contact ou aide -->
            <div class="mt-12 pt-8 border-t border-gray-200">
                <p class="text-gray-500 text-sm mb-4">
                    Vous pensez qu'il s'agit d'une erreur ou vous avez besoin d'aide ?
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center text-sm">
                    <a 
                        href="mailto:hello@trouvetababysitter.fr" 
                        class="flex items-center gap-2 text-primary hover:text-orange-700 transition-colors duration-200"
                    >
                        <Mail class="w-4 h-4" />
                        hello@trouvetababysitter.fr
                    </a>
                    <span class="hidden sm:block text-gray-300">•</span>
                    <Link 
                        href="/comment-ca-marche" 
                        class="flex items-center gap-2 text-primary hover:text-orange-700 transition-colors duration-200"
                    >
                        <HelpCircle class="w-4 h-4" />
                        Comment ça marche ?
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Home, Search, Mail, HelpCircle } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = route('home');
    }
};
</script>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}
</style> 