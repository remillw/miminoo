<script setup lang="ts">
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useStatusColors } from '@/composables/useStatusColors';
import type { User, Review, DashboardStats } from '@/types';

interface ParentWithStats extends User {
    averageRating: () => number;
    totalReviews: () => number;
    reviewsCount?: number;
    averageRatingValue?: number;
}

interface Props {
    parent: ParentWithStats;
    reviews: Review[];
    averageRating: number;
    totalReviews: number;
}

const props = defineProps<Props>();

// Variable pour la modal d'avis
const showAllReviews = ref(false);

// Utiliser le composable pour les couleurs
const { getStatusText } = useStatusColors();

// Obtenir la ville depuis l'adresse
const getCity = (address?: Address) => {
    if (!address) return '';
    const parts = address.address.split(',');
    return parts[parts.length - 1]?.trim() || '';
};

// Formater les dates
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDateShort = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
    });
};

// Calculer le temps depuis la création
const memberSince = computed(() => {
    const years = new Date().getFullYear() - parseInt(props.parent.member_since);
    if (years === 0) return 'Nouveau membre';
    return `Membre depuis ${years} ${years > 1 ? 'ans' : 'an'}`;
});

// Couleurs pour les statuts d'annonces
const getAdStatusClass = (status: string) => {
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

const getAdStatusText = (status: string) => {
    switch (status) {
        case 'active':
            return 'Active';
        case 'completed':
            return 'Terminée';
        case 'cancelled':
            return 'Annulée';
        default:
            return 'Inconnue';
    }
};

// Fonction pour formater la date des avis
const formatReviewDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>

<template>
    <Head :title="`${parent.firstname} ${parent.lastname} - Profil Parent`" />

    <GlobalLayout>
        <div class="bg-secondary min-h-screen p-4">
            <div class="mx-auto grid max-w-6xl grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Section principale (colonne gauche) -->
                <div class="space-y-6">
                    <!-- Header Profile -->
                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <div class="flex items-start space-x-6">
                            <div class="relative">
                                <img
                                    :src="parent.avatar || '/storage/default-avatar.png'"
                                    :alt="`${parent.firstname} ${parent.lastname}`"
                                    class="h-24 w-24 rounded-full border-4 border-white object-cover shadow-lg"
                                />
                                <!-- Badge parent -->
                                <div
                                    class="absolute -right-1 -bottom-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-blue-500"
                                >
                                    <svg class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="mb-2 flex items-center space-x-3">
                                    <h1 class="text-2xl font-bold text-gray-900">{{ parent.firstname }} {{ parent.lastname }}</h1>
                                    <div class="flex items-center space-x-1 text-blue-600">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                        <span class="text-sm font-medium">Parent vérifié</span>
                                    </div>
                                </div>

                                <p class="mb-3 text-sm text-gray-600">
                                    <span>{{ memberSince }}</span>
                                    <span v-if="parent.address"> | {{ getCity(parent.address) }}</span>
                                </p>

                                <!-- Note moyenne et nombre d'avis -->
                                <div v-if="reviews && reviews.length > 0" class="mb-3 flex items-center space-x-4">
                                    <div class="flex items-center space-x-1">
                                        <div class="flex space-x-1">
                                            <template v-for="i in 5" :key="i">
                                                <svg 
                                                    class="h-4 w-4" 
                                                    :class="i <= Math.round(averageRating || 0) ? 'text-yellow-400' : 'text-gray-300'"
                                                    fill="currentColor" 
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            </template>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ averageRating?.toFixed(1) || 0 }}</span>
                                        <span class="text-sm text-gray-500">({{ totalReviews || 0 }} avis)</span>
                                    </div>
                                </div>
                                <div v-else class="mb-3 flex items-center space-x-4">
                                    <div class="flex items-center space-x-1">
                                        <div class="flex space-x-1">
                                            <svg class="h-4 w-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">Nouveau profil</span>
                                    </div>
                                </div>

                                <div class="text-primary text-lg font-semibold">
                                    {{ parent.total_ads }} {{ parent.total_ads > 1 ? 'annonces publiées' : 'annonce publiée' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Annonces récentes -->
                    <div v-if="parent.ads.length > 0" class="rounded-2xl bg-white p-6 shadow-sm">
                        <h2 class="mb-6 text-xl font-bold text-gray-900">Annonces récentes</h2>
                        <div class="space-y-4">
                            <div
                                v-for="ad in parent.ads"
                                :key="ad.id"
                                class="rounded-lg border border-gray-200 p-4 transition-shadow hover:shadow-md"
                            >
                                <div class="mb-3 flex items-start justify-between">
                                    <div class="flex-1">
                                        <a
                                            :href="route('announcements.show', { slug: ad.slug })"
                                            class="hover:text-primary text-lg font-semibold text-gray-900 transition-colors"
                                        >
                                            {{ ad.title }}
                                        </a>
                                        <div class="mt-2 flex items-center gap-4 text-sm text-gray-600">
                                            <div class="flex items-center gap-1">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                        clip-rule="evenodd"
                                                    ></path>
                                                </svg>
                                                {{ formatDate(ad.date_start) }}
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"
                                                    ></path>
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z"
                                                        clip-rule="evenodd"
                                                    ></path>
                                                </svg>
                                                {{ ad.hourly_rate }}€/h
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <span
                                            :class="getAdStatusClass(ad.status)"
                                            class="rounded-full px-2 py-1 text-xs font-medium"
                                        >
                                            {{ getAdStatusText(ad.status) }}
                                        </span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600">{{ ad.description }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-2xl bg-white p-6 text-center shadow-sm">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                            <svg class="h-8 w-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-medium text-gray-900">Aucune annonce publiée</h3>
                        <p class="text-gray-600">Ce parent n'a pas encore publié d'annonces.</p>
                    </div>
                </div>

                <!-- Sidebar (colonne droite) -->
                <div class="space-y-6">
                    <!-- Informations de contact -->
                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Informations</h3>
                        <div class="space-y-3">
                            <div class="flex items-center space-x-3">
                                <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                <span class="text-gray-900">Membre depuis {{ parent.member_since }}</span>
                            </div>
                            <div v-if="parent.address" class="flex items-center space-x-3">
                                <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                <span class="text-gray-900">{{ getCity(parent.address) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Section Avis -->
                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900">Avis des babysitters</h2>
                            <div v-if="reviews && reviews.length > 0" class="flex items-center gap-2">
                                <div class="flex items-center gap-1">
                                    <svg class="h-4 w-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900">{{ averageRating?.toFixed(1) || 0 }}</span>
                                </div>
                                <span class="text-sm text-gray-500">({{ totalReviews || 0 }} avis)</span>
                            </div>
                        </div>

                        <div v-if="reviews && reviews.length > 0">
                            <!-- Les 3 derniers avis -->
                            <div class="space-y-4 mb-4">
                                <div
                                    v-for="review in reviews.slice(0, 3)"
                                    :key="review.id"
                                    class="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0"
                                >
                                    <div class="mb-3 flex items-start gap-3">
                                        <img
                                            :src="review.reviewer.avatar || '/storage/default-avatar.png'"
                                            :alt="`${review.reviewer.firstname} ${review.reviewer.lastname}`"
                                            class="h-10 w-10 rounded-full object-cover"
                                        />
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <h4 class="font-medium text-gray-900">
                                                    {{ review.reviewer.firstname }} {{ review.reviewer.lastname }}
                                                </h4>
                                                <p class="text-sm text-gray-500">{{ formatReviewDate(review.created_at) }}</p>
                                            </div>
                                            <div class="flex items-center gap-1 mt-1">
                                                <template v-for="i in 5" :key="i">
                                                    <svg
                                                        class="h-4 w-4"
                                                        :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                    </svg>
                                                </template>
                                            </div>
                                            <p v-if="review.comment" class="mt-2 text-gray-700 text-sm">{{ review.comment }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bouton pour voir plus d'avis -->
                            <button 
                                v-if="reviews.length > 3"
                                @click="showAllReviews = true"
                                class="w-full mt-4 text-center text-blue-600 hover:text-blue-800 font-medium py-2 px-4 border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors"
                            >
                                Voir tous les avis ({{ totalReviews }})
                            </button>
                        </div>

                        <div v-else class="text-center py-8">
                            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                <svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <h3 class="mb-2 text-lg font-medium text-gray-900">Aucun avis</h3>
                            <p class="text-gray-600">Ce parent n'a pas encore reçu d'avis de babysitters.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal pour tous les avis -->
        <div v-if="showAllReviews" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black bg-opacity-50" @click="showAllReviews = false"></div>
                <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
                    <div class="flex items-center justify-between p-6 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Tous les avis ({{ totalReviews }})
                        </h3>
                        <button @click="showAllReviews = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[60vh]">
                        <div class="space-y-6">
                            <div
                                v-for="review in reviews"
                                :key="review.id"
                                class="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0"
                            >
                                <div class="flex items-start gap-3">
                                    <img
                                        :src="review.reviewer.avatar || '/storage/default-avatar.png'"
                                        :alt="`${review.reviewer.firstname} ${review.reviewer.lastname}`"
                                        class="h-12 w-12 rounded-full object-cover"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h4 class="font-medium text-gray-900">
                                                {{ review.reviewer.firstname }} {{ review.reviewer.lastname }}
                                            </h4>
                                            <p class="text-sm text-gray-500">{{ formatReviewDate(review.created_at) }}</p>
                                        </div>
                                        <div class="flex items-center gap-1 mt-1">
                                            <template v-for="i in 5" :key="i">
                                                <svg
                                                    class="h-4 w-4"
                                                    :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            </template>
                                        </div>
                                        <p v-if="review.comment" class="mt-2 text-gray-700">{{ review.comment }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GlobalLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
