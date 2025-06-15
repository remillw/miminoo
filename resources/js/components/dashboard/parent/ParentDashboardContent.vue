<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Bell, Calendar, Clock, FileText, MessageCircle, Plus, Star, Users } from 'lucide-vue-next';
import { onMounted } from 'vue';
import { route } from 'ziggy-js';

interface Props {
    user: any;
    currentMode: string;
    parentProfile?: any;
    stats?: {
        active_ads: number;
        bookings_this_month: number;
        average_babysitter_rating: number;
    };
    nextReservation?: {
        id: number;
        babysitter_name: string;
        babysitter_avatar?: string;
        babysitter_rating: number;
        babysitter_reviews_count: number;
        service_start_at: string;
        service_end_at: string;
        status: string;
    };
    recentAds?: Array<{
        id: number;
        title: string;
        date: string;
        time: string;
        candidates_count: number;
        status: string;
    }>;
    notifications?: Array<{
        id: string;
        type: string;
        title: string;
        created_at: string;
        read_at?: string;
    }>;
    completedReservations?: Array<{
        id: number;
        babysitter_name: string;
        babysitter_avatar?: string;
        service_date: string;
        can_review: boolean;
    }>;
}

const props = withDefaults(defineProps<Props>(), {
    stats: () => ({ active_ads: 0, bookings_this_month: 0, average_babysitter_rating: 0 }),
    recentAds: () => [],
    notifications: () => [],
    completedReservations: () => [],
    nextReservation: () => null,
});

// Méthodes
const formatDateTime = (dateString: string) => {
    const date = new Date(dateString);
    return (
        date.toLocaleDateString('fr-FR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
        }) +
        ', ' +
        date.toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
        })
    );
};

const formatTime = (dateString: string) => {
    return new Date(dateString).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
};

const formatTimeAgo = (dateString: string) => {
    const now = new Date();
    const date = new Date(dateString);
    const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

    if (diffInMinutes < 1) {
        return "À l'instant";
    } else if (diffInMinutes < 60) {
        return `Il y a ${diffInMinutes} minute${diffInMinutes > 1 ? 's' : ''}`;
    } else if (diffInMinutes < 1440) {
        const hours = Math.floor(diffInMinutes / 60);
        return `Il y a ${hours} heure${hours > 1 ? 's' : ''}`;
    } else {
        const days = Math.floor(diffInMinutes / 1440);
        return `Il y a ${days} jour${days > 1 ? 's' : ''}`;
    }
};

const getStatusClass = (status: string) => {
    const classes: Record<string, string> = {
        active: 'bg-green-100 text-green-800',
        completed: 'bg-gray-100 text-gray-800',
        cancelled: 'bg-red-100 text-red-800',
        booked: 'bg-blue-100 text-blue-800',
        pending_payment: 'bg-orange-100 text-orange-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusText = (status: string) => {
    const texts: Record<string, string> = {
        active: 'Active',
        completed: 'Terminée',
        cancelled: 'Annulée',
        booked: 'Réservée',
        pending_payment: 'En attente de paiement',
    };
    return texts[status] || status;
};

const viewReservationDetails = (id: number) => {
    router.visit(route('messaging.index'));
};

const viewAdDetails = (id: number) => {
    router.visit(route('messaging.index'));
};

const createReview = (reservationId: number) => {
    router.visit(route('reviews.create', reservationId));
};

const markAsRead = async (notificationId: string) => {
    try {
        await router.post(
            route('notifications.mark-as-read', notificationId),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    // Recharger les notifications
                    router.reload({ only: ['notifications'] });
                },
            },
        );
    } catch (error) {
        console.error('Erreur lors du marquage de la notification:', error);
    }
};

// Charger les données au montage
onMounted(() => {
    // Les données sont déjà chargées depuis le backend
});
</script>

