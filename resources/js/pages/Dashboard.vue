<script setup lang="ts">
import BabysitterDashboardContent from '@/components/dashboard/babysitter/BabysitterDashboardContent.vue';
import ParentDashboardContent from '@/components/dashboard/parent/ParentDashboardContent.vue';
import DashboardFooter from '@/components/dashboard/shared/DashboardFooter.vue';
import LandingHeader from '@/components/LandingHeader.vue';
import UnifiedSidebar from '@/components/sidebar/UnifiedSidebar.vue';
import { useUserMode } from '@/composables/useUserMode';
import { Head } from '@inertiajs/vue3';
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

// Initialiser le mode au montage du composant
onMounted(() => {
    console.log('🔄 Dashboard mounted with roles:', {
        hasParentRole: props.hasParentRole,
        hasBabysitterRole: props.hasBabysitterRole,
        requestedMode: props.requestedMode,
    });
    initializeMode(props.hasParentRole, props.hasBabysitterRole, props.requestedMode);
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
                    <component :is="currentContent" :currentMode="currentMode" v-bind="$props" />
                </div>
            </main>
        </div>

        <DashboardFooter />
    </div>
</template>
