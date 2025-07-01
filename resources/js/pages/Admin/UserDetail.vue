<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Head, Link } from '@inertiajs/vue3';
import { Users, TrendingUp, ShieldAlert, FileText, Star, CreditCard, UserCheck, ArrowLeft, Mail, Phone, MapPin, Calendar, Edit } from 'lucide-vue-next';

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
        case 'approved':
        case 'verified':
        case 'active':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'suspended':
        case 'rejected':
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getStatusText = (status: string) => {
    switch (status) {
        case 'approved':
            return 'Approuvé';
        case 'pending':
            return 'En attente';
        case 'suspended':
            return 'Suspendu';
        case 'verified':
            return 'Vérifié';
        case 'rejected':
            return 'Rejeté';
        case 'active':
            return 'Active';
        case 'cancelled':
            return 'Annulée';
        default:
            return status;
    }
};

const getRatingStars = (rating: number) => {
    return '★'.repeat(rating) + '☆'.repeat(5 - rating);
};
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
                                    <Badge :class="getStatusClass(user.status)">
                                        {{ getStatusText(user.status) }}
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
                                    <Badge :class="getStatusClass(user.babysitter_profile.verification_status)">
                                        {{ getStatusText(user.babysitter_profile.verification_status) }}
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

                    <!-- Onglets pour les activités -->
                    <Tabs default-value="ads" class="w-full">
                        <TabsList class="grid w-full grid-cols-3">
                            <TabsTrigger value="ads">Annonces ({{ user.ads?.length || 0 }})</TabsTrigger>
                            <TabsTrigger value="applications">Candidatures ({{ user.applications?.length || 0 }})</TabsTrigger>
                            <TabsTrigger value="reviews">Avis ({{ user.reviews?.length || 0 }})</TabsTrigger>
                        </TabsList>
                        
                        <TabsContent value="ads" class="space-y-4">
                            <Card v-if="user.ads && user.ads.length > 0">
                                <CardHeader>
                                    <CardTitle>Annonces créées</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-3">
                                        <div 
                                            v-for="ad in user.ads" 
                                            :key="ad.id"
                                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                                        >
                                            <div>
                                                <p class="font-medium">{{ ad.title }}</p>
                                                <p class="text-sm text-gray-500">{{ formatDate(ad.created_at) }}</p>
                                            </div>
                                            <Badge :class="getStatusClass(ad.status)">
                                                {{ getStatusText(ad.status) }}
                                            </Badge>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            <p v-else class="text-center text-gray-500 py-8">Aucune annonce trouvée</p>
                        </TabsContent>
                        
                        <TabsContent value="applications" class="space-y-4">
                            <Card v-if="user.applications && user.applications.length > 0">
                                <CardHeader>
                                    <CardTitle>Candidatures envoyées</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-3">
                                        <div 
                                            v-for="application in user.applications" 
                                            :key="application.id"
                                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                                        >
                                            <div>
                                                <p class="font-medium">{{ application.ad.title }}</p>
                                                <p class="text-sm text-gray-500">{{ formatDate(application.created_at) }}</p>
                                            </div>
                                            <Badge :class="getStatusClass(application.status)">
                                                {{ getStatusText(application.status) }}
                                            </Badge>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            <p v-else class="text-center text-gray-500 py-8">Aucune candidature trouvée</p>
                        </TabsContent>
                        
                        <TabsContent value="reviews" class="space-y-4">
                            <Card v-if="user.reviews && user.reviews.length > 0">
                                <CardHeader>
                                    <CardTitle>Avis donnés et reçus</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <div 
                                            v-for="review in user.reviews" 
                                            :key="review.id"
                                            class="p-4 bg-gray-50 rounded-lg"
                                        >
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-lg">{{ getRatingStars(review.rating) }}</span>
                                                    <Badge variant="outline">{{ review.rating }}/5</Badge>
                                                </div>
                                                <span class="text-sm text-gray-500">{{ formatDate(review.created_at) }}</span>
                                            </div>
                                            <p class="text-sm mb-2">{{ review.comment }}</p>
                                            <div class="text-xs text-gray-500">
                                                <span v-if="review.reviewer">Par {{ review.reviewer.firstname }} {{ review.reviewer.lastname }}</span>
                                                <span v-if="review.reviewed">Pour {{ review.reviewed.firstname }} {{ review.reviewed.lastname }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            <p v-else class="text-center text-gray-500 py-8">Aucun avis trouvé</p>
                        </TabsContent>
                    </Tabs>
                </div>
            </main>
        </div>
    </div>
</template> 