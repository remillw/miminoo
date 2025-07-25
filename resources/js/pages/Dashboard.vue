<script setup lang="ts">
import BabysitterDashboardContent from '@/components/dashboard/babysitter/BabysitterDashboardContent.vue';
import ParentDashboardContent from '@/components/dashboard/parent/ParentDashboardContent.vue';
import Footer from '@/components/Footer.vue';
import LandingHeader from '@/components/LandingHeader.vue';
import UnifiedSidebar from '@/components/sidebar/UnifiedSidebar.vue';
import { useUserMode } from '@/composables/useUserMode';
import { usePushNotifications } from '@/composables/usePushNotifications';
import { useCapacitor } from '@/composables/useCapacitor';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

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
const { initializePushNotifications, testTokenSaving } = usePushNotifications();
const { isNative } = useCapacitor();
const page = usePage();

// Initialiser le mode au montage du composant
onMounted(async () => {
    console.log('🔄 Dashboard mounted with roles:', {
        hasParentRole: props.hasParentRole,
        hasBabysitterRole: props.hasBabysitterRole,
        requestedMode: props.requestedMode,
        isNative: isNative.value,
    });
    initializeMode(props.hasParentRole, props.hasBabysitterRole, props.requestedMode);
    
    // Vérifier le flag de déclenchement des notifications push
    const triggerDeviceTokenRegistration = (page.props as any).triggerDeviceTokenRegistration;
    
    console.log('🔍 Checking push notification triggers:', {
        triggerDeviceTokenRegistration,
        isNative: isNative.value,
        hasUser: !!props.user,
    });
    
    // Initialiser les notifications push si :
    // 1. L'utilisateur est connecté
    // 2. ET (on est sur mobile natif OU le flag de déclenchement est activé)
    if (props.user && (isNative.value || triggerDeviceTokenRegistration)) {
        console.log('🔔 Initialisation des notifications push pour l\'utilisateur connecté');
        await initializePushNotifications();
        
        // Nettoyer le flag après utilisation
        if (triggerDeviceTokenRegistration) {
            try {
                await fetch('/clear-device-token-flag', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': (page.props as any).csrf_token || '',
                    },
                });
                console.log('✅ Flag de déclenchement nettoyé');
            } catch (error) {
                console.error('❌ Erreur lors du nettoyage du flag:', error);
            }
        }
    }
});

// Contenu dynamique selon le mode
const currentContent = computed(() => {
    return currentMode.value === 'parent' ? ParentDashboardContent : BabysitterDashboardContent;
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gradient-to-br from-gray-50 to-gray-100">
        <Head title="Dashboard" />

        <LandingHeader />

        <div class="flex flex-1">
            <!-- Sidebar unifiée -->
            <UnifiedSidebar :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole" :requestedMode="requestedMode" />

            <!-- Contenu principal dynamique -->
            <main class="flex-1 p-6 pb-20 lg:pb-6">
                <div class="mx-auto max-w-7xl">
                    <!-- DEBUG: Bouton de test pour le token (temporaire) -->
                    <div v-if="isNative" class="mb-4 p-4 bg-yellow-100 border border-yellow-300 rounded">
                        <h3 class="font-bold text-yellow-800">🧪 Debug Push Notifications</h3>
                        <button 
                            @click="testTokenSaving"
                            class="mt-2 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                        >
                            Test Token Saving
                        </button>
                    </div>
                    
                    <component :is="currentContent" :currentMode="currentMode" v-bind="$props" />
                </div>
            </main>
        </div>

        <Footer />
    </div>
</template>
