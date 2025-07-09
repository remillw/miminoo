<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import DataTable from '@/components/DataTable.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Users, TrendingUp, ShieldAlert, FileText, Star, CreditCard, UserCheck, ArrowLeft, Mail, Phone, MapPin, Calendar, Edit } from 'lucide-vue-next';
import { ref } from 'vue';
import { useStatusColors } from '@/composables/useStatusColors';

interface Address {
    id: number;
    address: string;
    postal_code: string;
    country: string;
}

interface Role {
    id: number;
    name: string;
    label: string;
}

interface ParentProfile {
    id: number;
}

interface BabysitterProfile {
    id: number;
    bio?: string;
    experience_years?: number;
    hourly_rate?: number;
    verification_status: string;
}

interface Ad {
    id: number;
    title: string;
    status: string;
    created_at: string;
}

interface Application {
    id: number;
    status: string;
    created_at: string;
    ad: {
        title: string;
    };
}

interface Review {
    id: number;
    rating: number;
    comment: string;
    created_at: string;
    reviewer?: {
        firstname: string;
        lastname: string;
    };
    reviewed?: {
        firstname: string;
        lastname: string;
    };
}

interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    phone?: string;
    status: string;
    created_at: string;
    roles: Role[];
    address?: Address;
    parent_profile?: ParentProfile;
    babysitter_profile?: BabysitterProfile;
    ads?: Ad[];
    applications?: Application[];
    reviews?: Review[];
}

interface Props {
    user: User;
}

const props = defineProps<Props>();

// Composable pour les couleurs de statut
const { getAnnouncementStatusColor, getApplicationStatusColor, getUserStatusColor, getStatusText } = useStatusColors();

// État pour la navigation entre les sections
const activeSection = ref<'ads' | 'applications' | 'reviews'>('ads');

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Fonctions de statut remplacées par le composable useStatusColors

const getRatingStars = (rating: number) => {
    return '★'.repeat(rating) + '☆'.repeat(5 - rating);
};

// Configuration des colonnes pour les différentes tables
const adsColumns = [
    { key: 'title', label: 'Titre', sortable: true },
    { key: 'status', label: 'Statut', sortable: true, slot: 'status' },
    { key: 'created_at', label: 'Date de création', sortable: true, slot: 'date' },
];

const applicationsColumns = [
    { key: 'ad.title', label: 'Annonce', sortable: true },
    { key: 'status', label: 'Statut', sortable: true, slot: 'status' },
    { key: 'created_at', label: 'Date de candidature', sortable: true, slot: 'date' },
];

const reviewsColumns = [
    { key: 'rating', label: 'Note', sortable: true, slot: 'rating' },
    { key: 'comment', label: 'Commentaire', sortable: false },
    { key: 'created_at', label: 'Date', sortable: true, slot: 'date' },
    { key: 'reviewer', label: 'Évaluation', sortable: false, slot: 'participants' },
];
</script>

