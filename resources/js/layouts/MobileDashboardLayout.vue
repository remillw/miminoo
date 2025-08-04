<script setup lang="ts">
import MobileLoader from '@/components/MobileLoader.vue';
import UnifiedSidebar from '@/components/sidebar/UnifiedSidebar.vue';
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

// État du loader
const isLoading = ref(props.showLoader ?? false);

// Récupérer les informations utilisateur depuis les props globales
const user = computed(() => (page.props.auth as any)?.user);
const userRoles = computed(() => user.value?.roles?.map((role: any) => role.name) || []);

const hasParentRole = computed(() => props.hasParentRole ?? userRoles.value.includes('parent'));
const hasBabysitterRole = computed(() => props.hasBabysitterRole ?? userRoles.value.includes('babysitter'));

// Détecter si on est sur mobile
const isMobile = ref(false);

onMounted(() => {
    // Détecter mobile avec window.innerWidth et user agent
    const checkMobile = () => {
        isMobile.value = window.innerWidth < 1024 || /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    };
    
    checkMobile();
    window.addEventListener('resize', checkMobile);
    
    return () => window.removeEventListener('resize', checkMobile);
});

const handleLoaderComplete = () => {
    isLoading.value = false;
};
</script>

<template>
    <!-- Loader mobile -->
    <MobileLoader v-if="isLoading" @loaded="handleLoaderComplete" />
    
    <!-- Layout principal -->
    <div v-else class="bg-secondary flex min-h-screen flex-col">
        <!-- Header uniquement sur desktop -->
        <div v-if="!isMobile" class="hidden lg:block">
            <!-- On peut ajouter un header desktop minimal ici si nécessaire -->
        </div>

        <div class="flex flex-1">
            <UnifiedSidebar 
                :hasParentRole="hasParentRole" 
                :hasBabysitterRole="hasBabysitterRole" 
                :requestedMode="props.currentMode" 
            />

            <!-- Main content optimisé pour mobile -->
            <main class="flex-1 pb-20 lg:pb-0">
                <!-- Container avec padding mobile optimisé -->
                <div class="px-3 py-4 sm:px-4 sm:py-6 lg:px-8">
                    <div class="mx-auto max-w-7xl">
                        <slot />
                    </div>
                </div>
            </main>
        </div>

        <!-- Footer uniquement sur desktop -->
        <div v-if="!isMobile" class="hidden lg:block">
            <!-- Footer desktop minimal ou aucun -->
        </div>
    </div>
</template>

<style scoped>
/* Styles spécifiques pour l'expérience mobile app-like */
@media (max-width: 1023px) {
    /* Masquer le scrollbar sur mobile pour une expérience plus app-like */
    ::-webkit-scrollbar {
        display: none;
    }
    
    /* Éviter le zoom sur les inputs sur iOS */
    input, select, textarea {
        font-size: 16px;
    }
}

/* Styles pour l'expérience app native */
.app-container {
    /* Éviter le bounce scroll sur iOS */
    overscroll-behavior: none;
    /* Hauteur 100vh pour une expérience plein écran */
    min-height: 100vh;
    min-height: -webkit-fill-available;
}
</style>