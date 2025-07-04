<template>
    <div v-if="show" class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black">
        <div class="mx-4 w-full max-w-md rounded-lg bg-white shadow-xl">
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Annuler la réservation</h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                        <X class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="px-6 py-4">
                <div class="space-y-4">
                    <!-- Avertissement -->
                    <div class="rounded-lg bg-yellow-50 p-4">
                        <div class="flex items-start gap-3">
                            <AlertTriangle class="mt-0.5 h-5 w-5 flex-shrink-0 text-yellow-600" />
                            <div>
                                <h4 class="font-medium text-yellow-800">Conditions d'annulation</h4>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <div v-if="reservation?.can_be_cancelled_free">
                                        <p class="font-medium text-green-700">✓ Annulation gratuite</p>
                                        <p>Vous pouvez annuler sans frais car il reste plus de 24h avant le début du service.</p>
                                    </div>
                                    <div v-else>
                                        <p class="font-medium text-red-700">⚠️ Annulation avec pénalité</p>
                                        <p v-if="userRole === 'parent'">
                                            L'acompte de {{ reservation?.deposit_amount }}€ ne sera pas remboursé car il reste moins de 24h avant le
                                            début du service.
                                        </p>
                                        <p v-else>
                                            Annuler maintenant affectera négativement votre réputation et pourra entraîner un avis automatique
                                            défavorable.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Détails de la réservation -->
                    <div class="rounded-lg bg-gray-50 p-4">
                        <h4 class="mb-3 font-medium text-gray-900">Détails de la réservation</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Date :</span>
                                <span class="font-medium">{{ formatDate(reservation?.ad?.date_start) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Heure :</span>
                                <span class="font-medium"
                                    >{{ formatTime(reservation?.ad?.time_start) }} - {{ formatTime(reservation?.ad?.time_end) }}</span
                                >
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Montant :</span>
                                <span class="font-medium">{{ reservation?.total_deposit }}€</span>
                            </div>
                        </div>
                    </div>

                    <!-- Raison d'annulation -->
                    <div>
                        <label for="cancellation-reason" class="mb-2 block text-sm font-medium text-gray-700">
                            Raison de l'annulation (optionnel)
                        </label>
                        <textarea
                            id="cancellation-reason"
                            v-model="cancellationReason"
                            rows="3"
                            class="focus:border-primary focus:ring-primary w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-1 focus:outline-none"
                            placeholder="Expliquez brièvement pourquoi vous annulez cette réservation..."
                        ></textarea>
                    </div>

                    <!-- Message d'erreur -->
                    <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 p-3">
                        <p class="text-sm text-red-800">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-200 px-6 py-4">
                <div class="flex gap-3">
                    <button
                        @click="$emit('close')"
                        :disabled="loading"
                        class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-50 disabled:opacity-50"
                    >
                        Garder la réservation
                    </button>
                    <button
                        @click="confirmCancellation"
                        :disabled="loading"
                        class="flex-1 rounded-lg bg-red-600 px-4 py-2 text-white transition-colors hover:bg-red-700 disabled:opacity-50"
                    >
                        <span v-if="loading" class="flex items-center justify-center">
                            <Loader2 class="mr-2 h-4 w-4 animate-spin" />
                            Annulation...
                        </span>
                        <span v-else>Confirmer l'annulation</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { AlertTriangle, Loader2, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    show: Boolean,
    reservation: Object,
    userRole: String,
});

const emit = defineEmits(['close', 'success']);

// État local
const loading = ref(false);
const error = ref('');
const cancellationReason = ref('');

// Méthodes
function formatDate(dateString) {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function formatTime(timeString) {
    if (!timeString) return '';
    return timeString.substring(0, 5); // HH:MM
}

async function confirmCancellation() {
    if (!props.reservation) return;

    loading.value = true;
    error.value = '';

    try {
        // Déterminer la raison selon le rôle
        const reason = props.userRole === 'parent' ? 'parent_unavailable' : 'babysitter_unavailable';
        
        const response = await fetch(route('reservations.cancel', props.reservation.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                reason: reason,
                note: cancellationReason.value, // Le texte libre devient la note
            }),
        });

        const data = await response.json();

        if (data.success) {
            emit('success', data.reservation);
        } else {
            error.value = data.error || "Erreur lors de l'annulation de la réservation";
        }
    } catch (err) {
        console.error("Erreur lors de l'annulation:", err);
        error.value = "Une erreur est survenue lors de l'annulation";
    } finally {
        loading.value = false;
    }
}
</script>
