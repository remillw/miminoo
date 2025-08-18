<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-500 bg-opacity-75 p-2 sm:p-4" @click="handleBackdropClick">
        <div class="w-full max-w-lg max-h-[95vh] sm:max-h-[90vh] overflow-y-auto rounded-lg bg-white shadow-xl" @click.stop>
            <!-- Header -->
            <div class="border-b border-gray-200 px-4 sm:px-6 py-3 sm:py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-900">Options d'annulation</h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                        <X class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="px-4 sm:px-6 py-3 sm:py-4">
                <div class="space-y-4">
                    <!-- Choix pour les parents uniquement -->
                    <div v-if="userRole === 'parent'" class="space-y-4">
                        <p class="text-xs sm:text-sm text-gray-600">Choisissez l'option qui correspond à votre situation :</p>

                        <!-- Option 1: Annuler juste avec cette babysitter -->
                        <div
                            @click="selectedOption = 'babysitter_only'"
                            :class="{
                                'border-blue-500 bg-blue-50': selectedOption === 'babysitter_only',
                                'border-gray-200 hover:border-gray-300': selectedOption !== 'babysitter_only',
                            }"
                            class="cursor-pointer rounded-lg border-2 p-3 sm:p-4 transition-all"
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
                                    <h4 class="text-sm sm:text-base font-medium text-gray-900">Annuler avec cette babysitter uniquement</h4>
                                    <p class="mt-1 text-xs sm:text-sm text-gray-600">
                                        Votre annonce reste active pour d'autres babysitters. Seule cette réservation sera annulée.
                                    </p>
                                    <div class="mt-2 text-xs text-blue-600">
                                        ✓ Votre annonce reste visible<br />
                                        ✓ D'autres babysitters peuvent postuler<br />
                                        <template v-if="reservation?.can_be_cancelled_free">
                                            ✓ <strong>Remboursement intégral</strong><br />
                                            <span class="text-green-600">Vous serez remboursé - les frais</span>
                                        </template>
                                        <template v-else>
                                            ⚠️ <strong>Pénalité appliquée</strong><br />
                                            <span class="text-orange-600">Vous serez remboursé - les frais</span>
                                        </template>
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
                            class="cursor-pointer rounded-lg border-2 p-3 sm:p-4 transition-all"
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
                                    <h4 class="text-sm sm:text-base font-medium text-gray-900">Annuler toute l'annonce</h4>
                                    <p class="mt-1 text-xs sm:text-sm text-gray-600">
                                        Votre annonce sera supprimée définitivement. Toutes les candidatures seront archivées.
                                    </p>
                                    <div class="mt-2 text-xs text-red-600">
                                        ✗ Annonce supprimée définitivement<br />
                                        ✗ Toutes les candidatures archivées<br />
                                        <template v-if="reservation?.can_be_cancelled_free">
                                            ✓ <strong>Remboursement intégral</strong><br />
                                            <span class="text-green-600">Vous serez remboursé - les frais</span>
                                        </template>
                                        <template v-else>
                                            ⚠️ <strong>Pénalité appliquée</strong><br />
                                            <span class="text-orange-600">Vous serez remboursé - les frais</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Raison d'annulation -->
                        <div v-if="selectedOption === 'entire_announcement'" class="space-y-2">
                            <label class="block text-xs sm:text-sm font-medium text-gray-700">
                                Pourquoi annulez-vous l'annonce ? <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="cancelReason"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs sm:text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
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

                    <!-- Affichage pour babysitter -->
                    <div v-else class="space-y-4">
                        <!-- Conséquences automatiques -->
                        <div class="rounded-lg bg-yellow-50 p-4">
                            <div class="flex items-start gap-3">
                                <AlertTriangle class="mt-0.5 h-5 w-5 flex-shrink-0 text-yellow-600" />
                                <div>
                                    <h4 class="font-medium text-yellow-800">Conséquences de l'annulation</h4>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <div v-if="reservation?.can_be_cancelled_free">
                                            <p class="mb-2 font-medium text-green-700">✓ Annulation gratuite (plus de 48h)</p>
                                            <ul class="list-inside list-disc space-y-1 text-xs">
                                                <li>Aucune pénalité sur votre profil</li>
                                                <li>Le parent sera automatiquement remboursé (moins les frais)</li>
                                                <li>Vous ne recevrez aucun paiement pour cette réservation</li>
                                                <li>Notification automatique envoyée au parent</li>
                                            </ul>
                                        </div>
                                        <div v-else>
                                            <p class="mb-2 font-medium text-red-700">⚠️ Annulation tardive (moins de 48h)</p>
                                            <ul class="list-inside list-disc space-y-1 text-xs">
                                                <li class="text-red-600">Impact négatif sur votre taux d'annulation</li>
                                                <li class="text-red-600">Un avis négatif automatique sera ajouté à votre profil</li>
                                                <li>Le parent sera automatiquement remboursé (moins les frais)</li>
                                                <li>Vous ne recevrez aucun paiement pour cette réservation</li>
                                                <li>Notification automatique envoyée au parent</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Processus automatique -->
                        <div class="rounded-lg bg-blue-50 p-4">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-blue-100">
                                    <div class="h-2 w-2 rounded-full bg-blue-600"></div>
                                </div>
                                <div>
                                    <h4 class="font-medium text-blue-800">Traitement automatique</h4>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p class="mb-2">Le système va automatiquement :</p>
                                        <ul class="list-inside list-disc space-y-1 text-xs">
                                            <li>Annuler le transfert de fonds vers votre compte</li>
                                            <li>Rembourser le parent (montant payé moins les frais)</li>
                                            <li>Envoyer les notifications appropriées</li>
                                            <li>Mettre à jour votre profil si nécessaire</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Détails de la réservation -->
                    <div class="rounded-lg bg-gray-50 p-3 sm:p-4">
                        <h4 class="mb-2 sm:mb-3 text-sm sm:text-base font-medium text-gray-900">Détails de la réservation</h4>
                        <div class="space-y-1 sm:space-y-2 text-xs sm:text-sm">
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
                        <label for="cancellation-note" class="mb-2 block text-xs sm:text-sm font-medium text-gray-700"> Message personnel (optionnel) </label>
                        <textarea
                            id="cancellation-note"
                            v-model="personalNote"
                            rows="2"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs sm:text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
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
            <div class="border-t border-gray-200 px-4 sm:px-6 py-3 sm:py-4">
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                    <button
                        @click="$emit('close')"
                        :disabled="loading"
                        class="w-full sm:flex-1 rounded-lg border border-gray-300 px-4 py-2 text-xs sm:text-sm text-gray-700 transition-colors hover:bg-gray-50 disabled:opacity-50"
                    >
                        Garder la réservation
                    </button>
                    <button
                        @click="confirmCancellation"
                        :disabled="
                            loading || (userRole === 'parent' && !selectedOption) || (selectedOption === 'entire_announcement' && !cancelReason)
                        "
                        class="w-full sm:flex-1 rounded-lg bg-red-600 px-4 py-2 text-xs sm:text-sm text-white transition-colors hover:bg-red-700 disabled:opacity-50"
                    >
                        <span v-if="loading" class="flex items-center justify-center">
                            <Loader2 class="mr-1 sm:mr-2 h-3 w-3 sm:h-4 sm:w-4 animate-spin" />
                            <span class="text-xs sm:text-sm">Annulation...</span>
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
    // Pour les babysitters - un seul type d'annulation
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

