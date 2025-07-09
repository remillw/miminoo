<script setup lang="ts">
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useToast } from '@/composables/useToast';
import { useStatusColors } from '@/composables/useStatusColors';
import { useDateFormat } from '@/composables/useDateFormat';
import { router } from '@inertiajs/vue3';
import { Calendar, MapPin, Clock, Star, Users, Heart, MessageCircle, UserCheck, ShieldCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import type { 
    Announcement, 
    Child, 
    User, 
    Review, 
    Address
} from '@/types';

interface Parent extends User {
    // Hérite de toutes les propriétés de User
}

interface ReviewStats {
    average_rating: number;
    total_reviews: number;
    rating_breakdown: {
        1: number;
        2: number;
        3: number;
        4: number;
        5: number;
    };
}

interface Duration {
    hours: number;
    minutes: number;
    total_minutes: number;
    formatted: string;
}

interface ExtendedAnnouncement extends Announcement {
    duration: Duration;
    reviews_stats: ReviewStats;
    recent_reviews: Review[];
    can_apply: boolean;
    user_application_status?: string;
}

interface Props {
    announcement: ExtendedAnnouncement;
    auth?: {
        user?: User;
    };
}

const props = defineProps<Props>();
const { showSuccess, showError } = useToast();
const { getAnnouncementStatusColor, getStatusText } = useStatusColors();
const { formatDate: formatDateShort, formatTime } = useDateFormat();

// État du modal
const isModalOpen = ref(false);

// Formater les dates avec style complet
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
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
            <!-- Header simplifié -->
            <div class="bg-primary">
                <div class="mx-auto max-w-6xl px-4">
                    <!-- Breadcrumb -->
                    <nav class="mb-6 text-sm">
                        <a href="/annonces" class="text-primary-100 transition-colors hover:text-white"> ← Retour aux annonces </a>
                    </nav>

                    <!-- Titre principal -->
                    <h1 class="mb-4 text-3xl font-bold text-white">{{ announcement.title }}</h1>

                    <!-- Prix en évidence -->
                    <div class="text-white">
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-bold">{{ announcement.hourly_rate }}€</span>
                            <span class="text-primary-100">/heure</span>
                            <span class="text-primary-100 ml-3">•</span>
                            <span class="text-primary-100">{{ announcement.estimated_total }}€ total</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations principales en header -->
            <div class="border-b border-gray-200 bg-white py-6">
                <div class="mx-auto max-w-6xl px-4">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                                <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-500">Date</div>
                                <div class="font-semibold text-gray-900">{{ formatDateRange }}</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                                <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-500">Horaires</div>
                                <div class="font-semibold text-gray-900">{{ formatTimeRange }}</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                                <svg class="h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-500">Lieu</div>
                                <div class="font-semibold text-gray-900">{{ formatLocation(announcement.address) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu principal équilibré -->
            <div class="mx-auto max-w-6xl px-4 py-8">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <!-- Colonne gauche - Contenu principal -->
                    <div class="space-y-6">
                        <!-- Enfants à garder -->
                        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                            <h2 class="mb-4 text-xl font-bold text-gray-900">
                                {{ announcement.children.length }} enfant{{ announcement.children.length > 1 ? 's' : '' }} à garder
                            </h2>
                            <div class="space-y-3">
                                <div
                                    v-for="(child, index) in announcement.children"
                                    :key="index"
                                    class="flex items-center gap-4 rounded-lg bg-gray-50 p-4"
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
                        <div v-if="announcement.description" class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                            <h2 class="mb-4 text-xl font-bold text-gray-900">Informations complémentaires</h2>
                            <p class="leading-relaxed whitespace-pre-line text-gray-700">{{ announcement.description }}</p>
                        </div>

                        <!-- Profil du parent -->
                        <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                            <h3 class="mb-4 text-lg font-bold text-gray-900">À propos du parent</h3>
                            <div class="flex items-start gap-4">
                                <img
                                    :src="announcement.parent.avatar || '/storage/default-avatar.png'"
                                    :alt="`${announcement.parent.firstname} ${announcement.parent.lastname}`"
                                    class="h-16 w-16 rounded-full border-2 border-gray-200 object-cover"
                                />
                                <div class="flex-1">
                                    <div class="text-lg font-semibold text-gray-900">
                                        {{ announcement.parent.firstname }} {{ announcement.parent.lastname }}
                                    </div>
                                    <div class="mb-2 text-gray-600">Membre depuis {{ formatMemberSince(announcement.parent.member_since) }}</div>
                                    <div v-if="announcement.parent.review_stats.total_reviews > 0" class="flex items-center gap-2">
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
                                        <span class="text-sm font-medium text-gray-600">
                                            {{ announcement.parent.review_stats.average_rating }}/5 ({{
                                                announcement.parent.review_stats.total_reviews
                                            }}
                                            avis)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Colonne droite - Résumé et action -->
                    <div class="space-y-6">
                        <!-- Résumé de la mission -->
                        <div class="sticky top-6 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                            <h3 class="mb-6 text-lg font-bold text-gray-900">Résumé de la mission</h3>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between border-b border-gray-100 py-2">
                                    <span class="font-medium text-gray-600">Durée</span>
                                    <span class="font-semibold text-gray-900">{{ formatDuration }}</span>
                                </div>
                                <div class="flex items-center justify-between border-b border-gray-100 py-2">
                                    <span class="font-medium text-gray-600">Enfants</span>
                                    <span class="font-semibold text-gray-900">{{ announcement.children.length }}</span>
                                </div>
                                <div class="flex items-center justify-between border-b border-gray-100 py-2">
                                    <span class="font-medium text-gray-600">Tarif horaire</span>
                                    <span class="font-semibold text-gray-900">{{ announcement.hourly_rate }}€/h</span>
                                </div>
                                <div class="bg-primary-50 mt-6 flex items-center justify-between rounded-lg px-4 py-3">
                                    <span class="text-primary-800 text-lg font-bold">Total estimé</span>
                                    <span class="text-primary-600 text-xl font-bold">{{ announcement.estimated_total }}€</span>
                                </div>
                            </div>

                            <!-- Bouton de candidature -->
                            <div v-if="isFuture" class="mt-6">
                                <button
                                    @click="isModalOpen = true"
                                    class="bg-primary-600 hover:bg-primary-700 w-full transform rounded-xl px-6 py-4 font-semibold text-white shadow-lg transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]"
                                >
                                    Postuler à cette annonce
                                </button>
                                <p class="mt-3 text-center text-sm text-gray-500">Envoyez votre candidature en quelques clics</p>
                            </div>

                            <!-- Mission expirée -->
                            <div v-else class="mt-6 py-4 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                    <svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </div>
                                <h3 class="mb-1 font-semibold text-gray-900">Mission terminée</h3>
                                <p class="text-sm text-gray-600">Cette annonce n'est plus disponible</p>
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
