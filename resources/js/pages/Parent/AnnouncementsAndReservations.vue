<template>
    <DashboardLayout :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole">
        <div class="min-h-screen bg-secondary py-8">
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
                                <StarIcon class="h-8 w-8 text-green-600" />
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
                                <Check class="h-8 w-8 text-purple-600" />
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
                                <Euro class="h-8 w-8 text-indigo-600" />
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
                                Mes annonces ({{ announcements.data.length }})
                            </button>
                            <button
                                @click="activeTab = 'reservations'"
                                :class="{
                                    'border-primary text-primary': activeTab === 'reservations',
                                    'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700': activeTab !== 'reservations',
                                }"
                                class="border-b-2 px-1 py-2 text-sm font-medium"
                            >
                                Mes réservations ({{ reservations.data.length }})
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="mt-6">
                    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Filtres</h3>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <!-- Filtre par date -->
                            <div class="space-y-2">
                                <Label class="text-sm font-medium text-gray-700">Période</Label>
                                <Select v-model="tempDateFilter">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Sélectionner une période" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in dateFilterOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Filtre par statut spécifique à l'onglet -->
                            <div v-if="activeTab === 'announcements'" class="space-y-2">
                                <Label class="text-sm font-medium text-gray-700">Statut d'annonce</Label>
                                <Select v-model="tempAnnouncementStatus">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Tous les statuts" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in announcementStatusOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div v-else-if="activeTab === 'reservations'" class="space-y-2">
                                <Label class="text-sm font-medium text-gray-700">Statut de réservation</Label>
                                <Select v-model="tempReservationStatus">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Tous les statuts" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in reservationStatusOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-end gap-2">
                                <Button @click="applyFilters" class="flex-1">
                                    <Filter class="mr-2 h-4 w-4" />
                                    Appliquer
                                </Button>
                                <Button @click="resetFilters" variant="outline"> Réinitialiser </Button>
                            </div>
                        </div>
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

                    <!-- Liste des annonces avec scroll infini -->
                    <InfiniteScroll
                        :pagination="announcements"
                        :route="'parent.announcements-reservations'"
                        :parameters="currentFilters"
                        loading-message="Chargement des annonces..."
                        end-message="Toutes les annonces ont été chargées"
                        @load-more="handleLoadMoreAnnouncements"
                        @error="handleError"
                    >
                        <div v-if="allAnnouncements.length > 0" class="space-y-6">
                            <div
                                v-for="announcement in allAnnouncements"
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
                                            <Badge variant="outline" :class="getAnnouncementStatusColor(announcement.status).badge">
                                                {{ getStatusText('announcement', announcement.status) }}
                                            </Badge>
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
                                            <Edit class="h-4 w-4" />
                                            Modifier
                                        </button>
                                        <button
                                            @click="viewAnnouncement(announcement)"
                                            class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                        >
                                            <Eye class="h-4 w-4" />
                                            Voir l'annonce
                                        </button>
                                        <button
                                            v-if="canCancelAnnouncement(announcement)"
                                            @click="showCancelAnnouncementModal(announcement)"
                                            class="flex items-center gap-2 rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700 hover:bg-red-50"
                                        >
                                            <X class="h-4 w-4" />
                                            Annuler l'annonce
                                        </button>
                                    </div>

                                    <!-- Candidatures -->
                                    <div
                                        v-if="announcement.applications && announcement.applications.length > 0"
                                        class="border-t border-gray-200 pt-4"
                                    >
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
                                                    <Badge variant="outline" :class="getApplicationStatusColor(application.status).badge">
                                                        {{ getStatusText('application', application.status) }}
                                                    </Badge>
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

                        <div v-if="announcements.data.length === 0" class="py-12 text-center">
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
                    </InfiniteScroll>
                </div>

                <div v-else-if="activeTab === 'reservations'">
                    <!-- Liste des réservations avec scroll infini -->
                    <InfiniteScroll
                        :pagination="reservations"
                        :route="'parent.announcements-reservations'"
                        :parameters="currentFilters"
                        loading-message="Chargement des réservations..."
                        end-message="Toutes les réservations ont été chargées"
                        @load-more="handleLoadMoreReservations"
                        @error="handleError"
                    >
                        <div v-if="allReservations.length > 0" class="space-y-6">
                            <div
                                v-for="reservation in allReservations"
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
                                            <Badge variant="outline" :class="getReservationStatusColor(reservation.status).badge">
                                                {{ getStatusText('reservation', reservation.status) }}
                                            </Badge>
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
                                            v-if="!isServicePast(reservation)"
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
                                            <StarIcon class="h-4 w-4" />
                                            Laisser un avis
                                        </button>

                                        <button
                                            v-if="reservation.status === 'pending_payment'"
                                            @click="proceedToPayment(reservation.id)"
                                            class="bg-primary hover:bg-primary/90 flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-white"
                                        >
                                            <Euro class="h-4 w-4" />
                                            Payer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="reservations.data.length === 0" class="py-12 text-center">
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
                    </InfiniteScroll>
                </div>
            </div>
        </div>

        <!-- Modal d'annulation d'annonce -->
        <ConfirmModal
            :open="showCancelModal"
            title="Annuler l'annonce"
            :description="getCancelModalMessage()"
            type="danger"
            @confirm="confirmCancelAnnouncement"
            @cancel="closeCancelModal"
            @update:open="(value) => (showCancelModal = value)"
        />
    </DashboardLayout>
