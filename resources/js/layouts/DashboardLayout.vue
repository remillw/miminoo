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
const shouldHideHeaderFooter = computed(() => isMobileAppDetected.value || isMobileApp());

// Force la détection mobile à être réactive
const isMobileAppDetected = ref(false);

onMounted(() => {
    // Vérifier la détection mobile au montage
    isMobileAppDetected.value = isMobileApp();

    // Réécouter les événements Expo
    const handleExpoLoad = () => {
        console.log("[DashboardLayout] Expo app détectée, mise à jour de l'interface");
        isMobileAppDetected.value = true;
    };

    window.addEventListener('expo-app-loaded', handleExpoLoad);

    // Vérification périodique (fallback)
    const checkInterval = setInterval(() => {
        const wasDetected = isMobileAppDetected.value;
        isMobileAppDetected.value = isMobileApp();
        if (!wasDetected && isMobileAppDetected.value) {
            console.log('[DashboardLayout] App mobile détectée via vérification périodique');
            clearInterval(checkInterval);
        }
    }, 500);

    // Nettoyer après 5 secondes
    setTimeout(() => {
        clearInterval(checkInterval);
        window.removeEventListener('expo-app-loaded', handleExpoLoad);
    }, 5000);

    // Pas de loader automatique sur l'app mobile
    // Le loader ne s'affiche que si explicitement demandé via props.showLoader
    if (props.showLoader) {
        isLoading.value = true;
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
            <!-- Sidebar seulement si utilisateur connecté -->
            <UnifiedSidebar v-if="user" :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole" :requestedMode="props.currentMode" :unreadMessagesCount="$page?.props?.unreadMessagesCount || 0" />

            <!-- Main content optimisé pour l'app mobile -->
            <main class="flex-1 pb-20 lg:pb-0">
                <div :class="[shouldHideHeaderFooter ? 'mobile-app-container pt-8' : 'px-4 py-8 sm:px-6 lg:px-8']">
                    <div :class="shouldHideHeaderFooter ? '' : 'mx-auto max-w-7xl'">
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

/* Container spécifique pour l'app mobile */
.mobile-app-container {
    width: 100vw;
    max-width: 100vw;
    padding-left: 8px;
    padding-right: 8px;
    margin: 0;
    box-sizing: border-box;
    overflow-x: hidden;
}

/* Masquer le scrollbar sur mobile pour une expérience plus app-like */
@media (max-width: 1023px) {
    ::-webkit-scrollbar {
        display: none;
    }

    /* Éviter le zoom sur les inputs sur iOS */
    input,
    select,
    textarea {
        font-size: 16px;
    }

    /* S'assurer que tout reste dans les bounds */
    .mobile-app-container * {
        max-width: 100%;
        box-sizing: border-box;
    }
}
</style>
