<script setup lang="ts">
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

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
}

interface Address {
    address: string;
    postal_code: string;
    country: string;
    latitude: number;
    longitude: number;
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
    parent: Parent;
    address: Address;
}

interface Props {
    announcement: Announcement;
}

const props = defineProps<Props>();

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
    const start = new Date(props.announcement.date_start);
    const end = new Date(props.announcement.date_end);
    const diffMs = end.getTime() - start.getTime();
    const diffHours = Math.round((diffMs / (1000 * 60 * 60)) * 10) / 10;
    return diffHours;
});

// Obtenir la ville depuis l'adresse
const getCity = (address: Address) => {
    const parts = address.address.split(',');
    return parts[parts.length - 1]?.trim() || '';
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
</script>

<template>
    <Head :title="`${announcement.title} - Annonce`" />

    <GlobalLayout>
        <div class="bg-secondary min-h-screen p-4">
            <div class="mx-auto max-w-4xl">
                <!-- Breadcrumb -->
                <nav class="mb-6 flex items-center space-x-2 text-sm text-gray-600">
                    <a href="/announcements" class="hover:text-primary transition-colors">Annonces</a>
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                            clip-rule="evenodd"
                        ></path>
                    </svg>
                    <span class="text-gray-900">{{ announcement.title }}</span>
                </nav>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Contenu principal -->
                    <div class="space-y-6 lg:col-span-2">
                        <!-- En-tête de l'annonce -->
                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <div class="mb-4 flex items-start justify-between">
                                <div class="flex-1">
                                    <h1 class="mb-2 text-2xl font-bold text-gray-900">{{ announcement.title }}</h1>
                                    <div class="flex items-center gap-4 text-sm text-gray-600">
                                        <div class="flex items-center gap-1">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            {{ formatDate(announcement.date_start) }}
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            {{ formatTime(announcement.date_start) }} - {{ formatTime(announcement.date_end) }}
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            {{ getCity(announcement.address) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-primary text-2xl font-bold">{{ announcement.hourly_rate }}€/h</div>
                                    <div class="text-sm text-gray-600">{{ duration }}h • {{ announcement.estimated_total }}€ total</div>
                                </div>
                            </div>

                            <!-- Statut -->
                            <div
                                v-if="isFuture"
                                class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800"
                            >
                                <div class="mr-2 h-2 w-2 rounded-full bg-green-500"></div>
                                Disponible
                            </div>
                            <div v-else class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-800">
                                <div class="mr-2 h-2 w-2 rounded-full bg-gray-500"></div>
                                Passée
                            </div>
                        </div>

                        <!-- Enfants à garder -->
                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <h2 class="mb-4 text-xl font-bold text-gray-900">Enfants à garder</h2>
                            <div class="flex flex-wrap gap-3">
                                <div
                                    v-for="(child, index) in announcement.children"
                                    :key="index"
                                    :class="['flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium', getChildColor(index)]"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ child.nom }} ({{ formatChildAge(child) }})
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div v-if="announcement.description" class="rounded-2xl bg-white p-6 shadow-sm">
                            <h2 class="mb-4 text-xl font-bold text-gray-900">Informations complémentaires</h2>
                            <p class="leading-relaxed whitespace-pre-line text-gray-700">{{ announcement.description }}</p>
                        </div>

                        <!-- Localisation -->
                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <h2 class="mb-4 text-xl font-bold text-gray-900">Localisation</h2>
                            <div class="flex items-start gap-3">
                                <svg class="mt-0.5 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900">{{ announcement.address.address }}</p>
                                    <p class="text-sm text-gray-600">{{ announcement.address.postal_code }}, {{ announcement.address.country }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Profil du parent -->
                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <h3 class="mb-4 text-lg font-bold text-gray-900">Parent</h3>
                            <div class="flex items-center space-x-3">
                                <img
                                    :src="announcement.parent.avatar || '/storage/default-avatar.png'"
                                    :alt="`${announcement.parent.firstname} ${announcement.parent.lastname}`"
                                    class="h-12 w-12 rounded-full object-cover"
                                />
                                <div class="flex-1">
                                    <a
                                        :href="route('parent.show', { slug: announcement.parent.slug })"
                                        class="hover:text-primary font-semibold text-gray-900 transition-colors"
                                    >
                                        {{ announcement.parent.firstname }} {{ announcement.parent.lastname }}
                                    </a>
                                    <p class="text-sm text-gray-600">Parent vérifié</p>
                                </div>
                            </div>
                        </div>

                        <!-- Résumé de la mission -->
                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <h3 class="mb-4 text-lg font-bold text-gray-900">Résumé</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Date</span>
                                    <span class="font-medium text-gray-900">{{ formatDate(announcement.date_start) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Horaires</span>
                                    <span class="font-medium text-gray-900"
                                        >{{ formatTime(announcement.date_start) }} - {{ formatTime(announcement.date_end) }}</span
                                    >
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Durée</span>
                                    <span class="font-medium text-gray-900">{{ duration }}h</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Enfants</span>
                                    <span class="font-medium text-gray-900">{{ announcement.children.length }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tarif horaire</span>
                                    <span class="font-medium text-gray-900">{{ announcement.hourly_rate }}€/h</span>
                                </div>
                                <hr class="my-3" />
                                <div class="flex justify-between text-lg font-bold">
                                    <span class="text-gray-900">Total estimé</span>
                                    <span class="text-primary">{{ announcement.estimated_total }}€</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bouton de candidature -->
                        <div v-if="isFuture" class="rounded-2xl bg-white p-6 shadow-sm">
                            <button
                                class="bg-primary hover:bg-primary/90 mb-3 w-full rounded-lg px-4 py-3 font-semibold text-white transition-colors"
                            >
                                Postuler à cette annonce
                            </button>
                            <p class="text-center text-xs text-gray-500">Vous devez être connecté en tant que babysitter pour postuler</p>
                        </div>

                        <!-- Annonce expirée -->
                        <div v-else class="rounded-2xl bg-white p-6 shadow-sm">
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
                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <h3 class="mb-4 text-lg font-bold text-gray-900">Informations</h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center gap-2 text-gray-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                    Publié le {{ formatDateTime(announcement.created_at) }}
                                </div>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                    Annonce #{{ announcement.id }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GlobalLayout>
</template>
