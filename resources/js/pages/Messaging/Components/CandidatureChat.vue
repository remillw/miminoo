<template>
    <div :class="mobile ? 'space-y-3' : 'space-y-4'">
        <!-- En-tête candidature -->
        <div :class="mobile ? 'space-y-2' : 'flex items-center justify-between'">
            <div :class="mobile ? 'space-y-2' : 'flex items-center gap-3'">
                <div class="rounded-full bg-orange-200 px-3 py-1 text-sm font-medium text-orange-800">
                    Candidature - {{ application.status === 'pending' ? 'En attente' : 'En négociation' }}
                </div>
                <div :class="mobile ? 'text-xs' : 'text-sm'" class="text-gray-600">
                    Tarif proposé : <span class="font-semibold text-primary">{{ application.proposed_rate }}€/h</span>
                    <span v-if="application.counter_rate" class="ml-2">
                        → <span class="font-semibold text-blue-600">{{ application.counter_rate }}€/h</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Actions principales -->
        <div :class="mobile ? 'space-y-2' : 'flex items-center gap-3'">
            <!-- Actions pour parents UNIQUEMENT (selon le mode actuel) -->
            <template v-if="currentMode === 'parent' && application.status !== 'declined' && application.status !== 'expired'">
                <button
                    @click="handleReserveDirectly"
                    :class="mobile ? 'w-full justify-center' : ''"
                    class="flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700"
                    :title="`Réserver cette candidature au tarif de ${currentRate}€/h`"
                >
                    <Check class="h-4 w-4" />
                    Réserver {{ currentRate }}€/h
                </button>

                <div :class="mobile ? 'flex gap-2' : 'contents'">
                    <button
                        v-if="!showCounterOffer"
                        @click="showCounterOffer = true"
                        :class="mobile ? 'flex-1' : ''"
                        class="hover:bg-secondary flex items-center gap-2 rounded-lg border border-orange-300 px-4 py-2 text-sm font-medium text-orange-700 transition-colors justify-center"
                        title="Proposer un tarif différent de celui proposé par le babysitter"
                    >
                        <Euro class="h-4 w-4" />
                        <span v-if="!mobile">Contre-offre</span>
                        <span v-else>Négocier</span>
                    </button>

                    <button
                        @click="handleDecline"
                        :class="mobile ? 'flex-1' : ''"
                        class="hover:bg-primary-opacity flex items-center gap-2 rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 transition-colors justify-center"
                        title="Refuser définitivement cette candidature"
                    >
                        <X class="h-4 w-4" />
                        Refuser
                    </button>
                </div>
            </template>

            <!-- Actions pour babysitter (selon le mode actuel) -->
            <template v-if="currentMode === 'babysitter' && application.status === 'counter_offered' && application.counter_rate">
                <div :class="mobile ? 'w-full text-center' : ''" class="rounded-lg bg-blue-50 px-3 py-2 text-sm text-blue-700">
                    Contre-offre reçue : <span class="font-semibold">{{ application.counter_rate }}€/h</span>
                </div>

                <div :class="mobile ? 'flex gap-2' : 'contents'">
                    <button
                        @click="$emit('respond-counter', application.id, true, application.counter_rate)"
                        :class="mobile ? 'flex-1' : ''"
                        class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700"
                        :title="`Accepter la contre-offre de ${application.counter_rate}€/h`"
                    >
                        Accepter
                    </button>

                    <button
                        @click="$emit('respond-counter', application.id, false)"
                        :class="mobile ? 'flex-1' : ''"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                        title="Refuser la contre-offre et revenir au tarif initial"
                    >
                        Refuser
                    </button>
                </div>
            </template>

            <!-- Statut accepté pour babysitter -->
            <template v-if="currentMode === 'babysitter' && application.status === 'accepted'">
                <div :class="mobile ? 'w-full text-center' : ''" class="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">
                    ✅ Candidature acceptée au tarif de <span class="font-semibold">{{ currentRate }}€/h</span>
                </div>
            </template>
        </div>

        <!-- Formulaire contre-offre parent UNIQUEMENT (selon le mode actuel) -->
        <div v-if="showCounterOffer && currentMode === 'parent' && application.status !== 'declined' && application.status !== 'expired'" class="bg-secondary rounded-lg border border-orange-200 p-4">
            <h4 :class="mobile ? 'text-sm' : ''" class="mb-3 font-medium text-gray-900">Faire une contre-proposition :</h4>
            <div :class="mobile ? 'space-y-3' : 'flex items-center gap-3'">
                <div class="relative" :class="mobile ? 'w-full' : ''">
                    <input
                        v-model="counterOfferRate"
                        type="number"
                        step="0.5"
                        min="1"
                        max="50"
                        :class="mobile ? 'w-full' : 'w-24'"
                        class="focus:ring-primary rounded-lg border border-gray-300 px-3 py-2 text-center focus:ring-2 focus:outline-none"
                        placeholder="20"
                    />
                    <span class="absolute top-1/2 right-2 -translate-y-1/2 transform text-sm text-gray-500">€/h</span>
                </div>
                <div :class="mobile ? 'flex gap-2' : 'contents'">
                    <button
                        @click="submitCounterOffer"
                        :disabled="!counterOfferRate"
                        :class="mobile ? 'flex-1' : ''"
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-orange-700 disabled:opacity-50"
                        :title="counterOfferRate ? `Envoyer une contre-offre de ${counterOfferRate}€/h` : 'Veuillez saisir un tarif'"
                    >
                        Proposer
                    </button>
                    <button 
                        @click="showCounterOffer = false" 
                        :class="mobile ? 'flex-1' : ''"
                        class="px-4 py-2 text-sm text-gray-600 transition-colors hover:text-gray-800 rounded-lg border border-gray-300"
                        title="Annuler la contre-offre et revenir aux actions principales"
                    >
                        Annuler
                    </button>
                </div>
            </div>
        </div>

        <!-- Message de motivation -->
        <div v-if="application.motivation_note" class="rounded-lg bg-gray-50 p-3">
            <p :class="mobile ? 'text-xs' : 'text-sm'" class="text-gray-700 italic">"{{ application.motivation_note }}"</p>
        </div>

        <!-- Actions supplémentaires pour babysitter (selon le mode actuel) -->
        <div v-if="currentMode === 'babysitter' && canCancelApplication" class="border-t border-gray-200 pt-4">
            <button
                @click="handleCancelApplication"
                :class="mobile ? 'w-full justify-center' : ''"
                class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 transition-colors hover:bg-gray-50"
                :title="getCancelTooltipText()"
            >
                <X class="h-4 w-4" />
                Annuler ma candidature
            </button>
        </div>

        <!-- Modal de réservation (gardé pour compatibilité) -->
        <ReservationModal
            :show="showReservationModal"
            :application="application"
            @close="showReservationModal = false"
            @success="handleReservationSuccess"
        />
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { Check, Euro, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import { useUserMode } from '@/composables/useUserMode';
import ReservationModal from './ReservationModal.vue';

const props = defineProps({
    application: Object,
    userRole: String,
    mobile: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['reserve', 'decline', 'counter-offer', 'respond-counter', 'babysitter-counter', 'cancel-application']);

// Utiliser le mode actuel depuis localStorage
const { currentMode } = useUserMode();

// État local
const showCounterOffer = ref(false);
const counterOfferRate = ref('');
const showReservationModal = ref(false);

// Computed
const otherUser = computed(() => {
    return currentMode.value === 'parent' ? props.application.babysitter : props.application.parent;
});

const currentRate = computed(() => {
    // Afficher le tarif de contre-offre seulement si elle est acceptée
    return (props.application.status === 'accepted' && props.application.counter_rate)
        ? props.application.counter_rate
        : props.application.proposed_rate;
});

const canCancelApplication = computed(() => {
    // Permettre l'annulation tant que :
    // - Le statut n'est pas 'declined' ou 'expired'
    // - Et que le paiement n'est pas encore effectué (pas de status 'payment_required' ou 'active')
    const allowedStatuses = ['pending', 'counter_offered', 'accepted'];
    return allowedStatuses.includes(props.application.status);
});

// Méthodes
function handleReserve() {
    // Utiliser le tarif initial si la contre-offre n'est pas encore acceptée
    const rate = (props.application.status === 'accepted' && props.application.counter_rate) 
        ? props.application.counter_rate 
        : props.application.proposed_rate;
    
    if (confirm(`Réserver cette candidature au tarif de ${rate}€/h ?`)) {
        emit('reserve', props.application.id, rate);
    }
}

function handleDecline() {
    if (confirm('Êtes-vous sûr de vouloir refuser cette candidature ? Elle sera archivée.')) {
        emit('decline', props.application.id);
    }
}

function submitCounterOffer() {
    if (!counterOfferRate.value) return;

    emit('counter-offer', props.application.id, parseFloat(counterOfferRate.value));
    showCounterOffer.value = false;
    counterOfferRate.value = '';
}

function handleCancelApplication() {
    if (confirm('Êtes-vous sûr de vouloir annuler votre candidature ? Cette action est irréversible.')) {
        emit('cancel-application', props.application.id);
    }
}

function handleReserveDirectly() {
    console.log('🔄 Tentative de réservation directe');
    console.log('Application ID:', props.application.id);
    console.log('User role (prop):', props.userRole);
    console.log('Current mode (localStorage):', currentMode.value);

    // Vérifier le mode actuel depuis localStorage au lieu du prop
    if (currentMode.value !== 'parent') {
        console.error('❌ Seuls les parents peuvent réserver (mode actuel:', currentMode.value, ')');
        return;
    }

    try {
        const url = route('applications.payment', props.application.id);
        console.log('URL générée:', url);

        // Rediriger directement vers la page de paiement avec l'ID de l'application
        router.visit(url);
    } catch (error) {
        console.error('❌ Erreur lors de la génération de l\'URL:', error);
    }
}

function handleReservationSuccess(reservation) {
    showReservationModal.value = false;
    // Émettre un événement pour recharger les conversations
    emit('reserve', props.application.id, reservation.hourly_rate);
}

function formatTime(dateString) {
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch (err) {
        return 'Date inconnue';
    }
}

function getCancelTooltipText() {
    if (props.application.status === 'accepted') {
        return 'Annuler ma candidature acceptée (avant paiement du parent)';
    } else if (props.application.status === 'counter_offered') {
        return 'Annuler ma candidature en cours de négociation';
    } else {
        return 'Annuler ma candidature en attente de réponse';
    }
}
</script>
