<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Clock, MessageSquare, ShieldAlert, TrendingUp, UserCheck, Users } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';

interface Stats {
    total_users: number;
    total_babysitters: number;
    pending_verifications: number;
    verified_babysitters: number;
    total_ads: number;
    recent_registrations: number;
}

interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    avatar?: string;
    roles?: { name: string; label: string }[];
}

interface Props {
    stats: Stats;
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
                            href="/admin/babysitter-moderation"
                            class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100"
                        >
                            <ShieldAlert class="h-4 w-4" />
                            <span class="flex-1">Modération</span>
                            <span v-if="stats.pending_verifications > 0" class="ml-auto rounded-full bg-red-500 px-2 py-1 text-xs text-white">
                                {{ stats.pending_verifications }}
                            </span>
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
                <!-- Stats Cards -->
                <div class="mb-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Utilisateurs total</CardTitle>
                            <Users class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.total_users }}</div>
                            <p class="text-muted-foreground text-xs">+{{ stats.recent_registrations }} cette semaine</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Babysitters</CardTitle>
                            <UserCheck class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.total_babysitters }}</div>
                            <p class="text-muted-foreground text-xs">{{ stats.verified_babysitters }} vérifiés</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">En attente</CardTitle>
                            <Clock class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-orange-600">{{ stats.pending_verifications }}</div>
                            <p class="text-muted-foreground text-xs">Demandes de vérification</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Annonces</CardTitle>
                            <MessageSquare class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.total_ads }}</div>
                            <p class="text-muted-foreground text-xs">Total des annonces</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Actions rapides -->
                <div class="mb-8">
                    <h2 class="mb-4 text-xl font-semibold">Actions rapides</h2>
                    <div class="flex gap-4">
                        <Button as-child>
                            <Link href="/admin/babysitter-moderation">
                                Gérer les vérifications
                                <span v-if="stats.pending_verifications > 0" class="ml-2 rounded-full bg-red-500 px-2 py-1 text-xs text-white">
                                    {{ stats.pending_verifications }}
                                </span>
                            </Link>
                        </Button>
                    </div>
                </div>

                <!-- Activité récente -->
                <Card>
                    <CardHeader>
                        <CardTitle>Activité récente</CardTitle>
                        <CardDescription> Aperçu des dernières actions sur la plateforme </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between rounded-lg border p-3">
                                <div class="flex items-center space-x-3">
                                    <div class="h-2 w-2 rounded-full bg-green-500"></div>
                                    <span class="text-sm">{{ stats.recent_registrations }} nouveaux utilisateurs cette semaine</span>
                                </div>
                                <span class="text-muted-foreground text-xs">Cette semaine</span>
                            </div>

                            <div class="flex items-center justify-between rounded-lg border p-3">
                                <div class="flex items-center space-x-3">
                                    <div class="h-2 w-2 rounded-full bg-orange-500"></div>
                                    <span class="text-sm">{{ stats.pending_verifications }} demandes en attente</span>
                                </div>
                                <span class="text-muted-foreground text-xs">À traiter</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
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
