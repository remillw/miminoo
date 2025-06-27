<script setup lang="ts">
import PostulerModal from '@/components/PostulerModal.vue';
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Child {
    nom: string;
    age: string;
    unite: 'ans' | 'mois';
}

interface Parent {
    id: number;
    firstname: string;
    lastname: string;
    avatar?: string;
    slug: string;
    reviews: Review[];
    review_stats: ReviewStats;
    member_since: string;
}

interface Review {
    id: number;
    rating: number;
    comment: string;
    created_at: string;
    reviewer: {
        firstname: string;
        lastname: string;
        avatar?: string;
    };
}

interface ReviewStats {
    average_rating: number;
    total_reviews: number;
    rating_distribution: {
        [key: number]: {
            count: number;
            percentage: number;
        };
    };
}

interface Address {
    address: string;
    postal_code: string;
    country: string;
    latitude: number;
    longitude: number;
}

interface Duration {
    is_multi_day: boolean;
    total_hours: number;
    days: number;
    start_date: string;
    end_date: string;
    start_time: string;
    end_time: string;
}

interface Announcement {
    id: number;
    title: string;
    description?: string;
    date_start: string;
    date_end: string;
    hourly_rate: number;
    estimated_duration: number;
    estimated_total: number;
    status: string;
    children: Child[];
    created_at: string;
    slug: string;
    duration: Duration;
    parent: Parent;
    address: Address;
}

interface Props {
    announcement: Announcement;
}

const props = defineProps<Props>();

// État du modal
const isModalOpen = ref(false);

// Formater les dates
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatTime = (dateString: string) => {
    return new Date(dateString).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Calculer la durée
const duration = computed(() => {
    if (props.announcement.duration.is_multi_day) {
        return props.announcement.duration.total_hours;
    }
    const start = new Date(props.announcement.date_start);
    const end = new Date(props.announcement.date_end);
    const diffMs = end.getTime() - start.getTime();
    const diffHours = Math.round((diffMs / (1000 * 60 * 60)) * 10) / 10;
    return diffHours;
});

// Formater la durée pour affichage
const formatDuration = computed(() => {
    const dur = props.announcement.duration;
    if (dur.is_multi_day) {
        // Arrondir les jours et heures pour un affichage propre
        const days = Math.round(dur.days);
        const hours = Math.round(dur.total_hours * 10) / 10; // 1 décimale max
        return `${days} jour${days > 1 ? 's' : ''} (${hours}h total)`;
    } else {
        return `${duration.value}h`;
    }
});

// Formater les dates pour multi-jours
const formatDateRange = computed(() => {
    const dur = props.announcement.duration;
    if (dur.is_multi_day) {
        const startDate = new Date(dur.start_date);
        const endDate = new Date(dur.end_date);
        return `Du ${formatDate(startDate.toISOString())} au ${formatDate(endDate.toISOString())}`;
    } else {
        return formatDate(props.announcement.date_start);
    }
});

// Formater les horaires
const formatTimeRange = computed(() => {
    const dur = props.announcement.duration;
    if (dur.is_multi_day) {
        return `De ${dur.start_time} à ${dur.end_time}`;
    } else {
        return `${formatTime(props.announcement.date_start)} - ${formatTime(props.announcement.date_end)}`;
    }
});

// Fonctions pour les avis
const renderStars = (rating: number) => {
    return Array.from({ length: 5 }, (_, i) => i < rating);
};

const formatTimeAgo = (dateString: string) => {
    const now = new Date();
    const date = new Date(dateString);
    const diffInMonths = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24 * 30));

    if (diffInMonths < 1) return 'Ce mois-ci';
    if (diffInMonths === 1) return 'Il y a 1 mois';
    if (diffInMonths < 12) return `Il y a ${diffInMonths} mois`;

    const diffInYears = Math.floor(diffInMonths / 12);
    if (diffInYears === 1) return 'Il y a 1 an';
    return `Il y a ${diffInYears} ans`;
};

const formatMemberSince = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
};

// Obtenir la ville depuis l'adresse
const getCity = (address: Address) => {
    const parts = address.address.split(',');
    return parts[parts.length - 1]?.trim() || '';
};

// Formater la localisation (code postal + ville)
const formatLocation = (address: Address) => {
    return `${address.postal_code} ${getCity(address)}`;
};

// Formater l'âge des enfants
const formatChildAge = (child: Child) => {
    return `${child.age} ${child.unite}`;
};

// Couleurs pour les enfants
const childColors = [
    'bg-blue-100 text-blue-800',
    'bg-green-100 text-green-800',
    'bg-purple-100 text-purple-800',
    'bg-pink-100 text-pink-800',
    'bg-indigo-100 text-indigo-800',
    'bg-yellow-100 text-yellow-800',
];

