<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <div class="bg-white shadow-sm">
            <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Finaliser le paiement</h1>
                        <p class="mt-1 text-sm text-gray-600">Réservation pour {{ reservation.ad.title }}</p>
                    </div>
                    <Link :href="route('messaging.index')" class="text-gray-500 hover:text-gray-700">
                        <X class="h-6 w-6" />
                    </Link>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Détails de la réservation -->
                <div class="lg:col-span-1">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Détails de la réservation</h2>

                        <!-- Babysitter -->
                        <div class="mb-4 flex items-center gap-3">
                            <img
                                :src="reservation.babysitter.avatar || '/images/default-avatar.png'"
                                :alt="reservation.babysitter.name"
                                class="h-12 w-12 rounded-full object-cover"
                            />
                            <div>
                                <p class="font-medium text-gray-900">{{ reservation.babysitter.name }}</p>
                                <p class="text-sm text-gray-600">Babysitter</p>
                            </div>
                        </div>

                        <!-- Annonce -->
                        <div class="mb-4 rounded-lg bg-gray-50 p-4">
                            <h3 class="font-medium text-gray-900">{{ reservation.ad.title }}</h3>
                            <p class="text-sm text-gray-600">
                                {{ formatDate(reservation.ad.date_start) }} - {{ formatDate(reservation.ad.date_end) }}
                            </p>
                        </div>

                        <!-- Détails financiers -->
                        <div class="space-y-2 border-t border-gray-200 pt-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Tarif horaire :</span>
                                <span class="font-medium">{{ reservation.hourly_rate }}€/h</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Acompte (1h) :</span>
                                <span class="font-medium">{{ reservation.deposit_amount }}€</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Frais de service :</span>
                                <span class="font-medium">{{ reservation.service_fee }}€</span>
                            </div>
                            <div class="flex justify-between border-t border-gray-200 pt-2 font-semibold">
                                <span>Total à payer :</span>
                                <span class="text-primary text-lg">{{ reservation.total_deposit }}€</span>
                            </div>

                            <!-- Répartition détaillée -->
                            <div class="mt-4 rounded-lg bg-gray-50 p-3">
                                <h4 class="mb-2 text-xs font-medium tracking-wide text-gray-700 uppercase">Répartition des fonds</h4>
                                <div class="space-y-1 text-xs">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Frais Stripe :</span>
                                        <span class="font-medium text-gray-500">{{ reservation.stripe_fee }}€</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Montant reçu par la babysitter :</span>
                                        <span class="font-medium text-green-600">{{ reservation.babysitter_amount }}€</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Frais plateforme :</span>
                                        <span class="font-medium text-blue-600">{{ reservation.platform_fee }}€</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Conditions -->
                        <div class="mt-4 rounded-lg bg-blue-50 p-3">
                            <h4 class="mb-2 flex items-center text-sm font-medium text-blue-900">
                                <Info class="mr-2 h-4 w-4" />
                                Conditions d'annulation
                            </h4>
                            <ul class="space-y-1 text-xs text-blue-800">
                                <li>• Annulation gratuite jusqu'à 24h avant</li>
                                <li>• Fonds libérés 24h après la fin du service</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de paiement -->
                <div class="lg:col-span-2">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="mb-6 text-lg font-semibold text-gray-900">Paiement sécurisé</h2>

                        <!-- Moyens de paiement sauvegardés -->
                        <div v-if="savedPaymentMethods.length > 0" class="mb-6">
                            <h3 class="mb-3 font-medium text-gray-900">Moyens de paiement sauvegardés</h3>
                            <div class="space-y-2">
                                <label
                                    v-for="method in savedPaymentMethods"
                                    :key="method.id"
                                    class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 p-3 hover:bg-gray-50"
                                    :class="{ 'border-primary bg-primary/5': selectedPaymentMethod === method.id }"
                                >
                                    <input type="radio" :value="method.id" v-model="selectedPaymentMethod" class="text-primary focus:ring-primary" />
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-6 w-10 items-center justify-center rounded bg-gray-200 text-xs font-bold">
                                            {{ method.card.brand.toUpperCase() }}
                                        </div>
                                        <span class="text-sm">•••• •••• •••• {{ method.card.last4 }}</span>
                                        <span class="text-xs text-gray-500">{{ method.card.exp_month }}/{{ method.card.exp_year }}</span>
                                    </div>
                                </label>
                            </div>

                            <button
                                @click="
                                    useNewPaymentMethod = true;
                                    selectedPaymentMethod = null;
                                "
                                class="text-primary hover:text-primary-dark mt-3 text-sm"
                            >
                                + Utiliser un nouveau moyen de paiement
                            </button>
                        </div>

                        <!-- Nouveau moyen de paiement -->
                        <div v-if="savedPaymentMethods.length === 0 || useNewPaymentMethod" class="mb-6">
                            <h3 v-if="savedPaymentMethods.length > 0" class="mb-3 font-medium text-gray-900">Nouveau moyen de paiement</h3>

                            <!-- Indicateur de chargement -->
                            <div
                                v-if="!stripeElementsReady"
                                class="flex min-h-[200px] items-center justify-center rounded-lg border border-gray-200 p-4"
                            >
                                <div class="text-center text-gray-500">
                                    <Loader2 class="mx-auto mb-2 h-6 w-6 animate-spin" />
                                    <p class="text-sm">Chargement du formulaire de paiement...</p>
                                </div>
                            </div>

                            <!-- Container Stripe Elements -->
                            <div
                                id="payment-element"
                                class="min-h-[200px] rounded-lg border border-gray-200 p-4"
                                :class="{ hidden: !stripeElementsReady }"
                            >
                                <!-- Stripe Elements sera injecté ici -->
                            </div>

                            <!-- Option de sauvegarde -->
                            <label class="mt-3 flex items-center gap-2 text-sm">
                                <input type="checkbox" v-model="savePaymentMethod" class="text-primary focus:ring-primary rounded border-gray-300" />
                                <span class="text-gray-700">Enregistrer ce moyen de paiement pour les futures réservations</span>
                            </label>
                        </div>

                        <!-- Message d'erreur -->
                        <div v-if="paymentError" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3">
                            <p class="text-sm text-red-800">{{ paymentError }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3">
                            <Link
                                :href="route('messaging.index')"
                                class="flex-1 rounded-lg border border-gray-300 px-4 py-3 text-center text-gray-700 transition-colors hover:bg-gray-50"
                            >
                                Annuler
                            </Link>
                            <button
                                @click="confirmPayment"
                                :disabled="processing || (!selectedPaymentMethod && (!stripe || !elements))"
                                class="bg-primary hover:bg-primary-dark flex-1 rounded-lg px-4 py-3 text-white transition-colors disabled:opacity-50"
                            >
                                <span v-if="processing" class="flex items-center justify-center">
                                    <Loader2 class="mr-2 h-4 w-4 animate-spin" />
                                    Traitement...
                                </span>
                                <span v-else>Confirmer le paiement ({{ reservation.total_deposit }}€)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import { Info, Loader2, X } from 'lucide-vue-next';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps({
    reservation: Object,
    savedPaymentMethods: Array,
    stripePublishableKey: String,
});

// État du composant
const processing = ref(false);
const paymentError = ref('');

// Stripe
const stripe = ref(null);
const elements = ref(null);
const paymentElement = ref(null);
const stripeElementsReady = ref(false);

// Moyens de paiement
const selectedPaymentMethod = ref(null);
const useNewPaymentMethod = ref(false);
const savePaymentMethod = ref(false);

// Initialiser les moyens de paiement sauvegardés
if (props.savedPaymentMethods.length > 0) {
    selectedPaymentMethod.value = props.savedPaymentMethods[0].id;
} else {
    useNewPaymentMethod.value = true;
}

// Formatage des dates
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Charger Stripe
const loadStripeScript = () => {
    return new Promise((resolve, reject) => {
        if (window.Stripe) {
            resolve(window.Stripe);
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.onload = () => resolve(window.Stripe);
        script.onerror = () => reject(new Error('Erreur lors du chargement du script Stripe'));
        document.head.appendChild(script);
    });
};

// Initialiser Stripe Elements
const initializeStripeElements = async (clientSecret) => {
    if (!stripe.value || !clientSecret) {
        console.error('Stripe non initialisé ou client secret manquant');
        return;
    }

    try {
        stripeElementsReady.value = false;

        // Nettoyer les éléments existants
        if (elements.value) {
            elements.value.destroy();
        }

        elements.value = stripe.value.elements({
            clientSecret: clientSecret,
            appearance: {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#f97316',
                    colorBackground: '#ffffff',
                    colorText: '#1f2937',
                    colorDanger: '#ef4444',
                    fontFamily: 'system-ui, sans-serif',
                    spacingUnit: '4px',
                    borderRadius: '8px',
                },
            },
        });

        paymentElement.value = elements.value.create('payment', {
            fields: {
                billingDetails: 'auto',
            },
        });

        await paymentElement.value.mount('#payment-element');
        stripeElementsReady.value = true;

        paymentElement.value.on('change', (event) => {
            if (event.error) {
                paymentError.value = event.error.message;
            } else {
                paymentError.value = '';
            }
        });
    } catch (error) {
        console.error("Erreur lors de l'initialisation de Stripe Elements:", error);
        paymentError.value = 'Erreur lors du chargement du formulaire de paiement';
    }
};

// Récupérer le client secret
const getClientSecret = async () => {
    try {
        const response = await fetch(`/api/reservations/${props.reservation.id}/payment-intent`);
        const data = await response.json();
        return data.client_secret;
    } catch (error) {
        console.error('Erreur récupération client secret:', error);
        throw error;
    }
};

// Initialiser au montage
onMounted(async () => {
    try {
        const StripeConstructor = await loadStripeScript();
        if (props.stripePublishableKey && StripeConstructor) {
            stripe.value = StripeConstructor(props.stripePublishableKey);

            // Initialiser les éléments si on utilise un nouveau moyen de paiement
            if (useNewPaymentMethod.value) {
                await nextTick();
                const clientSecret = await getClientSecret();
                await initializeStripeElements(clientSecret);
            }
        }
    } catch (error) {
        console.error('Erreur initialisation Stripe:', error);
        paymentError.value = 'Erreur lors du chargement du système de paiement';
    }
});

// Nettoyer au démontage
onUnmounted(() => {
    if (elements.value) {
        elements.value.destroy();
    }
});

// Confirmer le paiement
const confirmPayment = async () => {
    processing.value = true;
    paymentError.value = '';

    try {
        if (selectedPaymentMethod.value) {
            // Paiement avec moyen sauvegardé
            router.post(
                route('reservations.confirm-payment', props.reservation.id),
                {
                    payment_method_id: selectedPaymentMethod.value,
                },
                {
                    onSuccess: () => {
                        router.visit(route('messaging.index'));
                    },
                    onError: (errors) => {
                        paymentError.value = errors.message || 'Erreur lors du paiement';
                    },
                    onFinish: () => {
                        processing.value = false;
                    },
                },
            );
        } else {
            // Nouveau moyen de paiement
            if (!stripe.value || !elements.value) {
                throw new Error('Formulaire de paiement non initialisé');
            }

            const { error, paymentIntent } = await stripe.value.confirmPayment({
                elements: elements.value,
                confirmParams: {
                    return_url: window.location.origin + '/messagerie',
                    save_payment_method: savePaymentMethod.value,
                },
                redirect: 'if_required',
            });

            if (error) {
                throw new Error(error.message);
            }

            // Confirmer côté serveur
            router.post(
                route('reservations.confirm-payment', props.reservation.id),
                {
                    payment_intent_id: paymentIntent.id,
                    save_payment_method: savePaymentMethod.value,
                },
                {
                    onSuccess: () => {
                        router.visit(route('messaging.index'));
                    },
                    onError: (errors) => {
                        paymentError.value = errors.message || 'Erreur lors de la confirmation du paiement';
                    },
                    onFinish: () => {
                        processing.value = false;
                    },
                },
            );
        }
    } catch (error) {
        console.error('Erreur paiement:', error);
        paymentError.value = error.message || 'Erreur lors du traitement du paiement';
        processing.value = false;
    }
};

// Réinitialiser Stripe Elements quand on change de mode
const handlePaymentMethodChange = async () => {
    if (useNewPaymentMethod.value && stripe.value) {
        await nextTick();
        try {
            const clientSecret = await getClientSecret();
            await initializeStripeElements(clientSecret);
        } catch (error) {
            console.error('Erreur initialisation éléments:', error);
            paymentError.value = 'Erreur lors de la configuration du paiement';
        }
    }
};

// Watcher pour le changement de mode de paiement
watch(() => useNewPaymentMethod.value, handlePaymentMethodChange);
</script>