</template>

<script setup lang="ts">
import InfiniteScroll from '@/components/InfiniteScroll.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useDateFormat } from '@/composables/useDateFormat';
import { useStatusColors } from '@/composables/useStatusColors';
import { useToast } from '@/composables/useToast';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import type { Announcement, Filters as BaseFilters, PaginatedData, Reservation } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Calendar, Check, Edit, Euro, Eye, FileText, Filter, MapPin, Plus, StarIcon, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

interface ExtendedReservation extends Omit<Reservation, 'babysitter'> {
    babysitter: {
        id: number;
        name: string;
        firstname?: string;
        lastname?: string;
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

interface Filters extends BaseFilters {
    announcement_status: string;
    reservation_status: string;
}

interface Props {
    announcements: PaginatedData<Announcement>;
    reservations: PaginatedData<ExtendedReservation>;
    stats: Stats;
    filters: Filters;
}

const props = defineProps<Props>();

// État local pour les données (incluant celles chargées via infinite scroll)
const allAnnouncements = ref([...props.announcements.data]);
const allReservations = ref([...props.reservations.data]);

const page = usePage();
const { showSuccess, showError } = useToast();
const { getAnnouncementStatusColor, getReservationStatusColor, getApplicationStatusColor, getStatusText } = useStatusColors();
const { formatDate: formatShortDate } = useDateFormat();

// Récupérer les informations utilisateur depuis les props globales
const user = computed(() => (page.props.auth as any)?.user);
const userRoles = computed(() => user.value?.roles?.map((role: any) => role.name) || []);

const hasParentRole = computed(() => userRoles.value.includes('parent'));
const hasBabysitterRole = computed(() => userRoles.value.includes('babysitter'));

// État local
const activeTab = ref<'announcements' | 'reservations'>('announcements');
const showCancelModal = ref(false);
const selectedAnnouncement = ref<Announcement | null>(null);

// Filtres initialisés depuis les props
const selectedAnnouncementStatus = ref<string>(props.filters.announcement_status || 'all');
const selectedReservationStatus = ref<string>(props.filters.reservation_status || 'all');
const selectedDateFilter = ref<string>(props.filters.date_filter || 'all');

// Temp variables for shadcn select
const tempAnnouncementStatus = ref<string>(selectedAnnouncementStatus.value);
const tempReservationStatus = ref<string>(selectedReservationStatus.value);
const tempDateFilter = ref<string>(selectedDateFilter.value);

// Filtres actuels pour InfiniteScroll
const currentFilters = computed(() => {
    return {
        announcement_status: tempAnnouncementStatus.value,
        reservation_status: tempReservationStatus.value,
        date_filter: tempDateFilter.value,
    };
});

// Options de filtres
const announcementStatusOptions = [
    { value: 'all', label: 'Toutes' },
    { value: 'active', label: 'Actives' },
    { value: 'booked', label: 'Réservées' },
    { value: 'service_completed', label: 'Service terminé' },
    { value: 'expired', label: 'Expirées' },
    { value: 'cancelled', label: 'Annulées' },
];

const reservationStatusOptions = [
    { value: 'all', label: 'Toutes' },
    { value: 'pending_payment', label: 'Paiement requis' },
    { value: 'paid', label: 'Confirmées' },
    { value: 'active', label: 'En cours' },
    { value: 'service_completed', label: 'Service terminé' },
    { value: 'completed', label: 'Terminées' },
    { value: 'cancelled_by_parent', label: 'Annulées par vous' },
    { value: 'cancelled_by_babysitter', label: 'Annulées par la babysitter' },
];

const dateFilterOptions = [
    { value: 'upcoming', label: 'Prochaines dates' },
    { value: 'past', label: 'Dates passées' },
    { value: 'all', label: 'Toutes les dates' },
];

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

// Toutes les fonctions de statut sont désormais dans useStatusColors

// Gestionnaires pour l'infinite scroll
const handleLoadMoreAnnouncements = (data: any) => {
    if (data.announcements && data.announcements.data) {
        allAnnouncements.value.push(...data.announcements.data);
    }
};

const handleLoadMoreReservations = (data: any) => {
    if (data.reservations && data.reservations.data) {
        allReservations.value.push(...data.reservations.data);
    }
};

const handleError = (error: string) => {
    console.error('Erreur infinite scroll:', error);
    showError('Erreur de chargement', 'Impossible de charger plus de données');
};

// Watchers pour réinitialiser les données quand les props changent
watch(
    () => props.announcements.data,
    (newData) => {
        allAnnouncements.value = [...newData];
    },
    { deep: true },
);

watch(
    () => props.reservations.data,
    (newData) => {
        allReservations.value = [...newData];
    },
    { deep: true },
);

// Actions
const viewMessaging = () => {
    router.visit('/messagerie');
};

const cancelReservation = (reservationId: number) => {
    const reservation = props.reservations.data.find((r: ExtendedReservation) => r.id === reservationId);
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
    router.visit(route('reviews.create', reservationId));
};

const proceedToPayment = (reservationId: number) => {
    router.visit(`/reservations/${reservationId}/payment`);
};

// Fonctions de filtrage
const applyFilters = () => {
    router.get(
        route('parent.announcements-reservations'),
        {
            announcement_status: tempAnnouncementStatus.value,
            reservation_status: tempReservationStatus.value,
            date_filter: tempDateFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const resetFilters = () => {
    tempAnnouncementStatus.value = 'all';
    tempReservationStatus.value = 'all';
    tempDateFilter.value = 'all';
    applyFilters();
};

// Fonctions de filtrage supprimées - maintenant avec bouton de confirmation

// Fonction pour vérifier si la garde est passée
const isServicePast = (reservation: ExtendedReservation) => {
    return new Date(reservation.service_end_at) < new Date();
};

// Nouvelles fonctions pour l'édition d'annonces
const canEditAnnouncement = (announcement: Announcement) => {
    // Une annonce peut être modifiée si :
    // - Elle est active
    // - Elle n'a pas de candidatures acceptées/confirmées
    // - La date n'est pas passée
    const hasAcceptedApplications = announcement.applications?.some((app) => ['accepted', 'counter_accepted'].includes(app.status)) || false;
    const isPastDate = new Date(announcement.date_start) < new Date();

    return announcement.status === 'active' && !hasAcceptedApplications && !isPastDate;
};

const editAnnouncement = (announcementId: number) => {
    router.visit(route('parent.announcements.edit', { announcement: announcementId }));
};

const viewAnnouncement = (announcement: Announcement) => {
    // Ouvrir l'annonce dans un nouvel onglet
    window.open(`/annonce/${announcement.id}`, '_blank');
};

// Méthodes pour l'annulation d'annonces
const canCancelAnnouncement = (announcement: Announcement) => {
    // Une annonce peut être annulée si elle est active
    return announcement.status === 'active';
};

const showCancelAnnouncementModal = (announcement: Announcement) => {
    selectedAnnouncement.value = announcement;
    showCancelModal.value = true;
};

const closeCancelModal = () => {
    showCancelModal.value = false;
    selectedAnnouncement.value = null;
};

const getCancelModalMessage = () => {
    if (!selectedAnnouncement.value) return '';

    const hasApplications = (selectedAnnouncement.value.applications?.length || 0) > 0;

    if (hasApplications) {
        return `Êtes-vous sûr de vouloir annuler l'annonce "${selectedAnnouncement.value.title}" ? Cela annulera également toutes les ${selectedAnnouncement.value.applications?.length || 0} candidature(s) associée(s) et les babysitters seront notifiées.`;
    }

    return `Êtes-vous sûr de vouloir annuler l'annonce "${selectedAnnouncement.value.title}" ?`;
};

const confirmCancelAnnouncement = () => {
    if (!selectedAnnouncement.value) return;

    router.post(
        route('announcements.cancel', selectedAnnouncement.value.id),
        {
            reason: 'no_longer_needed',
            note: 'Annulation depuis la page mes annonces',
        },
        {
            preserveState: true,
            onSuccess: (response) => {
                showSuccess('Annonce annulée avec succès');
                closeCancelModal();
                // Recharger la page pour mettre à jour les statuts
                router.reload();
            },
            onError: (errors) => {
                console.error("Erreur lors de l'annulation:", errors);
                showError("Erreur lors de l'annulation de l'annonce");
                closeCancelModal();
            },
        },
    );
};
</script>
