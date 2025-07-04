<script setup lang="ts">
import { useUserMode } from '@/composables/useUserMode';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Baby,
    Briefcase,
    Calendar,
    ChevronDown,
    CreditCard,
    Crown,
    Home,
    LogOut,
    Menu,
    MessageCircle,
    PlusCircle,
    Settings,
    User,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { route } from 'ziggy-js';

interface Props {
    hasParentRole: boolean;
    hasBabysitterRole: boolean;
    requestedMode?: 'parent' | 'babysitter';
}

const props = defineProps<Props>();

const showFullMenu = ref(false);
const showModeDropdown = ref(false);
const { currentMode, initializeMode, setMode } = useUserMode();

// Initialiser le mode au montage
onMounted(() => {
    initializeMode(props.hasParentRole, props.hasBabysitterRole, props.requestedMode);
});

// Computed pour vérifier si l'utilisateur a plusieurs rôles
const hasMultipleRoles = computed(() => {
    return props.hasParentRole && props.hasBabysitterRole;
});

// Icônes et labels pour les rôles
const roleConfig = {
    parent: {
        icon: Users,
        label: 'Parent',
        color: 'from-blue-500 to-indigo-600',
        bgColor: 'bg-blue-50',
        textColor: 'text-blue-700',
    },
    babysitter: {
        icon: Baby,
        label: 'Babysitter',
        color: 'from-pink-500 to-rose-600',
        bgColor: 'bg-pink-50',
        textColor: 'text-pink-700',
    },
};

// Navigation selon le mode
const navigationLinks = computed(() => {
    const parentLinks = [
        { name: 'Tableau de bord', href: 'dashboard', icon: Home, mode: 'parent' },
        { name: 'Créer une annonce', href: 'announcements.create', icon: PlusCircle, mode: 'parent' },
        { name: 'Mes gardes', href: 'parent.announcements-and-reservations', icon: Calendar, mode: 'parent' },
        { name: 'Messagerie', href: 'messages.index', icon: MessageCircle, mode: 'parent' },
        { name: 'Mon profil', href: 'profil', icon: User, mode: 'parent' },
        { name: 'Paiements | Factures', href: 'payments.index', icon: CreditCard, mode: 'parent' },
        { name: 'Paramètres', href: 'settings', icon: Settings, mode: 'parent' },
    ];

    const babysitterLinks = [
        { name: 'Tableau de bord', href: 'dashboard', icon: Home, mode: 'babysitter' },
        { name: 'Rechercher des gardes', href: 'babysitting.index', icon: Briefcase, mode: 'babysitter' },
        { name: 'Messagerie', href: 'messages.index', icon: MessageCircle, mode: 'babysitter' },
        { name: 'Mon profil', href: 'profil', icon: User, mode: 'babysitter' },
        { name: 'Paiements', href: 'babysitter.payments', icon: CreditCard, mode: 'babysitter' },
        { name: 'Paramètres', href: 'settings', icon: Settings, mode: 'babysitter' },
    ];

    return currentMode.value === 'parent' ? parentLinks : babysitterLinks;
});

// Fonction pour changer de mode
const switchMode = (mode: 'parent' | 'babysitter') => {
    if (mode === currentMode.value) return;

    setMode(mode);
    showModeDropdown.value = false;

    // Redirection vers le dashboard avec le nouveau mode
    window.location.href = route('dashboard', { mode });
};

// Fonction pour gérer le clic mobile
const handleMobileMenuToggle = () => {
    showFullMenu.value = !showFullMenu.value;
};

// Fonction pour fermer le menu mobile lors du clic sur un lien
const handleLinkClick = () => {
    showFullMenu.value = false;
};

// Route helper - utilise directement l'import de ziggy-js

// Check if current route matches
const isCurrentRoute = (routeName: string, mode?: string) => {
    const page = usePage();
    const currentPath = page.url.split('?')[0];

    // Vérification simple basée sur le nom de route
    if (routeName === 'dashboard') {
        return currentPath.includes('/dashboard') || currentPath === '/';
    }
    if (routeName === 'profil') {
        return currentPath.includes('/profil');
    }
    if (routeName === 'messages.index') {
        return currentPath.includes('/messagerie');
    }
    if (routeName === 'settings') {
        return currentPath.includes('/parametres');
    }
    if (routeName === 'announcements.create') {
        return currentPath.includes('/creer-une-annonce');
    }
    if (routeName === 'babysitting.index') {
        return currentPath.includes('/babysitting');
    }
    if (routeName === 'payments.index' || routeName === 'babysitter.payments') {
        return currentPath.includes('/paiements');
    }
    if (routeName === 'parent.announcements-and-reservations') {
        return currentPath.includes('/mes-annonces-et-reservations');
    }

    return false;
};
</script>

