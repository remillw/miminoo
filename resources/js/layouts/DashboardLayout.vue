<script setup lang="ts">
import Footer from '@/components/Footer.vue';
import LandingHeader from '@/components/LandingHeader.vue';
import MobileLoader from '@/components/MobileLoader.vue';
import UnifiedSidebar from '@/components/sidebar/UnifiedSidebar.vue';
import { useDeviceToken } from '@/composables/useDeviceToken';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

interface Props {
    role?: string;
    currentMode?: 'parent' | 'babysitter';
    hasParentRole?: boolean;
    hasBabysitterRole?: boolean;
    showLoader?: boolean;
}

const props = defineProps<Props>();
const page = usePage();

// Utiliser votre système de détection mobile existant
const { isMobileApp } = useDeviceToken();

// État du loader
const isLoading = ref(props.showLoader ?? false);

// Récupérer les informations utilisateur depuis les props globales
const user = computed(() => (page.props.auth as any)?.user);
const userRoles = computed(() => user.value?.roles?.map((role: any) => role.name) || []);

const hasParentRole = computed(() => props.hasParentRole ?? userRoles.value.includes('parent'));
const hasBabysitterRole = computed(() => props.hasBabysitterRole ?? userRoles.value.includes('babysitter'));

// Computed pour savoir si on doit cacher header/footer
const shouldHideHeaderFooter = computed(() => isMobileApp());

// Vérifier si c'est le premier chargement
const isFirstLoad = ref(false);

onMounted(() => {
    // Vérifier si c'est le premier chargement de l'app
    const hasLoadedBefore = sessionStorage.getItem('appHasLoaded');
    
    // Si on est dans l'app mobile et c'est le premier chargement
    if (isMobileApp() && !hasLoadedBefore && !props.showLoader) {
        isLoading.value = true;
        isFirstLoad.value = true;
        
        // Marquer que l'app a déjà été chargée
        sessionStorage.setItem('appHasLoaded', 'true');
        
        setTimeout(() => {
            isLoading.value = false;
        }, 1500);
    }
});

const handleLoaderComplete = () => {
    isLoading.value = false;
};
</script>

<template>
    <!-- Loader mobile pour l'app native -->
    <MobileLoader v-if="isLoading" @loaded="handleLoaderComplete" />
    
    <!-- Layout principal -->
    <div v-else class="bg-secondary flex min-h-screen flex-col">
        <!-- Header seulement si pas dans l'app mobile -->
        <LandingHeader v-if="!shouldHideHeaderFooter" />

        <div class="flex flex-1">
            <UnifiedSidebar 
                :hasParentRole="hasParentRole" 
                :hasBabysitterRole="hasBabysitterRole" 
                :requestedMode="props.currentMode" 
            />

            <!-- Main content optimisé pour l'app mobile -->
            <main class="flex-1 pb-20 lg:pb-0">
                <div :class="[
                    shouldHideHeaderFooter ? 'px-3 py-4' : 'px-4 py-6 sm:px-6 lg:px-8'
                ]">
                    <div class="mx-auto max-w-7xl">
                        <slot />
                    </div>
                </div>
            </main>
        </div>

        <!-- Footer seulement si pas dans l'app mobile -->
        <Footer v-if="!shouldHideHeaderFooter" />
    </div>
</template>

<style scoped>
/* Styles spécifiques pour l'expérience mobile app-like */
@supports (-webkit-touch-callout: none) {
    /* Styles iOS spécifiques */
    .bg-secondary {
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
        font-size: 16px;
    }
}
</style>
