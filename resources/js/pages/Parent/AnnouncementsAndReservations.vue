<template>
    <DashboardLayout :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole">
        <div class="min-h-screen bg-gray-50 py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Mes annonces et réservations</h1>
                    <p class="mt-2 text-gray-600">Gérez vos annonces de garde et suivez vos réservations</p>
                </div>

                <!-- Statistiques -->
                <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-5">
                    <div class="rounded-lg bg-white p-6 shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <FileText class="h-8 w-8 text-blue-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Annonces totales</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.total_announcements }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <Star class="h-8 w-8 text-green-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Annonces actives</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.active_announcements }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <Calendar class="h-8 w-8 text-orange-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Réservations</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.total_reservations }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <CheckCircle class="h-8 w-8 text-purple-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Gardes terminées</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.completed_reservations }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <CreditCard class="h-8 w-8 text-indigo-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total dépensé</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatAmount(stats.total_spent) }}€</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Onglets -->
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button
                                @click="activeTab = 'announcements'"
                                :class="{
                                    'border-primary text-primary': activeTab === 'announcements',
                                    'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': activeTab !== 'announcements',
                                }"
                                class="border-b-2 px-1 py-2 text-sm font-medium"
                            >
                                Mes annonces ({{ announcements.length }})
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
                        </nav>
                    </div>
                </div>

                <!-- Contenu des onglets -->
                <div v-if="activeTab === 'announcements'">
                    <!-- Bouton créer une annonce -->
                    <div class="mb-6">
                        <a
                            href="/creer-une-annonce"
                            class="bg-primary hover:bg-primary/90 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors"
                        >
                            <Plus class="h-4 w-4" />
                            Créer une nouvelle annonce
                        </a>
                    </div>

                    <!-- Liste des annonces -->
                    <div v-if="announcements.length > 0" class="space-y-6">
                        <div
                            v-for="announcement in announcements"
                            :key="announcement.id"
                            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
                        >
                            <div class="p-6">
                                <!-- En-tête de l'annonce -->
                                <div class="mb-4 flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            {{ announcement.title }}
                                        </h3>
                                        <p class="mt-1 flex items-center gap-1 text-sm text-gray-600">
                                            <Calendar class="h-4 w-4" />
                                            {{ formatDate(announcement.date_start) }} de {{ formatTime(announcement.date_start) }} à
                                            {{ formatTime(announcement.date_end) }}
                                        </p>
                                        <p class="flex items-center gap-1 text-sm text-gray-600">
                                            <MapPin class="h-4 w-4" />
                                            {{ announcement.address.address }}, {{ announcement.address.postal_code }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span
                                            :class="getAnnouncementStatusClass(announcement.status)"
                                            class="rounded-full px-2 py-1 text-xs font-medium"
                                        >
                                            {{ getAnnouncementStatusText(announcement.status) }}
                                        </span>
                                        <div class="text-right">
                                            <div class="text-primary text-lg font-bold">{{ announcement.hourly_rate }}€/h</div>
                                            <div class="text-sm text-gray-600">
                                                {{ announcement.estimated_duration }}h • {{ announcement.estimated_total }}€ total
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions de l'annonce -->
                                <div class="mb-4 flex items-center gap-3">
                                    <button
                                        v-if="canEditAnnouncement(announcement)"
                                        @click="editAnnouncement(announcement.id)"
                                        class="flex items-center gap-2 rounded-lg border border-blue-300 px-3 py-2 text-sm text-blue-700 hover:bg-blue-50"
                                    >
                                        <Edit2 class="h-4 w-4" />
                                        Modifier
                                    </button>
                                    <button
                                        @click="viewAnnouncement(announcement)"
                                        class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                    >
                                        <Eye class="h-4 w-4" />
                                        Voir l'annonce
                                    </button>
                                </div>

                                <!-- Candidatures -->
                                <div v-if="announcement.applications.length > 0" class="border-t border-gray-200 pt-4">
                                    <h4 class="mb-3 text-sm font-medium text-gray-900">
                                        {{ announcement.applications.length }} candidature{{ announcement.applications.length > 1 ? 's' : '' }}
                                    </h4>
                                    <div class="space-y-3">
                                        <div
                                            v-for="application in announcement.applications"
                                            :key="application.id"
                                            class="flex items-center justify-between rounded-lg bg-gray-50 p-3"
                                        >
                                            <div class="flex items-center gap-3">
                                                <img
                                                    :src="application.babysitter.avatar || '/default-avatar.png'"
                                                    :alt="application.babysitter.name"
                                                    class="h-10 w-10 rounded-full object-cover"
                                                />
                                                <div>
                                                    <p class="font-medium text-gray-900">{{ application.babysitter.name }}</p>
                                                    <p class="text-sm text-gray-600">
                                                        Propose {{ application.counter_rate || application.proposed_rate }}€/h
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span
                                                    :class="getApplicationStatusClass(application.status)"
                                                    class="rounded-full px-2 py-1 text-xs font-medium"
                                                >
                                                    {{ getApplicationStatusText(application.status) }}
                                                </span>
                                                <button @click="viewMessaging" class="text-primary hover:text-primary/80 text-sm font-medium">
                                                    Voir la conversation
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="border-t border-gray-200 pt-4">
                                    <p class="text-sm text-gray-500">Aucune candidature pour le moment</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-12 text-center">
                        <FileText class="mx-auto mb-4 h-12 w-12 text-gray-300" />
                        <h3 class="mb-2 text-lg font-medium text-gray-900">Aucune annonce</h3>
                        <p class="mb-4 text-gray-600">Créez votre première annonce pour trouver une babysitter</p>
                        <a
                            href="/creer-une-annonce"
                            class="bg-primary hover:bg-primary/90 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors"
                        >
                            <Plus class="h-4 w-4" />
                            Créer ma première annonce
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
                                            <div class="text-lg font-bold text-gray-900">{{ formatAmount(reservation.total_deposit) }}€</div>
                                            <div class="text-sm text-gray-600">{{ reservation.hourly_rate }}€/h</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Informations babysitter -->
                                <div class="mb-4 flex items-center gap-3 rounded-lg bg-gray-50 p-3">
                                    <img
                                        :src="reservation.babysitter.avatar || '/default-avatar.png'"
                                        :alt="reservation.babysitter.name"
                                        class="h-12 w-12 rounded-full object-cover"
                                    />
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900">{{ reservation.babysitter.name }}</p>
                                        <p class="text-sm text-gray-600">Babysitter</p>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-3">
                                    <button
                                        @click="viewMessaging"
                                        class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                    >
                                        <MessageCircle class="h-4 w-4" />
                                        Message
                                    </button>

                                    <button
                                        v-if="reservation.can_be_cancelled && reservation.status === 'paid'"
                                        @click="cancelReservation(reservation.id)"
                                        class="flex items-center gap-2 rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700 hover:bg-red-50"
                                    >
                                        <X class="h-4 w-4" />
                                        Annuler
                                    </button>

                                    <button
                                        v-if="reservation.can_be_reviewed || reservation.status === 'service_completed'"
                                        @click="leaveReview(reservation.id)"
                                        class="flex items-center gap-2 rounded-lg border border-yellow-300 px-3 py-2 text-sm text-yellow-700 hover:bg-yellow-50"
                                    >
                                        <Star class="h-4 w-4" />
                                        Laisser un avis
                                    </button>

                                    <button
                                        v-if="reservation.status === 'pending_payment'"
                                        @click="proceedToPayment(reservation.id)"
                                        class="bg-primary hover:bg-primary/90 flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-white"
                                    >
                                        <CreditCard class="h-4 w-4" />
                                        Payer
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
                            Parcourir les babysitters
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { Calendar, CheckCircle, CreditCard, Edit2, Eye, FileText, MapPin, MessageCircle, Plus, Search, Star, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Announcement {
    id: number;
    title: string;
    date_start: string;
    date_end: string;
    hourly_rate: number;
    status: string;
    applications_count: number;
    applications: Application[];
    estimated_duration: number;
    estimated_total: number;
    address: {
        address: string;
        postal_code: string;
    };
}

interface Application {
    id: number;
    status: string;
    proposed_rate: number;
    counter_rate?: number;
    babysitter: {
        id: number;
        name: string;
        avatar?: string;
    };
}

interface Reservation {
    id: number;
    status: string;
    hourly_rate: number;
    deposit_amount: number;
    service_fee: number;
    total_deposit: number;
    babysitter_amount: number;
    service_start_at: string;
    service_end_at: string;
    paid_at?: string;
    can_be_cancelled: boolean;
    can_be_reviewed: boolean;
    babysitter: {
        id: number;
        name: string;
        avatar?: string;
    };
    ad: {
        id: number;
        title: string;
        date_start: string;
        date_end: string;
    };
}

interface Stats {
    total_announcements: number;
    active_announcements: number;
    total_reservations: number;
    completed_reservations: number;
    total_spent: number;
}

interface Props {
    announcements: Announcement[];
    reservations: Reservation[];
    stats: Stats;
}

const props = defineProps<Props>();

const page = usePage();

// Récupérer les informations utilisateur depuis les props globales
const user = computed(() => (page.props.auth as any)?.user);
const userRoles = computed(() => user.value?.roles?.map((role: any) => role.name) || []);

const hasParentRole = computed(() => userRoles.value.includes('parent'));
const hasBabysitterRole = computed(() => userRoles.value.includes('babysitter'));

// État local
const activeTab = ref<'announcements' | 'reservations'>('announcements');

// Méthodes de formatage
const formatAmount = (amount: number) => {
    // Les montants sont déjà en euros dans l'application, pas en centimes
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
const getAnnouncementStatusClass = (status: string) => {
    const classes: { [key: string]: string } = {
        active: 'bg-green-100 text-green-800',
        booked: 'bg-blue-100 text-blue-800',
        service_completed: 'bg-purple-100 text-purple-800',
        completed: 'bg-gray-100 text-gray-800',
        expired: 'bg-orange-100 text-orange-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getAnnouncementStatusText = (status: string) => {
    const texts: { [key: string]: string } = {
        active: 'Active',
        booked: 'Réservée',
        service_completed: 'Service terminé',
        completed: 'Terminée',
        expired: 'Expirée',
        cancelled: 'Annulée',
    };
    return texts[status] || status;
};

const getApplicationStatusClass = (status: string) => {
    const classes: { [key: string]: string } = {
        pending: 'bg-yellow-100 text-yellow-800',
        accepted: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        counter_offered: 'bg-blue-100 text-blue-800',
        counter_accepted: 'bg-green-100 text-green-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getApplicationStatusText = (status: string) => {
    const texts: { [key: string]: string } = {
        pending: 'En attente',
        accepted: 'Acceptée',
        rejected: 'Refusée',
        counter_offered: 'Contre-offre reçue',
        counter_accepted: 'Contre-offre acceptée',
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
        cancelled_by_parent: 'Annulée par vous',
        cancelled_by_babysitter: 'Annulée par la babysitter',
    };
    return texts[status] || status;
};

// Actions
const viewMessaging = () => {
    router.visit('/messagerie');
};

const cancelReservation = (reservationId: number) => {
    const reservation = props.reservations.find((r: Reservation) => r.id === reservationId);
    if (!reservation) return;

    // Vérifier si c'est une annulation tardive (moins de 24h)
    const hoursUntilService = new Date(reservation.service_start_at).getTime() - new Date().getTime();
    const isLateCancel = hoursUntilService < 24 * 60 * 60 * 1000;

    const confirmMessage = isLateCancel
        ? 'ATTENTION : Vous annulez moins de 24h avant le service. Votre acompte sera définitivement perdu. Êtes-vous sûr ?'
        : "Êtes-vous sûr de vouloir annuler cette réservation ? Des frais peuvent s'appliquer.";

    if (confirm(confirmMessage)) {
        router.post(`/reservations/${reservationId}/cancel`, {
            reason: 'parent_unavailable',
            note: '',
        });
    }
};

const leaveReview = (reservationId: number) => {
    router.visit(`/reviews/create/${reservationId}`);
};

const proceedToPayment = (reservationId: number) => {
    router.visit(`/reservations/${reservationId}/payment`);
};

// Nouvelles fonctions pour l'édition d'annonces
const canEditAnnouncement = (announcement: Announcement) => {
    // Une annonce peut être modifiée si :
    // - Elle est active
    // - Elle n'a pas de candidatures acceptées/confirmées
    // - La date n'est pas passée
    const hasAcceptedApplications = announcement.applications.some((app) => ['accepted', 'counter_accepted'].includes(app.status));
    const isPastDate = new Date(announcement.date_start) < new Date();

    return announcement.status === 'active' && !hasAcceptedApplications && !isPastDate;
};

const editAnnouncement = (announcementId: number) => {
    router.visit(`/annonces/${announcementId}/modifier`);
};

const viewAnnouncement = (announcement: Announcement) => {
    // Ouvrir l'annonce dans un nouvel onglet
    window.open(`/annonce/${announcement.id}`, '_blank');
};
</script>
