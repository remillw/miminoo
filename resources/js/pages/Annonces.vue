<script setup lang="ts">
import CardAnnonce from '@/components/CardAnnonce.vue';
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Filter, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Announcement {
    id: number;
    title: string;
    description: string;
    date_start: string;
    date_end: string;
    status: string;
    created_at: string;
    parent: {
        id: number;
        firstname: string;
        lastname: string;
        avatar?: string;
    };
    address: {
        address: string;
        postal_code: string;
        country: string;
        latitude: number;
        longitude: number;
    };
    additional_data: {
        children: Array<{
            nom: string;
            age: string;
            unite: string;
        }>;
        hourly_rate: number;
        estimated_duration: number;
        estimated_total: number;
    };
}

interface Props {
    announcements: {
        data: Announcement[];
        current_page: number;
        last_page: number;
        total: number;
    };
}

const props = defineProps<Props>();

const tarif = ref(10);
const age = ref('');
const date = ref('');
const lieu = ref('');

// Transformer les annonces backend pour le composant CardAnnonce
const annonces = computed(() => {
    return props.announcements.data.map((announcement) => {
        // Calculer les âges des enfants
        const childrenAges = announcement.additional_data.children.map((child) => `${child.age} ${child.unite}`);

        // Formatage de la date
        const dateStart = new Date(announcement.date_start);
        const dateEnd = new Date(announcement.date_end);

        const formatDate = (date: Date) => {
            return date.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: 'long',
                year: 'numeric',
            });
        };

        const formatTime = (date: Date) => {
            return date.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        // Extraction de la ville depuis l'adresse
        const addressParts = announcement.address.address.split(',');
        // On retire le pays
        addressParts.pop();
        // On prend la ville et on retire le code postal s'il est présent
        let city = addressParts.pop()?.trim() || 'Non spécifié';
        // Enlever le code postal de la ville s'il est présent
        city = city.replace(/\d{5}/, '').trim();
        const postalCode = announcement.address.postal_code;

        return {
            id: announcement.id,
            avatar: announcement.parent.avatar || '/storage/default-avatar.png',
            name: `${announcement.parent.firstname} ${announcement.parent.lastname.charAt(0)}.`,
            rating: 4.5, // Valeur par défaut, à implémenter plus tard
            reviews: 0, // Valeur par défaut, à implémenter plus tard
            date: formatDate(dateStart),
            rawDate: announcement.date_start, // Date ISO pour le modal
            time: `${formatTime(dateStart)} - ${formatTime(dateEnd)}`,
            postalCode: postalCode,
            city: city,
            childrenLabel: `${announcement.additional_data.children.length} enfant${announcement.additional_data.children.length > 1 ? 's' : ''} (${childrenAges.join(', ')})`,
            childrenCount: announcement.additional_data.children.length,
            description: announcement.description,
            rate: announcement.additional_data.hourly_rate,
        };
    });
});

const resetFilters = () => {
    tarif.value = 10;
    age.value = '';
    date.value = '';
    lieu.value = '';
};

const progressStyle = computed(() => {
    const percent = ((tarif.value - 10) / (100 - 10)) * 100;
    return {
        background: `linear-gradient(to right, #FF8359 ${percent}%, #E5E7EB ${percent}%)`,
    };
});

const showFilters = ref(false);
</script>

<template>
    <GlobalLayout>
        <div class="min-h-screen bg-orange-50 px-4 py-16">
            <div class="mx-auto max-w-7xl">
                <!-- Titre + Sous-titre -->
                <h1 class="mb-2 text-center text-2xl font-bold md:text-3xl">Trouver votre prochain babysitting</h1>
                <p class="mx-auto mb-10 max-w-xl text-center text-gray-600">
                    Découvrez des opportunités de garde d'enfants qui correspondent à vos disponibilités et à vos compétences
                </p>

                <!-- Barre de recherche + bouton filtre -->
                <div class="mb-10 flex items-center justify-center gap-2">
                    <div class="relative flex w-full max-w-3xl">
                        <input
                            type="text"
                            placeholder="Rechercher par lieu, nom des parents, mots-clé…"
                            class="focus:ring-primary w-full rounded-l-xl border border-gray-300 bg-white py-4 pr-4 pl-12 text-base placeholder-gray-400 shadow-sm focus:ring-2 focus:outline-none"
                        />
                        <Search class="absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2 text-gray-400" />
                        <button
                            @click="showFilters = !showFilters"
                            :aria-pressed="showFilters"
                            :class="[
                                'flex items-center rounded-r-xl border border-l-0 border-gray-300 px-5 transition',
                                showFilters ? 'bg-primary border-primary' : 'bg-white hover:bg-gray-100',
                            ]"
                        >
                            <Filter :class="['h-5 w-5', showFilters ? 'text-white' : 'text-gray-500']" />
                        </button>
                    </div>
                </div>

                <!-- Bloc filtres -->
                <div v-if="showFilters" class="mx-auto grid max-w-6xl grid-cols-1 gap-6 rounded-2xl bg-white p-6 shadow-md md:grid-cols-4">
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
                    <div class="flex justify-end gap-4 pt-4 md:col-span-4">
                        <button
                            class="rounded-md border border-gray-300 bg-white px-6 py-2 text-sm font-semibold hover:bg-gray-100"
                            @click="resetFilters"
                        >
                            Réinitialiser
                        </button>

                        <button class="bg-primary rounded-md px-6 py-2 text-sm font-semibold text-white hover:bg-orange-500">Appliquer</button>
                    </div>
                </div>

                <!-- Liste des annonces -->
                <div class="mt-10 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <CardAnnonce v-for="annonce in annonces" :key="annonce.id" v-bind="annonce" />
                </div>

                <!-- Message si aucune annonce -->
                <div v-if="annonces.length === 0" class="mt-10 text-center">
                    <div class="mx-auto max-w-md rounded-lg bg-white p-8 shadow-md">
                        <div class="mb-4 text-6xl">👶</div>
                        <h3 class="mb-2 text-xl font-semibold text-gray-800">Aucune annonce disponible</h3>
                        <p class="text-gray-600">Soyez le premier à publier une annonce de babysitting !</p>
                        <a
                            href="/annonces/create"
                            class="mt-4 inline-block rounded-lg bg-orange-500 px-6 py-2 font-semibold text-white transition-colors hover:bg-orange-600"
                        >
                            Créer une annonce
                        </a>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="props.announcements.last_page > 1" class="mt-10 flex justify-center gap-2">
                    <button
                        v-for="page in props.announcements.last_page"
                        :key="page"
                        :class="[
                            'flex h-9 w-9 items-center justify-center rounded-full font-bold transition-colors',
                            page === props.announcements.current_page
                                ? 'bg-primary text-white'
                                : 'border border-gray-300 bg-white text-gray-400 hover:bg-gray-100',
                        ]"
                    >
                        {{ page }}
                    </button>
                </div>
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
</style>
