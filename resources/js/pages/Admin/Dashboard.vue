<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Clock, CreditCard, MessageSquare, ShieldAlert, TrendingUp, UserCheck, Users, FileText, Calendar, Star, Plus, Edit, Trash2 } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

interface Stats {
    total_users: number;
    total_parents: number;
    total_babysitters: number;
    pending_verifications: number;
    verified_babysitters: number;
    total_ads: number;
    active_ads: number;
    total_reservations: number;
    total_reviews: number;
    recent_registrations: number;
    stripe_total_accounts: number;
    stripe_active_accounts: number;
    stripe_pending_accounts: number;
    stripe_rejected_accounts: number;
}

interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    created_at: string;
    roles: Array<{ name: string; label: string }>;
}

interface Ad {
    id: number;
    title: string;
    status: string;
    created_at: string;
    parent: {
        firstname: string;
        lastname: string;
    };
}

interface Review {
    id: number;
    rating: number;
    comment: string;
    created_at: string;
    reviewer: {
        firstname: string;
        lastname: string;
    };
    reviewed: {
        firstname: string;
        lastname: string;
    };
}

interface RecentActivity {
    new_users: User[];
    recent_ads: Ad[];
    recent_reviews: Review[];
}

interface Props {
    stats: Stats;
    recentActivity: RecentActivity;
}

const props = defineProps<Props>();
const page = usePage();
const auth = page.props.auth as { user: User | null };

console.log('Auth data:', auth);
console.log('User roles:', auth.user?.roles);

// Vérification que l'utilisateur est admin côté frontend
const isAdmin = computed(() => {
    return auth.user?.roles?.some((role) => role.name === 'admin') || false;
});

