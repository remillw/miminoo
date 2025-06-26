<template>
    <div :class="mobile ? 'space-y-3' : 'space-y-4'">
        <!-- En-tête candidature -->
        <div :class="mobile ? 'space-y-2' : 'flex items-center justify-between'">
            <div :class="mobile ? 'space-y-2' : 'flex items-center gap-3'">
                <div class="rounded-full bg-orange-200 px-3 py-1 text-sm font-medium text-orange-800">
                    Candidature - {{ application.status === 'pending' ? 'En attente' : 'En négociation' }}
                </div>
                <div :class="mobile ? 'text-xs' : 'text-sm'" class="text-gray-600">
                    <!-- Si accepté avec contre-offre, afficher le tarif accepté comme principal -->
                    <template v-if="application.status === 'accepted' && application.counter_rate">
                        Tarif accepté : <span class="font-semibold text-green-600">{{ application.counter_rate }}€/h</span>
                        <span class="ml-2 text-xs text-gray-500">(Initial : {{ application.proposed_rate }}€/h)</span>
                    </template>
                    <!-- Sinon, affichage normal -->
                    <template v-else>
                        Tarif proposé : <span class="text-primary font-semibold">{{ application.proposed_rate }}€/h</span>
                        <span v-if="application.counter_rate" class="ml-2">
                            → <span class="font-semibold text-blue-600">{{ application.counter_rate }}€/h</span>
                        </span>
                    </template>
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
                        class="hover:bg-secondary flex items-center justify-center gap-2 rounded-lg border border-orange-300 px-4 py-2 text-sm font-medium text-orange-700 transition-colors"
                        title="Proposer un tarif différent de celui proposé par le babysitter"
                    >
                        <Euro class="h-4 w-4" />
                        <span v-if="!mobile">Contre-offre</span>
                        <span v-else>Négocier</span>
                    </button>

                    <button
                        @click="handleDecline"
                        :class="mobile ? 'flex-1' : ''"
                        class="hover:bg-primary-opacity flex items-center justify-center gap-2 rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 transition-colors"
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
                    💰 Contre-offre reçue : <span class="font-semibold">{{ application.counter_rate }}€/h</span> <br /><span class="text-xs"
                        >(Votre proposition : {{ application.proposed_rate }}€/h)</span
                    >
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

            <!-- Statut en attente pour babysitter -->
            <template v-if="currentMode === 'babysitter' && application.status === 'pending'">
                <div :class="mobile ? 'w-full text-center' : ''" class="rounded-lg bg-yellow-50 px-3 py-2 text-sm text-yellow-700">
                    ⏳ Candidature en attente - Tarif proposé : <span class="font-semibold">{{ application.proposed_rate }}€/h</span> <br /><span
                        class="text-xs font-normal"
                        >En attente de la réponse du parent</span
                    >
                </div>
            </template>

            <!-- Statut accepté pour babysitter -->
            <template v-if="currentMode === 'babysitter' && application.status === 'accepted'">
                <div :class="mobile ? 'w-full text-center' : ''" class="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700">
                    ✅ Candidature acceptée au tarif de <span class="font-semibold">{{ currentRate }}€/h</span>
                    <template v-if="application.counter_rate && application.counter_rate !== application.proposed_rate">
                        <br /><span class="text-xs">(Tarif initial : {{ application.proposed_rate }}€/h)</span>
                    </template>
                    <br /><span class="text-xs font-normal">En attente du paiement par le parent</span>
                </div>
            </template>

            <!-- Statut en attente de paiement côté babysitter -->
            <template v-if="currentMode === 'babysitter' && application.conversation?.status === 'active'">
                <div :class="mobile ? 'w-full text-center' : ''" class="rounded-lg bg-blue-50 px-3 py-2 text-sm text-blue-700">
                    💳 Paiement effectué - Réservation confirmée au tarif de <span class="font-semibold">{{ currentRate }}€/h</span> <br /><span
                        class="text-xs font-normal"
                        >La garde peut commencer !</span
                    >
                </div>
            </template>
        </div>

        <!-- Formulaire contre-offre parent UNIQUEMENT (selon le mode actuel) -->
        <div
            v-if="showCounterOffer && currentMode === 'parent' && application.status !== 'declined' && application.status !== 'expired'"
            class="bg-secondary rounded-lg border border-orange-200 p-4"
        >
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
                        class="bg-primary rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-orange-700 disabled:opacity-50"
                        :title="counterOfferRate ? `Envoyer une contre-offre de ${counterOfferRate}€/h` : 'Veuillez saisir un tarif'"
                    >
                        Proposer
                    </button>
                    <button
                        @click="showCounterOffer = false"
                        :class="mobile ? 'flex-1' : ''"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 transition-colors hover:text-gray-800"
                        title="Annuler la contre-offre et revenir aux actions principales"
                    >
                        Annuler
                    </button>
                </div>
            </div>
        </div>

        <!-- Actions supplémentaires pour babysitter (selon le mode actuel) -->
        <div v-if="currentMode === 'babysitter' && canCancelApplication && !showCounterOffer" class="mt-4 flex justify-center">
            <button
                @click="showBabysitterCancelModal = true"
                class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 transition-colors hover:border-red-300 hover:bg-red-100"
                :title="getCancelTooltipText()"
            >
                <X class="h-4 w-4" />
                Annuler ma candidature
            </button>
        </div>

        <!-- Modal de confirmation annulation babysitter -->
        <CancelConfirmationModal
            :show="showBabysitterCancelModal"
            :type="'babysitter'"
            :application="application"
            @close="showBabysitterCancelModal = false"
            @confirm="handleConfirmBabysitterCancel"
        />

        <!-- Modal de confirmation annulation parent -->
        <CancelConfirmationModal
            :show="showParentCancelModal"
            :type="'parent'"
            :application="application"
            @close="showParentCancelModal = false"
            @confirm="handleConfirmParentCancel"
        />

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
import { useToast } from '@/composables/useToast';
import { useUserMode } from '@/composables/useUserMode';
import { router } from '@inertiajs/vue3';
import { Check, Euro, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import CancelConfirmationModal from './CancelConfirmationModal.vue';
import ReservationModal from './ReservationModal.vue';

const props = defineProps({
    application: Object,
    userRole: String,
    mobile: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['reserve', 'decline', 'counter-offer', 'respond-counter', 'babysitter-counter', 'cancel-application']);

// Utiliser le mode actuel depuis localStorage
const { currentMode } = useUserMode();

// État local
const showCounterOffer = ref(false);
const counterOfferRate = ref('');
const showReservationModal = ref(false);
const showBabysitterCancelModal = ref(false);
const showParentCancelModal = ref(false);

// Toast
const { toast } = useToast();

// Computed
const otherUser = computed(() => {
    return currentMode.value === 'parent' ? props.application.babysitter : props.application.parent;
});

const currentRate = computed(() => {
    // Si il y a une contre-offre ET qu'elle est acceptée, utiliser la contre-offre
    if (props.application.counter_rate && props.application.status === 'accepted') {
        return props.application.counter_rate;
    }
    // Sinon utiliser le tarif proposé initial
    return props.application.proposed_rate;
});

const canCancelApplication = computed(() => {
    // Permettre l'annulation tant que :
    // - Le statut n'est pas 'declined', 'expired' ou 'cancelled'
    // - Inclure les conversations actives (payées)
    const allowedStatuses = ['pending', 'counter_offered', 'accepted'];
    const conversationStatus = props.application.conversation?.status;

    return allowedStatuses.includes(props.application.status) || conversationStatus === 'active';
});

const canParentCancelReservation = computed(() => {
    // Permettre l'annulation par le parent tant que :
    // - Le statut n'est pas 'declined', 'expired' ou 'cancelled'
    // - Inclure les conversations actives (payées)
    const allowedStatuses = ['pending', 'counter_offered', 'accepted'];
    const conversationStatus = props.application.conversation?.status;

    return allowedStatuses.includes(props.application.status) || conversationStatus === 'active';
});

// Méthodes
function handleReserve() {
    // Utiliser le tarif initial si la contre-offre n'est pas encore acceptée
    const rate =
        props.application.status === 'accepted' && props.application.counter_rate ? props.application.counter_rate : props.application.proposed_rate;

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
        console.error("❌ Erreur lors de la génération de l'URL:", error);
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
    const conversationStatus = props.application.conversation?.status;

    if (conversationStatus === 'active') {
        return 'Annuler ma candidature (paiement déjà effectué - conditions spéciales)';
    } else if (props.application.status === 'accepted') {
        return 'Annuler ma candidature acceptée (avant paiement du parent)';
    } else if (props.application.status === 'counter_offered') {
        return 'Annuler ma candidature en cours de négociation';
    } else {
        return 'Annuler ma candidature en attente de réponse';
    }
}

function handleConfirmBabysitterCancel() {
    router.post(
        route('applications.cancel', props.application.id),
        {},
        {
            preserveState: true,
            onSuccess: (response) => {
                showBabysitterCancelModal.value = false;
                toast.success('Candidature annulée avec succès');
                router.get(route('messaging.index'));
            },
            onError: (errors) => {
                console.error('❌ Erreur annulation candidature:', errors);
                toast.error(errors.error || "Erreur lors de l'annulation de la candidature");
            },
        },
    );
}

function handleConfirmParentCancel() {
    router.post(
        route('applications.cancel-by-parent', props.application.id),
        {},
        {
            preserveState: true,
            onSuccess: (response) => {
                showParentCancelModal.value = false;
                toast.success('Réservation annulée avec succès');
                router.get(route('messaging.index'));
            },
            onError: (errors) => {
                console.error('❌ Erreur annulation réservation:', errors);
                toast.error(errors.error || "Erreur lors de l'annulation de la réservation");
            },
        },
    );
}

function getParentCancelTooltipText() {
    const conversationStatus = props.application.conversation?.status;

    if (conversationStatus === 'active') {
        return 'Annuler ma réservation (paiement déjà effectué - conditions spéciales)';
    } else if (props.application.status === 'accepted') {
        return 'Annuler ma réservation acceptée (avant paiement du parent)';
    } else if (props.application.status === 'counter_offered') {
        return 'Annuler ma réservation en cours de négociation';
    } else {
        return 'Annuler ma réservation en attente de réponse';
    }
}
</script>
