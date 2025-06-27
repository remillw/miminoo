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
        const days = Math.round(dur.days);
        const hours = Math.round(dur.total_hours * 10) / 10;
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

// Obtenir la ville depuis l'adresse
const getCity = (address: Address) => {
    const parts = address.address.split(',');
    return parts[parts.length - 1]?.trim() || '';
};

// Formater la localisation
const formatLocation = (address: Address) => {
    return `${address.postal_code} ${getCity(address)}`;
};

// Formater l'âge des enfants
const formatChildAge = (child: Child) => {
    return `${child.age} ${child.unite}`;
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

// Fonctions pour les avis
const renderStars = (rating: number) => {
    return Array.from({ length: 5 }, (_, i) => i < rating);
};

const formatMemberSince = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
};
</script>

<template>
    <Head :title="`${announcement.title} - Annonce`" />

    <GlobalLayout>
        <div class="min-h-screen bg-gray-50">
            <!-- Header épuré -->
            <div class="from-primary-500 to-primary-600 bg-gradient-to-r py-12">
                <div class="mx-auto max-w-4xl px-4">
                    <!-- Breadcrumb simple -->
                    <nav class="mb-8 text-sm">
                        <a href="/annonces" class="text-primary-100 transition-colors hover:text-white"> ← Retour aux annonces </a>
                    </nav>

                    <!-- Titre et infos principales -->
                    <div class="text-white">
                        <h1 class="mb-6 text-3xl font-bold">{{ announcement.title }}</h1>

                        <!-- Informations essentielles en une ligne épurée -->
                        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="text-center md:text-left">
                                <div class="text-primary-100 text-sm font-medium">Date</div>
                                <div class="font-semibold text-white">{{ formatDateRange }}</div>
                            </div>
                            <div class="text-center md:text-left">
                                <div class="text-primary-100 text-sm font-medium">Horaires</div>
                                <div class="font-semibold text-white">{{ formatTimeRange }}</div>
                            </div>
                            <div class="text-center md:text-left">
                                <div class="text-primary-100 text-sm font-medium">Lieu</div>
                                <div class="font-semibold text-white">{{ formatLocation(announcement.address) }}</div>
                            </div>
                        </div>

                        <!-- Prix en évidence -->
                        <div class="text-center">
                            <div class="inline-flex items-baseline gap-2 rounded-2xl bg-white/10 px-6 py-3 backdrop-blur">
                                <span class="text-3xl font-bold">{{ announcement.hourly_rate }}€</span>
                                <span class="text-primary-100">/heure</span>
                                <span class="text-primary-100 ml-2">•</span>
                                <span class="text-primary-100">{{ announcement.estimated_total }}€ total</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="mx-auto -mt-8 max-w-4xl px-4">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <!-- Contenu principal -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- Enfants à garder -->
                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <h2 class="mb-4 text-xl font-bold text-gray-900">
                                {{ announcement.children.length }} enfant{{ announcement.children.length > 1 ? 's' : '' }} à garder
                            </h2>
                            <div class="space-y-3">
                                <div
                                    v-for="(child, index) in announcement.children"
                                    :key="index"
                                    class="flex items-center gap-4 rounded-xl bg-gray-50 p-4"
                                >
                                    <div class="bg-primary-100 flex h-10 w-10 items-center justify-center rounded-full">
                                        <svg class="text-primary-600 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ child.nom }}</div>
                                        <div class="text-sm text-gray-600">{{ formatChildAge(child) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="announcement.description" class="rounded-2xl bg-white p-6 shadow-sm">
                            <h2 class="mb-4 text-xl font-bold text-gray-900">Informations complémentaires</h2>
                            <p class="leading-relaxed whitespace-pre-line text-gray-700">{{ announcement.description }}</p>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Profil du parent -->
                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <h3 class="mb-4 text-lg font-bold text-gray-900">Parent</h3>
                            <div class="flex items-center gap-4">
                                <img
                                    :src="announcement.parent.avatar || '/storage/default-avatar.png'"
                                    :alt="`${announcement.parent.firstname} ${announcement.parent.lastname}`"
                                    class="h-12 w-12 rounded-full object-cover"
                                />
                                <div>
                                    <div class="font-semibold text-gray-900">
                                        {{ announcement.parent.firstname }} {{ announcement.parent.lastname }}
                                    </div>
                                    <div class="text-sm text-gray-600">Membre depuis {{ formatMemberSince(announcement.parent.member_since) }}</div>
                                    <div v-if="announcement.parent.review_stats.total_reviews > 0" class="mt-1 flex items-center gap-1">
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
                                        <span class="text-sm text-gray-600">
                                            {{ announcement.parent.review_stats.average_rating }}/5 ({{
                                                announcement.parent.review_stats.total_reviews
                                            }}
                                            avis)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Résumé de la mission -->
                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <h3 class="mb-4 text-lg font-bold text-gray-900">Résumé</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Durée</span>
                                    <span class="font-medium">{{ formatDuration }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Enfants</span>
                                    <span class="font-medium">{{ announcement.children.length }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tarif horaire</span>
                                    <span class="font-medium">{{ announcement.hourly_rate }}€/h</span>
                                </div>
                                <hr class="my-4" />
                                <div class="flex justify-between text-lg font-bold">
                                    <span>Total estimé</span>
                                    <span class="text-primary-600">{{ announcement.estimated_total }}€</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bouton de candidature -->
                        <div v-if="isFuture" class="rounded-2xl bg-white p-6 shadow-sm">
                            <button
                                @click="isModalOpen = true"
                                class="from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 w-full transform rounded-xl bg-gradient-to-r px-6 py-3 font-semibold text-white transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]"
                            >
                                Postuler à cette annonce
                            </button>
                            <p class="mt-3 text-center text-sm text-gray-500">Envoyez votre candidature en quelques clics</p>
                        </div>

                        <!-- Mission expirée -->
                        <div v-else class="rounded-2xl bg-white p-6 text-center shadow-sm">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                <svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                            </div>
                            <h3 class="mb-1 font-medium text-gray-900">Mission terminée</h3>
                            <p class="text-sm text-gray-600">Cette annonce n'est plus disponible</p>
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
