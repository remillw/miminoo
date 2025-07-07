<template>
    <div v-if="show" class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black">
        <div class="mx-4 w-full max-w-lg rounded-lg bg-white shadow-xl">
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Options d'annulation</h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                        <X class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="px-6 py-4">
                <div class="space-y-4">
                    <!-- Choix pour les parents uniquement -->
                    <div v-if="userRole === 'parent'" class="space-y-4">
                        <p class="text-sm text-gray-600">Choisissez l'option qui correspond à votre situation :</p>

                        <!-- Option 1: Annuler juste avec cette babysitter -->
                        <div
                            @click="selectedOption = 'babysitter_only'"
                            :class="{
                                'border-blue-500 bg-blue-50': selectedOption === 'babysitter_only',
                                'border-gray-200 hover:border-gray-300': selectedOption !== 'babysitter_only',
                            }"
                            class="cursor-pointer rounded-lg border-2 p-4 transition-all"
                        >
                            <div class="flex items-start space-x-3">
                                <div class="mt-1 flex-shrink-0">
                                    <div
                                        :class="{
                                            'border-blue-500': selectedOption === 'babysitter_only',
                                            'border-gray-300': selectedOption !== 'babysitter_only',
                                        }"
                                        class="flex h-4 w-4 items-center justify-center rounded-full border-2"
                                    >
                                        <div v-if="selectedOption === 'babysitter_only'" class="h-2 w-2 rounded-full bg-blue-500"></div>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900">Annuler avec cette babysitter uniquement</h4>
                                    <p class="mt-1 text-sm text-gray-600">
                                        Votre annonce reste active pour d'autres babysitters. Seule cette réservation sera annulée.
                                    </p>
                                    <div class="mt-2 text-xs text-blue-600">
                                        ✓ Votre annonce reste visible<br />
                                        ✓ D'autres babysitters peuvent postuler<br />
                                        {{ reservation?.can_be_cancelled_free ? '✓ Remboursement complet' : '⚠️ Pénalité selon délai' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Option 2: Annuler toute l'annonce -->
                        <div
                            @click="selectedOption = 'entire_announcement'"
                            :class="{
                                'border-red-500 bg-red-50': selectedOption === 'entire_announcement',
                                'border-gray-200 hover:border-gray-300': selectedOption !== 'entire_announcement',
                            }"
                            class="cursor-pointer rounded-lg border-2 p-4 transition-all"
                        >
                            <div class="flex items-start space-x-3">
                                <div class="mt-1 flex-shrink-0">
                                    <div
                                        :class="{
                                            'border-red-500': selectedOption === 'entire_announcement',
                                            'border-gray-300': selectedOption !== 'entire_announcement',
                                        }"
                                        class="flex h-4 w-4 items-center justify-center rounded-full border-2"
                                    >
                                        <div v-if="selectedOption === 'entire_announcement'" class="h-2 w-2 rounded-full bg-red-500"></div>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900">Annuler toute l'annonce</h4>
                                    <p class="mt-1 text-sm text-gray-600">
                                        Votre annonce sera supprimée définitivement. Toutes les candidatures seront archivées.
                                    </p>
                                    <div class="mt-2 text-xs text-red-600">
                                        ✗ Annonce supprimée définitivement<br />
                                        ✗ Toutes les candidatures archivées<br />
                                        {{ reservation?.can_be_cancelled_free ? '✓ Remboursement complet' : '⚠️ Pénalité selon délai' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Raison d'annulation -->
                        <div v-if="selectedOption === 'entire_announcement'" class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                Pourquoi annulez-vous l'annonce ? <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="cancelReason"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                            >
                                <option value="">Sélectionnez une raison</option>
                                <option value="found_other_solution">J'ai trouvé une autre solution</option>
                                <option value="no_longer_needed">Je n'ai plus besoin de garde</option>
                                <option value="date_changed">Mes dates ont changé</option>
                                <option value="budget_issues">Problème de budget</option>
                                <option value="other">Autre raison</option>
                            </select>
                        </div>
                    </div>

                    <!-- Affichage simplifié pour babysitter -->
                    <div v-else class="space-y-4">
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
                                            <p>
                                                Annuler maintenant affectera négativement votre réputation et pourra entraîner un avis automatique
                                                défavorable.
                                            </p>
                                        </div>
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
                                <span class="font-medium">{{ formatDate(reservation?.service_start_at) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Heure :</span>
                                <span class="font-medium"
                                    >{{ formatTime(reservation?.service_start_at) }} - {{ formatTime(reservation?.service_end_at) }}</span
                                >
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Montant :</span>
                                <span class="font-medium">{{ reservation?.total_deposit }}€</span>
                            </div>
                        </div>
                    </div>

                    <!-- Note personnelle optionnelle -->
                    <div>
                        <label for="cancellation-note" class="mb-2 block text-sm font-medium text-gray-700"> Message personnel (optionnel) </label>
                        <textarea
                            id="cancellation-note"
                            v-model="personalNote"
                            rows="3"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                            placeholder="Expliquez brièvement votre situation..."
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
                        :disabled="
                            loading || (userRole === 'parent' && !selectedOption) || (selectedOption === 'entire_announcement' && !cancelReason)
                        "
                        class="flex-1 rounded-lg bg-red-600 px-4 py-2 text-white transition-colors hover:bg-red-700 disabled:opacity-50"
                    >
                        <span v-if="loading" class="flex items-center justify-center">
                            <Loader2 class="mr-2 h-4 w-4 animate-spin" />
                            Annulation...
                        </span>
                        <span v-else>{{ getConfirmButtonText() }}</span>
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
const selectedOption = ref(''); // 'babysitter_only' ou 'entire_announcement'
const cancelReason = ref('');
const personalNote = ref('');

// Computed
const getConfirmButtonText = () => {
    if (props.userRole === 'parent') {
        return selectedOption.value === 'entire_announcement' ? "Annuler l'annonce" : 'Annuler avec cette babysitter';
    }
    return "Confirmer l'annulation";
};

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

function formatTime(dateTimeString) {
    if (!dateTimeString) return '';
    return new Date(dateTimeString).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function confirmCancellation() {
    if (!props.reservation) return;

    loading.value = true;
    error.value = '';

    try {
        let endpoint, payload;

        if (props.userRole === 'parent' && selectedOption.value === 'entire_announcement') {
            // Annuler toute l'annonce
            if (!props.reservation.ad?.id) {
                error.value = "Impossible d'annuler l'annonce : informations manquantes";
                loading.value = false;
                return;
            }
            endpoint = route('announcements.cancel', props.reservation.ad.id);
            payload = {
                reason: cancelReason.value,
                note: personalNote.value,
            };
        } else {
            // Annuler juste cette réservation
            const reason = props.userRole === 'parent' ? 'parent_unavailable' : 'babysitter_unavailable';
            endpoint = route('reservations.cancel', props.reservation.id);
            payload = {
                reason: reason,
                note: personalNote.value,
            };
        }

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (data.success) {
            emit('success', {
                type: selectedOption.value === 'entire_announcement' ? 'announcement_cancelled' : 'reservation_cancelled',
                ...data,
            });
        } else {
            error.value = data.error || "Erreur lors de l'annulation";
        }
    } catch (err) {
        console.error("Erreur lors de l'annulation:", err);
        error.value = "Une erreur est survenue lors de l'annulation";
    } finally {
        loading.value = false;
    }
}
</script>
