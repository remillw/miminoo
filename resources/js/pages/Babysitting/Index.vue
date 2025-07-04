<template>
    <Head title="Mes candidatures et réservations" />

    <DashboardLayout :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- En-tête -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 md:text-3xl">Mes candidatures et réservations</h1>
                <p class="mt-2 text-gray-600">Suivez l'état de vos candidatures et gérez vos réservations</p>
            </div>

            <!-- Onglets de navigation -->
            <div class="mb-8">
                <nav class="flex space-x-8" aria-label="Tabs">
                    <button
                        @click="activeTab = 'candidatures'"
                        :class="[
                            activeTab === 'candidatures'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                        ]"
                    >
                        Mes candidatures
                        <span
                            v-if="applications.length > 0"
                            :class="[
                                activeTab === 'candidatures' ? 'bg-orange-100 text-primary' : 'bg-gray-100 text-gray-900',
                                'ml-2 inline-block rounded-full px-2 py-1 text-xs font-medium',
                            ]"
                        >
                            {{ applications.length }}
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'reservations'"
                        :class="[
                            activeTab === 'reservations'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                            'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                        ]"
                    >
                        Mes réservations
                        <span
                            v-if="reservations.length > 0"
                            :class="[
                                activeTab === 'reservations' ? 'bg-orange-100 text-primary' : 'bg-gray-100 text-gray-900',
                                'ml-2 inline-block rounded-full px-2 py-1 text-xs font-medium',
                            ]"
                        >
                            {{ reservations.length }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Contenu des onglets -->
            <div class="space-y-6">
                <!-- Onglet Candidatures -->
                <div v-if="activeTab === 'candidatures'">
                    <div v-if="applications.length === 0" class="py-12 text-center">
                        <div class="mx-auto h-24 w-24 text-gray-300">
                            <Briefcase class="h-24 w-24" />
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Aucune candidature</h3>
                        <p class="mt-2 text-gray-500">Vous n'avez encore postuler à aucune annonce.</p>
                        <div class="mt-6">
                            <Link
                                href="/annonces"
                                class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-[#e66c48]"
                            >
                                <Search class="mr-2 h-4 w-4" />
                                Voir les annonces
                            </Link>
                        </div>
                    </div>

                    <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-1">
                        <div
                            v-for="application in applications"
                            :key="application.id"
                            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow transition-shadow hover:shadow-md"
                        >
                            <div class="px-6 py-4">
                                <!-- En-tête candidature -->
                                <div class="mb-4 flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="mb-1 text-lg font-semibold text-gray-900">
                                            {{ application.ad.title }}
                                        </h3>
                                        <p class="flex items-center gap-1 text-sm text-gray-600">
                                            <Calendar class="h-4 w-4" />
                                            {{ formatDate(application.ad.date_start) }} de {{ formatTime(application.ad.date_start) }} à
                                            {{ formatTime(application.ad.date_end) }}
                                        </p>
                                    </div>
                                    <span
                                        :class="getStatusClass(application.status)"
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    >
                                        {{ getStatusText(application.status) }}
                                    </span>
                                </div>

                                <!-- Informations parent -->
                                <div class="mb-4 flex items-center gap-3 rounded-lg bg-gray-50 p-3">
                                    <img
                                        :src="application.ad.parent.avatar || '/storage/default-avatar.png'"
                                        :alt="application.ad.parent.name"
                                        class="h-12 w-12 rounded-full border-2 border-gray-200 object-cover"
                                    />
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ application.ad.parent.name }}</h4>
                                        <p class="text-sm text-gray-600">Paris 16e • À domicile</p>
                                    </div>
                                </div>

                                <!-- Tarif et détails -->
                                <div class="mb-4 flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="text-center">
                                            <div class="text-lg font-semibold text-gray-900">
                                                {{ application.proposed_rate || application.ad.hourly_rate }}€
                                            </div>
                                            <div class="text-xs text-gray-500">Total</div>
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            <span class="font-medium">{{ application.proposed_rate || application.ad.hourly_rate }}€/heure</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Note de motivation si présente -->
                                <div v-if="application.motivation_note" class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3">
                                    <p class="text-sm text-blue-800 italic">"{{ application.motivation_note }}"</p>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-3">
                                    <button
                                        v-if="application.status === 'pending'"
                                        @click="openMessaging(application)"
                                        class="inline-flex flex-1 items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        <MessageCircle class="mr-2 h-4 w-4" />
                                        Message
                                    </button>
                                    <button
                                        @click="viewDetails(application)"
                                        class="inline-flex flex-1 items-center justify-center rounded-md border border-transparent bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-orange-700"
                                    >
                                        Détails
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Onglet Réservations -->
                <div v-if="activeTab === 'reservations'">
                    <div v-if="reservations.length === 0" class="py-12 text-center">
                        <div class="mx-auto h-24 w-24 text-gray-300">
                            <Calendar class="h-24 w-24" />
                        </div>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Aucune réservation</h3>
                        <p class="mt-2 text-gray-500">Vos futures gardes apparaîtront ici.</p>
                    </div>

                    <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-1">
                        <div
                            v-for="reservation in reservations"
                            :key="reservation.id"
                            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow transition-shadow hover:shadow-md"
                        >
                            <div class="px-6 py-4">
                                <!-- En-tête réservation -->
                                <div class="mb-4 flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="mb-1 text-lg font-semibold text-gray-900">
                                            {{ reservation.ad.title }}
                                        </h3>
                                        <p class="flex items-center gap-1 text-sm text-gray-600">
                                            <Calendar class="h-4 w-4" />
                                            {{ formatDate(reservation.service_start_at) }} de {{ formatTime(reservation.service_start_at) }} à
                                            {{ formatTime(reservation.service_end_at) }}
                                        </p>
                                    </div>
                                    <span
                                        :class="getReservationStatusClass(reservation.status)"
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    >
                                        {{ getReservationStatusText(reservation.status) }}
                                    </span>
                                </div>

                                <!-- Informations parent -->
                                <div class="mb-4 flex items-center gap-3 rounded-lg bg-gray-50 p-3">
                                    <img
                                        :src="reservation.parent.avatar || '/storage/default-avatar.png'"
                                        :alt="reservation.parent.name"
                                        class="h-12 w-12 rounded-full border-2 border-gray-200 object-cover"
                                    />
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900">{{ reservation.parent.name }}</h4>
                                        <p class="text-sm text-gray-600">Paris 16e • À domicile</p>
                                    </div>
                                    <div v-if="reservation.status === 'completed'" class="text-right">
                                        <button class="text-yellow-600 hover:text-yellow-700">
                                            <Star class="h-5 w-5" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Rémunération -->
                                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3">
                                    <div class="text-sm text-green-800">
                                        <strong>Rémunération estimée</strong>
                                    </div>
                                    <div class="text-2xl font-bold text-green-900">{{ reservation.babysitter_amount }}€</div>
                                    <div class="text-sm text-green-600">
                                        {{ reservation.hourly_rate }}€/heure × {{ getHoursDuration(reservation) }} heures
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-3">
                                    <button
                                        @click="openMessaging(reservation)"
                                        class="inline-flex flex-1 items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        <MessageCircle class="mr-2 h-4 w-4" />
                                        Message
                                    </button>
                                    <button
                                        @click="viewReservationDetails(reservation)"
                                        class="inline-flex flex-1 items-center justify-center rounded-md border border-transparent bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-orange-700"
                                    >
                                        Détails
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup>  
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Briefcase, Calendar, MessageCircle, Search, Star } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const page = usePage();

