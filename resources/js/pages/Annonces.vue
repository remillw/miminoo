<script setup lang="ts">
import CardAnnonce from '@/components/CardAnnonce.vue';
import InfiniteScroll from '@/components/InfiniteScroll.vue';
import { Button } from '@/components/ui/button';
import { useDateFormat } from '@/composables/useDateFormat';
import { useGeolocation } from '@/composables/useGeolocation';
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { router } from '@inertiajs/vue3';
import { Filter, MapPin, Navigation, Search } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { route } from 'ziggy-js';

interface Announcement {
    id: number;
    title: string;
    date_start: string;
    date_end: string;
    status: string;
    created_at: string;
    distance?: number; // Distance calculée côté serveur
    applications_count: number; // Nombre de candidatures
    can_apply: boolean; // Si l'utilisateur peut postuler
    user_application_status?: string; // Statut de la candidature de l'utilisateur
    parent: {
        id: number;
        firstname: string;
        lastname: string;
        avatar?: string;
        average_rating?: number | null;
        total_reviews?: number;
    };
    address: {
        address: string;
        postal_code: string;
        country: string;
        latitude: number;
        longitude: number;
    };
    // Nouvelles colonnes dédiées
    children: Array<{
        nom: string;
        age: string;
        unite: string;
    }>;
    hourly_rate: number;
    estimated_duration: number;
    estimated_total: number;
    additional_info?: string | null; // Maintenant un simple champ texte optionnel
}

interface Props {
    announcements: {
        data: Announcement[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters?: {
        search?: string;
        min_rate?: number;
        age_range?: string;
        date?: string;
        location?: string;
        latitude?: number;
        longitude?: number;
    };
}

const props = defineProps<Props>();

// Géolocalisation
const { userPosition, isGeolocationEnabled, isLoading: geoLoading, requestGeolocation, getDistanceFromUser } = useGeolocation();

// Formatage des dates
const { formatDateRange, parseLocalDate, formatTime } = useDateFormat();

// Filtres
const searchQuery = ref(props.filters?.search || '');
const tarif = ref(props.filters?.min_rate || 10);
const age = ref(props.filters?.age_range || '');
const date = ref(props.filters?.date || '');
const lieu = ref(props.filters?.location || '');
const showFilters = ref(false);

// Variables pour éviter la boucle infinie
const lastLocationSent = ref<number>(0);
const LOCATION_CACHE_DURATION = 5 * 60 * 1000; // 5 minutes en millisecondes

// Appliquer les filtres
const applyFilters = async () => {
    const params: any = {};

    if (searchQuery.value.trim()) params.search = searchQuery.value.trim();
    if (tarif.value > 10) params.min_rate = tarif.value;
    if (age.value) params.age_range = age.value;
    if (date.value) params.date = date.value;
    if (lieu.value.trim()) params.location = lieu.value.trim();

    // Si géolocalisation activée ET coordonnées pas envoyées récemment
    const now = Date.now();
    if (isGeolocationEnabled.value && userPosition.value && now - lastLocationSent.value > LOCATION_CACHE_DURATION) {
        try {
            // Stocker les coordonnées côté serveur via une requête séparée
            await fetch('/api/set-user-location', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    latitude: userPosition.value.latitude,
                    longitude: userPosition.value.longitude,
                }),
            });
            lastLocationSent.value = now;
        } catch (error) {
            console.warn('Impossible de définir la position utilisateur:', error);
        }
    }

    // Faire la requête normale sans coordonnées dans l'URL (reset de la pagination)
    router.get(route('announcements.index'), params, {
        preserveState: false,
        preserveScroll: false,
    });
};

// Réinitialiser les filtres
const resetFilters = () => {
    searchQuery.value = '';
    tarif.value = 10;
    age.value = '';
    date.value = '';
    lieu.value = '';

    // Faire une requête pour obtenir toutes les annonces
    router.get(
        route('announcements.index'),
        {},
        {
            preserveState: false,
            preserveScroll: false,
        },
    );
};

// Appliquer les filtres et fermer le panneau
const applyFiltersAndClose = () => {
    applyFilters();
    showFilters.value = false;
};

