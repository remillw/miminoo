<script setup lang="ts">
import { useStatusColors } from '@/composables/useStatusColors';
import { useToast } from '@/composables/useToast';
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import type { Address, BabysitterProfile, Review, User } from '@/types';
import { computed, ref } from 'vue';

interface Experience {
    id: number;
    title: string;
    organization?: string;
    description?: string;
    duration?: string;
    category?: string;
}

interface ExtendedBabysitterProfile extends BabysitterProfile {
    user: User;
    reviews: Review[];
    recent_reviews: Review[];
    review_stats: {
        average_rating: number;
        total_reviews: number;
        rating_breakdown: Record<number, number>;
    };
    member_since: string;
}

interface Props {
    user: User;
    profile: ExtendedBabysitterProfile;
    auth?: {
        user?: User;
    };
}

const props = defineProps<Props>();
const { showSuccess, showError } = useToast();
const { getStatusText } = useStatusColors();

// Calculer les tranches d'âge acceptées (toutes sauf les exclues)
const acceptedAgeRanges = computed(() => {
    if (props.profile.comfortable_with_all_ages) {
        return props.profile.available_age_ranges;
    }

    const excludedIds = props.profile.excluded_age_ranges.map((range) => range.id);
    return props.profile.available_age_ranges.filter((range) => !excludedIds.includes(range.id));
});

// Séparer les formations et expériences
const formations = computed(() => props.profile.experiences.filter((exp) => exp.type === 'formation'));

const workExperiences = computed(() => props.profile.experiences.filter((exp) => exp.type === 'experience'));

// Formater les dates
const formatDate = (dateString?: string) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
    });
};

// Obtenir la ville depuis l'adresse
const getCity = (address?: Address) => {
    if (!address) return '';
    const parts = address.address.split(',');
    return parts[parts.length - 1]?.trim() || '';
};

// Couleurs pour les compétences
const skillColors = [
    'bg-yellow-100 text-yellow-800',
    'bg-green-100 text-green-800',
    'bg-blue-100 text-blue-800',
    'bg-purple-100 text-purple-800',
    'bg-pink-100 text-pink-800',
    'bg-indigo-100 text-indigo-800',
    'bg-red-100 text-red-800',
    'bg-orange-100 text-orange-800',
];

const getSkillColor = (index: number) => {
    return skillColors[index % skillColors.length];
};

// Computed pour les photos supplémentaires
const additionalPhotos = computed(() => {
    if (props.profile.additional_photos_urls) {
        return props.profile.additional_photos_urls;
    }
    if (props.profile.profile_photos) {
        return props.profile.profile_photos.map((photo) => {
            if (photo.startsWith('data:image')) {
                return photo;
            }
            return `/storage/${photo}`;
        });
    }
    return [];
});

// Variables pour la modal de photos
const showPhotoModal = ref(false);
const currentPhoto = ref('');
const currentPhotoIndex = ref(0);

// Fonctions pour gérer les photos
const openPhotoModal = (photo: string, index: number) => {
    currentPhoto.value = photo;
    currentPhotoIndex.value = index;
    showPhotoModal.value = true;
};

const closePhotoModal = () => {
    showPhotoModal.value = false;
};

const nextPhoto = () => {
    if (currentPhotoIndex.value < additionalPhotos.value.length - 1) {
        currentPhotoIndex.value++;
        currentPhoto.value = additionalPhotos.value[currentPhotoIndex.value];
    }
};

const prevPhoto = () => {
    if (currentPhotoIndex.value > 0) {
        currentPhotoIndex.value--;
        currentPhoto.value = additionalPhotos.value[currentPhotoIndex.value];
    }
};