const user = computed(() => page.props.auth?.user);
const userRoles = computed(() => user.value?.roles?.map(role => role.name) || []);

const hasParentRole = computed(() => userRoles.value.includes('parent'));
const hasBabysitterRole = computed(() => userRoles.value.includes('babysitter'));

// Props
const props = defineProps({
    applications: Array,
    reservations: Array,
});

// State
const activeTab = ref('candidatures');

// Methods
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatTime = (datetime) => {
    return new Date(datetime).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        accepted: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        counter_offered: 'bg-blue-100 text-blue-800',
        archived: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusText = (status) => {
    const texts = {
        pending: 'En attente',
        accepted: 'Acceptée',
        rejected: 'Refusée',
        counter_offered: 'Contre-offre',
        archived: 'Archivée',
    };
    return texts[status] || 'Inconnu';
};

const getReservationStatusClass = (status) => {
    const classes = {
        pending_payment: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-blue-100 text-blue-800',
        active: 'bg-green-100 text-green-800',
        completed: 'bg-gray-100 text-gray-800',
        cancelled_by_parent: 'bg-red-100 text-red-800',
        cancelled_by_babysitter: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getReservationStatusText = (status) => {
    const texts = {
        pending_payment: 'Paiement requis',
        paid: 'Confirmée',
        active: 'En cours',
        completed: 'Terminée',
        cancelled_by_parent: 'Annulée par le parent',
        cancelled_by_babysitter: 'Annulée',
    };
    return texts[status] || 'Inconnu';
};

const getHoursDuration = (reservation) => {
    const start = new Date(reservation.service_start_at);
    const end = new Date(reservation.service_end_at);
    return Math.round(((end - start) / (1000 * 60 * 60)) * 10) / 10;
};

const openMessaging = (item) => {
    // Rediriger vers la messagerie
    window.location.href = '/messagerie';
};

const viewDetails = (application) => {
    // Rediriger vers les détails de l'annonce
    window.location.href = `/annonce/${application.ad.id}`;
};

const viewReservationDetails = (reservation) => {
    // Rediriger vers les détails de la réservation
    window.location.href = `/reservations/${reservation.id}`;
};
</script>
