<template>
    <div v-if="show" class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black">
        <div class="mx-4 max-h-[90vh] w-full max-w-md overflow-y-auto rounded-lg bg-white shadow-xl">
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Confirmer la réservation</h3>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                        <X class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="px-6 py-4">
                <div class="space-y-4">
                    <!-- Détails de la réservation -->
                    <div class="rounded-lg bg-gray-50 p-4">
                        <h4 class="mb-3 font-medium text-gray-900">Détails de la réservation</h4>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Babysitter :</span>
                                <span class="font-medium">{{ application.babysitter?.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tarif horaire :</span>
                                <span class="font-medium">{{ finalRate }}€/h</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Acompte (1h) :</span>
                                <span class="font-medium">{{ finalRate }}€</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Frais de service :</span>
                                <span class="font-medium">2,00€</span>
                            </div>
                            <div class="mt-2 border-t border-gray-200 pt-2">
                                <div class="flex justify-between font-semibold">
                                    <span>Total à payer :</span>
                                    <span class="text-primary">{{ totalAmount }}€</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Conditions d'annulation -->
                    <div class="rounded-lg bg-blue-50 p-4">
                        <h4 class="mb-2 flex items-center font-medium text-blue-900">
                            <Info class="mr-2 h-4 w-4" />
                            Conditions d'annulation
                        </h4>
                        <ul class="space-y-1 text-sm text-blue-800">
                            <li>• Annulation gratuite jusqu'à 24h avant le début</li>
                            <li>• Annulation moins de 24h avant : acompte non remboursé</li>
                            <li>• La babysitter peut annuler sa candidature gratuitement</li>
                            <li>• Les fonds sont libérés 24h après la fin du service</li>
                        </ul>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-4">
                        <button
                            @click="$emit('close')"
                            class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Annuler
                        </button>
                        <button
                            @click="proceedToPayment"
                            :disabled="loading"
                            class="bg-primary hover:bg-primary-dark flex-1 rounded-lg px-4 py-2 text-white transition-colors disabled:opacity-50"
                        >
                            <span v-if="loading" class="flex items-center justify-center">
                                <Loader2 class="mr-2 h-4 w-4 animate-spin" />
                                Préparation...
                            </span>
                            <span v-else>Procéder au paiement</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Info, Loader2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps({
    show: Boolean,
    application: Object,
});

const emit = defineEmits(['close', 'success']);

// État du composant
const loading = ref(false);

// Calculs
const finalRate = computed(() => {
    return props.application?.counter_rate || props.application?.proposed_rate || 0;
});

const totalAmount = computed(() => {
    return (parseFloat(finalRate.value) + 2.0).toFixed(2);
});

// Créer la réservation et rediriger vers la page de paiement
function proceedToPayment() {
    if (!props.application) return;

    loading.value = true;

    router.post(
        route('applications.create-reservation', props.application.id),
        {
            final_rate: finalRate.value,
        },
        {
            onSuccess: () => {
                // Fermer le modal
                emit('close');
            },
            onError: (errors) => {
                console.error('Erreur lors de la création de la réservation:', errors);
            },
            onFinish: () => {
                loading.value = false;
            },
        },
    );
}
</script>

<style scoped>
/* Styles pour le modal */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
