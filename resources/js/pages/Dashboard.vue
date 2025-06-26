<script setup lang="ts">
import BabysitterDashboardContent from '@/components/dashboard/babysitter/BabysitterDashboardContent.vue';
import BabysitterSidebar from '@/components/dashboard/babysitter/BabysitterSidebar.vue';
import ParentDashboardContent from '@/components/dashboard/parent/ParentDashboardContent.vue';
import ParentSidebar from '@/components/dashboard/parent/ParentSidebar.vue';
import DashboardFooter from '@/components/dashboard/shared/DashboardFooter.vue';
import LandingHeader from '@/components/LandingHeader.vue';
import { Button } from '@/components/ui/button';
import { useUserMode } from '@/composables/useUserMode';
import { Head, router } from '@inertiajs/vue3';
import { Baby, Users } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import { route } from 'ziggy-js';

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
const { currentMode, initializeMode, setMode } = useUserMode();

// Initialiser le mode au montage du composant
onMounted(() => {
    initializeMode(props.hasParentRole, props.hasBabysitterRole, props.requestedMode);
});

// Computed pour vérifier si l'utilisateur a plusieurs rôles
const hasMultipleRoles = computed(() => {
    return props.hasParentRole && props.hasBabysitterRole;
});

// Fonction pour changer de mode
const switchMode = (mode: 'parent' | 'babysitter') => {
    if (mode === currentMode.value) return;

    // Mettre à jour le localStorage
    setMode(mode);

    // Rediriger avec le nouveau mode
    router.get(
        route('dashboard', { mode }),
        {},
        {
            preserveState: false,
            preserveScroll: true,
        },
    );
};

// Composants dynamiques selon le mode
const currentSidebar = computed(() => {
    return currentMode.value === 'parent' ? ParentSidebar : BabysitterSidebar;
});

const currentContent = computed(() => {
    return currentMode.value === 'parent' ? ParentDashboardContent : BabysitterDashboardContent;
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-[#fcf8f6]">
        <Head title="Dashboard" />

        <LandingHeader />

        <!-- Switch de rôle si l'utilisateur a plusieurs rôles -->
        <div v-if="hasMultipleRoles" class="border-b bg-white px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-gray-700">Mode actuel :</span>
                    <div class="flex rounded-lg border bg-gray-50 p-1">
                        <Button
                            @click="switchMode('parent')"
                            :variant="currentMode === 'parent' ? 'default' : 'ghost'"
                            size="sm"
                            class="flex items-center gap-2"
                            :class="currentMode === 'parent' ? 'bg-primary hover:bg-primary text-white' : 'text-gray-600 hover:bg-gray-100'"
                        >
                            <Users class="h-4 w-4" />
                            Parent
                        </Button>
                        <Button
                            @click="switchMode('babysitter')"
                            :variant="currentMode === 'babysitter' ? 'default' : 'ghost'"
                            size="sm"
                            class="flex items-center gap-2"
                            :class="currentMode === 'babysitter' ? 'bg-primary hover:bg-primary text-white' : 'text-gray-600 hover:bg-gray-100'"
                        >
                            <Baby class="h-4 w-4" />
                            Babysitter
                        </Button>
                    </div>
                </div>

                <div class="text-sm text-gray-500">
                    <span class="capitalize">{{ currentMode }}</span> -
                    <span class="font-medium">{{ props.user.firstname }} {{ props.user.lastname }}</span>
                </div>
            </div>
        </div>

        <div class="flex flex-1">
            <!-- Sidebar dynamique -->
            <component :is="currentSidebar" />

            <!-- Contenu principal dynamique -->
            <main class="flex-1 p-6">
                <component :is="currentContent" :currentMode="currentMode" v-bind="$props" />
            </main>
        </div>

        <DashboardFooter />
    </div>
</template>
