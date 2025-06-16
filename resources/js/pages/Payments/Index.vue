<template>
    <AppLayout>
        <div class="min-h-screen bg-gray-50 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ mode === 'babysitter' ? 'Mes paiements' : 'Paiements & Factures' }}
                    </h1>
                    <p class="mt-2 text-gray-600">
                        {{ mode === 'babysitter' 
                            ? 'Gérez vos virements et consultez vos gains' 
                            : 'Consultez vos dépenses et téléchargez vos factures' 
                        }}
                    </p>
                </div>

                <!-- Mode Babysitter -->
                <div v-if="mode === 'babysitter'">
                    <!-- Statut du compte -->
                    <div class="mb-8">
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Statut du compte Stripe</h2>
                                    <p class="text-sm text-gray-600 mt-1">
                                        {{ getAccountStatusText(accountStatus) }}
                                    </p>
                                </div>
                                <div class="flex items-center">
                                    <div :class="getStatusBadgeClass(accountStatus)" class="px-3 py-1 rounded-full text-sm font-medium">
                                        {{ getStatusText(accountStatus) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Lien d'activation si nécessaire -->
                            <div v-if="accountStatus === 'pending'" class="mt-4">
                                <button 
                                    @click="completeStripeOnboarding"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                                >
                                    Compléter la configuration Stripe
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Solde et configuration des virements (si compte actif) -->
                    <div v-if="accountStatus === 'active'" class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <!-- Solde disponible -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Solde disponible</h3>
                            <div v-if="accountBalance">
                                <div class="text-3xl font-bold text-green-600 mb-2">
                                    {{ formatAmount(accountBalance.available[0]?.amount || 0) }}€
                                </div>
                                <div class="text-sm text-gray-600">
                                    En attente : {{ formatAmount(accountBalance.pending[0]?.amount || 0) }}€
                                </div>
                            </div>
                            <div v-else class="text-gray-500">
                                Chargement du solde...
                            </div>
                        </div>

                        <!-- Configuration des virements -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Configuration des virements</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Fréquence des virements
                                    </label>
                                    <select 
                                        v-model="transferSettings.frequency"
                                        @change="updateTransferSettings"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="manual">Manuel</option>
                                        <option value="daily">Quotidien</option>
                                        <option value="weekly">Hebdomadaire</option>
                                        <option value="monthly">Mensuel</option>
                                    </select>
                                </div>

                                <div v-if="transferSettings.frequency === 'weekly'">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Jour de la semaine
                                    </label>
                                    <select 
                                        v-model="transferSettings.weekly_anchor"
                                        @change="updateTransferSettings"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="monday">Lundi</option>
                                        <option value="tuesday">Mardi</option>
                                        <option value="wednesday">Mercredi</option>
                                        <option value="thursday">Jeudi</option>
                                        <option value="friday">Vendredi</option>
                                    </select>
                                </div>

                                <div v-if="transferSettings.frequency === 'monthly'">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Jour du mois
                                    </label>
                                    <select 
                                        v-model="transferSettings.monthly_anchor"
                                        @change="updateTransferSettings"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option v-for="day in 28" :key="day" :value="day">{{ day }}</option>
                                    </select>
                                </div>

                                <!-- Virement manuel -->
                                <div v-if="transferSettings.frequency === 'manual'" class="pt-4 border-t">
                                    <button 
                                        @click="triggerManualPayout"
                                        :disabled="!canTriggerPayout || isProcessingPayout"
                                        class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                                    >
                                        <span v-if="isProcessingPayout">Traitement en cours...</span>
                                        <span v-else>Déclencher un virement (min. 25€)</span>
                                    </button>
                                    <p v-if="!canTriggerPayout" class="text-sm text-red-600 mt-2">
                                        Solde insuffisant (minimum 25€ requis)
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historique des transactions -->
                    <div v-if="accountStatus === 'active'" class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Historique des transactions</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="transaction in recentTransactions" :key="transaction.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ formatDate(transaction.created) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ getTransactionType(transaction.type) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" :class="getAmountClass(transaction.amount)">
                                            {{ formatAmount(transaction.amount) }}€
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getTransactionStatusClass(transaction.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                                {{ getTransactionStatusText(transaction.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <button 
                                                v-if="transaction.type === 'payout'"
                                                @click="downloadPayoutReceipt(transaction.id)"
                                                class="text-blue-600 hover:text-blue-800"
                                            >
                                                Télécharger reçu
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="recentTransactions.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            Aucune transaction pour le moment
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Mode Parent -->
                <div v-else>
                    <!-- Cartes statistiques -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <CreditCard class="h-8 w-8 text-blue-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Total dépensé</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ formatAmount(stats.total_spent) }}€</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <Calendar class="h-8 w-8 text-green-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Réservations totales</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ stats.total_reservations }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <Clock class="h-8 w-8 text-orange-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Paiements en attente</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ stats.pending_payments }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historique des transactions -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Historique des paiements</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Babysitter</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="transaction in transactions" :key="transaction.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ formatDate(transaction.date) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ transaction.babysitter_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ formatDate(transaction.start_date) }} - {{ transaction.duration }}h
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ formatAmount(transaction.amount) }}€
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getReservationStatusClass(transaction.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                                {{ getReservationStatusText(transaction.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <button 
                                                v-if="transaction.can_download_invoice"
                                                @click="downloadInvoice(transaction.id)"
                                                class="text-blue-600 hover:text-blue-800"
                                            >
                                                Télécharger facture
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="transactions.length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            Aucune transaction pour le moment
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { CreditCard, Calendar, Clock } from 'lucide-vue-next';

// Types
interface BabysitterProps {
    mode: 'babysitter';
    accountStatus: string;
    accountDetails: any;
    accountBalance: any;
    recentTransactions: any[];
    stripeAccountId: string;
    babysitterProfile: any;
}

interface ParentProps {
    mode: 'parent';
    stats: {
        total_spent: number;
        total_reservations: number;
        pending_payments: number;
    };
    transactions: any[];
}

type Props = BabysitterProps | ParentProps;

const props = defineProps<Props>();

// États réactifs pour le mode babysitter
const transferSettings = ref({
    frequency: 'manual',
    weekly_anchor: 'monday',
    monthly_anchor: 1
});

const isProcessingPayout = ref(false);

// Computed properties
const canTriggerPayout = computed(() => {
    if (props.mode !== 'babysitter') return false;
    const balance = props.accountBalance?.available[0]?.amount || 0;
    return balance >= 2500; // 25€ en centimes
});

// Méthodes pour le formatage
const formatAmount = (amount: number) => {
    return (amount / 100).toFixed(2);
};

const formatDate = (date: string | Date) => {
    return new Date(date).toLocaleDateString('fr-FR');
};

// Méthodes pour les statuts (mode babysitter)
const getAccountStatusText = (status: string) => {
    const statusTexts = {
        'pending': 'Votre compte Stripe est en cours de configuration',
        'active': 'Votre compte Stripe est actif et prêt à recevoir des paiements',
        'restricted': 'Votre compte Stripe nécessite des informations supplémentaires',
        'inactive': 'Votre compte Stripe est inactif'
    };
    return statusTexts[status] || 'Statut inconnu';
};

const getStatusText = (status: string) => {
    const statusTexts = {
        'pending': 'En attente',
        'active': 'Actif',
        'restricted': 'Restreint',
        'inactive': 'Inactif'
    };
    return statusTexts[status] || 'Inconnu';
};

const getStatusBadgeClass = (status: string) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'active': 'bg-green-100 text-green-800',
        'restricted': 'bg-red-100 text-red-800',
        'inactive': 'bg-gray-100 text-gray-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getTransactionType = (type: string) => {
    const types = {
        'payment': 'Paiement reçu',
        'payout': 'Virement',
        'refund': 'Remboursement'
    };
    return types[type] || type;
};

const getAmountClass = (amount: number) => {
    return amount > 0 ? 'text-green-600' : 'text-red-600';
};

const getTransactionStatusClass = (status: string) => {
    const classes = {
        'succeeded': 'bg-green-100 text-green-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'failed': 'bg-red-100 text-red-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getTransactionStatusText = (status: string) => {
    const statusTexts = {
        'succeeded': 'Réussi',
        'pending': 'En attente',
        'failed': 'Échoué'
    };
    return statusTexts[status] || status;
};

// Méthodes pour les statuts (mode parent)
const getReservationStatusClass = (status: string) => {
    const classes = {
        'completed': 'bg-green-100 text-green-800',
        'service_completed': 'bg-green-100 text-green-800',
        'pending_payment': 'bg-yellow-100 text-yellow-800',
        'payment_failed': 'bg-red-100 text-red-800',
        'cancelled': 'bg-gray-100 text-gray-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getReservationStatusText = (status: string) => {
    const statusTexts = {
        'completed': 'Terminé',
        'service_completed': 'Service terminé',
        'pending_payment': 'Paiement en attente',
        'payment_failed': 'Paiement échoué',
        'cancelled': 'Annulé'
    };
    return statusTexts[status] || status;
};

// Actions pour le mode babysitter
const completeStripeOnboarding = () => {
    router.post('/stripe/onboarding');
};

const updateTransferSettings = () => {
    router.post('/stripe/transfer-settings', transferSettings.value);
};

const triggerManualPayout = () => {
    if (!canTriggerPayout.value || isProcessingPayout.value) return;
    
    isProcessingPayout.value = true;
    router.post('/stripe/manual-payout', {}, {
        onFinish: () => {
            isProcessingPayout.value = false;
        }
    });
};

const downloadPayoutReceipt = (payoutId: string) => {
    router.get(`/stripe/payout-receipt/${payoutId}`);
};

// Actions pour le mode parent
const downloadInvoice = (reservationId: number) => {
    router.get(`/paiements/facture/${reservationId}`);
};
</script> 