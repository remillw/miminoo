<template>
    <Dialog :open="show" @update:open="$emit('close')">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <AlertTriangle class="h-5 w-5 text-amber-500" />
                    {{ getTitle() }}
                </DialogTitle>
                <DialogDescription class="text-sm text-gray-600">
                    {{ getDescription() }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Calculs et informations -->
                <div class="space-y-3 rounded-lg bg-gray-50 p-4">
                    <div v-if="type === 'babysitter'">
                        <div v-if="isPaymentCompleted" class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Votre gain perdu :</span>
                                <span class="font-medium text-red-600">-{{ formatAmount(estimatedAmount) }}€</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Remboursement parent :</span>
                                <span class="font-medium text-green-600">+{{ formatAmount(estimatedAmount + estimatedFees) }}€</span>
                            </div>
                            <hr class="my-2" />
                            <div v-if="willGetBadReview" class="flex items-center gap-2 text-sm text-amber-600">
                                <Star class="h-4 w-4" />
                                <span>Avis négatif automatique (service &lt;48h)</span>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-600">Aucun paiement effectué, annulation gratuite.</div>
                    </div>

                    <div v-else-if="type === 'parent'" class="space-y-2">
                        <div v-if="isLateCancel" class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Acompte payé :</span>
                                <span class="font-medium">{{ formatAmount(estimatedAmount) }}€</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Remboursement :</span>
                                <span class="font-medium text-red-600">0€</span>
                            </div>
                            <div class="mt-2 text-xs text-red-600">⚠️ Annulation &lt;24h : acompte définitivement perdu</div>
                        </div>
                        <div v-else class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Acompte payé :</span>
                                <span class="font-medium">{{ formatAmount(estimatedAmount) }}€</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Frais de service :</span>
                                <span class="font-medium text-red-600">-2€</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Frais Stripe :</span>
                                <span class="font-medium text-red-600">~-{{ formatAmount(estimatedFees) }}€</span>
                            </div>
                            <hr class="my-2" />
                            <div class="flex justify-between text-sm font-medium">
                                <span>Remboursement estimé :</span>
                                <span class="text-green-600">~{{ formatAmount(estimatedRefund) }}€</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-4">
                    <Button variant="outline" class="flex-1" @click="$emit('close')"> Annuler </Button>
                    <Button
                        :variant="type === 'parent' && isLateCancel ? 'destructive' : 'default'"
                        class="flex-1"
                        @click="handleConfirm"
                        :disabled="isLoading"
                    >
                        <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                        {{ getConfirmText() }}
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

<script setup>
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { AlertTriangle, Loader2, Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    show: Boolean,
    type: String, // 'babysitter' | 'parent'
    application: Object,
});

const emit = defineEmits(['close', 'confirm']);

const isLoading = ref(false);

// Computed values
const reservation = computed(() => props.application?.conversation?.reservation);
const isPaymentCompleted = computed(() => ['paid', 'active'].includes(props.application?.status));

const hoursBeforeService = computed(() => {
    if (!reservation.value?.service_start_at) return 0;
    const serviceDate = new Date(reservation.value.service_start_at);
    return Math.floor((serviceDate - new Date()) / (1000 * 60 * 60));
});

const isLateCancel = computed(() => {
    return props.type === 'parent' && hoursBeforeService.value < 24 && isPaymentCompleted.value;
});

const willGetBadReview = computed(() => {
    return props.type === 'babysitter' && hoursBeforeService.value < 48 && isPaymentCompleted.value;
});

const estimatedAmount = computed(() => {
    return reservation.value?.total_deposit || props.application?.proposed_rate * 3 || 0;
});

const estimatedFees = computed(() => {
    // Estimation des frais Stripe (~3.5%)
    return Math.ceil(estimatedAmount.value * 0.035);
});

const estimatedRefund = computed(() => {
    if (props.type === 'parent' && !isLateCancel.value) {
        return Math.max(0, estimatedAmount.value - 2 - estimatedFees.value);
    }
    return 0;
});

// Methods
function getTitle() {
    if (props.type === 'babysitter') {
        return 'Annuler ma candidature';
    } else {
        return isLateCancel.value ? 'Annulation tardive' : 'Annuler ma réservation';
    }
}

function getDescription() {
    if (props.type === 'babysitter') {
        if (isPaymentCompleted.value) {
            return 'Cette action est définitive et entraînera des pénalités.';
        } else {
            return "Aucun paiement n'a été effectué, vous pouvez annuler sans frais.";
        }
    } else {
        if (isLateCancel.value) {
            return 'Vous annulez moins de 24h avant le service. Votre acompte sera définitivement perdu.';
        } else {
            return "Des frais d'annulation s'appliquent. Voici le détail de votre remboursement.";
        }
    }
}

function getConfirmText() {
    if (props.type === 'babysitter') {
        return isPaymentCompleted.value ? "Confirmer l'annulation" : 'Annuler ma candidature';
    } else {
        return isLateCancel.value ? 'Perdre mon acompte' : "Confirmer l'annulation";
    }
}

function formatAmount(amount) {
    return Math.round(amount * 100) / 100;
}

async function handleConfirm() {
    isLoading.value = true;
    try {
        await emit('confirm');
        emit('close');
    } finally {
        isLoading.value = false;
    }
}
</script>
