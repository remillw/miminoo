<template>
    <DashboardLayout :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole">
        <div class="min-h-screen bg-gray-50 py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ props.mode === 'babysitter' ? 'Mes paiements' : 'Paiements & Factures' }}
                    </h1>
                    <p class="mt-2 text-gray-600">
                        {{
                            props.mode === 'babysitter'
                                ? 'Gérez vos virements et consultez vos gains'
                                : 'Consultez vos dépenses et téléchargez vos factures'
                        }}
                    </p>
                </div>

                <!-- Mode Babysitter -->
                <div v-if="isBabysitterMode(props)">
                    <!-- Statut du compte -->
                    <div class="mb-8">
                        <div class="rounded-lg bg-white p-6 shadow">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Statut du compte Stripe</h2>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ isBabysitterMode(props) ? getAccountStatusText(props.accountStatus) : '' }}
                                    </p>
                                </div>
                                <div class="flex items-center">
                                    <div
                                        v-if="isBabysitterMode(props)"
                                        :class="getStatusBadgeClass(props.accountStatus)"
                                        class="rounded-full px-3 py-1 text-sm font-medium"
                                    >
                                        {{ getStatusText(props.accountStatus) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Lien d'activation si nécessaire -->
                            <div
                                v-if="isBabysitterMode(props) && (props.accountStatus === 'pending' || props.accountStatus === 'rejected')"
                                class="mt-4"
                            >
                                <button
                                    @click="completeStripeOnboarding"
                                    class="rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700"
                                >
                                    Compléter la configuration Stripe
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="isBabysitterMode(props) && props.accountStatus === 'active'" class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
                        <!-- Solde disponible -->
                        <div class="rounded-lg bg-white p-6 shadow">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900">Solde disponible</h3>
                            <div v-if="isBabysitterMode(props) && props.accountBalance">
                                <div class="mb-2 text-3xl font-bold text-green-600">
                                    {{ formatAmount(props.accountBalance.available[0]?.amount || 0) }}€
                                </div>
                                <div class="text-sm text-gray-600">
                                    En attente : {{ formatAmount(props.accountBalance.pending[0]?.amount || 0) }}€
                                </div>
                            </div>
                            <div v-else class="text-gray-500">Chargement du solde...</div>
                        </div>

                        <!-- Configuration des virements -->
                        <div class="rounded-lg bg-white p-6 shadow">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900">Configuration des virements</h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700"> Fréquence des virements </label>
                                    <select
                                        v-model="transferSettings.frequency"
                                        @change="updateTransferSettings"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option value="manual">Manuel</option>
                                        <option value="daily">Quotidien</option>
                                        <option value="weekly">Hebdomadaire</option>
                                        <option value="monthly">Mensuel</option>
                                    </select>
                                </div>

                                <div v-if="transferSettings.frequency === 'weekly'">
                                    <label class="mb-2 block text-sm font-medium text-gray-700"> Jour de la semaine </label>
                                    <select
                                        v-model="transferSettings.weekly_anchor"
                                        @change="updateTransferSettings"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option value="monday">Lundi</option>
                                        <option value="tuesday">Mardi</option>
                                        <option value="wednesday">Mercredi</option>
                                        <option value="thursday">Jeudi</option>
                                        <option value="friday">Vendredi</option>
                                    </select>
                                </div>

                                <div v-if="transferSettings.frequency === 'monthly'">
                                    <label class="mb-2 block text-sm font-medium text-gray-700"> Jour du mois </label>
                                    <select
                                        v-model="transferSettings.monthly_anchor"
                                        @change="updateTransferSettings"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option v-for="day in 28" :key="day" :value="day">{{ day }}</option>
                                    </select>
                                </div>

                                <!-- Virement manuel -->
                                <div v-if="transferSettings.frequency === 'manual'" class="border-t pt-4">
                                    <button
                                        @click="triggerManualPayout"
                                        :disabled="!canTriggerPayout || isProcessingPayout"
                                        class="w-full rounded-lg bg-green-600 px-4 py-2 text-white transition-colors hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-gray-400"
                                    >
                                        <span v-if="isProcessingPayout">Traitement en cours...</span>
                                        <span v-else>Déclencher un virement (min. 25€)</span>
                                    </button>
                                    <p v-if="!canTriggerPayout" class="mt-2 text-sm text-red-600">Solde insuffisant (minimum 25€ requis)</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historique des transactions -->
                    <div v-if="isBabysitterMode(props) && props.accountStatus === 'active'" class="rounded-lg bg-white shadow">
                        <div class="border-b border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900">Historique des transactions</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Montant</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Statut</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="transaction in isBabysitterMode(props) ? props.recentTransactions : []" :key="transaction.id">
                                        <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                            {{ formatDate(transaction.created) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                            {{ getTransactionType(transaction.type) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium whitespace-nowrap" :class="getAmountClass(transaction.amount)">
                                            {{ formatAmount(transaction.amount) }}€
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="getTransactionStatusClass(transaction.status)"
                                                class="rounded-full px-2 py-1 text-xs font-medium"
                                            >
                                                {{ getTransactionStatusText(transaction.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                            <button
                                                v-if="transaction.type === 'payout'"
                                                @click="downloadPayoutReceipt(transaction.id)"
                                                class="text-blue-600 hover:text-blue-800"
                                            >
                                                Télécharger reçu
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="isBabysitterMode(props) && (!props.recentTransactions || props.recentTransactions.length === 0)">
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Aucune transaction pour le moment</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Mode Parent -->
                <div v-if="isParentMode(props)">
                    <!-- Cartes statistiques -->
                    <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div class="rounded-lg bg-white p-6 shadow">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <CreditCard class="h-8 w-8 text-blue-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Total dépensé</p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ isParentMode(props) ? formatAmount(props.stats.total_spent) : '0' }}€
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg bg-white p-6 shadow">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <Calendar class="h-8 w-8 text-green-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Réservations totales</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ isParentMode(props) ? props.stats.total_reservations : 0 }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg bg-white p-6 shadow">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <Clock class="text-primary h-8 w-8" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Paiements en attente</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ isParentMode(props) ? props.stats.pending_payments : 0 }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historique des transactions -->
                    <div class="rounded-lg bg-white shadow">
                        <div class="border-b border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900">Historique des paiements</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Babysitter</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Service</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Montant</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Statut</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="transaction in isParentMode(props) ? props.transactions : []" :key="`${transaction.type}-${transaction.id}`"
                                        :class="transaction.type === 'refund' ? 'bg-green-50' : ''">
                                        <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                            {{ formatDate(transaction.date) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                            {{ transaction.babysitter_name }}
                                            <span v-if="transaction.type === 'refund'" class="ml-2 text-xs text-green-600">(Remboursement)</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                            <span v-if="transaction.type === 'payment'">
                                                {{ formatDate(transaction.service_start) }} - {{ transaction.duration }}h
                                            </span>
                                            <span v-else-if="transaction.type === 'refund'" class="text-green-600">
                                                {{ transaction.description }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                            <span :class="transaction.type === 'refund' ? 'text-green-600' : 'text-gray-900'">
                                                {{ transaction.type === 'refund' ? '+' : '' }}{{ formatAmount(transaction.amount) }}€
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="transaction.type === 'refund' ? 'bg-green-100 text-green-800' : getReservationStatusClass(transaction.status)"
                                                class="rounded-full px-2 py-1 text-xs font-medium"
                                            >
                                                {{ transaction.type === 'refund' ? 'Remboursé' : getReservationStatusText(transaction.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-500">
                                            <button
                                                v-if="transaction.type === 'payment' && transaction.can_download_invoice"
                                                @click="downloadInvoice(transaction.id)"
                                                class="text-blue-600 hover:text-blue-800"
                                            >
                                                Télécharger facture
                                            </button>
                                            <span v-if="transaction.type === 'refund'" class="text-xs text-gray-400">
                                                Frais service/Stripe déduits
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="isParentMode(props) && (!props.transactions || props.transactions.length === 0)">
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">Aucune transaction pour le moment</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { Calendar, Clock, CreditCard } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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

const page = usePage();

// Récupérer les informations utilisateur depuis les props globales
const user = computed(() => (page.props.auth as any)?.user);
const userRoles = computed(() => user.value?.roles?.map((role: any) => role.name) || []);

const hasParentRole = computed(() => userRoles.value.includes('parent'));
const hasBabysitterRole = computed(() => userRoles.value.includes('babysitter'));

// États réactifs pour le mode babysitter
const transferSettings = ref({
    frequency: 'manual',
    weekly_anchor: 'monday',
    monthly_anchor: 1,
});

const isProcessingPayout = ref(false);

// Type guards - basés uniquement sur les props du serveur
const isBabysitterMode = (props: Props): props is BabysitterProps => {
    return props.mode === 'babysitter';
};

const isParentMode = (props: Props): props is ParentProps => {
    return props.mode === 'parent';
};

// Computed properties
const canTriggerPayout = computed(() => {
    if (!isBabysitterMode(props)) return false;
    const balance = props.accountBalance?.available[0]?.amount || 0;
    return balance >= 2500; // 25€ en centimes
});

// Méthodes pour le formatage
const formatAmount = (amount: number) => {
    // Ne pas diviser par 100 - les montants sont déjà en euros dans l'application
    return Number(amount).toFixed(2);
};

const formatDate = (date: string | Date | null | undefined) => {
    if (!date) return '-';
    const dateObj = new Date(date);
    if (isNaN(dateObj.getTime())) return '-';
    return dateObj.toLocaleDateString('fr-FR');
};

// Méthodes pour les statuts (mode babysitter)
const getAccountStatusText = (status: string) => {
    const statusTexts: { [key: string]: string } = {
        pending: 'Votre compte Stripe est en cours de configuration',
        active: 'Votre compte Stripe est actif et prêt à recevoir des paiements',
        restricted: 'Votre compte Stripe nécessite des informations supplémentaires',
        rejected: 'Votre compte Stripe a été rejeté',
        inactive: 'Votre compte Stripe est inactif',
    };
    return statusTexts[status] || 'Statut inconnu';
};

const getStatusText = (status: string) => {
    const statusTexts: { [key: string]: string } = {
        pending: 'En attente',
        active: 'Actif',
        restricted: 'Restreint',
        rejected: 'Rejeté',
        inactive: 'Inactif',
    };
    return statusTexts[status] || 'Inconnu';
};

const getStatusBadgeClass = (status: string) => {
    const classes: { [key: string]: string } = {
        pending: 'bg-yellow-100 text-yellow-800',
        active: 'bg-green-100 text-green-800',
        restricted: 'bg-red-100 text-red-800',
        rejected: 'bg-red-100 text-red-800',
        inactive: 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getTransactionType = (type: string) => {
    const types: { [key: string]: string } = {
        payment: 'Paiement reçu',
        payout: 'Virement',
        refund: 'Remboursement',
    };
    return types[type] || type;
};

const getAmountClass = (amount: number) => {
    return amount > 0 ? 'text-green-600' : 'text-red-600';
};

const getTransactionStatusClass = (status: string) => {
    const classes: { [key: string]: string } = {
        succeeded: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        failed: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getTransactionStatusText = (status: string) => {
    const statusTexts: { [key: string]: string } = {
        succeeded: 'Réussi',
        pending: 'En attente',
        failed: 'Échoué',
    };
    return statusTexts[status] || status;
};

// Méthodes pour les statuts (mode parent)
const getReservationStatusClass = (status: string) => {
    const classes: { [key: string]: string } = {
        pending_payment: 'bg-yellow-100 text-yellow-800',
        paid: 'bg-green-100 text-green-800',
        active: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        service_completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-gray-100 text-gray-800',
        cancelled_by_parent: 'bg-red-100 text-red-800',
        cancelled_by_babysitter: 'bg-red-100 text-red-800',
        payment_failed: 'bg-red-100 text-red-800',
        refunded: 'bg-purple-100 text-purple-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getReservationStatusText = (status: string) => {
    const statusTexts: { [key: string]: string } = {
        pending_payment: 'En attente de paiement',
        paid: 'Payé',
        active: 'En cours',
        completed: 'Terminé',
        service_completed: 'Service terminé',
        cancelled: 'Annulé',
        cancelled_by_parent: 'Annulé par le parent',
        cancelled_by_babysitter: 'Annulé par la babysitter',
        payment_failed: 'Paiement échoué',
        refunded: 'Remboursé',
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
    router.post(
        '/stripe/manual-payout',
        {},
        {
            onFinish: () => {
                isProcessingPayout.value = false;
            },
        },
    );
};

// Méthodes pour les actions
const downloadInvoice = async (transactionId: number) => {
    try {
        const response = await fetch(`/reservations/${transactionId}/invoice`, {
            method: 'GET',
            headers: {
                'Accept': 'application/pdf',
            },
        });

        if (!response.ok) {
            if (response.status === 400) {
                alert('La facture n\'est disponible qu\'après la fin du service de babysitting.');
            } else {
                alert('Erreur lors du téléchargement de la facture');
            }
            return;
        }

        // Télécharger le fichier
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `facture-${transactionId}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors du téléchargement de la facture');
    }
};

const downloadPayoutReceipt = async (transactionId: string) => {
    try {
        // Télécharger le reçu de virement pour les babysitters
        window.open(`/babysitter/virements/${transactionId}/recu`, '_blank');
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors du téléchargement du reçu');
    }
};
</script>
