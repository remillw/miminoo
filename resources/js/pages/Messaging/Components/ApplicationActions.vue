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
                    Accepter {{ application.proposed_rate }}€/h
                </button>
                <button
                    @click="$emit('decline')"
                    class="rounded bg-red-600 px-3 py-1.5 text-sm font-medium text-white transition-colors hover:bg-red-700"
                    title="Refuser cette candidature définitivement"
                >
                    Refuser
                </button>
            </div>
        </template>

        <!-- Actions pour babysitter -->
        <template v-if="userRole === 'babysitter'">
            <!-- Candidature en attente -->
            <div v-if="application.status === 'pending' && !application.is_expired" class="text-center">
                <p class="text-xs text-gray-600">En attente de réponse du parent</p>
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

defineEmits(['viewed', 'accept', 'decline']);
</script>
