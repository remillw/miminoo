<script setup lang="ts">
import MobileLoader from '@/components/MobileLoader.vue';
import { useDeviceToken } from '@/composables/useDeviceToken';
import { onMounted, ref } from 'vue';

interface Props {
    showLoader?: boolean;
}

const props = defineProps<Props>();

// Utiliser votre système de détection mobile existant
const { isMobileApp } = useDeviceToken();

// État du loader
const isLoading = ref(props.showLoader ?? false);

onMounted(() => {
    // Si on est dans l'app mobile, afficher le loader brièvement
    if (isMobileApp() && !props.showLoader) {
        isLoading.value = true;
        setTimeout(() => {
            isLoading.value = false;
        }, 2000);
    }
});

const handleLoaderComplete = () => {
    isLoading.value = false;
};
</script>

<template>
    <!-- Loader mobile pour l'app native -->
    <MobileLoader v-if="isLoading" @loaded="handleLoaderComplete" />
    
    <!-- Layout d'authentification mobile -->
    <div v-else class="min-h-screen bg-gradient-to-br from-primary/5 to-orange-50">
        <!-- Container principal centré -->
        <div class="flex min-h-screen items-center justify-center px-4 py-8">
            <div class="w-full max-w-md space-y-8">
                <!-- Logo et titre -->
                <div class="text-center">
                    <img
                        src="/storage/trouve-ta-babysitter-logo.svg"
                        alt="Trouve ta Babysitter"
                        class="mx-auto h-16 w-auto"
                    />
                    <h1 class="mt-6 text-2xl font-bold text-gray-900">
                        Trouve ta Babysitter
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">
                        La solution de garde d'enfants de confiance
                    </p>
                </div>

                <!-- Contenu principal (formulaire de connexion) -->
                <div class="bg-white rounded-2xl shadow-xl p-6 space-y-6">
                    <slot />
                </div>

                <!-- Footer mobile -->
                <div class="text-center text-xs text-gray-500 space-y-2">
                    <p>&copy; 2024 Trouve ta Babysitter</p>
                    <div class="flex justify-center space-x-4">
                        <button class="hover:text-primary transition-colors">CGU</button>
                        <button class="hover:text-primary transition-colors">Confidentialité</button>
                        <button class="hover:text-primary transition-colors">Aide</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Styles spécifiques pour l'expérience mobile app-like */
@supports (-webkit-touch-callout: none) {
    /* Styles iOS spécifiques */
    .min-h-screen {
        /* Éviter le bounce scroll sur iOS */
        overscroll-behavior: none;
        /* Hauteur 100vh pour une expérience plein écran */
        min-height: 100vh;
        min-height: -webkit-fill-available;
    }
}

/* Masquer le scrollbar sur mobile pour une expérience plus app-like */
@media (max-width: 1023px) {
    ::-webkit-scrollbar {
        display: none;
    }
    
    /* Éviter le zoom sur les inputs sur iOS */
    input, select, textarea {
        font-size: 16px !important;
    }
}
</style>