// Si pas admin, rediriger vers 403
onMounted(() => {
    if (!isAdmin.value) {
        router.visit('/403', { method: 'get' });
    }
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusClass = (status: string) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-800';
        case 'completed':
            return 'bg-blue-100 text-blue-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getStatusText = (status: string) => {
    switch (status) {
        case 'active':
            return 'Active';
        case 'completed':
            return 'Terminée';
        case 'cancelled':
            return 'Annulée';
        default:
            return status;
    }
};
</script>

<template>
    <Head title="Dashboard Administrateur" />

    <div v-if="isAdmin" class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="border-b bg-white shadow-sm">
            <div class="flex h-16 items-center px-6">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-semibold text-gray-900">Dashboard Administrateur</h1>
                </div>
                <div class="ml-auto flex items-center space-x-4">
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
                        <Link href="/admin" class="flex items-center space-x-2 rounded-lg bg-blue-50 px-3 py-2 text-blue-700">
                            <TrendingUp class="h-4 w-4" />
                            <span>Dashboard</span>
                        </Link>

                        <Link
                            href="/admin/parents"
                            class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100"
                        >
                            <Users class="h-4 w-4" />
                            <span>Parents</span>
                        </Link>

                        <Link
                            href="/admin/babysitters"
                            class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100"
                        >
                            <UserCheck class="h-4 w-4" />
                            <span>Babysitters</span>
                        </Link>

                        <Link
                            href="/admin/moderation-babysitters"
                            class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100"
                        >
                            <ShieldAlert class="h-4 w-4" />
                            <span class="flex-1">Modération</span>
                            <span v-if="stats.pending_verifications > 0" class="ml-auto rounded-full bg-red-500 px-2 py-1 text-xs text-white">
                                {{ stats.pending_verifications }}
                            </span>
                        </Link>

                        <Link
                            href="/admin/annonces"
                            class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100"
                        >
                            <FileText class="h-4 w-4" />
                            <span>Annonces</span>
                        </Link>

                        <Link
                            href="/admin/avis"
                            class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100"
                        >
                            <Star class="h-4 w-4" />
                            <span>Avis</span>
                        </Link>

                        <Link href="/admin/comptes-stripe" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <CreditCard class="h-4 w-4" />
                            <span>Comptes Stripe</span>
                        </Link>

                        <Link href="/dashboard" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <Users class="h-4 w-4" />
                            <span>Retour utilisateur</span>
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="flex-1 p-6">
                <!-- Statistiques principales -->
                <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Utilisateurs totaux -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Utilisateurs totaux</CardTitle>
                            <Users class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.total_users }}</div>
                            <p class="text-xs text-muted-foreground">
                                +{{ stats.recent_registrations }} cette semaine
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Parents -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Parents</CardTitle>
                            <Users class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.total_parents }}</div>
                            <p class="text-xs text-muted-foreground">
                                Parents inscrits
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Babysitters -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Babysitters</CardTitle>
                            <UserCheck class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.verified_babysitters }}/{{ stats.total_babysitters }}</div>
                            <p class="text-xs text-muted-foreground">
                                Vérifiés/Total
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Annonces -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Annonces</CardTitle>
                            <FileText class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.active_ads }}/{{ stats.total_ads }}</div>
                            <p class="text-xs text-muted-foreground">
                                Actives/Total
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Réservations -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Réservations</CardTitle>
                            <Calendar class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.total_reservations }}</div>
                            <p class="text-xs text-muted-foreground">
                                Total des gardes
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Avis -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Avis</CardTitle>
                            <Star class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.total_reviews }}</div>
                            <p class="text-xs text-muted-foreground">
                                Avis publiés
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Stripe en attente -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Stripe Connect</CardTitle>
                            <CreditCard class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.stripe_active_accounts }}/{{ stats.stripe_total_accounts }}</div>
                            <p class="text-xs text-muted-foreground">
                                Actifs/Total
                                <span v-if="stats.stripe_pending_accounts > 0" class="ml-2 rounded-full bg-orange-500 px-2 py-1 text-xs text-white">
                                    {{ stats.stripe_pending_accounts }}
                                </span>
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Vérifications en attente -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Vérifications</CardTitle>
                            <ShieldAlert class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.pending_verifications }}</div>
                            <p class="text-xs text-muted-foreground">
                                En attente
                                <span v-if="stats.pending_verifications > 0" class="ml-2 rounded-full bg-red-500 px-2 py-1 text-xs text-white">
                                    Urgent
                                </span>
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Actions rapides -->
                <div class="mb-8">
                    <h2 class="mb-4 text-xl font-semibold">Actions rapides</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Button as-child>
                            <Link href="/admin/babysitter-moderation">
                                <ShieldAlert class="mr-2 h-4 w-4" />
                                Gérer les vérifications
                                <span v-if="stats.pending_verifications > 0" class="ml-2 rounded-full bg-red-500 px-2 py-1 text-xs text-white">
                                    {{ stats.pending_verifications }}
                                </span>
                            </Link>
                        </Button>

                        <Button as-child variant="outline">
                            <Link href="/admin/parents">
                                <Users class="mr-2 h-4 w-4" />
                                Gérer les parents
                            </Link>
                        </Button>

                        <Button as-child variant="outline">
                            <Link href="/admin/babysitters">
                                <UserCheck class="mr-2 h-4 w-4" />
                                Gérer les babysitters
                            </Link>
                        </Button>

                        <Button as-child variant="outline">
                            <Link href="/admin/announcements">
                                <FileText class="mr-2 h-4 w-4" />
                                Gérer les annonces
                            </Link>
                        </Button>
                    </div>
                </div>

                <!-- Activité récente -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Nouveaux utilisateurs -->
                <Card>
                    <CardHeader>
                            <CardTitle>Nouveaux utilisateurs</CardTitle>
                            <CardDescription>Les 5 dernières inscriptions</CardDescription>
                    </CardHeader>
                    <CardContent>
                            <div class="space-y-3">
                                <div
                                    v-for="user in recentActivity.new_users"
                                    :key="user.id"
                                    class="flex items-center justify-between"
                                >
                                    <div>
                                        <p class="text-sm font-medium">{{ user.firstname }} {{ user.lastname }}</p>
                                        <p class="text-xs text-gray-500">{{ user.email }}</p>
                                        <p class="text-xs text-gray-400">
                                            {{ user.roles.map(r => r.label).join(', ') }}
                                        </p>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ formatDate(user.created_at) }}
                                    </div>
                                </div>
                                <div v-if="recentActivity.new_users.length === 0" class="text-center text-sm text-gray-500">
                                    Aucun nouvel utilisateur
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Dernières annonces -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Dernières annonces</CardTitle>
                            <CardDescription>Les 5 dernières annonces créées</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div
                                    v-for="ad in recentActivity.recent_ads"
                                    :key="ad.id"
                                    class="flex items-center justify-between"
                                >
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ ad.title }}</p>
                                        <p class="text-xs text-gray-500">
                                            Par {{ ad.parent.firstname }} {{ ad.parent.lastname }}
                                        </p>
                                        <span :class="['inline-flex items-center rounded-full px-2 py-1 text-xs font-medium', getStatusClass(ad.status)]">
                                            {{ getStatusText(ad.status) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <Button size="sm" variant="ghost" as-child>
                                            <Link :href="`/admin/announcements/${ad.id}/edit`">
                                                <Edit class="h-3 w-3" />
                                            </Link>
                                        </Button>
                                        <div class="text-xs text-gray-400">
                                            {{ formatDate(ad.created_at) }}
                                        </div>
                                    </div>
                                </div>
                                <div v-if="recentActivity.recent_ads.length === 0" class="text-center text-sm text-gray-500">
                                    Aucune annonce récente
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Derniers avis -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Derniers avis</CardTitle>
                            <CardDescription>Les 5 derniers avis publiés</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div
                                    v-for="review in recentActivity.recent_reviews"
                                    :key="review.id"
                                    class="border-b border-gray-100 pb-3 last:border-0"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <div class="flex">
                                                <Star
                                                    v-for="i in 5"
                                                    :key="i"
                                                    :class="[
                                                        'h-3 w-3',
                                                        i <= review.rating ? 'text-yellow-400 fill-current' : 'text-gray-300'
                                                    ]"
                                                />
                                            </div>
                                            <span class="text-xs font-medium">{{ review.rating }}/5</span>
                                        </div>
                                        <span class="text-xs text-gray-400">
                                            {{ formatDate(review.created_at) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-600 line-clamp-2">{{ review.comment }}</p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        De {{ review.reviewer.firstname }} {{ review.reviewer.lastname }}
                                        pour {{ review.reviewed.firstname }} {{ review.reviewed.lastname }}
                                    </p>
                                </div>
                                <div v-if="recentActivity.recent_reviews.length === 0" class="text-center text-sm text-gray-500">
                                    Aucun avis récent
                            </div>
                        </div>
                    </CardContent>
                </Card>
                </div>
            </main>
        </div>
    </div>

    <!-- Loading state pendant vérification -->
    <div v-else class="flex min-h-screen items-center justify-center">
        <div class="text-center">
            <div class="mx-auto mb-4 h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-blue-600"></div>
            <p class="text-gray-600">Vérification des permissions...</p>
        </div>
    </div>
</template>
