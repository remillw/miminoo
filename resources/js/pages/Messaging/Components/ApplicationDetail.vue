<template>
    <div class="mx-auto max-w-2xl">
        <!-- En-tête candidature -->
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-start justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ application.ad_title }}</h3>
                    <p class="mt-1 flex items-center gap-1 text-sm text-gray-600">
                        <Calendar class="h-4 w-4" />
                        {{ application.ad_date }}
                    </p>
                </div>
                <StatusBadge :status="application.status" :is-expired="application.is_expired" />
            </div>

            <!-- Informations de la babysitter/parent -->
            <div class="mb-4 flex items-center gap-4 rounded-lg bg-gray-50 p-4">
                <img
                    :src="otherUser.avatar || '/images/default-avatar.png'"
                    :alt="otherUser.name"
                    class="h-16 w-16 rounded-full border-2 border-gray-200 object-cover"
                />
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900">{{ otherUser.name }}</h4>
                    <p v-if="userRole === 'parent' && otherUser.experience" class="text-sm text-gray-600">
                        {{ otherUser.experience }} {{ otherUser.experience === 1 ? 'an' : 'ans' }} d'expérience
                    </p>
                </div>
            </div>

            <!-- Tarif proposé -->
            <div class="mb-4">
                <h4 class="mb-2 font-medium text-gray-900">Tarif proposé</h4>
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold text-blue-900">{{ application.proposed_rate }}€/h</span>
                        <span class="text-sm text-blue-600">Tarif horaire</span>
                    </div>
                </div>
            </div>

            <!-- Contre-offre si applicable -->
            <div v-if="application.counter_rate" class="mb-4">
                <h4 class="mb-2 font-medium text-gray-900">Contre-offre du parent</h4>
                <div class="bg-secondary rounded-lg border border-orange-200 p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-lg font-semibold text-orange-900">{{ application.counter_rate }}€/h</span>
                        <span class="text-primary text-sm">Nouveau tarif proposé</span>
                    </div>
                    <p v-if="application.counter_message" class="text-sm text-orange-800">
                        <strong>Message :</strong> {{ application.counter_message }}
                    </p>
                </div>
            </div>

            <!-- Message de motivation -->
            <div v-if="application.motivation_note" class="mb-4">
                <h4 class="mb-2 font-medium text-gray-900">Message de motivation</h4>
                <div class="rounded-lg bg-gray-50 p-4">
                    <p class="whitespace-pre-wrap text-gray-700">{{ application.motivation_note }}</p>
                </div>
            </div>

            <!-- Timer d'expiration -->
            <div v-if="application.time_remaining && !application.is_expired" class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                <div class="flex items-center gap-2 text-yellow-800">
                    <Clock class="h-5 w-5" />
                    <span class="font-medium">Expire dans {{ application.time_remaining }}</span>
                </div>
            </div>

            <!-- Actions pour babysitter (répondre à contre-offre) -->
            <div v-if="userRole === 'babysitter' && application.status === 'counter_offered' && !application.is_expired" class="border-t pt-4">
                <h4 class="mb-3 font-medium text-gray-900">Répondre à la contre-offre</h4>
                <div class="flex gap-3">
                    <button
                        @click="$emit('respond-counter', application.id, 'accept')"
                        class="flex-1 rounded-lg bg-green-600 px-4 py-3 font-medium text-white transition-colors hover:bg-green-700"
                    >
                        Accepter {{ application.counter_rate }}€/h
                    </button>
                    <button
                        @click="$emit('respond-counter', application.id, 'decline')"
                        class="flex-1 rounded-lg bg-red-600 px-4 py-3 font-medium text-white transition-colors hover:bg-red-700"
                    >
                        Refuser la contre-offre
                    </button>
                </div>
                <p class="mt-2 text-center text-xs text-gray-500">Cette offre expire dans {{ application.time_remaining }}</p>
            </div>

            <!-- Message d'attente pour parent -->
            <div
                v-if="userRole === 'parent' && application.status === 'counter_offered' && !application.is_expired"
                class="border-t pt-4 text-center"
            >
                <div class="inline-flex items-center gap-2 text-blue-600">
                    <div class="h-4 w-4 animate-spin rounded-full border-b-2 border-blue-600"></div>
                    <span class="text-sm font-medium">En attente de la réponse de la babysitter...</span>
                </div>
                <p class="mt-1 text-xs text-gray-500">Réponse attendue avant {{ application.time_remaining }}</p>
            </div>

            <!-- État final -->
            <div v-if="application.status === 'accepted'" class="border-t pt-4 text-center">
                <div class="mb-2 inline-flex items-center gap-2 text-green-600">
                    <CheckCircle class="h-5 w-5" />
                    <span class="font-medium">Candidature acceptée</span>
                </div>
                <p class="text-sm text-gray-600">Une conversation a été créée pour organiser les détails.</p>
            </div>

            <div v-if="application.status === 'declined'" class="border-t pt-4 text-center">
                <div class="mb-2 inline-flex items-center gap-2 text-red-600">
                    <XCircle class="h-5 w-5" />
                    <span class="font-medium">Candidature refusée</span>
                </div>
            </div>

            <div v-if="application.is_expired" class="border-t pt-4 text-center">
                <div class="mb-2 inline-flex items-center gap-2 text-gray-500">
                    <Clock class="h-5 w-5" />
                    <span class="font-medium">Candidature expirée</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Calendar, CheckCircle, Clock, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import StatusBadge from './StatusBadge.vue';

const props = defineProps({
    application: Object,
    userRole: String,
});

const emit = defineEmits(['accept', 'decline', 'respond-counter']);

const otherUser = computed(() => {
    return props.userRole === 'parent' ? props.application.babysitter : props.application.parent;
});
</script>
