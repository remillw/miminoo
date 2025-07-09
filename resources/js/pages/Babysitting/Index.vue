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

            <!-- Navigation des onglets -->
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
                                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
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
                                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
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
                                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
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
                                        :class="getApplicationStatusClass(application.status)"
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                    >
                                        {{ getApplicationStatusText(application.status) }}
                                    </span>
                                    <div class="text-right">
                                        <div class="text-primary text-lg font-bold">{{ application.counter_rate || application.proposed_rate || application.ad.hourly_rate }}€/h</div>
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
                                        :class="getReservationStatusClass(reservation.status)"
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                    >
                                        {{ getReservationStatusText(reservation.status) }}
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
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { useToast } from '@/composables/useToast';
import { router, usePage, Head } from '@inertiajs/vue3';
import { Calendar, CheckCircle, Clock, Briefcase, MessageCircle, Search, Star, Eye, DollarSign } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

interface Application {
    id: number;
    status: string;
    proposed_rate?: number;
    counter_rate?: number;
    motivation_note?: string;
    created_at: string;
    ad: {
        id: number;
        title: string;
        date_start: string;
        date_end: string;
        hourly_rate: number;
        parent: {
            id: number;
            name: string;
            avatar?: string;
        };
    };
}

interface Reservation {
    id: number;
    status: string;
    hourly_rate: number;
    service_start_at: string;
    service_end_at: string;
    babysitter_amount: number;
    babysitter_reviewed: boolean;
    can_review: boolean;
    ad: {
        id: number;
        title: string;
    };
    parent: {
        id: number;
        name: string;
        avatar?: string;
    };
}

interface Stats {
    total_applications: number;
    pending_applications: number;
    total_reservations: number;
    completed_reservations: number;
    total_earned: number;
}

interface Filters {
    application_status: string;
    reservation_status: string;
    date_filter: string;
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

// État local
const activeTab = ref<'candidatures' | 'reservations'>('candidatures');

// Filtres initialisés depuis les props
const selectedApplicationStatus = ref<string>(props.filters.application_status);
const selectedReservationStatus = ref<string>(props.filters.reservation_status);
const selectedDateFilter = ref<string>(props.filters.date_filter);

// Toast
const { showSuccess, showError } = useToast();

// Options de filtres
const applicationStatusOptions = [
    { value: 'all', label: 'Toutes' },
    { value: 'pending', label: 'En attente' },
    { value: 'accepted', label: 'Acceptées' },
    { value: 'rejected', label: 'Refusées' },
    { value: 'counter_offered', label: 'Contre-offres' },
    { value: 'archived', label: 'Archivées' },
];

const reservationStatusOptions = [
    { value: 'all', label: 'Toutes' },
    { value: 'pending_payment', label: 'Paiement requis' },
    { value: 'paid', label: 'Confirmées' },
    { value: 'active', label: 'En cours' },
    { value: 'service_completed', label: 'Service terminé' },
    { value: 'completed', label: 'Terminées' },
    { value: 'cancelled_by_parent', label: 'Annulées par le parent' },
    { value: 'cancelled_by_babysitter', label: 'Annulées par moi' },
];

const dateFilterOptions = [
    { value: 'upcoming', label: 'Prochaines dates' },
    { value: 'past', label: 'Dates passées' },
    { value: 'all', label: 'Toutes les dates' },
];

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

// Méthodes pour les classes de statut
const getApplicationStatusClass = (status: string) => {
    const classes: { [key: string]: string } = {
        pending: 'bg-yellow-100 text-yellow-800',
        accepted: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        counter_offered: 'bg-blue-100 text-blue-800',
        archived: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getApplicationStatusText = (status: string) => {
    const texts: { [key: string]: string } = {
        pending: 'En attente',
        accepted: 'Acceptée',
        rejected: 'Refusée',
        counter_offered: 'Contre-offre',
        archived: 'Archivée',
    };
    return texts[status] || status;
};

const getReservationStatusClass = (status: string) => {
    const classes: { [key: string]: string } = {
        pending_payment: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-blue-100 text-blue-800',
        active: 'bg-green-100 text-green-800',
        service_completed: 'bg-purple-100 text-purple-800',
        completed: 'bg-gray-100 text-gray-800',
        cancelled_by_parent: 'bg-red-100 text-red-800',
        cancelled_by_babysitter: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getReservationStatusText = (status: string) => {
    const texts: { [key: string]: string } = {
        pending_payment: 'Paiement requis',
        paid: 'Confirmée',
        active: 'En cours',
        service_completed: 'Service terminé',
        completed: 'Terminée',
        cancelled_by_parent: 'Annulée par le parent',
        cancelled_by_babysitter: 'Annulée par moi',
    };
    return texts[status] || status;
};

// Fonctions de filtrage
const applyFilters = () => {
    router.get(route('babysitting.index'), {
        application_status: selectedApplicationStatus.value,
        reservation_status: selectedReservationStatus.value,
        date_filter: selectedDateFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const onApplicationStatusChange = () => {
    applyFilters();
};

const onReservationStatusChange = () => {
    applyFilters();
};

const onDateFilterChange = () => {
    applyFilters();
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
    router.visit(route('reviews.create', reservationId));
};
</script>
