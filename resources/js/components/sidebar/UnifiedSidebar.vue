<script setup lang="ts">
import { useDeviceToken } from '@/composables/useDeviceToken';
import { useUserMode } from '@/composables/useUserMode';
import { Link, usePage } from '@inertiajs/vue3';
import { Baby, Briefcase, Calendar, CreditCard, Home, LogOut, MessageCircle, PlusCircle, Settings, User, Users, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Props {
    hasParentRole: boolean;
    hasBabysitterRole: boolean;
    requestedMode?: 'parent' | 'babysitter';
}

const props = defineProps<Props>();
const { currentMode, initializeMode, setMode } = useUserMode();
const { isMobileApp } = useDeviceToken();

// État pour le menu mobile étendu
const showMobileMenu = ref(false);

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
    window.location.href = `/tableau-de-bord?mode=${mode}`;
};

// Navigation avec routes correctes
const links = computed(() => {
    const parentLinks = [
        { name: 'Tableau de bord', href: '/tableau-de-bord', icon: Home },
        { name: 'Créer une annonce', href: '/creer-une-annonce', icon: PlusCircle },
        { name: 'Mes annonces', href: '/mes-annonces-et-reservations', icon: Calendar },
        { name: 'Messagerie', href: '/messagerie', icon: MessageCircle },
        { name: 'Mon profil', href: '/profil', icon: User },
        { name: 'Paiements', href: '/paiements', icon: CreditCard },
        { name: 'Paramètres', href: '/parametres', icon: Settings },
    ];

    const babysitterLinks = [
        { name: 'Tableau de bord', href: '/tableau-de-bord', icon: Home },
        { name: 'Annonces', href: '/annonces', icon: Briefcase },
        { name: 'Mes gardes', href: '/babysitting', icon: Calendar },
        { name: 'Messagerie', href: '/messagerie', icon: MessageCircle },
        { name: 'Mon profil', href: '/profil', icon: User },
        { name: 'Paiements', href: '/babysitter/paiements', icon: CreditCard },
        { name: 'Paramètres', href: '/parametres', icon: Settings },
    ];

    return currentMode.value === 'parent' ? parentLinks : babysitterLinks;
});

// Menu mobile simplifié - seulement les liens essentiels
const mobileMainLinks = computed(() => {
    const parentMobileLinks = [
        { name: 'Tableau de bord', href: '/tableau-de-bord', icon: Home },
        { name: 'Réservations', href: '/mes-annonces-et-reservations', icon: Calendar },
        { name: 'Messages', href: '/messagerie', icon: MessageCircle },
    ];

    const babysitterMobileLinks = [
        { name: 'Tableau de bord', href: '/tableau-de-bord', icon: Home },
        { name: 'Annonces', href: '/annonces', icon: Briefcase },
        { name: 'Messages', href: '/messagerie', icon: MessageCircle },
    ];

    return currentMode.value === 'parent' ? parentMobileLinks : babysitterMobileLinks;
});