// Recherche en temps réel pour la query seulement
const searchWithDelay = (() => {
    let timeout: number;
    return () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            applyFilters();
        }, 500);
    };
})();

// Application automatique des filtres quand ils changent
const applyFiltersWithDelay = (() => {
    let timeout: number;
    return () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            // Ne pas fermer les filtres automatiquement
            applyFilters();
        }, 1000); // Plus de délai pour laisser temps aux utilisateurs
    };
})();

// Watchers pour tous les filtres
watch(searchQuery, searchWithDelay);
// Seulement pour les filtres les plus importants automatiquement
watch([tarif], applyFiltersWithDelay);
// Les autres filtres sont appliqués quand on clique "Réinitialiser" ou à la fermeture du panneau

// Activer la géolocalisation
const enableGeolocation = async () => {
    const position = await requestGeolocation();
    if (position) {
        const now = Date.now();
        // Seulement envoyer si pas déjà envoyé récemment
        if (now - lastLocationSent.value > LOCATION_CACHE_DURATION) {
            try {
                // Stocker les coordonnées côté serveur
                await fetch('/api/set-user-location', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        latitude: position.latitude,
                        longitude: position.longitude,
                    }),
                });
                lastLocationSent.value = now;
            } catch (error) {
                console.warn('Impossible de définir la position utilisateur:', error);
            }
        }

        // Relancer la recherche avec la position
        applyFilters();
    }
};

// Filtre actuel pour InfiniteScroll - exclure les coordonnées de géolocalisation pour éviter les conflits
const currentFilters = computed(() => {
    const filters: any = {};

    if (searchQuery.value) filters.search = searchQuery.value;
    if (tarif.value > 10) filters.min_rate = tarif.value;
    if (age.value) filters.age_range = age.value;
    if (date.value) filters.date = date.value;
    if (lieu.value) filters.location = lieu.value;

    // Ne pas inclure les coordonnées dans les paramètres de pagination infinie
    // car elles sont gérées séparément dans applyFilters()

    return filters;
});

// État local pour les annonces (incluant celles chargées via infinite scroll)
const allAnnouncements = ref([...props.announcements.data]);

// Transformer les annonces backend pour le composant CardAnnonce
const annonces = computed(() => {
    // Filtrer d'abord les annonces passées côté frontend pour plus de sécurité
    const filteredAnnouncements = allAnnouncements.value.filter((announcement) => {
        const startDate = new Date(announcement.date_start);
        const now = new Date();
        return startDate > now; // Exclure les annonces dont la date/heure de début est déjà passée
    });

    return filteredAnnouncements.map((announcement) => {
        // Calculer les âges des enfants - utiliser la nouvelle colonne children
        const childrenAges = announcement.children.map((child) => `${child.age} ${child.unite}`);

        // Formatage de la date et gestion multi-jours avec correction du décalage horaire
        const { dateDisplay, timeDisplay, isMultiDay } = formatDateRange(
            announcement.date_start,
            announcement.date_end,
            announcement.estimated_duration,
        );

        // Parser les dates pour les utiliser dans les calculs
        const dateStart = parseLocalDate(announcement.date_start);
        const dateEnd = parseLocalDate(announcement.date_end);

        // Extraction de la ville depuis l'adresse
        const addressParts = announcement.address.address.split(',');
        // On retire le pays
        addressParts.pop();
        // On prend la ville et on retire le code postal s'il est présent
        let city = addressParts.pop()?.trim() || 'Non spécifié';
        // Enlever le code postal de la ville s'il est présent
        city = city.replace(/\d{5}/, '').trim();
        const postalCode = announcement.address.postal_code;

        // Distance calculée
        let distance: number | undefined = undefined;
        if (announcement.distance !== undefined) {
            distance = parseFloat(announcement.distance.toFixed(1));
        } else if (isGeolocationEnabled.value) {
            const calculatedDistance = getDistanceFromUser(announcement.address.latitude, announcement.address.longitude);
            distance = calculatedDistance ? parseFloat(calculatedDistance.toFixed(1)) : undefined;
        }

        return {
            id: announcement.id,
            parentId: announcement.parent?.id || 0, // ID du parent pour vérifier la propriété
            avatar: announcement.parent?.avatar || '/storage/default-avatar.png',
            name: announcement.parent ? `${announcement.parent.firstname} ${announcement.parent.lastname.charAt(0)}.` : 'Parent invité',
            rating: announcement.parent?.average_rating || null, // Vraie note du parent
            reviews: announcement.parent?.total_reviews || 0, // Nombre réel d'avis
            date: dateDisplay, // Utilise le formatage adapté multi-jours
            rawDate: announcement.date_start, // Date ISO pour le modal
            time: timeDisplay, // Utilise le formatage adapté multi-jours
            startTime: formatTime(dateStart), // Heure de début pour calcul de durée
            endTime: formatTime(dateEnd), // Heure de fin pour calcul de durée
            postalCode: postalCode,
            city: city,
            childrenLabel: `${announcement.children.length} enfant${announcement.children.length > 1 ? 's' : ''} (${childrenAges.join(', ')})`,
            childrenCount: announcement.children.length,
            description: announcement.additional_info || '', // Pour l'affichage dans la card
            additionalInfo: announcement.additional_info || undefined, // Pour passer à la modal
            rate: announcement.hourly_rate, // Nouvelle colonne dédiée
            distance: distance,
            latitude: announcement.address.latitude,
            longitude: announcement.address.longitude,
            isMultiDay: isMultiDay, // Ajouter cette info pour la card
            applicationsCount: announcement.applications_count, // Nombre de candidatures
            canApply: announcement.can_apply, // Si l'utilisateur peut postuler
            userApplicationStatus: announcement.user_application_status, // Statut de la candidature
            existingApplication: announcement.existing_application, // Données de candidature existante pour repostulation
        };
    });
});