<template>
    <div class="space-y-6">
        <!-- En-tête avec salutation -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Bonjour, {{ props.user.firstname }}</h1>
                <p class="text-gray-600">Bienvenue sur votre tableau de bord</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Colonne principale -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Prochaine garde -->
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center gap-2">
                        <Calendar class="h-5 w-5 text-orange-500" />
                        <h2 class="text-lg font-semibold text-gray-900">Prochaine garde</h2>
                    </div>

                    <div v-if="props.nextReservation" class="space-y-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-4">
                                <img
                                    :src="props.nextReservation.babysitter_avatar || '/default-avatar.svg'"
                                    :alt="props.nextReservation.babysitter_name"
                                    class="h-16 w-16 rounded-full object-cover"
                                />
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900">{{ props.nextReservation.babysitter_name }}</span>
                                        <div class="flex items-center gap-1">
                                            <Star class="h-4 w-4 fill-current text-yellow-400" />
                                            <span class="text-sm text-gray-600"
                                                >{{ props.nextReservation.babysitter_rating }} ({{
                                                    props.nextReservation.babysitter_reviews_count
                                                }}
                                                avis)</span
                                            >
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Clock class="h-4 w-4 text-gray-400" />
                                        <span class="text-gray-700">
                                            {{ formatDateTime(props.nextReservation.service_start_at) }} -
                                            {{ formatTime(props.nextReservation.service_end_at) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button
                                @click="viewReservationDetails(props.nextReservation.id)"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                Voir les détails
                            </button>
                        </div>
                    </div>

                    <div v-else class="py-8 text-center text-gray-500">
                        <Calendar class="mx-auto mb-3 h-12 w-12 text-gray-300" />
                        <p class="mb-3">Aucune garde prévue</p>
                        <a
                            href="/annonces/create"
                            class="bg-primary hover:bg-primary/90 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors"
                        >
                            <Plus class="h-4 w-4" />
                            Créer une annonce
                        </a>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="rounded-lg bg-white p-6 text-center shadow-sm">
                        <div class="text-2xl font-bold text-gray-900">{{ props.stats?.active_ads || 0 }}</div>
                        <div class="text-sm text-gray-600">Annonces actives</div>
                    </div>
                    <div class="rounded-lg bg-white p-6 text-center shadow-sm">
                        <div class="text-2xl font-bold text-gray-900">{{ props.stats?.bookings_this_month || 0 }}</div>
                        <div class="text-sm text-gray-600">Gardes ce mois</div>
                    </div>
                    <div class="rounded-lg bg-white p-6 text-center shadow-sm">
                        <div class="flex items-center justify-center gap-1">
                            <span class="text-2xl font-bold text-gray-900">{{ props.stats?.average_babysitter_rating || 0 }}</span>
                            <Star class="h-5 w-5 fill-current text-yellow-400" />
                        </div>
                        <div class="text-sm text-gray-600">Avis des babysitters</div>
                    </div>
                </div>

                <!-- Dernières annonces -->
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Vos dernières annonces</h2>

                    <div v-if="props.recentAds.length > 0" class="space-y-4">
                        <div
                            v-for="ad in props.recentAds"
                            :key="ad.id"
                            class="flex items-center justify-between rounded-lg border border-gray-200 p-4"
                        >
                            <div class="space-y-1">
                                <h3 class="font-medium text-gray-900">{{ ad.title }}</h3>
                                <p class="text-sm text-gray-600">{{ formatDate(ad.date) }}, {{ ad.time }}</p>
                                <button @click="viewAdDetails(ad.id)" class="text-sm text-orange-600 hover:text-orange-700">Voir les détails</button>
                            </div>
                            <div class="text-right">
                                <div class="mb-2">
                                    <span class="text-sm font-medium text-gray-900"
                                        >{{ ad.candidates_count }} candidature{{ ad.candidates_count > 1 ? 's' : '' }}</span
                                    >
                                </div>
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="getStatusClass(ad.status)"
                                >
                                    {{ getStatusText(ad.status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-8 text-center text-gray-500">
                        <FileText class="mx-auto mb-3 h-12 w-12 text-gray-300" />
                        <p class="mb-3">Aucune annonce récente</p>
                        <a
                            href="/annonces/create"
                            class="bg-primary hover:bg-primary/90 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors"
                        >
                            <Plus class="h-4 w-4" />
                            Créer votre première annonce
                        </a>
                    </div>
                </div>

                <!-- Avis à laisser -->
                <div v-if="props.completedReservations && props.completedReservations.length > 0" class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Avis à laisser</h2>

                    <div class="space-y-4">
                        <div
                            v-for="reservation in props.completedReservations"
                            :key="reservation.id"
                            class="flex items-center justify-between rounded-lg border border-gray-200 p-4"
                        >
                            <div class="flex items-center gap-4">
                                <img
                                    :src="reservation.babysitter_avatar || '/default-avatar.svg'"
                                    :alt="reservation.babysitter_name"
                                    class="h-12 w-12 rounded-full object-cover"
                                />
                                <div>
                                    <h3 class="font-medium text-gray-900">{{ reservation.babysitter_name }}</h3>
                                    <p class="text-sm text-gray-600">Garde du {{ formatDate(reservation.service_date) }}</p>
                                </div>
                            </div>
                            <button
                                @click="createReview(reservation.id)"
                                class="inline-flex items-center gap-2 rounded-lg bg-yellow-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-yellow-600"
                            >
                                <Star class="h-4 w-4" />
                                Laisser un avis
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colonne latérale -->
            <div class="space-y-6">
                <!-- Notifications -->
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center gap-2">
                        <Bell class="h-5 w-5 text-orange-500" />
                        <h2 class="text-lg font-semibold text-gray-900">Notifications</h2>
                    </div>

                    <div
                        v-if="props.notifications.length > 0"
                        class="scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 max-h-80 space-y-3 overflow-y-auto"
                    >
                        <div
                            v-for="notification in props.notifications"
                            :key="notification.id"
                            class="flex cursor-pointer items-start gap-3 rounded-lg p-3 hover:bg-gray-50"
                            :class="{ 'bg-blue-50': !notification.read_at }"
                            @click="markAsRead(notification.id)"
                        >
                            <div class="flex-shrink-0">
                                <MessageCircle v-if="notification.type === 'new_message'" class="h-5 w-5 text-blue-500" />
                                <Users v-else-if="notification.type === 'new_application'" class="h-5 w-5 text-green-500" />
                                <Star v-else-if="notification.type === 'review_request'" class="h-5 w-5 text-yellow-500" />
                                <Bell v-else class="h-5 w-5 text-gray-500" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-900" :class="{ 'font-medium': !notification.read_at }">
                                    {{ notification.title }}
                                </p>
                                <p class="text-xs text-gray-500">{{ formatTimeAgo(notification.created_at) }}</p>
                            </div>
                            <div v-if="!notification.read_at" class="h-2 w-2 rounded-full bg-blue-500"></div>
                        </div>
                    </div>

                    <div v-else class="py-4 text-center text-gray-500">
                        <Bell class="mx-auto mb-2 h-8 w-8 text-gray-300" />
                        <p class="text-sm">Aucune notification</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Styles pour le scrollbar personnalisé */
.scrollbar-thin {
    scrollbar-width: thin;
}

.scrollbar-thumb-gray-300::-webkit-scrollbar {
    width: 6px;
}

.scrollbar-thumb-gray-300::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 3px;
}

.scrollbar-thumb-gray-300::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.scrollbar-thumb-gray-300::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