// Liens secondaires pour le menu "Plus" mobile
const mobileSecondaryLinks = computed(() => {
    const allLinks = links.value;
    const mainLinks = mobileMainLinks.value;

    // Retourner tous les liens qui ne sont pas dans les liens principaux mobiles
    return allLinks.filter((link) => !mainLinks.some((mainLink) => mainLink.href === link.href));
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
                    href="/deconnexion"
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
                <!-- Les 3 liens principaux simplifiés -->
                <Link
                    v-for="link in mobileMainLinks"
                    :key="link.name"
                    :href="link.href"
                    :class="['flex flex-col items-center gap-1 p-2 transition-colors', isActive(link.href) ? 'text-primary' : 'text-gray-500']"
                >
                    <component :is="link.icon" class="h-5 w-5" />
                    <span class="text-xs font-medium">{{ link.name.split(' ')[0] }}</span>
                </Link>

                <!-- Bouton contextuel: + pour parent, Profil pour babysitter -->
                <Link
                    v-if="currentMode === 'parent'"
                    href="/creer-une-annonce"
                    class="hover:text-primary flex flex-col items-center gap-1 p-2 text-gray-500 transition-colors"
                >
                    <PlusCircle class="h-5 w-5" />
                    <span class="text-xs font-medium">Créer</span>
                </Link>
                <Link v-else href="/babysitting" class="hover:text-primary flex flex-col items-center gap-1 p-2 text-gray-500 transition-colors">
                    <Calendar class="h-5 w-5" />
                    <span class="text-xs font-medium">Mes gardes</span>
                </Link>

                <!-- Lien direct vers la page menu -->
                <Link
                    href="/profil/menu"
                    :class="[
                        'flex flex-col items-center gap-1 p-2 transition-colors',
                        isActive('/profil/menu') ? 'text-primary' : 'hover:text-primary text-gray-500',
                    ]"
                >
                    <User class="h-5 w-5" />
                    <span class="text-xs font-medium">Menu</span>
                </Link>
            </div>
        </div>

        <!-- Menu mobile étendu en overlay (Page Réglages) -->
        <div v-if="showMobileMenu" class="bg-opacity-50 fixed inset-0 z-50 bg-black" @click="showMobileMenu = false">
            <div class="absolute right-0 bottom-0 left-0 max-h-96 overflow-y-auto rounded-t-xl bg-white shadow-lg" @click.stop>
                <div class="p-4">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Réglages</h3>
                        <button @click="showMobileMenu = false" class="text-gray-400 hover:text-gray-600">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <nav class="space-y-1">
                        <!-- Section Compte -->
                        <div class="mb-3">
                            <h4 class="mb-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">Compte</h4>
                            <Link
                                v-for="link in mobileSecondaryLinks.filter((l) => ['Mon profil', 'Mes gardes', 'Mes annonces'].includes(l.name))"
                                :key="link.name"
                                :href="link.href"
                                @click="showMobileMenu = false"
                                :class="[
                                    isActive(link.href) ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50',
                                    'group flex items-center rounded-lg px-3 py-3 text-sm font-medium transition-colors',
                                ]"
                            >
                                <component :is="link.icon" :class="[isActive(link.href) ? 'text-white' : 'text-gray-400', 'mr-3 h-5 w-5']" />
                                {{ link.name }}
                            </Link>
                        </div>

                        <!-- Section Paiements -->
                        <div class="mb-3">
                            <h4 class="mb-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">Paiements</h4>
                            <Link
                                v-for="link in mobileSecondaryLinks.filter((l) => l.name.includes('Paiements'))"
                                :key="link.name"
                                :href="link.href"
                                @click="showMobileMenu = false"
                                :class="[
                                    isActive(link.href) ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50',
                                    'group flex items-center rounded-lg px-3 py-3 text-sm font-medium transition-colors',
                                ]"
                            >
                                <component :is="link.icon" :class="[isActive(link.href) ? 'text-white' : 'text-gray-400', 'mr-3 h-5 w-5']" />
                                {{ link.name }}
                            </Link>
                        </div>

                        <!-- Section Support & Légal (liens factices pour l'instant) -->
                        <div class="mb-3">
                            <h4 class="mb-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">Support & Légal</h4>
                            <button
                                class="group flex w-full items-center rounded-lg px-3 py-3 text-left text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
                            >
                                <Settings class="mr-3 h-5 w-5 text-gray-400" />
                                Mentions légales
                            </button>
                            <button
                                class="group flex w-full items-center rounded-lg px-3 py-3 text-left text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
                            >
                                <Settings class="mr-3 h-5 w-5 text-gray-400" />
                                Politique de confidentialité
                            </button>
                        </div>

                        <!-- Séparateur -->
                        <div class="my-3 border-t border-gray-200"></div>

                        <!-- Lien de déconnexion -->
                        <Link
                            href="/deconnexion"
                            method="post"
                            @click="showMobileMenu = false"
                            class="group flex items-center rounded-lg px-3 py-3 text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
                        >
                            <LogOut class="mr-3 h-5 w-5 text-red-500" />
                            Déconnexion
                        </Link>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>