const handlePhotoError = (event: Event) => {
    const img = event.target as HTMLImageElement;
    img.style.display = 'none';
    console.error('Erreur de chargement de la photo:', img.src);
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
    <Head :title="`${profile.user.firstname} ${profile.user.lastname} - Profil Babysitter`" />

    <GlobalLayout>
        <div class="bg-secondary min-h-screen p-4">
            <div class="mx-auto grid max-w-6xl grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Section principale -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Header Profile -->
                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <div class="flex items-start space-x-6">
                            <div class="relative">
                                <img
                                    :src="profile.user.avatar || '/storage/default-avatar.png'"
                                    :alt="`${profile.user.firstname} ${profile.user.lastname}`"
                                    class="h-24 w-24 rounded-full border-4 border-white object-cover shadow-lg"
                                />
                                <!-- Badge de disponibilité -->
                                <div
                                    v-if="profile.is_available"
                                    class="absolute -right-1 -bottom-1 flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-green-500"
                                >
                                    <div class="h-2 w-2 rounded-full bg-white"></div>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="mb-2 flex items-center space-x-3">
                                    <h1 class="text-2xl font-bold text-gray-900">{{ profile.user.firstname }} {{ profile.user.lastname }}</h1>
                                    <div v-if="profile.documents_verified" class="flex items-center space-x-1 text-green-600">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                        <span class="text-sm font-medium">Identité vérifiée</span>
                                    </div>
                                </div>

                                <p class="mb-3 text-sm text-gray-600">
                                    <span v-if="profile.experience_years">
                                        {{ profile.experience_years }}
                                        {{ profile.experience_years > 1 ? 'ans' : 'an' }} d'expérience
                                    </span>
                                    <span v-if="profile.address && profile.experience_years"> | </span>
                                    <span v-if="profile.address">{{ getCity(profile.address) }}</span>
                                    <span v-if="profile.available_radius_km"> | Rayon {{ profile.available_radius_km }} km</span>
                                </p>

                                <div class="mb-3 flex items-center space-x-4">
                                    <div class="flex items-center space-x-1">
                                        <div class="flex space-x-1">
                                            <svg class="h-4 w-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                                ></path>
                                            </svg>
                                        </div>
                                        <span v-if="!profile.review_stats.average_rating" class="text-sm font-medium text-gray-900"
                                            >Nouveau profil</span
                                        >
                                        <div v-else class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-900">{{
                                                profile.review_stats.average_rating.toFixed(1)
                                            }}</span>
                                            <span class="text-sm text-gray-500">({{ profile.review_stats.total_reviews }} avis)</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="profile.hourly_rate" class="text-primary text-lg font-semibold">{{ profile.hourly_rate }}€/heure</div>
                            </div>
                        </div>
                    </div>

                    <!-- À propos de moi -->
                    <div v-if="profile.bio" class="rounded-2xl bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-xl font-bold text-gray-900">À propos de moi</h2>
                        <p class="leading-relaxed text-gray-700">
                            {{ profile.bio }}
                        </p>
                    </div>

                    <!-- Photos supplémentaires -->
                    <div v-if="additionalPhotos.length > 0" class="rounded-2xl bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-xl font-bold text-gray-900">Photos</h2>
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                            <div
                                v-for="(photo, index) in additionalPhotos"
                                :key="index"
                                class="hover:border-primary aspect-square cursor-pointer overflow-hidden rounded-lg border border-gray-200 transition-colors"
                                @click="openPhotoModal(photo, index)"
                            >
                                <img
                                    :src="photo"
                                    :alt="`Photo ${index + 1} de ${profile.user.firstname}`"
                                    class="h-full w-full object-cover transition-transform duration-200 hover:scale-105"
                                    @error="handlePhotoError"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Expériences professionnelles -->
                    <div v-if="workExperiences.length > 0" class="rounded-2xl bg-white p-6 shadow-sm">
                        <h2 class="mb-6 text-xl font-bold text-gray-900">Expérience</h2>
                        <div class="space-y-6">
                            <div v-for="experience in workExperiences" :key="experience.id" class="flex space-x-4">
                                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-orange-100">
                                    <svg class="text-primary h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">{{ experience.title }}</h3>
                                    <p class="mb-2 text-sm text-gray-500">
                                        <span v-if="experience.organization">{{ experience.organization }} | </span>
                                        <span v-if="experience.duration">{{ experience.duration }}</span>
                                    </p>
                                    <p v-if="experience.description" class="text-sm text-gray-700">
                                        {{ experience.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formation -->
                    <div v-if="formations.length > 0" class="rounded-2xl bg-white p-6 shadow-sm">
                        <h2 class="mb-6 text-xl font-bold text-gray-900">Formation</h2>
                        <div class="space-y-4">
                            <div v-for="formation in formations" :key="formation.id" class="flex space-x-4">
                                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-100">
                                    <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"
                                        ></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900">{{ formation.title }}</h3>
                                    <p class="mb-1 text-sm text-gray-500">
                                        <span v-if="formation.duration"
                                            >{{ formatDate(formation.start_date) }} - {{ formatDate(formation.end_date) }}</span
                                        >
                                    </p>
                                    <p v-if="formation.organization" class="text-sm text-gray-600">{{ formation.organization }}</p>
                                    <p v-if="formation.description" class="mt-1 text-sm text-gray-600">{{ formation.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Avis -->
                    <div v-if="profile.reviews && profile.reviews.length > 0" class="rounded-2xl bg-white p-6 shadow-sm">
                        <div class="mb-6 flex items-center gap-2">
                            <h2 class="text-xl font-bold text-gray-900">Avis des parents</h2>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-1">
                                    <svg class="h-4 w-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                        />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900">{{ profile.review_stats.average_rating?.toFixed(1) || 0 }}</span>
                                </div>
                                <span class="text-sm text-gray-500">({{ profile.review_stats.total_reviews || 0 }} avis)</span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div v-for="review in profile.reviews" :key="review.id" class="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0">
                                <div class="mb-3 flex items-start gap-3">
                                    <img
                                        :src="review.reviewer.avatar || '/storage/default-avatar.png'"
                                        :alt="`${review.reviewer.firstname} ${review.reviewer.lastname}`"
                                        class="h-10 w-10 rounded-full object-cover"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-medium text-gray-900">
                                                {{ review.reviewer.firstname }} {{ review.reviewer.lastname.charAt(0) }}.
                                            </h4>
                                            <div class="flex items-center gap-1">
                                                <template v-for="i in 5" :key="i">
                                                    <svg
                                                        class="h-4 w-4"
                                                        :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path
                                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                                        />
                                                    </svg>
                                                </template>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-500">{{ formatReviewDate(review.created_at) }}</p>
                                        <p v-if="review.comment" class="mt-2 text-gray-700">{{ review.comment }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Statut de disponibilité -->
                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Disponibilité</h3>
                        <div class="flex items-center space-x-3">
                            <div :class="['h-3 w-3 rounded-full', profile.is_available ? 'bg-green-500' : 'bg-red-500']"></div>
                            <span :class="['font-medium', profile.is_available ? 'text-green-700' : 'text-red-700']">
                                {{ profile.is_available ? 'Disponible pour du babysitting' : 'Non disponible actuellement' }}
                            </span>
                        </div>
                    </div>

                    <!-- Transport -->
                    <div v-if="profile.has_driving_license || profile.has_vehicle" class="rounded-2xl bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Transport</h3>
                        <div class="space-y-3">
                            <div v-if="profile.has_driving_license" class="flex items-center space-x-3">
                                <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                <span class="text-gray-900">Permis de conduire</span>
                            </div>
                            <div v-if="profile.has_vehicle" class="flex items-center space-x-3">
                                <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                <span class="text-gray-900">Véhicule personnel</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tranches d'âge -->
                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Tranches d'âge acceptées</h3>
                        <div v-if="profile.comfortable_with_all_ages" class="font-medium text-green-700">À l'aise avec toutes les tranches d'âge</div>
                        <div v-else class="flex flex-wrap gap-2">
                            <span
                                v-for="ageRange in acceptedAgeRanges"
                                :key="ageRange.id"
                                class="rounded-full bg-green-100 px-3 py-1 text-sm text-green-800"
                            >
                                {{ ageRange.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Compétences -->
                    <div v-if="profile.skills.length > 0" class="rounded-2xl bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Compétences</h3>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="(skill, index) in profile.skills"
                                :key="skill.id"
                                :class="['rounded-full px-3 py-1 text-sm', getSkillColor(index)]"
                            >
                                {{ skill.name }}
                            </span>
                        </div>
                    </div>

                    <!-- Langues -->
                    <div v-if="profile.languages.length > 0" class="rounded-2xl bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Langues</h3>
                        <div class="space-y-3">
                            <div v-for="language in profile.languages" :key="language.id" class="flex items-center space-x-3">
                                <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                <span class="text-gray-900">
                                    {{ language.name }}
                                    <span v-if="language.pivot?.level" class="text-sm text-gray-500"> ({{ language.pivot.level }}) </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Vérifications -->
                    <div class="rounded-2xl bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-lg font-bold text-gray-900">Vérifications</h3>
                        <div class="space-y-3">
                            <div class="flex items-center space-x-3">
                                <div class="flex h-4 w-4 items-center justify-center rounded-full border-2 border-green-500 bg-green-500">
                                    <svg class="h-2 w-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </div>
                                <span class="text-gray-900">Compte vérifié</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div
                                    :class="[
                                        'flex h-4 w-4 items-center justify-center rounded-full border-2',
                                        profile.documents_verified ? 'border-green-500 bg-green-500' : 'border-gray-300',
                                    ]"
                                >
                                    <svg v-if="profile.documents_verified" class="h-2 w-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </div>
                                <span :class="profile.documents_verified ? 'text-gray-900' : 'text-gray-500'"> Identité vérifiée </span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="flex h-4 w-4 items-center justify-center rounded-full border-2 border-green-500 bg-green-500">
                                    <svg class="h-2 w-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </div>
                                <span class="text-gray-900">Email vérifié</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal pour afficher les photos en grand -->
        <div v-if="showPhotoModal" class="bg-opacity-75 fixed inset-0 z-50 flex items-center justify-center bg-black" @click="closePhotoModal">
            <div class="relative max-h-screen max-w-screen-lg p-4">
                <!-- Bouton fermer -->
                <button
                    @click="closePhotoModal"
                    class="bg-opacity-50 hover:bg-opacity-75 absolute top-4 right-4 z-10 rounded-full bg-black p-2 text-white transition-colors"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Navigation précédent -->
                <button
                    v-if="currentPhotoIndex > 0"
                    @click.stop="prevPhoto"
                    class="bg-opacity-50 hover:bg-opacity-75 absolute top-1/2 left-4 z-10 -translate-y-1/2 rounded-full bg-black p-2 text-white transition-colors"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Navigation suivant -->
                <button
                    v-if="currentPhotoIndex < additionalPhotos.length - 1"
                    @click.stop="nextPhoto"
                    class="bg-opacity-50 hover:bg-opacity-75 absolute top-1/2 right-4 z-10 -translate-y-1/2 rounded-full bg-black p-2 text-white transition-colors"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- Image -->
                <img
                    :src="currentPhoto"
                    :alt="`Photo ${currentPhotoIndex + 1} de ${profile.user.firstname}`"
                    class="max-h-full max-w-full rounded-lg object-contain"
                    @click.stop
                />

                <!-- Indicateur de position -->
                <div class="bg-opacity-50 absolute bottom-4 left-1/2 -translate-x-1/2 rounded-full bg-black px-3 py-1 text-sm text-white">
                    {{ currentPhotoIndex + 1 }} / {{ additionalPhotos.length }}
                </div>
            </div>
        </div>
    </GlobalLayout>
</template>