const getChildColor = (index: number) => {
    return childColors[index % childColors.length];
};

// Vérifier si l'annonce est dans le futur
const isFuture = computed(() => {
    return new Date(props.announcement.date_start) > new Date();
});

// Calculer les horaires pour la modal
const hoursForModal = computed(() => {
    return `${formatTime(props.announcement.date_start)} - ${formatTime(props.announcement.date_end)}`;
});

// Obtenir la localisation pour la modal
const locationForModal = computed(() => {
    return `${getCity(props.announcement.address)}, ${props.announcement.address.postal_code}`;
});
</script>

<template>
    <Head :title="`${announcement.title} - Annonce`" />

    <GlobalLayout>
        <!-- Hero section avec dégradé -->
        <div class="min-h-screen bg-gradient-to-br from-orange-50 via-white to-blue-50">
            <!-- Header avec image de fond -->
            <div class="relative bg-gradient-to-r from-orange-500 to-orange-600 pt-6 pb-8">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative mx-auto max-w-4xl px-4">
                    <!-- Breadcrumb amélioré -->
                    <nav class="mb-6 flex items-center space-x-2 text-sm text-orange-100">
                        <a href="/annonces" class="flex items-center gap-1 transition-colors hover:text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Annonces
                        </a>
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"
                            ></path>
                        </svg>
                        <span class="truncate font-medium text-white">{{ announcement.title }}</span>
                    </nav>

                    <!-- En-tête héroïque -->
                    <div class="text-center text-white">
                        <h1 class="mb-4 text-3xl leading-tight font-bold md:text-4xl">{{ announcement.title }}</h1>
                        <div class="flex flex-wrap justify-center gap-6 text-sm text-orange-100">
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                <span>{{ formatDateRange }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                <span>{{ formatTimeRange }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                <span>{{ formatLocation(announcement.address) }}</span>
                            </div>
                        </div>

                        <!-- Prix en évidence -->
                        <div class="mt-6 inline-block rounded-full bg-white/20 px-6 py-3 backdrop-blur-sm">
                            <div class="text-3xl font-bold text-white">{{ announcement.hourly_rate }}€<span class="text-lg">/heure</span></div>
                            <div class="text-orange-100">{{ formatDuration }} • {{ announcement.estimated_total }}€ total</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="relative z-10 mx-auto -mt-4 max-w-4xl px-4">
                <!-- Carte des informations principales -->
                <div class="mb-6 rounded-3xl border border-gray-100 bg-white p-6 shadow-xl">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <!-- Date -->
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-orange-100">
                                <svg class="h-6 w-6 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-500">Date</p>
                                <p class="text-lg leading-tight font-bold text-gray-900">{{ formatDateRange }}</p>
                            </div>
                        </div>

                        <!-- Heure -->
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100">
                                <svg class="h-6 w-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-500">Horaires</p>
                                <p class="text-lg leading-tight font-bold text-gray-900">{{ formatTimeRange }}</p>
                            </div>
                        </div>

                        <!-- Lieu -->
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100">
                                <svg class="h-6 w-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-500">Lieu</p>
                                <p class="text-lg leading-tight font-bold text-gray-900">{{ formatLocation(announcement.address) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-3">
                    <!-- Contenu principal -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Statut avec icône -->
                        <div class="flex justify-center">
                            <div
                                v-if="isFuture"
                                class="inline-flex items-center rounded-full bg-green-500 px-4 py-2 text-sm font-medium text-white shadow-lg"
                            >
                                <svg class="mr-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                Disponible pour candidatures
                            </div>
                            <div v-else class="inline-flex items-center rounded-full bg-gray-500 px-4 py-2 text-sm font-medium text-white shadow-lg">
                                <svg class="mr-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                Mission terminée
                            </div>
                        </div>

                        <!-- Enfants à garder -->
                        <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-xl">
                            <div class="mb-6 flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                                    <svg class="h-6 w-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">Enfants à garder</h2>
                                    <p class="text-sm text-gray-600">
                                        {{ announcement.children.length }} enfant{{ announcement.children.length > 1 ? 's' : '' }}
                                    </p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div
                                    v-for="(child, index) in announcement.children"
                                    :key="index"
                                    :class="[
                                        'flex items-center gap-3 rounded-2xl border-2 border-dashed px-4 py-3 transition-all hover:border-solid',
                                        getChildColor(index),
                                    ]"
                                >
                                    <div class="flex-shrink-0 rounded-full bg-white/50 p-2">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold">{{ child.nom }}</p>
                                        <p class="text-sm opacity-80">{{ formatChildAge(child) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="announcement.description" class="rounded-3xl border border-gray-100 bg-white p-6 shadow-xl">
                            <div class="mb-6 flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100">
                                    <svg class="h-6 w-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">Informations complémentaires</h2>
                                    <p class="text-sm text-gray-600">Détails fournis par le parent</p>
                                </div>
                            </div>
                            <div class="rounded-2xl bg-gray-50 p-4">
                                <p class="leading-relaxed whitespace-pre-line text-gray-700">{{ announcement.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Profil du parent -->
                        <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-xl">
                            <h3 class="mb-4 text-lg font-bold text-gray-900">Parent</h3>
                            <div class="flex items-center space-x-3">
                                <img
                                    :src="announcement.parent.avatar || '/storage/default-avatar.png'"
                                    :alt="`${announcement.parent.firstname} ${announcement.parent.lastname}`"
                                    class="h-12 w-12 rounded-full object-cover"
                                />
                                <div class="min-w-0 flex-1">
                                    <a
                                        :href="route('parent.show', { slug: announcement.parent.slug })"
                                        class="hover:text-primary block truncate font-semibold text-gray-900 transition-colors"
                                    >
                                        {{ announcement.parent.firstname }} {{ announcement.parent.lastname }}
                                    </a>
                                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-3">
                                        <div v-if="announcement.parent.review_stats.total_reviews > 0" class="flex items-center gap-1">
                                            <div class="flex">
                                                <svg
                                                    v-for="(filled, index) in renderStars(announcement.parent.review_stats.average_rating)"
                                                    :key="index"
                                                    class="h-4 w-4"
                                                    :class="filled ? 'text-yellow-400' : 'text-gray-300'"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path
                                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                                    />
                                                </svg>
                                            </div>
                                            <span class="text-sm font-medium text-gray-700">{{
                                                announcement.parent.review_stats.average_rating
                                            }}</span>
                                            <span class="hidden text-sm text-gray-500 sm:inline"
                                                >({{ announcement.parent.review_stats.total_reviews }} avis)</span
                                            >
                                        </div>
                                        <p class="text-sm text-gray-600">
                                            <span class="sm:hidden">{{ announcement.parent.review_stats.total_reviews }} avis • </span>
                                            Membre depuis {{ formatMemberSince(announcement.parent.member_since) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Avis du parent -->
                        <div
                            v-if="announcement.parent.review_stats.total_reviews > 0"
                            class="rounded-3xl border border-gray-100 bg-white p-6 shadow-xl"
                        >
                            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <h3 class="text-lg font-bold text-gray-900">Avis sur ce parent</h3>
                                <div class="flex items-center gap-2 text-sm">
                                    <div class="flex">
                                        <svg
                                            v-for="(filled, index) in renderStars(announcement.parent.review_stats.average_rating)"
                                            :key="index"
                                            class="h-4 w-4"
                                            :class="filled ? 'text-yellow-400' : 'text-gray-300'"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                            />
                                        </svg>
                                    </div>
                                    <span class="font-medium text-gray-900">{{ announcement.parent.review_stats.average_rating }}/5</span>
                                    <span class="text-gray-500">•</span>
                                    <span class="text-gray-600">{{ announcement.parent.review_stats.total_reviews }} avis</span>
                                </div>
                            </div>

                            <!-- Liste des avis mobile-optimisée -->
                            <div class="max-h-80 space-y-4 overflow-y-auto">
                                <div
                                    v-for="review in announcement.parent.reviews.slice(0, 3)"
                                    :key="review.id"
                                    class="border-b border-gray-100 pb-4 last:border-b-0 last:pb-0"
                                >
                                    <div class="flex gap-3">
                                        <img
                                            :src="review.reviewer.avatar || '/default-avatar.svg'"
                                            :alt="review.reviewer.firstname"
                                            class="h-8 w-8 flex-shrink-0 rounded-full object-cover"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <div class="mb-2 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <span class="text-sm font-medium text-gray-900">
                                                        {{ review.reviewer.firstname }} {{ review.reviewer.lastname }}
                                                    </span>
                                                    <div class="mt-1 flex items-center gap-1">
                                                        <div class="flex">
                                                            <svg
                                                                v-for="(filled, index) in renderStars(review.rating)"
                                                                :key="index"
                                                                class="h-3 w-3"
                                                                :class="filled ? 'text-yellow-400' : 'text-gray-300'"
                                                                fill="currentColor"
                                                                viewBox="0 0 20 20"
                                                            >
                                                                <path
                                                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                                                />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="text-xs text-gray-500 sm:text-right">
                                                    {{ formatTimeAgo(review.created_at) }}
                                                </span>
                                            </div>
                                            <p class="text-sm leading-relaxed text-gray-700">{{ review.comment }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lien vers tous les avis si plus de 3 -->
                            <div v-if="announcement.parent.reviews.length > 3" class="mt-4 border-t border-gray-100 pt-4">
                                <a
                                    :href="route('parent.show', { slug: announcement.parent.slug })"
                                    class="text-primary hover:text-primary/80 text-sm font-medium transition-colors"
                                >
                                    Voir tous les {{ announcement.parent.review_stats.total_reviews }} avis →
                                </a>
                            </div>
                        </div>

                        <!-- Résumé de la mission -->
                        <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-xl">
                            <div class="mb-6 flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                                    <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Résumé de la mission</h3>
                            </div>
                            <div class="space-y-3">
                                <div class="flex flex-col gap-1 sm:flex-row sm:justify-between">
                                    <span class="text-sm text-gray-600 sm:text-base">Date</span>
                                    <span class="text-sm font-medium text-gray-900 sm:text-base">{{ formatDateRange }}</span>
                                </div>
                                <div class="flex flex-col gap-1 sm:flex-row sm:justify-between">
                                    <span class="text-sm text-gray-600 sm:text-base">Horaires</span>
                                    <span class="text-sm font-medium text-gray-900 sm:text-base">{{ formatTimeRange }}</span>
                                </div>
                                <div class="flex flex-col gap-1 sm:flex-row sm:justify-between">
                                    <span class="text-sm text-gray-600 sm:text-base">Durée</span>
                                    <span class="text-sm font-medium text-gray-900 sm:text-base">{{ formatDuration }}</span>
                                </div>
                                <div class="flex flex-col gap-1 sm:flex-row sm:justify-between">
                                    <span class="text-sm text-gray-600 sm:text-base">Enfants</span>
                                    <span class="text-sm font-medium text-gray-900 sm:text-base">{{ announcement.children.length }}</span>
                                </div>
                                <div class="flex flex-col gap-1 sm:flex-row sm:justify-between">
                                    <span class="text-sm text-gray-600 sm:text-base">Tarif horaire</span>
                                    <span class="text-sm font-medium text-gray-900 sm:text-base">{{ announcement.hourly_rate }}€/h</span>
                                </div>
                                <hr class="my-3" />
                                <div class="flex flex-col gap-1 text-lg font-bold sm:flex-row sm:justify-between">
                                    <span class="text-gray-900">Total estimé</span>
                                    <span class="text-primary">{{ announcement.estimated_total }}€</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bouton de candidature -->
                        <div v-if="isFuture" class="rounded-2xl bg-white p-4 shadow-sm sm:p-6">
                            <button
                                @click="isModalOpen = true"
                                class="mb-3 w-full transform cursor-pointer rounded-lg bg-gradient-to-r from-orange-500 to-orange-600 px-4 py-3 text-sm font-semibold text-white transition-all duration-200 hover:scale-[1.02] hover:from-orange-600 hover:to-orange-700 hover:shadow-lg active:scale-[0.98] sm:text-base"
                                title="Cliquez pour postuler à cette annonce"
                            >
                                ✋ Postuler à cette annonce
                            </button>
                            <p class="text-center text-xs text-gray-500">Cliquez pour envoyer votre candidature</p>
                        </div>

                        <!-- Annonce expirée -->
                        <div v-else class="rounded-2xl bg-white p-4 shadow-sm sm:p-6">
                            <div class="text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                    <svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </div>
                                <h3 class="mb-1 text-lg font-medium text-gray-900">Annonce expirée</h3>
                                <p class="text-sm text-gray-600">Cette mission a déjà eu lieu</p>
                            </div>
                        </div>

                        <!-- Informations -->
                        <div class="rounded-2xl bg-white p-4 shadow-sm sm:p-6">
                            <h3 class="mb-4 text-lg font-bold text-gray-900">Informations</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center gap-2 text-gray-600">
                                    <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                    <span class="break-words">Publié le {{ formatDateTime(announcement.created_at) }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                    <span>Annonce #{{ announcement.id }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de candidature -->
        <PostulerModal
            :is-open="isModalOpen"
            :on-close="() => (isModalOpen = false)"
            :announcement-id="announcement.id"
            :date="announcement.date_start"
            :hours="hoursForModal"
            :location="locationForModal"
            :children-count="announcement.children.length"
            :avatar-url="announcement.parent.avatar"
            :family-name="`${announcement.parent.firstname} ${announcement.parent.lastname}`"
            :requested-rate="announcement.hourly_rate"
            :additional-info="announcement.description"
        />
    </GlobalLayout>
</template>
