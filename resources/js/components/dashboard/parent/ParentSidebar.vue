<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Calendar, CreditCard, Home, LogOut, Menu, MessageCircle, MoreHorizontal, PlusCircle, Settings, User, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const showFullMenu = ref(false);

// Définition des liens du menu
const links = [
    { icon: Home, label: 'Tableau de bord', href: '/tableau-de-bord' },
    { icon: PlusCircle, label: 'Créer une annonce', href: '/creer-une-annonce' },
    { icon: Calendar, label: 'Mes gardes', href: '/mes-annonces-et-reservations' },
    { icon: MessageCircle, label: 'Messagerie', href: '/messagerie' },
    { icon: User, label: 'Mon profil', href: '/profil' },
    { icon: CreditCard, label: 'Paiements | Factures', href: '/paiements' },
    { icon: Settings, label: 'Paramètres', href: '/parametres' },
];

// Liens principaux pour la navigation mobile (4 plus importants)
const mobileLinks = [
    { icon: Home, label: 'Accueil', href: '/tableau-de-bord' },
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

    if (href === '/tableau-de-bord') {
        return currentUrl === '/tableau-de-bord' || currentUrl === '/';
    }

    return currentUrl.startsWith(href);
};
</script>

<template>
    <!-- Desktop Sidebar (inchangée) -->
    <aside class="hidden min-h-screen w-64 flex-col border-r bg-white pt-10 lg:flex">
        <nav class="flex flex-1 flex-col gap-1 px-4">
            <Link
                v-for="link in links"
                :key="link.label"
                :href="link.href"
                class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition"
                :class="[currentUrl === link.href ? 'text-primary bg-orange-100' : 'hover:bg-secondary text-gray-700']"
            >
                <component :is="link.icon" class="h-5 w-5" />
                {{ link.label }}
            </Link>
        </nav>
        <div class="border-t p-4">
            <Link href="/deconnexion" method="post" as="button" class="hover:text-primary flex items-center gap-2 text-gray-500">
                <LogOut class="h-5 w-5" /> Déconnexion
            </Link>
        </div>
    </aside>

    <!-- Mobile Bottom Navigation -->
    <div class="fixed right-0 bottom-0 left-0 z-50 border-t border-gray-200 bg-white lg:hidden">
        <div class="flex items-center justify-around px-2 py-2">
            <!-- Les 3 premiers liens principaux -->
            <Link
                v-for="item in mobileLinks.slice(0, 3)"
                :key="item.label"
                :href="item.href"
                :class="[
                    isCurrentRoute(item.href) ? 'bg-blue-50 text-blue-600' : 'text-gray-600',
                    'flex flex-col items-center justify-center rounded-lg px-1 py-2 transition-colors',
                ]"
            >
                <component :is="item.icon" :class="[isCurrentRoute(item.href) ? 'text-blue-600' : 'text-gray-400', 'mb-1 h-5 w-5']" />
                <span class="text-xs font-medium">{{ item.label }}</span>
            </Link>
            
            <!-- Bouton Plus pour ouvrir le menu complet -->
            <button 
                @click="showFullMenu = !showFullMenu" 
                class="flex flex-col items-center justify-center rounded-lg px-1 py-2 text-gray-600 transition-colors"
            >
                <MoreHorizontal class="mb-1 h-5 w-5 text-gray-400" />
                <span class="text-xs font-medium">Plus</span>
            </button>
        </div>

        <!-- Menu complet en overlay -->
        <div v-if="showFullMenu" class="absolute right-0 bottom-full left-0 max-h-96 overflow-y-auto border-t border-gray-200 bg-white shadow-lg">
            <div class="p-4">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Menu complet</h3>
                    <button @click="showFullMenu = false" class="text-gray-400 hover:text-gray-600">
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
                            isCurrentRoute(item.href) ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50',
                            'group flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                        ]"
                    >
                        <component :is="item.icon" :class="[isCurrentRoute(item.href) ? 'text-blue-500' : 'text-gray-400', 'mr-3 h-5 w-5']" />
                        {{ item.label }}
                    </Link>
                    
                    <!-- Séparateur -->
                    <div class="border-t border-gray-200 my-2"></div>
                    
                    <!-- Lien de déconnexion -->
                    <Link
                        href="/deconnexion"
                        method="post"
                        @click="showFullMenu = false"
                        class="group flex items-center rounded-lg px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
                    >
                        <LogOut class="mr-3 h-5 w-5 text-red-500" />
                        Déconnexion
                    </Link>
                </nav>
            </div>
        </div>
    </div>
</template>