const progressStyle = computed(() => {
    const percent = ((tarif.value - 10) / (100 - 10)) * 100;
    return {
        background: `linear-gradient(to right, #FF8359 ${percent}%, #E5E7EB ${percent}%)`,
    };
});

// Gestionnaires pour l'infinite scroll
const handleLoadMore = (data: any) => {
    if (data.announcements && data.announcements.data) {
        // Ajouter les nouvelles annonces à la liste existante
        allAnnouncements.value.push(...data.announcements.data);
    }
};

const handleError = (error: string) => {
    console.error('Erreur infinite scroll:', error);
};

// Watcher pour réinitialiser les données quand les filtres changent
watch(
    () => props.announcements.data,
    (newData) => {
        allAnnouncements.value = [...newData];
    },
    { deep: true },
);

// Charger la position au montage si elle existe
onMounted(() => {
    // Ne plus relancer automatiquement avec géolocalisation pour éviter les boucles
    // L'utilisateur devra cliquer sur "Activer" pour déclencher le tri par distance
});
</script>

<template>
    <GlobalLayout>
        <div class="bg-secondary min-h-screen px-4 py-16">
            <div class="mx-auto max-w-7xl">
                <!-- Titre + Sous-titre -->
                <h1 class="mb-2 text-center text-2xl font-bold md:text-3xl">Trouver votre prochain babysitting</h1>
                <p class="mx-auto mb-10 max-w-xl text-center text-gray-600">
                    Découvrez des opportunités de garde d'enfants qui correspondent à vos disponibilités et à vos compétences
                </p>

                <!-- Géolocalisation -->
                <div v-if="!isGeolocationEnabled" class="mb-6 flex justify-center">
                    <div class="flex items-center gap-4 rounded-xl bg-white p-4 shadow-md">
                        <MapPin class="text-primary h-5 w-5" />
                        <span class="text-gray-700">Activez la géolocalisation pour voir les annonces les plus proches</span>
                        <Button @click="enableGeolocation" :disabled="geoLoading" size="sm" class="bg-primary hover:bg-primary">
                            <Navigation class="mr-2 h-4 w-4" />
                            {{ geoLoading ? 'Localisation...' : 'Activer' }}
                        </Button>
                    </div>
                </div>

                <!-- Barre de recherche + bouton filtre optimisée mobile -->
                <div class="mb-6 flex items-center justify-center gap-2 sm:mb-10">
                    <div class="relative flex w-full max-w-3xl">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Rechercher par lieu, nom des parents, mots-clé…"
                            class="focus:ring-primary w-full rounded-l-xl border border-gray-300 bg-white py-3 pr-4 pl-12 text-sm placeholder-gray-400 shadow-sm focus:ring-2 focus:outline-none sm:py-4 sm:text-base"
                        />
                        <Search class="absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2 text-gray-400 sm:h-5 sm:w-5" />
                        <button
                            @click="showFilters = !showFilters"
                            :aria-pressed="showFilters"
                            :class="[
                                'flex items-center rounded-r-xl border border-l-0 border-gray-300 px-4 transition sm:px-5',
                                showFilters ? 'bg-primary border-primary' : 'bg-white hover:bg-gray-100',
                            ]"
                        >
                            <Filter :class="['h-4 w-4 sm:h-5 sm:w-5', showFilters ? 'text-white' : 'text-gray-500']" />
                        </button>
                    </div>
                </div>

                <!-- Bloc filtres optimisé mobile -->
                <div
                    v-if="showFilters"
                    class="mx-auto mb-6 grid max-w-6xl grid-cols-1 gap-4 rounded-2xl bg-white p-4 shadow-md sm:p-6 md:grid-cols-4 md:gap-6"
                >
                    <!-- Tarif -->
                    <div>
                        <h3 class="mb-6 text-lg font-semibold">Tarif horaire minimum</h3>
                        <div class="flex items-center gap-4">
                            <span class="w-10 text-right text-sm font-semibold text-gray-900">{{ tarif }}€</span>

                            <input
                                type="range"
                                min="10"
                                max="100"
                                step="5"
                                v-model="tarif"
                                class="h-2 w-full appearance-none rounded-full transition-all duration-150"
                                :style="progressStyle"
                            />

                            <span class="w-10 text-sm font-semibold text-gray-900">100€</span>
                        </div>
                    </div>

                    <!-- Âge -->
                    <div>
                        <h3 class="mb-3 text-lg font-semibold">Âge des enfants</h3>
                        <div class="relative">
                            <select
                                v-model="age"
                                class="focus:ring-primary w-full appearance-none rounded-xl border border-gray-300 bg-white px-4 py-2 pr-10 text-base text-gray-700 focus:ring-2 focus:outline-none"
                            >
                                <option value="">Tous les âges</option>
                                <option value="<3">&lt; 3 ans</option>
                                <option value="3-6">3 - 6 ans</option>
                                <option value="6+">+ 6 ans</option>
                            </select>

                            <svg
                                class="pointer-events-none absolute top-1/2 right-3 h-5 w-5 -translate-y-1/2 text-gray-400"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Date -->
                    <div>
                        <h3 class="mb-3 text-lg font-semibold">Date</h3>
                        <div class="relative">
                            <input
                                type="date"
                                v-model="date"
                                class="focus:ring-primary w-full rounded-xl border border-gray-300 bg-white px-4 py-2 pr-10 text-base text-gray-700 focus:ring-2 focus:outline-none"
                            />

                            <svg
                                class="pointer-events-none absolute top-1/2 right-3 h-5 w-5 -translate-y-1/2 text-gray-400"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Lieu -->
                    <div>
                        <h3 class="mb-3 text-lg font-semibold">Lieu</h3>
                        <div class="relative">
                            <input
                                type="text"
                                v-model="lieu"
                                placeholder="Dans quelle ville ?"
                                class="focus:ring-primary w-full rounded-xl border border-gray-300 bg-white px-4 py-2 pr-10 text-base text-gray-700 focus:ring-2 focus:outline-none"
                            />

                            <svg
                                class="pointer-events-none absolute top-1/2 right-3 h-5 w-5 -translate-y-1/2 text-gray-400"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 11c1.104 0 2-.896 2-2s-.896-2-2-2-2 .896-2 2 .896 2 2 2z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Boutons -->
                    <div class="flex justify-between gap-4 pt-4 md:col-span-4">
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 bg-white px-6 py-2 text-sm font-semibold transition-colors hover:bg-gray-100"
                            @click="resetFilters"
                        >
                            Réinitialiser
                        </button>
                        <button
                            type="button"
                            @click="applyFiltersAndClose"
                            class="bg-primary hover:bg-primary rounded-md px-6 py-2 text-sm font-semibold text-white transition-colors"
                        >
                            Appliquer les filtres
                        </button>
                    </div>
                </div>

                <!-- Information géolocalisation -->
                <div v-if="isGeolocationEnabled" class="mb-6 text-center">
                    <p class="text-sm text-gray-600">
                        📍 Annonces triées par distance ({{ props.announcements.total }} résultat{{ props.announcements.total > 1 ? 's' : '' }})
                    </p>
                </div>

                <!-- Liste des annonces avec scroll infini -->
                <InfiniteScroll
                    :pagination="props.announcements"
                    :route="'announcements.index'"
                    :parameters="currentFilters"
                    loading-message="Chargement des annonces..."
                    end-message="Toutes les annonces ont été chargées"
                    @load-more="handleLoadMore"
                    @error="handleError"
                >
                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 md:mt-10 lg:grid-cols-3 xl:gap-8">
                        <CardAnnonce v-for="annonce in annonces" :key="annonce.id" v-bind="annonce" />
                    </div>

                    <!-- Message si aucune annonce optimisé mobile -->
                    <div v-if="annonces.length === 0" class="mt-6 text-center sm:mt-10">
                        <div class="mx-auto max-w-md rounded-lg bg-white p-6 shadow-md sm:p-8">
                            <div class="mb-4 text-6xl">👶</div>
                            <h3 class="mb-2 text-xl font-semibold text-gray-800">Aucune annonce trouvée</h3>
                            <p class="mb-4 text-gray-600">
                                {{
                                    Object.keys(props.filters || {}).length > 0
                                        ? 'Essayez de modifier vos critères de recherche ou réinitialisez les filtres.'
                                        : 'Aucune annonce de babysitting disponible pour le moment. Revenez bientôt !'
                                }}
                            </p>
                            <div class="mt-4 flex flex-col gap-2">
                                <button
                                    v-if="Object.keys(props.filters || {}).length > 0"
                                    @click="resetFilters"
                                    class="bg-primary hover:bg-primary inline-block rounded-lg px-6 py-2 font-semibold text-white transition-colors"
                                >
                                    Réinitialiser les filtres
                                </button>
                                <button
                                    v-else
                                    @click="() => window.location.reload()"
                                    class="inline-block rounded-lg bg-blue-500 px-6 py-2 font-semibold text-white transition-colors hover:bg-blue-600"
                                >
                                    Actualiser la page
                                </button>
                            </div>
                        </div>
                    </div>
                </InfiniteScroll>
            </div>
        </div>
    </GlobalLayout>
</template>

<style scoped>
input[type='range'] {
    height: 8px; /* plus fin pour s'aligner au pouce */
}

input[type='range']::-webkit-slider-thumb {
    -webkit-appearance: none;
    height: 16px;
    width: 16px;
    border-radius: 50%;
    background-color: #ff8359;
    cursor: pointer;
    margin-top: 0px; /* ajusté pour centrer */
    box-shadow: 0 0 2px rgba(0, 0, 0, 0.2);
    transition: background 0.2s ease;
}

input[type='range']::-moz-range-thumb {
    height: 16px;
    width: 16px;
    border-radius: 50%;
    background-color: #ff8359;
    cursor: pointer;
    box-shadow: 0 0 2px rgba(0, 0, 0, 0.2);
}

/* Masque l'icône native du champ date sur Chrome/Safari */
input[type='date']::-webkit-calendar-picker-indicator {
    opacity: 0;
    cursor: pointer;
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0; /* masque l'icône réelle du navigateur */
    width: 100%; /* étend la zone cliquable */
    height: 100%;
    cursor: pointer;
}

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
