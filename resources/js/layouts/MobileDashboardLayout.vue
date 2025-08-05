<script setup lang="ts">
import UnifiedSidebar from '@/components/sidebar/UnifiedSidebar.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

interface Props {
    role?: string;
    currentMode?: 'parent' | 'babysitter';
    hasParentRole?: boolean;
    hasBabysitterRole?: boolean;
}

const props = defineProps<Props>();
const page = usePage();

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

</script>

<template>
    <!-- Layout principal -->
    <div class="bg-secondary flex min-h-screen flex-col">
        <div class="flex flex-1">
            <UnifiedSidebar 
                :hasParentRole="hasParentRole" 
                :hasBabysitterRole="hasBabysitterRole" 
                :requestedMode="props.currentMode" 
            />

            <!-- Main content optimisé pour mobile -->
            <main class="flex-1 pb-20 lg:pb-0 overflow-x-hidden">
                <!-- Container avec padding mobile optimisé -->
                <div class="px-3 py-2 sm:px-4 sm:py-4 lg:px-8 lg:py-6">
                    <div class="mx-auto max-w-7xl">
                        <slot />
                    </div>
                </div>
            </main>
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
    
    /* Smooth scrolling pour une expérience fluide */
    * {
        scroll-behavior: smooth;
    }
    
    /* Optimisations pour les transitions */
    button, a {
        transition: all 0.2s ease;
        -webkit-tap-highlight-color: transparent;
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

/* GPU acceleration pour les animations */
@media (max-width: 1023px) {
    main {
        transform: translateZ(0);
        will-change: transform;
    }
}
</style>