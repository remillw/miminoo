<script setup lang="ts">
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Calendar, CreditCard, Home, LogOut, MessageCircle, PlusCircle, Settings, User, Menu, X } from 'lucide-vue-next';
import { computed } from 'vue';

const showFullMenu = ref(false);

// Définition des liens du menu
const links = [
    { icon: Home, label: 'Tableau de bord', href: '/dashboard' },
    { icon: PlusCircle, label: 'Créer une annonce', href: '/annonces/create' },
    { icon: Calendar, label: 'Mes annonces', href: '/mes-annonces' },
    { icon: Calendar, label: 'Mes réservations', href: '/reservations' },
    { icon: MessageCircle, label: 'Messagerie', href: '/messagerie' },
    { icon: User, label: 'Mon profil', href: '/profil' },
    { icon: CreditCard, label: 'Paiements | Factures', href: '/paiements' },
    { icon: Settings, label: 'Paramètres', href: '/parametres' },
];

// Liens principaux pour la navigation mobile (4 plus importants)
const mobileLinks = [
    { icon: Home, label: 'Accueil', href: '/dashboard' },
    { icon: Calendar, label: 'Résa', href: '/reservations' },
    { icon: MessageCircle, label: 'Messages', href: '/messagerie' },
    { icon: User, label: 'Profil', href: '/profil' },
];

// Récupération de l'URL courante
const currentUrl = computed(() => usePage().url);

// Fonction pour vérifier si la route est active
const isCurrentRoute = (href: string) => {
    const page = usePage();
    const currentUrl = page.url;
    
    if (href === '/dashboard') {
        return currentUrl === '/dashboard' || currentUrl === '/';
    }
    
    return currentUrl.startsWith(href);
};
</script>

<template>
    <!-- Desktop Sidebar (inchangée) -->
    <aside class="hidden lg:flex min-h-screen w-64 flex-col border-r bg-white pt-10">
        <nav class="flex flex-1 flex-col gap-1 px-4">
            <Link
                v-for="link in links"
                :key="link.label"
                :href="link.href"
                class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition"
                :class="[currentUrl === link.href ? 'text-primary bg-orange-100' : 'text-gray-700 hover:bg-secondary']"
            >
                <component :is="link.icon" class="h-5 w-5" />
                {{ link.label }}
            </Link>
        </nav>
        <div class="border-t p-4">
            <Link href="/logout" method="post" as="button" class="hover:text-primary flex items-center gap-2 text-gray-500">
                <LogOut class="h-5 w-5" /> Déconnexion
            </Link>
        </div>
    </aside>

    <!-- Mobile Bottom Navigation -->
    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50">
        <div class="grid grid-cols-4 gap-1 px-2 py-2">
            <Link
                v-for="item in mobileLinks"
                :key="item.label"
                :href="item.href"
                :class="[
                    isCurrentRoute(item.href)
                        ? 'text-blue-600 bg-blue-50'
                        : 'text-gray-600',
                    'flex flex-col items-center justify-center py-2 px-1 rounded-lg transition-colors'
                ]"
            >
                <component
                    :is="item.icon"
                    :class="[
                        isCurrentRoute(item.href) ? 'text-blue-600' : 'text-gray-400',
                        'h-5 w-5 mb-1'
                    ]"
                />
                <span class="text-xs font-medium">{{ item.label }}</span>
            </Link>
        </div>
        
        <!-- Menu complet accessible via un bouton -->
        <div class="absolute top-0 right-4 transform -translate-y-full">
            <button
                @click="showFullMenu = !showFullMenu"
                class="bg-blue-600 text-white p-2 rounded-t-lg shadow-lg"
            >
                <Menu class="h-5 w-5" />
            </button>
        </div>

        <!-- Menu complet en overlay -->
        <div
            v-if="showFullMenu"
            class="absolute bottom-full left-0 right-0 bg-white border-t border-gray-200 shadow-lg max-h-96 overflow-y-auto"
        >
            <div class="p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Menu complet</h3>
                    <button
                        @click="showFullMenu = false"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <nav class="space-y-2">
                    <Link
                        v-for="item in links"
                        :key="item.label"
                        :href="item.href"
                        @click="showFullMenu = false"
                        :class="[
                            isCurrentRoute(item.href)
                                ? 'bg-blue-50 text-blue-700'
                                : 'text-gray-600 hover:bg-gray-50',
                            'group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors'
                        ]"
                    >
                        <component
                            :is="item.icon"
                            :class="[
                                isCurrentRoute(item.href) ? 'text-blue-500' : 'text-gray-400',
                                'mr-3 h-5 w-5'
                            ]"
                        />
                        {{ item.label }}
                    </Link>
                </nav>
            </div>
        </div>
    </div>
</template>
