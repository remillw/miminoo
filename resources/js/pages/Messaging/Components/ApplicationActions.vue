<template>
    <div class="flex flex-col gap-2">
        <!-- Actions pour parent -->
        <template v-if="userRole === 'parent'">
            <!-- Marquer comme vue -->
            <button
                v-if="!application.viewed_at && application.status === 'pending'"
                @click="$emit('viewed')"
                class="text-xs text-blue-600 transition-colors hover:text-blue-800"
            >
                Marquer comme vue
            </button>

            <!-- Actions pour candidature en attente -->
            <div v-if="application.status === 'pending' && !application.is_expired" class="flex flex-col gap-2">
                <button
                    @click="$emit('accept')"
                    class="rounded bg-green-600 px-3 py-1.5 text-sm font-medium text-white transition-colors hover:bg-green-700"
                    :title="`Accepter la candidature au tarif de ${application.proposed_rate}€/h`"
                >
                    Accepter
                </button>
                <button
                    @click="$emit('counter-offer')"
                    class="rounded bg-blue-600 px-3 py-1.5 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                    title="Proposer un tarif différent"
                >
                    Contre-offre
                </button>
                <button
                    @click="$emit('decline')"
                    class="rounded bg-red-600 px-3 py-1.5 text-sm font-medium text-white transition-colors hover:bg-red-700"
                    title="Refuser cette candidature"
                >
                    Refuser
                </button>
            </div>

            <!-- Actions pour contre-offre en attente -->
            <div v-if="application.status === 'counter_offered' && !application.is_expired" class="text-center">
                <p class="mb-2 text-xs text-gray-600">En attente de réponse</p>
                <div class="flex items-center justify-center">
                    <div class="h-4 w-4 animate-spin rounded-full border-b-2 border-blue-600"></div>
                </div>
            </div>
        </template>

        <!-- Actions pour babysitter -->
        <template v-if="userRole === 'babysitter'">
            <!-- Répondre à une contre-offre -->
            <div v-if="application.status === 'counter_offered' && !application.is_expired" class="flex flex-col gap-2">
                <p class="mb-1 text-xs font-medium text-blue-600">Répondre à la contre-offre :</p>
                <button
                    @click="$emit('respond-counter', 'accept')"
                    class="rounded bg-green-600 px-3 py-1.5 text-sm font-medium text-white transition-colors hover:bg-green-700"
                    :title="`Accepter la contre-offre de ${application.counter_rate}€/h et procéder au paiement`"
                >
                    Accepter {{ application.counter_rate }}€/h
                </button>
                <button
                    @click="$emit('respond-counter', 'decline')"
                    class="rounded bg-red-600 px-3 py-1.5 text-sm font-medium text-white transition-colors hover:bg-red-700"
                    title="Refuser la contre-offre et retourner au tarif initial"
                >
                    Refuser
                </button>
            </div>

            <!-- Candidature en attente -->
            <div v-if="application.status === 'pending' && !application.is_expired" class="text-center">
                <p class="text-xs text-gray-600">En attente de réponse</p>
                <div class="mt-1 flex items-center justify-center">
                    <div class="h-2 w-2 animate-pulse rounded-full bg-yellow-400"></div>
                </div>
            </div>
        </template>

        <!-- États finaux -->
        <div v-if="application.status === 'accepted'" class="text-center">
            <CheckCircle class="mx-auto mb-1 h-5 w-5 text-green-600" />
            <p class="text-xs font-medium text-green-600">Acceptée</p>
        </div>

        <div v-if="application.status === 'declined'" class="text-center">
            <XCircle class="mx-auto mb-1 h-5 w-5 text-red-600" />
            <p class="text-xs font-medium text-red-600">Refusée</p>
        </div>

        <div v-if="application.is_expired" class="text-center">
            <Clock class="mx-auto mb-1 h-5 w-5 text-gray-400" />
            <p class="text-xs font-medium text-gray-500">Expirée</p>
        </div>
    </div>
</template>

<script setup>
import { CheckCircle, Clock, XCircle } from 'lucide-vue-next';

defineProps({
    application: Object,
    userRole: String,
});

defineEmits(['viewed', 'accept', 'decline', 'counter-offer', 'respond-counter']);
</script>
