<script setup lang="ts">
import BabysitterDashboardContent from '@/components/dashboard/babysitter/BabysitterDashboardContent.vue';
import ParentDashboardContent from '@/components/dashboard/parent/ParentDashboardContent.vue';
import Footer from '@/components/Footer.vue';
import LandingHeader from '@/components/LandingHeader.vue';
import MobileAppDebug from '@/components/MobileAppDebug.vue';
import UnifiedSidebar from '@/components/sidebar/UnifiedSidebar.vue';
import { useUserMode } from '@/composables/useUserMode';
import { useDeviceToken } from '@/composables/useDeviceToken';
import { useToast } from '@/composables/useToast';

import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    avatar?: string;
    status: string;
}

interface Props {
    user: User;
    userRoles: string[];
    hasParentRole: boolean;
    hasBabysitterRole: boolean;
    requestedMode?: 'parent' | 'babysitter';
    parentProfile?: any;
    babysitterProfile?: any;
    showVerificationToast?: boolean;
    unreadNotifications?: any[];
    unreadNotificationsCount?: number;
    // Données du dashboard
    stats?: any;
    availability?: any;
    recentAds?: any[];
    notifications?: any[];
    nextReservation?: any;
    recentReviews?: any[];
    completedReservations?: any[];
}

const props = defineProps<Props>();
const { currentMode, initializeMode } = useUserMode();
const { showVerificationRequired } = useToast();
const { isMobileApp } = useDeviceToken();

// Force la détection mobile à être réactive
const isMobileAppDetected = ref(false);

// Computed pour savoir si on doit cacher header/footer
const shouldHideHeaderFooter = computed(() => isMobileAppDetected.value || isMobileApp());
const page = usePage();

// Initialiser le mode au montage du composant
onMounted(() => {
    initializeMode(props.hasParentRole, props.hasBabysitterRole, props.requestedMode);
    
    // Vérifier si on doit afficher le toast de vérification
    if (props.showVerificationToast) {
        console.log('🔒 Dashboard: Utilisateur redirigé depuis paiements - Affichage toast de vérification');
        showVerificationRequired();
    }
    
    // Détection mobile
    isMobileAppDetected.value = isMobileApp();
    
    // Réécouter les événements Expo
    const handleExpoLoad = () => {
        console.log('[Dashboard] Expo app détectée, mise à jour de l\'interface');
        isMobileAppDetected.value = true;
    };
    
    window.addEventListener('expo-app-loaded', handleExpoLoad);
    
    // Vérification périodique (fallback)
    const checkInterval = setInterval(() => {
        const wasDetected = isMobileAppDetected.value;
        isMobileAppDetected.value = isMobileApp();
        if (!wasDetected && isMobileAppDetected.value) {
            console.log('[Dashboard] App mobile détectée via vérification périodique');
            clearInterval(checkInterval);
        }
    }, 500);
    
    // Nettoyer après 5 secondes
    setTimeout(() => {
        clearInterval(checkInterval);
        window.removeEventListener('expo-app-loaded', handleExpoLoad);
    }, 5000);
});

// Contenu dynamique selon le mode
const currentContent = computed(() => {
    return currentMode.value === 'parent' ? ParentDashboardContent : BabysitterDashboardContent;
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-secondary">
        <Head title="Dashboard" />

        <!-- Header seulement si pas dans l'app mobile -->
        <LandingHeader v-if="!shouldHideHeaderFooter" />

        <div class="flex flex-1">
            <!-- Sidebar unifiée -->
            <UnifiedSidebar :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole" :requestedMode="requestedMode" />

            <!-- Contenu principal dynamique -->
            <main class="flex-1 pb-20 lg:pb-6">
                <div :class="[
                    shouldHideHeaderFooter ? 'mobile-app-container' : 'p-6'
                ]">
                    <div :class="shouldHideHeaderFooter ? '' : 'mx-auto max-w-7xl'">
                        <component :is="currentContent" :currentMode="currentMode" v-bind="$props" />
                    </div>
                </div>
            </main>
        </div>

        <!-- Footer seulement si pas dans l'app mobile -->
        <Footer v-if="!shouldHideHeaderFooter" />
    </div>
    
    <!-- Debug pour l'app mobile -->
    <MobileAppDebug />
</template>

<style scoped>
/* Container spécifique pour l'app mobile */
.mobile-app-container {
    width: 100vw;
    max-width: 100vw;
    padding: 8px;
    margin: 0;
    box-sizing: border-box;
    overflow-x: hidden;
}

/* S'assurer que tout reste dans les bounds */
@media (max-width: 1023px) {
    .mobile-app-container * {
        max-width: 100%;
        box-sizing: border-box;
    }
}
</style>
