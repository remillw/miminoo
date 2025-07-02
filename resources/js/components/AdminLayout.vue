<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { 
    TrendingUp, 
    Users, 
    UserCheck, 
    ShieldAlert, 
    FileText, 
    Star, 
    CreditCard 
} from 'lucide-vue-next';

interface Props {
    title: string;
    description?: string;
    activeSection?: string;
    headerActions?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    activeSection: '',
    headerActions: false
});

interface User {
    id: number;
    firstname: string;
    lastname: string;
    roles?: { name: string; label: string }[];
}

const page = usePage();
const auth = page.props.auth as { user: User | null };

// Récupération des stats pour les badges
const stats = computed(() => {
    return (page.props as any).stats || {};
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
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="border-b bg-white shadow-sm">
            <div class="flex h-16 items-center px-6">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-semibold text-gray-900">{{ title }}</h1>
                    <p v-if="description" class="text-sm text-gray-500">{{ description }}</p>
                </div>
                <div class="ml-auto flex items-center space-x-4">
                    <slot name="header-actions" />
                    <span class="rounded-md border border-gray-200 bg-blue-50 px-2 py-1 text-sm text-blue-700">
                        {{ auth.user?.firstname }} {{ auth.user?.lastname }}
                    </span>
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
                            <Badge 
                                v-if="stats.pending_verifications > 0" 
                                class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"
                            >
                                {{ stats.pending_verifications }}
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