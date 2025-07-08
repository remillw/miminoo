<script setup lang="ts">
import { useUserMode } from '@/composables/useUserMode';
import { Link, usePage } from '@inertiajs/vue3';
import { Baby, Briefcase, Calendar, CreditCard, Home, LogOut, MessageCircle, PlusCircle, Settings, User, Users } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import { route } from 'ziggy-js';

interface Props {
    hasParentRole: boolean;
    hasBabysitterRole: boolean;
    requestedMode?: 'parent' | 'babysitter';
}

const props = defineProps<Props>();
const { currentMode, initializeMode, setMode } = useUserMode();

// Initialiser le mode au montage
onMounted(() => {
    console.log('🔧 UnifiedSidebar - Props reçues:', {
        hasParentRole: props.hasParentRole,
        hasBabysitterRole: props.hasBabysitterRole,
        requestedMode: props.requestedMode,
    });

    // Debug: vérifier les props utilisateur depuis Inertia
    const page = usePage();
    const user = (page.props.auth as any)?.user;
    console.log('👤 UnifiedSidebar - User depuis Inertia:', user);
    console.log('🏷️ UnifiedSidebar - User roles:', user?.roles);

    initializeMode(props.hasParentRole, props.hasBabysitterRole, props.requestedMode);
});

// Récupération des rôles depuis Inertia si les props ne sont pas disponibles
const inertiaPage = usePage();
const user = computed(() => (inertiaPage.props.auth as any)?.user);
const userRoles = computed(() => user.value?.roles?.map((role: any) => role.name) || []);

// Rôles avec fallback sur Inertia
const actualHasParentRole = computed(() => props.hasParentRole || userRoles.value.includes('parent'));
const actualHasBabysitterRole = computed(() => props.hasBabysitterRole || userRoles.value.includes('babysitter'));

// Computed pour vérifier si l'utilisateur a plusieurs rôles
const hasMultipleRoles = computed(() => {
    return actualHasParentRole.value && actualHasBabysitterRole.value;
});

// Fonction pour changer de mode
const switchMode = (mode: 'parent' | 'babysitter') => {
    if (mode === currentMode.value) return;

    setMode(mode);

    // Redirection avec le helper route de Ziggy
    try {
        window.location.href = route('dashboard', { mode });
    } catch {
        // Fallback si Ziggy n'est pas disponible
        window.location.href = `/dashboard?mode=${mode}`;
    }
};

// Navigation avec routes correctes
const links = computed(() => {
    const parentLinks = [
        { name: 'Tableau de bord', href: route('dashboard', { mode: 'parent' }), icon: Home },
        { name: 'Créer une annonce', href: route('creer.une.annonce'), icon: PlusCircle },
        { name: 'Mes gardes', href: route('parent.announcements-reservations'), icon: Calendar },
        { name: 'Messagerie', href: route('messaging.index', { mode: 'parent' }), icon: MessageCircle },
        { name: 'Mon profil', href: route('profil', { mode: 'parent' }), icon: User },
        { name: 'Paiements', href: '/paiements', icon: CreditCard },
        { name: 'Paramètres', href: '/parametres', icon: Settings },
    ];

    const babysitterLinks = [
        { name: 'Tableau de bord', href: route('dashboard', { mode: 'babysitter' }), icon: Home },
        { name: 'Annonces', href: '/annonces', icon: Briefcase },
        { name: 'Mes gardes', href: '/babysitting', icon: Calendar },
        { name: 'Messagerie', href: route('messaging.index', { mode: 'babysitter' }), icon: MessageCircle },
        { name: 'Mon profil', href: route('profil', { mode: 'babysitter' }), icon: User },
        { name: 'Paiements', href: '/babysitter/paiements', icon: CreditCard },
        { name: 'Paramètres', href: '/parametres', icon: Settings },
    ];

    return currentMode.value === 'parent' ? parentLinks : babysitterLinks;
});

const page = usePage();
const currentPath = computed(() => page.url);

const isActive = (href: string) => {
    if (!href) return false;

    // Gestion spéciale pour le dashboard
    if (href.includes('/dashboard')) {
        return currentPath.value.includes('/dashboard') || currentPath.value === '/';
    }

    // Pour les autres routes
    const urlPath = new URL(href, window.location.origin).pathname;
    return currentPath.value.includes(urlPath);
};
</script>

<template>
    <!-- Desktop Sidebar -->
    <div class="hidden lg:flex lg:w-64 lg:flex-col">
        <div class="flex flex-1 flex-col border-r bg-white shadow-sm">
            <!-- Header -->
            <div class="border-b p-6">
                <div class="flex items-center gap-3">
                    <div class="bg-primary flex h-10 w-10 items-center justify-center rounded-lg">
                        <component :is="currentMode === 'parent' ? Users : Baby" class="h-5 w-5 text-white" />
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">{{ currentMode === 'parent' ? 'Parent' : 'Babysitter' }}</h3>
                        <p class="text-sm text-gray-600">Mode actuel</p>
                    </div>
                </div>

                <!-- Switch de rôle simple -->
                <div v-if="hasMultipleRoles" class="mt-4">
                    <div class="flex gap-2">
                        <button
                            v-if="actualHasParentRole"
                            @click="switchMode('parent')"
                            :class="[
                                'flex-1 rounded-lg border px-3 py-2 text-sm transition-colors',
                                currentMode === 'parent' ? 'border-primary bg-primary text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-50',
                            ]"
                        >
                            Parent
                        </button>
                        <button
                            v-if="actualHasBabysitterRole"
                            @click="switchMode('babysitter')"
                            :class="[
                                'flex-1 rounded-lg border px-3 py-2 text-sm transition-colors',
                                currentMode === 'babysitter'
                                    ? 'border-primary bg-primary text-white'
                                    : 'border-gray-300 text-gray-700 hover:bg-gray-50',
                            ]"
                        >
                            Babysitter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-1 p-4">
                <Link
                    v-for="link in links"
                    :key="link.name"
                    :href="link.href"
                    :class="[
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                        isActive(link.href) ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100',
                    ]"
                >
                    <component :is="link.icon" class="h-5 w-5" />
                    {{ link.name }}
                </Link>
            </nav>

            <!-- Footer -->
            <div class="border-t p-4">
                <Link
                    :href="route('deconnexion')"
                    method="post"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-red-50 hover:text-red-700"
                >
                    <LogOut class="h-5 w-5" />
                    Déconnexion
                </Link>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation -->
    <div class="fixed right-0 bottom-0 left-0 z-50 lg:hidden">
        <div class="border-t bg-white px-4 py-2 shadow-lg">
            <div class="flex items-center justify-around">
                <Link
                    v-for="link in links.slice(0, 4)"
                    :key="link.name"
                    :href="link.href"
                    :class="['flex flex-col items-center gap-1 p-2 transition-colors', isActive(link.href) ? 'text-primary' : 'text-gray-500']"
                >
                    <component :is="link.icon" class="h-5 w-5" />
                    <span class="text-xs font-medium">{{ link.name.split(' ')[0] }}</span>
                </Link>
            </div>
        </div>
    </div>
</template>
