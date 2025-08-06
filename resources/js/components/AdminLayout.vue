<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Link, usePage } from '@inertiajs/vue3';
import { CreditCard, FileText, MessageSquare, ShieldAlert, Star, TrendingUp, UserCheck, Users } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    title: string;
    description?: string;
    activeSection?: string;
    headerActions?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    activeSection: '',
    headerActions: false,
});

interface User {
    id: number;
    firstname: string;
    lastname: string;
    roles?: { name: string; label: string }[];
}

const page = usePage();
const auth = page.props.auth as { user: User | null };

// Récupération des stats pour les badges via le middleware
const adminStats = computed(() => {
    return (page.props as any).adminStats || {};
});

// Fallback sur les stats locales si disponibles (pour compatibilité)
const stats = computed(() => {
    return (page.props as any).stats || {};
});

// Utiliser adminStats en priorité, sinon fallback sur stats
const badgeStats = computed(() => {
    return {
        pending_verifications: adminStats.value.pending_verifications || stats.value.pending_verifications || 0,
        unread_contacts: adminStats.value.unread_contacts || stats.value.unread_contacts || 0,
    };
});

const isActive = (section: string) => {
    return props.activeSection === section;
};

const getNavItemClass = (section: string) => {
    return isActive(section)
        ? 'flex items-center space-x-2 rounded-lg bg-blue-50 px-3 py-2 text-blue-700'
        : 'flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100';
};
</script>

<template>
    <div class="bg-secondary min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="flex h-16 items-center justify-between px-6">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-semibold text-gray-900">{{ props.title }}</h1>
                    <p v-if="props.description" class="text-sm text-gray-600">{{ props.description }}</p>
                </div>

                <div v-if="props.headerActions" class="flex items-center space-x-4">
                    <slot name="headerActions" />
                </div>

                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600"> Bonjour, {{ auth.user?.firstname }} {{ auth.user?.lastname }} </span>
                    <Link href="/dashboard" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Retour au dashboard
                    </Link>
                </div>
            </div>
        </header>

        <div class="flex">
            <!-- Sidebar -->
            <nav class="w-64 border-r bg-white">
                <div class="p-6">
                    <div class="space-y-2">
                        <Link href="/admin" :class="getNavItemClass('dashboard')">
                            <TrendingUp class="h-4 w-4" />
                            <span>Dashboard</span>
                        </Link>

                        <Link href="/admin/parents" :class="getNavItemClass('parents')">
                            <Users class="h-4 w-4" />
                            <span>Parents</span>
                        </Link>

                        <Link href="/admin/babysitters" :class="getNavItemClass('babysitters')">
                            <UserCheck class="h-4 w-4" />
                            <span>Babysitters</span>
                        </Link>

                        <Link href="/admin/moderation-babysitters" :class="getNavItemClass('moderation')">
                            <ShieldAlert class="h-4 w-4" />
                            <span class="flex-1">Modération</span>
                            <Badge v-if="badgeStats.pending_verifications > 0" class="ml-auto rounded-full bg-red-500 px-2 py-1 text-xs text-white">
                                {{ badgeStats.pending_verifications }}
                            </Badge>
                        </Link>

                        <Link href="/admin/annonces" :class="getNavItemClass('announcements')">
                            <FileText class="h-4 w-4" />
                            <span>Annonces</span>
                        </Link>

                        <Link href="/admin/avis" :class="getNavItemClass('reviews')">
                            <Star class="h-4 w-4" />
                            <span>Avis</span>
                        </Link>

                        <Link href="/admin/contacts" :class="getNavItemClass('contacts')">
                            <MessageSquare class="h-4 w-4" />
                            <span class="flex-1">Contacts</span>
                            <Badge v-if="badgeStats.unread_contacts > 0" class="ml-auto rounded-full bg-orange-500 px-2 py-1 text-xs text-white">
                                {{ badgeStats.unread_contacts }}
                            </Badge>
                        </Link>

                        <Link href="/admin/comptes-stripe" :class="getNavItemClass('stripe')">
                            <CreditCard class="h-4 w-4" />
                            <span>Comptes Stripe</span>
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Contenu principal -->
            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
