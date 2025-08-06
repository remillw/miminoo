<template>
    <div class="flex w-full max-w-md flex-col gap-4 rounded-3xl bg-white p-4 shadow-md sm:p-6">
        <!-- Header: Avatar + Name + Rating optimisé mobile -->
        <div class="flex items-center gap-3 sm:gap-4">
            <img :src="avatar" alt="Avatar" class="h-12 w-12 rounded-full object-cover sm:h-14 sm:w-14" />
            <div class="min-w-0 flex-1">
                <div class="truncate text-base font-bold sm:text-lg">{{ name }}</div>

                <!-- N'afficher les avis que s'il y en a -->
                <div v-if="rating && reviews > 0" class="flex items-center gap-1 text-sm text-gray-400">
                    <Star class="h-4 w-4 fill-current text-yellow-400" />
                    <span class="font-semibold text-gray-700">{{ rating }}</span>
                    <span class="text-gray-400">({{ reviews }} avis)</span>
                </div>
            </div>
        </div>

        <!-- Date, time, location optimisé mobile -->
        <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 sm:gap-4">
            <div class="flex items-start gap-2">
                <CalendarClock class="text-primary bg-secondary h-6 w-6 rounded-md p-1 sm:h-7 sm:w-7" />
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ date }}</p>
                    <p class="text-gray-400">{{ time }}</p>
                </div>
            </div>
            <div class="flex items-start gap-2">
                <MapPin class="text-primary bg-secondary h-6 w-6 rounded-md p-1 sm:h-7 sm:w-7" />
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold">
                        {{ city }}<span class="text-gray-500">, {{ postalCode }}</span>
                    </p>
                    <!-- Affichage de la distance si disponible -->
                    <p v-if="distance !== null" class="flex items-center gap-1 text-xs text-gray-500">
                        <span>📍 {{ distance }} km</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Kids optimisé mobile -->
        <div class="flex items-center gap-2 text-sm">
            <Users class="text-primary bg-secondary h-6 w-6 rounded-md p-1 sm:h-7 sm:w-7" />
            <p class="min-w-0 flex-1 truncate font-semibold">{{ childrenLabel }}</p>
        </div>

        <!-- Description optimisée mobile -->
        <p v-if="description" class="line-clamp-2 text-sm text-gray-800 sm:line-clamp-3">
            {{ description }}
        </p>

        <!-- Footer optimisé mobile -->
        <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-2 sm:pt-3">
            <div class="min-w-0 flex-1">
                <span class="text-lg font-bold sm:text-xl">{{ rate }}€</span>
                <span class="text-gray-400">/heure</span>
            </div>

            <!-- Bouton Postuler, Annonce pleine, ou message si c'est sa propre annonce -->
            <button
                v-if="!isOwnAnnouncement && !isAnnouncementFull"
                @click="isModalOpen = true"
                class="bg-primary hover:bg-primary rounded px-4 py-2 text-sm font-semibold text-white transition-colors sm:px-5"
            >
                Postuler
            </button>

            <div
                v-else-if="!isOwnAnnouncement && isAnnouncementFull"
                class="rounded bg-gray-300 px-3 py-2 text-sm font-medium text-gray-600 cursor-not-allowed"
                title="Cette annonce a atteint le nombre maximum de candidatures"
            >
                Annonce pleine
            </div>

            <div v-else class="rounded bg-gray-100 px-3 py-2 text-sm font-medium text-gray-500">Votre annonce</div>
        </div>

        <!-- Modal de candidature -->
        <PostulerModal
            :is-open="isModalOpen"
            :on-close="() => (isModalOpen = false)"
            :announcement-id="id"
            :date="rawDate"
            :hours="time"
            :location="`${city}, ${postalCode}`"
            :children-count="childrenCount"
            :avatar-url="avatar"
            :family-name="name"
            :requested-rate="rate"
            :additional-info="additionalInfo"
            :start-time="startTime"
            :end-time="endTime"
        />
    </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CalendarClock, MapPin, Star, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import PostulerModal from './PostulerModal.vue';

const props = defineProps({
    id: {
        type: Number,
        required: true,
    },
    parentId: {
        type: Number,
        required: true,
    },
    avatar: {
        type: String,
        required: true,
    },
    name: {
        type: String,
        required: true,
    },
    rating: {
        type: [Number, null],
        default: null,
    },
    reviews: {
        type: Number,
        default: 0,
    },
    date: {
        type: String,
        required: true,
    },
    rawDate: {
        type: String,
        required: true,
    },
    time: {
        type: String,
        required: true,
    },
    startTime: {
        type: String,
        required: true,
    },
    endTime: {
        type: String,
        required: true,
    },
    postalCode: {
        type: String,
        required: true,
    },
    city: {
        type: String,
        required: true,
    },
    childrenLabel: {
        type: String,
        required: true,
    },
    childrenCount: {
        type: Number,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    rate: {
        type: Number,
        required: true,
    },
    distance: {
        type: Number,
        default: null,
    },
    latitude: {
        type: Number,
        required: true,
    },
    longitude: {
        type: Number,
        required: true,
    },
    additionalInfo: {
        type: String,
        default: null,
    },
    isMultiDay: {
        type: Boolean,
        default: false,
    },
    applicationsCount: {
        type: Number,
        default: 0,
    },
});

const isModalOpen = ref(false);

// Accès à l'utilisateur connecté
const page = usePage();
const user = computed(() => (page.props as any).auth?.user);

// Vérifier si c'est la propre annonce de l'utilisateur
const isOwnAnnouncement = computed(() => {
    return user.value && props.parentId === user.value.id;
});

// Vérifier si l'annonce est pleine (10 candidatures ou plus)
const isAnnouncementFull = computed(() => {
    return props.applicationsCount >= 10;
});
</script>

<style scoped>
/* Classes utilitaires pour line-clamp */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