<template>
    <!-- Desktop Sidebar -->
    <div class="hidden lg:flex lg:w-80 lg:flex-col">
        <div class="flex flex-1 flex-col border-r bg-white shadow-lg">
            <!-- Header avec switch de rôle moderne -->
            <div class="relative border-b bg-gradient-to-r from-gray-50 to-gray-100 p-6">
                <!-- Switch de rôle élégant -->
                <div v-if="hasMultipleRoles" class="relative">
                    <button
                        @click="showModeDropdown = !showModeDropdown"
                        class="group flex w-full items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm transition-all duration-200 hover:border-gray-300 hover:shadow-md"
                    >
                        <div class="flex items-center gap-3">
                            <div :class="['flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-r', roleConfig[currentMode].color]">
                                <component :is="roleConfig[currentMode].icon" class="h-5 w-5 text-white" />
                            </div>
                            <div class="text-left">
                                <p class="text-sm font-semibold text-gray-900">Mode {{ roleConfig[currentMode].label }}</p>
                                <p class="text-xs text-gray-500">Cliquez pour changer</p>
                            </div>
                        </div>
                        <ChevronDown :class="['h-4 w-4 text-gray-400 transition-transform duration-200', showModeDropdown ? 'rotate-180' : '']" />
                    </button>

                    <!-- Dropdown menu -->
                    <div
                        v-if="showModeDropdown"
                        class="absolute top-full right-0 left-0 z-50 mt-2 overflow-hidden rounded-xl border bg-white shadow-xl"
                    >
                        <div class="p-2">
                            <button
                                v-if="hasParentRole"
                                @click="switchMode('parent')"
                                :class="[
                                    'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors duration-150',
                                    currentMode === 'parent' ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50',
                                ]"
                            >
                                <div
                                    :class="[
                                        'flex h-8 w-8 items-center justify-center rounded-lg',
                                        currentMode === 'parent' ? 'bg-blue-100' : 'bg-gray-100',
                                    ]"
                                >
                                    <Users class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Mode Parent</p>
                                    <p class="text-xs opacity-75">Gérer vos annonces</p>
                                </div>
                                <Crown v-if="currentMode === 'parent'" class="ml-auto h-4 w-4 text-blue-500" />
                            </button>

                            <button
                                v-if="hasBabysitterRole"
                                @click="switchMode('babysitter')"
                                :class="[
                                    'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors duration-150',
                                    currentMode === 'babysitter' ? 'bg-pink-50 text-pink-700' : 'text-gray-700 hover:bg-gray-50',
                                ]"
                            >
                                <div
                                    :class="[
                                        'flex h-8 w-8 items-center justify-center rounded-lg',
                                        currentMode === 'babysitter' ? 'bg-pink-100' : 'bg-gray-100',
                                    ]"
                                >
                                    <Baby class="h-4 w-4" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium">Mode Babysitter</p>
                                    <p class="text-xs opacity-75">Trouver des gardes</p>
                                </div>
                                <Crown v-if="currentMode === 'babysitter'" class="ml-auto h-4 w-4 text-pink-500" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mode unique -->
                <div v-else class="flex items-center gap-3">
                    <div :class="['flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-r', roleConfig[currentMode].color]">
                        <component :is="roleConfig[currentMode].icon" class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ roleConfig[currentMode].label }}</h3>
                        <p class="text-sm text-gray-600">Tableau de bord</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-1 p-4">
                <Link
                    v-for="item in navigationLinks"
                    :key="item.name"
                    :href="route(item.href, { mode: item.mode })"
                    :class="[
                        'group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-all duration-200',
                        isCurrentRoute(item.href, item.mode)
                            ? `bg-gradient-to-r ${roleConfig[currentMode].color} text-white shadow-lg`
                            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900',
                    ]"
                >
                    <component
                        :is="item.icon"
                        :class="[
                            'h-5 w-5 transition-colors',
                            isCurrentRoute(item.href, item.mode) ? 'text-white' : 'text-gray-400 group-hover:text-gray-600',
                        ]"
                    />
                    {{ item.name }}
                </Link>
            </nav>

            <!-- Footer -->
            <div class="border-t p-4">
                <Link
                    :href="route('logout')"
                    method="post"
                    class="group flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-red-50 hover:text-red-700"
                >
                    <LogOut class="h-5 w-5 text-gray-400 group-hover:text-red-500" />
                    Déconnexion
                </Link>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation -->
    <div class="fixed right-0 bottom-0 left-0 z-50 lg:hidden">
        <div class="border-t bg-white px-4 py-2 shadow-lg">
            <div class="flex items-center justify-around">
                <!-- Quick actions -->
                <Link
                    v-for="item in navigationLinks.slice(0, 4)"
                    :key="item.name"
                    :href="route(item.href, { mode: item.mode })"
                    :class="[
                        'flex flex-col items-center gap-1 p-2 transition-colors',
                        isCurrentRoute(item.href, item.mode) ? roleConfig[currentMode].textColor : 'text-gray-500',
                    ]"
                >
                    <component :is="item.icon" :class="['h-5 w-5', isCurrentRoute(item.href, item.mode) ? '' : 'opacity-75']" />
                    <span class="text-xs font-medium">{{ item.name.split(' ')[0] }}</span>
                </Link>

                <!-- More menu -->
                <button
                    @click="handleMobileMenuToggle"
                    :class="[
                        'flex flex-col items-center gap-1 p-2 transition-colors',
                        showFullMenu ? roleConfig[currentMode].textColor : 'text-gray-500',
                    ]"
                >
                    <Menu class="h-5 w-5" />
                    <span class="text-xs font-medium">Plus</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Full Menu Overlay -->
    <div v-if="showFullMenu" class="fixed inset-0 z-50 lg:hidden">
        <div class="absolute inset-0 bg-black/50" @click="handleMobileMenuToggle"></div>
        <div class="absolute right-0 bottom-0 left-0 max-h-[80vh] overflow-y-auto rounded-t-2xl bg-white">
            <!-- Header -->
            <div class="flex items-center justify-between border-b p-4">
                <h3 class="text-lg font-semibold text-gray-900">Menu</h3>
                <button @click="handleMobileMenuToggle" class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100">
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Mode Switch Mobile -->
            <div v-if="hasMultipleRoles" class="border-b p-4">
                <p class="mb-3 text-sm font-medium text-gray-700">Changer de mode</p>
                <div class="flex gap-2">
                    <button
                        v-if="hasParentRole"
                        @click="switchMode('parent')"
                        :class="[
                            'flex flex-1 items-center gap-2 rounded-lg border px-3 py-2 transition-colors',
                            currentMode === 'parent' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-700 hover:bg-gray-50',
                        ]"
                    >
                        <Users class="h-4 w-4" />
                        <span class="text-sm font-medium">Parent</span>
                    </button>
                    <button
                        v-if="hasBabysitterRole"
                        @click="switchMode('babysitter')"
                        :class="[
                            'flex flex-1 items-center gap-2 rounded-lg border px-3 py-2 transition-colors',
                            currentMode === 'babysitter'
                                ? 'border-pink-500 bg-pink-50 text-pink-700'
                                : 'border-gray-200 text-gray-700 hover:bg-gray-50',
                        ]"
                    >
                        <Baby class="h-4 w-4" />
                        <span class="text-sm font-medium">Babysitter</span>
                    </button>
                </div>
            </div>

            <!-- Navigation -->
            <div class="p-4">
                <div class="space-y-2">
                    <Link
                        v-for="item in navigationLinks"
                        :key="item.name"
                        :href="route(item.href, { mode: item.mode })"
                        @click="handleLinkClick"
                        :class="[
                            'flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition-colors',
                            isCurrentRoute(item.href, item.mode)
                                ? `${roleConfig[currentMode].bgColor} ${roleConfig[currentMode].textColor}`
                                : 'text-gray-700 hover:bg-gray-50',
                        ]"
                    >
                        <component :is="item.icon" class="h-5 w-5" />
                        {{ item.name }}
                    </Link>

                    <!-- Logout -->
                    <Link
                        :href="route('logout')"
                        method="post"
                        @click="handleLinkClick"
                        class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-red-50 hover:text-red-700"
                    >
                        <LogOut class="h-5 w-5" />
                        Déconnexion
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <!-- Backdrop pour fermer le dropdown desktop -->
    <div v-if="showModeDropdown" @click="showModeDropdown = false" class="fixed inset-0 z-40"></div>
</template>