function getTimeBeforeService() {
    if (!props.reservation?.service_start_at) return 0;
    const serviceDate = new Date(props.reservation.service_start_at);
    const now = new Date();
    return Math.floor((serviceDate - now) / (1000 * 60 * 60)); // heures
}

function getRefundDetails() {
    if (!props.reservation) return { amount: 0, description: '' };

    const totalPaid = props.reservation.total_deposit || 0; // 13€ exemple
    const serviceFees = props.reservation.service_fee || 2; // 2€
    const stripeRefundFees = Math.round((0.25 + (totalPaid - serviceFees) * 0.015) * 100) / 100; // ~0.41€

    const refundAmount = Math.max(0, totalPaid - serviceFees - stripeRefundFees);

    return {
        amount: refundAmount.toFixed(2),
        description: `Montant payé ${totalPaid}€ - Frais service ${serviceFees}€ - Frais Stripe ${stripeRefundFees}€`,
    };
}

function getPenaltyDetails() {
    const timeBeforeService = getTimeBeforeService();

    if (timeBeforeService < 24) {
        return 'Aucun remboursement - Acompte définitivement perdu';
    } else {
        const refund = getRefundDetails();
        return `Remboursement partiel: ${refund.amount}€ (frais déduits)`;
    }
}

function getBabysitterAmount() {
    if (!props.reservation) return 0;

    const totalPaid = props.reservation.total_deposit || 0;
    const serviceFees = props.reservation.service_fee || 2;
    const stripeFees = Math.round((totalPaid * 0.029 + 0.25) * 100) / 100;

    return Math.max(0, totalPaid - serviceFees - stripeFees).toFixed(2);
}

function handleBackdropClick() {
    // Fermer la modal quand on clique sur le fond (backdrop)
    emit('close');
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

        // Vérifier d'abord si la réponse est OK
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        // Vérifier le Content-Type pour s'assurer que c'est du JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Si ce n'est pas du JSON, lire comme texte pour débugger
            const text = await response.text();
            console.error('Réponse non-JSON reçue:', text);
            throw new Error('Réponse du serveur invalide (pas de JSON)');
        }

        const data = await response.json();

        if (data.success) {
            emit('success', {
                type: selectedOption.value === 'entire_announcement' ? 'announcement_cancelled' : 'reservation_cancelled',
                message: personalNote.value, // Inclure le message personnel
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
