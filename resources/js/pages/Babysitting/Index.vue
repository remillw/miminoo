<template>
    <Head title="Mes candidatures et réservations" />

    <DashboardLayout :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- En-tête avec statistiques -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Mes candidatures et réservations</h1>
                <p class="mt-2 text-gray-600">Suivez l'état de vos candidatures et gérez vos réservations</p>

                <!-- Statistiques -->
                <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <Briefcase class="h-8 w-8 text-gray-400" />
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="truncate text-sm font-medium text-gray-500">Total candidatures</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ stats.total_applications }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <Clock class="h-8 w-8 text-yellow-400" />
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="truncate text-sm font-medium text-gray-500">En attente</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ stats.pending_applications }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <Calendar class="h-8 w-8 text-blue-400" />
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="truncate text-sm font-medium text-gray-500">Total réservations</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ stats.total_reservations }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <CheckCircle class="h-8 w-8 text-green-400" />
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="truncate text-sm font-medium text-gray-500">Terminées</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ stats.completed_reservations }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <DollarSign class="h-8 w-8 text-green-500" />
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="truncate text-sm font-medium text-gray-500">Gains totaux</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ formatAmount(stats.total_earned) }}€</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="mb-8 rounded-lg bg-white p-6 shadow">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Filtres</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <Label for="application-status">Statut des candidatures</Label>
                        <Select v-model="tempApplicationStatusFilter">
                            <SelectTrigger id="application-status">
                                <SelectValue placeholder="Tous les statuts" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="option in applicationStatusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label for="reservation-status">Statut des réservations</Label>
                        <Select v-model="tempReservationStatusFilter">
                            <SelectTrigger id="reservation-status">
                                <SelectValue placeholder="Tous les statuts" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="option in reservationStatusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label for="date-filter">Période</Label>
                        <Select v-model="tempDateFilter">
                            <SelectTrigger id="date-filter">
                                <SelectValue placeholder="Toutes les périodes" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="option in dateFilterOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex items-end">
                        <Button @click="applyFilters" class="w-full"> Appliquer les filtres </Button>
                    </div>
                </div>
            </div>

            <!-- Vue avec onglets -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <div class="-mb-px flex space-x-8">
                        <button
                            @click="activeTab = 'candidatures'"
                            :class="{
                                'border-primary text-primary': activeTab === 'candidatures',
                                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': activeTab !== 'candidatures',
                            }"
                            class="border-b-2 px-1 py-2 text-sm font-medium"
                        >
                            Mes candidatures ({{ applications.length }})
                        </button>
                        <button
                            @click="activeTab = 'reservations'"
                            :class="{
                                'border-primary text-primary': activeTab === 'reservations',
                                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': activeTab !== 'reservations',
                            }"
                            class="border-b-2 px-1 py-2 text-sm font-medium"
                        >
                            Mes réservations ({{ reservations.length }})
                        </button>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="mt-6">
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Filtre par date (commun aux deux onglets) -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700">Date :</label>
                            <select
                                v-model="selectedDateFilter"
                                @change="onDateFilterChange"
                                class="focus:border-primary focus:ring-primary rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-1 focus:outline-none"
                            >
                                <option v-for="option in dateFilterOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Filtre par statut spécifique à l'onglet -->
                        <div v-if="activeTab === 'candidatures'" class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700">Statut :</label>
                            <select
                                v-model="selectedApplicationStatus"
                                @change="onApplicationStatusChange"
                                class="focus:border-primary focus:ring-primary rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-1 focus:outline-none"
                            >
                                <option v-for="option in applicationStatusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div v-else-if="activeTab === 'reservations'" class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700">Statut :</label>
                            <select
                                v-model="selectedReservationStatus"
                                @change="onReservationStatusChange"
                                class="focus:border-primary focus:ring-primary rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-1 focus:outline-none"
                            >
                                <option v-for="option in reservationStatusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu des onglets -->
            <div v-if="activeTab === 'candidatures'">
                <!-- Liste des candidatures -->
                <div v-if="applications.length > 0" class="space-y-6">
                    <div
                        v-for="application in applications"
                        :key="application.id"
                        class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
                    >
                        <div class="p-6">
                            <!-- En-tête de la candidature -->
                            <div class="mb-4 flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ application.ad.title }}
                                    </h3>
                                    <p class="mt-1 flex items-center gap-1 text-sm text-gray-600">
                                        <Calendar class="h-4 w-4" />
                                        {{ formatDate(application.ad.date_start) }} de {{ formatTime(application.ad.date_start) }} à
                                        {{ formatTime(application.ad.date_end) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span
                                        :class="getApplicationStatusColor(application.status).badge"
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                    >
                                        {{ getStatusText('application', application.status) }}
                                    </span>
                                    <div class="text-right">
                                        <div class="text-primary text-lg font-bold">
                                            {{ application.counter_rate || application.proposed_rate || application.ad.hourly_rate }}€/h
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Informations parent -->
                            <div class="mb-4 flex items-center gap-3 rounded-lg bg-gray-50 p-3">
                                <img
                                    :src="application.ad.parent.avatar || '/default-avatar.png'"
                                    :alt="application.ad.parent.name"
                                    class="h-10 w-10 rounded-full object-cover"
                                />
                                <div>
                                    <p class="font-medium text-gray-900">{{ application.ad.parent.name }}</p>
                                    <p class="text-sm text-gray-600">Parent</p>
                                </div>
                            </div>

                            <!-- Note de motivation si présente -->
                            <div v-if="application.motivation_note" class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3">
                                <p class="text-sm text-blue-800">"{{ application.motivation_note }}"</p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-3">
                                <button
                                    v-if="!isServicePast(application.ad)"
                                    @click="viewMessaging"
                                    class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                >
                                    <MessageCircle class="h-4 w-4" />
                                    Message
                                </button>
                                <button
                                    @click="viewDetails(application)"
                                    class="flex items-center gap-2 rounded-lg border border-blue-300 px-3 py-2 text-sm text-blue-700 hover:bg-blue-50"
                                >
                                    <Eye class="h-4 w-4" />
                                    Voir l'annonce
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="py-12 text-center">
                    <Briefcase class="mx-auto mb-4 h-12 w-12 text-gray-300" />
                    <h3 class="mb-2 text-lg font-medium text-gray-900">Aucune candidature</h3>
                    <p class="mb-4 text-gray-600">Vous n'avez encore postulé à aucune annonce</p>
                    <a
                        href="/annonces"
                        class="bg-primary hover:bg-primary/90 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors"
                    >
                        <Search class="h-4 w-4" />
                        Voir les annonces
                    </a>
                </div>
            </div>

            <div v-else-if="activeTab === 'reservations'">
                <!-- Liste des réservations -->
                <div v-if="reservations.length > 0" class="space-y-6">
                    <div
                        v-for="reservation in reservations"
                        :key="reservation.id"
                        class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
                    >
                        <div class="p-6">
                            <!-- En-tête de la réservation -->
                            <div class="mb-4 flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ reservation.ad.title }}
                                    </h3>
                                    <p class="mt-1 flex items-center gap-1 text-sm text-gray-600">
                                        <Calendar class="h-4 w-4" />
                                        {{ formatDate(reservation.service_start_at) }} de {{ formatTime(reservation.service_start_at) }} à
                                        {{ formatTime(reservation.service_end_at) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span
                                        :class="getReservationStatusColor(reservation.status).badge"
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                    >
                                        {{ getStatusText('reservation', reservation.status) }}
                                    </span>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-gray-900">{{ formatAmount(reservation.babysitter_amount) }}€</div>
                                        <div class="text-sm text-gray-600">{{ reservation.hourly_rate }}€/h</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Informations parent -->
                            <div class="mb-4 flex items-center gap-3 rounded-lg bg-gray-50 p-3">
                                <img
                                    :src="reservation.parent.avatar || '/default-avatar.png'"
                                    :alt="reservation.parent.name"
                                    class="h-10 w-10 rounded-full object-cover"
                                />
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ reservation.parent.name }}</p>
                                    <p class="text-sm text-gray-600">Parent</p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-3">
                                <button
                                    v-if="!isServicePast(reservation)"
                                    @click="viewMessaging"
                                    class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                >
                                    <MessageCircle class="h-4 w-4" />
                                    Message
                                </button>

                                <button
                                    v-if="reservation.can_review"
                                    @click="leaveReview(reservation.id)"
                                    class="flex items-center gap-2 rounded-lg border border-yellow-300 px-3 py-2 text-sm text-yellow-700 hover:bg-yellow-50"
                                >
                                    <Star class="h-4 w-4" />
                                    Laisser un avis
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="py-12 text-center">
                    <Calendar class="mx-auto mb-4 h-12 w-12 text-gray-300" />
                    <h3 class="mb-2 text-lg font-medium text-gray-900">Aucune réservation</h3>
                    <p class="mb-4 text-gray-600">Vos réservations de garde apparaîtront ici</p>
                    <a
                        href="/annonces"
                        class="bg-primary hover:bg-primary/90 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors"
                    >
                        <Search class="h-4 w-4" />
                        Parcourir les annonces
                    </a>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useStatusColors } from '@/composables/useStatusColors';
import { useToast } from '@/composables/useToast';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import type { Application, Filters, Reservation } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Briefcase, Calendar, CheckCircle, Clock, DollarSign, Eye, MessageCircle, Search, Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

interface Stats {
    total_applications: number;
    pending_applications: number;
    total_reservations: number;
    completed_reservations: number;
    total_earned: number;
}

interface Props {
    applications: Application[];
    reservations: Reservation[];
    stats: Stats;
    filters: Filters;
}

const props = defineProps<Props>();

const page = usePage();

// Récupérer les informations utilisateur depuis les props globales
const user = computed(() => (page.props.auth as any)?.user);
const userRoles = computed(() => user.value?.roles?.map((role: any) => role.name) || []);

const hasParentRole = computed(() => userRoles.value.includes('parent'));
const hasBabysitterRole = computed(() => userRoles.value.includes('babysitter'));

// Variables pour les filtres temporaires
const tempApplicationStatusFilter = ref(props.filters.application_status || 'all');
const tempReservationStatusFilter = ref(props.filters.reservation_status || 'all');
const tempDateFilter = ref(props.filters.date_filter || 'all');

// Utiliser le composable pour les couleurs de statut
const { getApplicationStatusColor, getReservationStatusColor, getStatusText } = useStatusColors();

// Options pour les filtres
const applicationStatusOptions = [
    { value: 'all', label: 'Tous les statuts' },
    { value: 'pending', label: 'En attente' },
    { value: 'counter_offered', label: 'Contre-offre' },
    { value: 'accepted', label: 'Acceptée' },
    { value: 'declined', label: 'Refusée' },
    { value: 'cancelled', label: 'Annulée' },
    { value: 'archived', label: 'Archivée' },
];

const reservationStatusOptions = [
    { value: 'all', label: 'Tous les statuts' },
    { value: 'pending_payment', label: 'En attente de paiement' },
    { value: 'paid', label: 'Payé' },
    { value: 'active', label: 'En cours' },
    { value: 'service_completed', label: 'Service terminé' },
    { value: 'completed', label: 'Terminé' },
    { value: 'cancelled', label: 'Annulé' },
];

const dateFilterOptions = [
    { value: 'all', label: 'Toutes les périodes' },
    { value: 'week', label: 'Cette semaine' },
    { value: 'month', label: 'Ce mois' },
    { value: 'year', label: 'Cette année' },
];

// État local
const activeTab = ref<'candidatures' | 'reservations'>('candidatures');

// Toast
const { showSuccess, showError } = useToast();

// Méthodes de formatage
const formatAmount = (amount: number) => {
    return Number(amount).toFixed(2);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('fr-FR');
};

const formatTime = (date: string) => {
    return new Date(date).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Fonction pour appliquer les filtres
const applyFilters = () => {
    const params: any = {
        application_status: tempApplicationStatusFilter.value !== 'all' ? tempApplicationStatusFilter.value : undefined,
        reservation_status: tempReservationStatusFilter.value !== 'all' ? tempReservationStatusFilter.value : undefined,
        date_filter: tempDateFilter.value !== 'all' ? tempDateFilter.value : undefined,
    };

    // Supprimer les paramètres undefined
    Object.keys(params).forEach((key) => params[key] === undefined && delete params[key]);

    router.get('/babysitting', params, {
        preserveState: false,
        preserveScroll: false,
    });
};

// Fonction pour vérifier si le service est passé
const isServicePast = (item: any) => {
    const endDate = item.service_end_at || item.date_end;
    return new Date(endDate) < new Date();
};

// Actions
const viewMessaging = () => {
    router.visit('/messagerie');
};

const viewDetails = (application: Application) => {
    window.open(`/annonce/${application.ad.id}`, '_blank');
};

const leaveReview = (reservationId: number) => {
    router.visit(`/avis/creer/${reservationId}`);
};
</script>
