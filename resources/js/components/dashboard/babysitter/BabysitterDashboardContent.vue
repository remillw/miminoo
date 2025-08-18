<template>
    <div class="space-y-6">
        <!-- En-tête avec salutation et statut -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Bonjour, {{ user.firstname }}</h1>
                <p class="text-gray-600">Bienvenue sur votre tableau de bord</p>
            </div>
            <div
                class="flex items-center justify-between rounded-xl border border-gray-100 bg-white p-4 shadow-sm transition-all duration-200 hover:shadow-md"
            >
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1">
                            <div
                                class="mr-2 h-3 w-3 rounded-full transition-colors duration-200"
                                :class="isAvailable ? 'bg-emerald-500' : 'bg-red-500'"
                            ></div>
                            <span
                                class="text-sm font-medium transition-colors duration-200"
                                :class="isAvailable ? 'text-emerald-700' : 'text-gray-600'"
                            >
                                {{ isAvailable ? 'Disponible' : 'Indisponible' }}
                            </span>
                        </div>
                    </div>

                    <button
                        @click="toggleAvailability"
                        :class="isAvailable ? 'bg-gradient-to-r from-emerald-500 to-green-500' : 'bg-gray-200'"
                        class="relative inline-flex h-7 w-12 items-center rounded-full shadow-lg transition-all duration-300 ease-in-out hover:scale-105 active:scale-95"
                    >
                        <span
                            :class="isAvailable ? 'translate-x-6 bg-white' : 'translate-x-1 bg-white'"
                            class="inline-block h-5 w-5 transform rounded-full shadow-md transition-all duration-300 ease-in-out"
                        ></span>
                    </button>
                </div>
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

                    <div v-if="nextReservation" class="space-y-4">
                        <div class="flex items-start justify-between">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <Users class="h-4 w-4 text-gray-400" />
                                    <span class="font-medium text-gray-900">{{ nextReservation.parent_name }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Clock class="h-4 w-4 text-gray-400" />
                                    <span class="text-gray-700">
                                        {{ formatDateTime(nextReservation.service_start_at) }} -
                                        {{ formatTime(nextReservation.service_end_at) }}
                                    </span>
                                </div>
                                <div v-if="nextReservation.ad && nextReservation.ad.address" class="flex items-center gap-2">
                                    <MapPin class="h-4 w-4 text-gray-400" />
                                    <span class="text-gray-700">{{ nextReservation.ad.address.address }}, {{ nextReservation.ad.address.postal_code }}</span>
                                </div>
                            </div>
                            <button
                                @click="viewReservationDetails(nextReservation.id)"
                                class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                <Eye class="h-4 w-4" />
                                Voir l'annonce
                            </button>
                        </div>
                    </div>

                    <div v-else class="py-8 text-center text-gray-500">
                        <Calendar class="mx-auto mb-3 h-12 w-12 text-gray-300" />
                        <p>Aucune garde prévue</p>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="rounded-lg bg-white p-6 text-center shadow-sm">
                        <div class="text-2xl font-bold text-gray-900">{{ stats?.hours_this_month || 0 }}</div>
                        <div class="text-sm text-gray-600">Heures ce mois-ci</div>
                    </div>
                    <div class="rounded-lg bg-white p-6 text-center shadow-sm">
                        <div class="text-2xl font-bold text-gray-900">{{ stats?.earnings_this_month || 0 }}€</div>
                        <div class="text-sm text-gray-600">Gains ce mois</div>
                    </div>
                    <div class="rounded-lg bg-white p-6 text-center shadow-sm">
                        <div class="flex items-center justify-center gap-1">
                            <span class="text-2xl font-bold text-gray-900">{{ stats?.average_rating || 0 }}</span>
                            <Star class="h-5 w-5 fill-current text-yellow-400" />
                        </div>
                        <div class="text-sm text-gray-600">Note moyenne</div>
                    </div>
                </div>

                <!-- Dernières candidatures -->
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Vos dernières candidatures</h2>

                    <div v-if="recentAds?.length > 0" class="space-y-4">
                        <div v-for="ad in recentAds" :key="ad.id" class="flex items-center justify-between rounded-lg border border-gray-200 p-4">
                            <div class="space-y-1">
                                <h3 class="font-medium text-gray-900">{{ ad.title }}</h3>
                                <p class="text-sm text-gray-600">{{ formatDate(ad.date) }}, {{ ad.time }}</p>
                                <button @click="viewAdDetails(ad.id)" class="text-primary text-sm hover:text-orange-700">Voir les détails</button>
                            </div>
                            <div class="text-right">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="getAnnouncementStatusColor(ad.status).badge"
                                >
                                    {{ getStatusText('announcement', ad.status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-8 text-center text-gray-500">
                        <FileText class="mx-auto mb-3 h-12 w-12 text-gray-300" />
                        <p class="mb-3">Aucune candidature récente</p>
                        <a
                            href="/babysitters"
                            class="bg-primary hover:bg-primary/90 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors"
                        >
                            <Search class="h-4 w-4" />
                            Rechercher des annonces
                        </a>
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
                        v-if="notifications?.length > 0"
                        class="scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 max-h-80 space-y-3 overflow-y-auto"
                    >
                        <div
                            v-for="notification in notifications"
                            :key="notification.id"
                            class="group flex cursor-pointer items-start gap-3 rounded-lg p-3 hover:bg-gray-50"
                            :class="{ 'bg-blue-50': !notification.read_at }"
                        >
                            <div class="flex-shrink-0">
                                <Star v-if="notification.type === 'review_request'" class="h-5 w-5 text-yellow-500" />
                                <DollarSign v-else-if="notification.type === 'funds_released'" class="h-5 w-5 text-green-500" />
                                <AlertTriangle v-else-if="notification.type === 'dispute_created'" class="h-5 w-5 text-red-500" />
                                <Bell v-else class="h-5 w-5 text-blue-500" />
                            </div>
                            <div class="min-w-0 flex-1" @click="markAsRead(notification.id)">
                                <p class="text-sm text-gray-900" :class="{ 'font-medium': !notification.read_at }">
                                    {{ notification.title }}
                                </p>
                                <p class="text-xs text-gray-500">{{ formatTimeAgo(notification.created_at) }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <div v-if="!notification.read_at" class="h-2 w-2 rounded-full bg-blue-500"></div>
                                
                                <!-- Bouton pour voir l'annonce si c'est une notification d'annonce -->
                                <button
                                    v-if="notification.data?.ad_id && notification.data?.ad_slug"
                                    @click.stop="viewAnnouncement(notification.data.ad_id, notification.data.ad_slug)"
                                    class="rounded p-1 text-xs text-blue-500 opacity-0 transition-all group-hover:opacity-100 hover:bg-blue-100 hover:text-blue-700 hover:opacity-100"
                                    title="Voir l'annonce"
                                >
                                    <ExternalLink class="h-3 w-3" />
                                </button>
                                
                                <button
                                    v-if="!notification.read_at"
                                    @click.stop="markAsRead(notification.id)"
                                    class="rounded p-1 text-xs text-gray-400 opacity-0 transition-all group-hover:opacity-100 hover:bg-gray-200 hover:text-gray-600 hover:opacity-100"
                                    title="Marquer comme lu"
                                >
                                    ✓
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-4 text-center text-gray-500">
                        <Bell class="mx-auto mb-2 h-8 w-8 text-gray-300" />
                        <p class="text-sm">Aucune notification</p>
                    </div>
                </div>

                <!-- Derniers avis -->
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Derniers avis</h2>

                    <div v-if="recentReviews?.length > 0" class="space-y-4">
                        <div v-for="review in recentReviews.slice(0, 2)" :key="review.id" class="space-y-2">
                            <div class="flex items-center gap-1">
                                <Star
                                    v-for="star in 5"
                                    :key="star"
                                    class="h-4 w-4"
                                    :class="star <= review.rating ? 'fill-current text-yellow-400' : 'text-gray-300'"
                                />
                                <span class="ml-2 text-xs text-gray-500">Il y a {{ formatTimeAgo(review.created_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-700">"{{ review.comment }}"</p>
                            <p class="text-xs font-medium text-gray-900">{{ review.reviewer_name }}</p>
                        </div>
                    </div>

                    <div v-else class="py-4 text-center text-gray-500">
                        <Star class="mx-auto mb-2 h-8 w-8 text-gray-300" />
                        <p class="text-sm">Aucun avis pour le moment</p>
                    </div>
                </div>

                <!-- Avis à laisser -->
                <div v-if="completedReservations?.length > 0" class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center gap-2">
                        <Star class="h-5 w-5 text-yellow-500" />
                        <h2 class="text-lg font-semibold text-gray-900">Avis à laisser</h2>
                        <span class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                            {{ completedReservations.length }}
                        </span>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="reservation in completedReservations.slice(0, 3)"
                            :key="reservation.id"
                            class="rounded-lg border border-gray-100 bg-gray-50 p-3 transition-colors hover:bg-yellow-50"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <img
                                        :src="reservation.parent_avatar || '/default-avatar.svg'"
                                        :alt="reservation.parent_name"
                                        class="h-8 w-8 rounded-full object-cover"
                                    />
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">{{ reservation.parent_name }}</h3>
                                        <p class="text-xs text-gray-600">{{ formatDate(reservation.service_date) }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="createReview(reservation.id)"
                                    class="flex items-center gap-1 rounded-lg bg-yellow-500 px-3 py-1 text-xs font-medium text-white transition-colors hover:bg-yellow-600"
                                >
                                    <Star class="h-3 w-3" />
                                    Avis
                                </button>
                            </div>
                        </div>

                        <div v-if="completedReservations.length > 3" class="text-center">
                            <button @click="viewAllPendingReviews" class="text-xs text-yellow-600 hover:text-yellow-800">
                                Voir {{ completedReservations.length - 3 }} autres
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useStatusColors } from '@/composables/useStatusColors';
import { router } from '@inertiajs/vue3';
import { AlertTriangle, Bell, Calendar, Clock, DollarSign, ExternalLink, Eye, FileText, MapPin, Search, Star, Users } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps({
    user: Object,
    currentMode: String,
    babysitterProfile: Object,
    stats: Object,
    availability: Object,
    nextReservation: Object,
    recentAds: Array,
    notifications: Array,
    recentReviews: Array,
    completedReservations: {
        type: Array,
        default: () => [],
    },
});

// État local pour la disponibilité
const isAvailable = ref(props.availability?.is_available || false);

// Composables
const { getAnnouncementStatusColor, getStatusText } = useStatusColors();

// Méthodes
const toggleAvailability = async () => {
    try {
        const response = await fetch('/babysitter/toggle-availability', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json();

        if (data.success) {
            isAvailable.value = data.is_available;
            // Optionnel : afficher un message de succès
            console.log(data.message);
        } else {
            console.error('Erreur lors de la mise à jour de la disponibilité');
        }
    } catch (error) {
        console.error('Erreur réseau:', error);
    }
};

const formatDateTime = (dateString) => {
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

const formatTime = (dateString) => {
    return new Date(dateString).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
};

const formatTimeAgo = (dateString) => {
    const now = new Date();
    const date = new Date(dateString);
    const diffInMinutes = Math.floor((now - date) / (1000 * 60));

    if (diffInMinutes < 60) {
        return `${diffInMinutes} minutes`;
    } else if (diffInMinutes < 1440) {
        return `${Math.floor(diffInMinutes / 60)} heures`;
    } else {
        return `${Math.floor(diffInMinutes / 1440)} jours`;
    }
};

// Fonctions de statut maintenant dans useStatusColors

// Fonction pour créer le slug de l'annonce (identique aux autres fichiers)
function createAdSlug(ad) {
    if (!ad || !ad.id) {
        console.error('❌ Ad invalide pour slug:', ad);
        return 'annonce-inconnue';
    }

    // Reproduire exactement l'algorithme PHP
    let date = 'date-inconnue';
    if (ad.date_start) {
        try {
            // PHP: $ad->date_start->format('Y-m-d');
            date = new Date(ad.date_start).toISOString().split('T')[0]; // YYYY-MM-DD
        } catch (e) {
            console.error('❌ Erreur parsing date:', ad.date_start);
        }
    }

    // PHP: strtolower(preg_replace('/[^a-z0-9]/i', '-', $ad->title))
    const title = ad.title ? ad.title.toLowerCase().replace(/[^a-z0-9]/g, '-') : 'annonce';

    // PHP: trim($date . '-' . $title . '-' . $ad->id, '-')
    const slug = (date + '-' + title + '-' + ad.id).replace(/^-+|-+$/g, '');
    // PHP: preg_replace('/-+/', '-', $slug)
    const finalSlug = slug.replace(/-+/g, '-');

    return finalSlug;
}

const viewReservationDetails = (id) => {
    // Debug pour voir les données disponibles
    console.log('🔍 nextReservation:', props.nextReservation);
    console.log('🔍 nextReservation.ad:', props.nextReservation?.ad);
    
    // Chercher l'annonce associée à cette réservation
    if (props.nextReservation && props.nextReservation.ad) {
        const slug = createAdSlug(props.nextReservation.ad);
        console.log('🔍 Generated slug:', slug);
        const url = route('announcements.show', { slug });
        window.open(url, '_blank');
    } else {
        // Fallback vers la messagerie si pas d'annonce
        console.log('❌ Pas de données d\'annonce disponibles, redirection vers messagerie');
        router.visit('/messagerie');
    }
};

const viewAdDetails = (id) => {
    router.visit('/messagerie');
};

const markAsRead = async (notificationId) => {
    try {
        await router.post(
            `/notifications/${notificationId}/mark-as-read`,
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

const createReview = (reservationId) => {
    router.visit(`/avis/creer/${reservationId}`);
};

const viewAllPendingReviews = () => {
    router.visit('/avis');
};

const viewAnnouncement = (adId, adSlug) => {
    // Naviguer vers la page de l'annonce
    router.visit(`/annonce/${adId}${adSlug ? `/${adSlug}` : ''}`);
};

// Charger les données au montage
onMounted(() => {
    // Les données sont déjà chargées depuis le backend
});
</script>

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