<template>
    <Head :title="`${user.firstname} ${user.lastname} - Admin`" />

    <div class="min-h-screen bg-gray-50">
        <header class="border-b bg-white shadow-sm">
            <div class="flex h-16 items-center px-6">
                <div class="flex items-center space-x-4">
                    <Button as-child variant="ghost" size="sm">
                        <Link href="/admin/parents">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Retour
                        </Link>
                    </Button>
                    <h1 class="text-xl font-semibold text-gray-900">{{ user.firstname }} {{ user.lastname }}</h1>
                </div>
                <div class="ml-auto">
                    <Button as-child>
                        <Link :href="`/admin/utilisateurs/${user.id}/modifier`">
                            <Edit class="mr-2 h-4 w-4" />
                            Modifier
                        </Link>
                    </Button>
                </div>
            </div>
        </header>

        <div class="flex">
            <nav class="w-64 border-r bg-white">
                <div class="p-6">
                    <div class="space-y-2">
                        <Link href="/admin" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <TrendingUp class="h-4 w-4" />
                            <span>Dashboard</span>
                        </Link>
                        <Link href="/admin/parents" class="flex items-center space-x-2 rounded-lg bg-blue-50 px-3 py-2 text-blue-700">
                            <Users class="h-4 w-4" />
                            <span>Parents</span>
                        </Link>
                        <Link href="/admin/babysitters" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <UserCheck class="h-4 w-4" />
                            <span>Babysitters</span>
                        </Link>
                        <Link href="/admin/moderation-babysitters" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <ShieldAlert class="h-4 w-4" />
                            <span>Modération</span>
                        </Link>
                        <Link href="/admin/annonces" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <FileText class="h-4 w-4" />
                            <span>Annonces</span>
                        </Link>
                        <Link href="/admin/avis" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <Star class="h-4 w-4" />
                            <span>Avis</span>
                        </Link>
                        <Link href="/admin/comptes-stripe" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <CreditCard class="h-4 w-4" />
                            <span>Comptes Stripe</span>
                        </Link>
                    </div>
                </div>
            </nav>

            <main class="flex-1 p-6">
                <div class="max-w-4xl mx-auto space-y-6">
                    <!-- Informations générales -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle>Informations personnelles</CardTitle>
                                    <CardDescription>Détails du compte utilisateur</CardDescription>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Badge 
                                        v-for="role in user.roles" 
                                        :key="role.id" 
                                        variant="outline"
                                    >
                                        {{ role.label }}
                                    </Badge>
                                    <Badge :class="getUserStatusColor(user.status).badge">
                                        {{ getStatusText('user', user.status) }}
                                    </Badge>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div class="flex items-center space-x-3">
                                        <Users class="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p class="text-sm text-gray-500">Nom complet</p>
                                            <p class="font-medium">{{ user.firstname }} {{ user.lastname }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-3">
                                        <Mail class="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p class="text-sm text-gray-500">Email</p>
                                            <p class="font-medium">{{ user.email }}</p>
                                        </div>
                                    </div>
                                    
                                    <div v-if="user.phone" class="flex items-center space-x-3">
                                        <Phone class="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p class="text-sm text-gray-500">Téléphone</p>
                                            <p class="font-medium">{{ user.phone }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <div v-if="user.address" class="flex items-start space-x-3">
                                        <MapPin class="h-5 w-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <p class="text-sm text-gray-500">Adresse</p>
                                            <p class="font-medium">{{ user.address.address }}</p>
                                            <p class="text-sm text-gray-600">{{ user.address.postal_code }}, {{ user.address.country }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-3">
                                        <Calendar class="h-5 w-5 text-gray-400" />
                                        <div>
                                            <p class="text-sm text-gray-500">Inscription</p>
                                            <p class="font-medium">{{ formatDate(user.created_at) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Profil babysitter si applicable -->
                    <Card v-if="user.babysitter_profile">
                        <CardHeader>
                            <CardTitle>Profil Babysitter</CardTitle>
                            <CardDescription>Informations spécifiques au rôle de babysitter</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">Statut de vérification</span>
                                    <Badge :class="getUserStatusColor(user.babysitter_profile.verification_status).badge">
                                        {{ getStatusText('user', user.babysitter_profile.verification_status) }}
                                    </Badge>
                                </div>
                                
                                <div v-if="user.babysitter_profile.experience_years" class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">Expérience</span>
                                    <span class="font-medium">{{ user.babysitter_profile.experience_years }} année{{ user.babysitter_profile.experience_years > 1 ? 's' : '' }}</span>
                                </div>
                                
                                <div v-if="user.babysitter_profile.hourly_rate" class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500">Tarif horaire</span>
                                    <span class="font-medium">{{ user.babysitter_profile.hourly_rate }}€/h</span>
                                </div>
                                
                                <div v-if="user.babysitter_profile.bio" class="pt-4 border-t">
                                    <p class="text-sm text-gray-500 mb-2">Bio</p>
                                    <p class="text-sm">{{ user.babysitter_profile.bio }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Navigation entre les sections -->
                    <div class="flex space-x-1 bg-gray-100 p-1 rounded-lg w-fit">
                        <Button
                            @click="activeSection = 'ads'"
                            :variant="activeSection === 'ads' ? 'default' : 'ghost'"
                            size="sm"
                        >
                            Annonces ({{ user.ads?.length || 0 }})
                        </Button>
                        <Button
                            @click="activeSection = 'applications'"
                            :variant="activeSection === 'applications' ? 'default' : 'ghost'"
                            size="sm"
                        >
                            Candidatures ({{ user.applications?.length || 0 }})
                        </Button>
                        <Button
                            @click="activeSection = 'reviews'"
                            :variant="activeSection === 'reviews' ? 'default' : 'ghost'"
                            size="sm"
                        >
                            Avis ({{ user.reviews?.length || 0 }})
                        </Button>
                    </div>

                    <!-- Section Annonces -->
                    <div v-if="activeSection === 'ads'">
                        <DataTable
                            v-if="user.ads && user.ads.length > 0"
                            :data="user.ads"
                            :columns="adsColumns"
                            search-placeholder="Rechercher une annonce..."
                            empty-message="Aucune annonce trouvée"
                        >
                            <template #status="{ value }">
                                <Badge :class="getAnnouncementStatusColor(value).badge">
                                    {{ getStatusText('announcement', value) }}
                                </Badge>
                            </template>
                            <template #date="{ value }">
                                {{ formatDate(value) }}
                            </template>
                        </DataTable>
                        <Card v-else>
                            <CardContent class="py-8 text-center text-gray-500">
                                Aucune annonce trouvée
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Section Candidatures -->
                    <div v-if="activeSection === 'applications'">
                        <DataTable
                            v-if="user.applications && user.applications.length > 0"
                            :data="user.applications"
                            :columns="applicationsColumns"
                            search-placeholder="Rechercher une candidature..."
                            empty-message="Aucune candidature trouvée"
                        >
                            <template #status="{ value }">
                                <Badge :class="getApplicationStatusColor(value).badge">
                                    {{ getStatusText('application', value) }}
                                </Badge>
                            </template>
                            <template #date="{ value }">
                                {{ formatDate(value) }}
                            </template>
                        </DataTable>
                        <Card v-else>
                            <CardContent class="py-8 text-center text-gray-500">
                                Aucune candidature trouvée
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Section Avis -->
                    <div v-if="activeSection === 'reviews'">
                        <DataTable
                            v-if="user.reviews && user.reviews.length > 0"
                            :data="user.reviews"
                            :columns="reviewsColumns"
                            search-placeholder="Rechercher un avis..."
                            empty-message="Aucun avis trouvé"
                        >
                            <template #rating="{ value }">
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg">{{ getRatingStars(value) }}</span>
                                    <Badge variant="outline">{{ value }}/5</Badge>
                                </div>
                            </template>
                            <template #participants="{ item }">
                                <div class="text-xs text-gray-500">
                                    <div v-if="item.reviewer">Par {{ item.reviewer.firstname }} {{ item.reviewer.lastname }}</div>
                                    <div v-if="item.reviewed">Pour {{ item.reviewed.firstname }} {{ item.reviewed.lastname }}</div>
                                </div>
                            </template>
                            <template #date="{ value }">
                                {{ formatDate(value) }}
                            </template>
                        </DataTable>
                        <Card v-else>
                            <CardContent class="py-8 text-center text-gray-500">
                                Aucun avis trouvé
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template> 