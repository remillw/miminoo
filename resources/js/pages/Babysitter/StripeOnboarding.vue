<script setup lang="ts">
// import { Alert, AlertDescription } from '@/components/ui/alert';
// import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    Building,
    CheckCircle,
    Clock,
    CreditCard,
    Euro,
    ExternalLink,
    FileText,
    MapPin,
    Shield,
    TrendingUp,
    User,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface AccountDetails {
    id: string;
    email: string;
    charges_enabled: boolean;
    payouts_enabled: boolean;
    details_submitted: boolean;
    requirements: {
        currently_due: string[];
        eventually_due: string[];
        past_due: string[];
        pending_verification: string[];
        disabled_reason: string | null;
    };
    business_profile: {
        name: string | null;
        product_description: string | null;
        url: string | null;
    };
    individual: {
        first_name: string | null;
        last_name: string | null;
        verification: {
            status: string;
            document: string;
        };
    };
    created: number;
}

interface AccountBalance {
    available: Array<{ amount: number; currency: string }>;
    pending: Array<{ amount: number; currency: string }>;
}

interface Transaction {
    id: string;
    amount: number;
    currency: string;
    created: number;
    description: string;
}

interface Props {
    accountStatus: string;
    accountDetails: AccountDetails | null;
    accountBalance: AccountBalance | null;
    recentTransactions: Transaction[];
    stripeAccountId: string;
}

const props = defineProps<Props>();

const isLoading = ref(false);
const currentStatus = ref(props.accountStatus);
const error = ref('');

const statusConfig = computed(() => {
    switch (currentStatus.value) {
        case 'active':
            return {
                icon: CheckCircle,
                label: 'Compte activé',
                color: 'bg-green-100 text-green-800',
                description: 'Votre compte de paiement est entièrement configuré et prêt à recevoir des paiements.',
            };
        case 'pending':
            return {
                icon: Clock,
                label: 'Configuration requise',
                color: 'bg-orange-100 text-orange-800',
                description: 'Quelques informations supplémentaires sont nécessaires pour activer votre compte.',
            };
        case 'rejected':
            return {
                icon: AlertCircle,
                label: 'Action requise',
                color: 'bg-red-100 text-red-800',
                description: 'Il y a un problème avec votre compte qui nécessite votre attention.',
            };
        default:
            return {
                icon: Clock,
                label: 'En attente',
                color: 'bg-gray-100 text-gray-800',
                description: 'Initialisation de votre compte de paiement...',
            };
    }
});

const totalAvailable = computed(() => {
    if (!props.accountBalance?.available) return 0;
    return props.accountBalance.available.reduce((sum, balance) => {
        return balance.currency === 'eur' ? sum + balance.amount / 100 : sum;
    }, 0);
});

const totalPending = computed(() => {
    if (!props.accountBalance?.pending) return 0;
    return props.accountBalance.pending.reduce((sum, balance) => {
        return balance.currency === 'eur' ? sum + balance.amount / 100 : sum;
    }, 0);
});

const requirementMessages = computed(() => {
    if (!props.accountDetails?.requirements) return [];

    const messages = [];
    const reqs = props.accountDetails.requirements;

    if (reqs.currently_due.length > 0) {
        messages.push({
            type: 'error',
            title: 'Actions requises immédiatement',
            items: reqs.currently_due,
            description: 'Ces informations sont nécessaires pour continuer à utiliser votre compte.',
        });
    }

    if (reqs.past_due.length > 0) {
        messages.push({
            type: 'error',
            title: 'Actions en retard',
            items: reqs.past_due,
            description: 'Ces informations auraient dû être fournies. Votre compte peut être limité.',
        });
    }

    if (reqs.pending_verification.length > 0) {
        messages.push({
            type: 'warning',
            title: 'Vérification en cours',
            items: reqs.pending_verification,
            description: 'Nous vérifions actuellement ces informations.',
        });
    }

    if (reqs.eventually_due.length > 0) {
        messages.push({
            type: 'info',
            title: 'À fournir prochainement',
            items: reqs.eventually_due,
            description: 'Ces informations seront demandées dans le futur.',
        });
    }

    return messages;
});

const formatRequirement = (requirement: string) => {
    const mapping: { [key: string]: string } = {
        'individual.verification.document': "Pièce d'identité",
        'individual.verification.additional_document': 'Document complémentaire',
        external_account: 'Coordonnées bancaires',
        'tos_acceptance.date': 'Acceptation des conditions',
        'business_profile.url': 'Site web',
        'business_profile.mcc': "Code d'activité",
        'individual.address.line1': 'Adresse',
        'individual.address.postal_code': 'Code postal',
        'individual.address.city': 'Ville',
        'individual.dob.day': 'Date de naissance',
        'individual.dob.month': 'Date de naissance',
        'individual.dob.year': 'Date de naissance',
        'individual.first_name': 'Prénom',
        'individual.last_name': 'Nom',
        'individual.phone': 'Numéro de téléphone',
    };

    return mapping[requirement] || requirement;
};

const startOnboarding = async () => {
    if (isLoading.value) return;

    isLoading.value = true;
    error.value = '';

    try {
        const response = await fetch('/stripe/create-onboarding-link', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json();

        if (response.ok && data.onboarding_url) {
            window.location.href = data.onboarding_url;
        } else {
            throw new Error(data.error || "Erreur lors de la création du lien d'onboarding");
        }
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Une erreur est survenue';
    } finally {
        isLoading.value = false;
    }
};

const checkAccountStatus = async () => {
    try {
        const response = await fetch('/api/stripe/account-status');
        const data = await response.json();

        if (response.ok) {
            currentStatus.value = data.status;
        }
    } catch (err) {
        console.error('Erreur lors de la vérification du statut:', err);
    }
};

const goBackToProfile = () => {
    router.visit('/profil');
};

const formatDate = (timestamp: number) => {
    return new Date(timestamp * 1000).toLocaleDateString('fr-FR');
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};

onMounted(() => {
    const interval = setInterval(() => {
        if (currentStatus.value === 'pending') {
            checkAccountStatus();
        } else {
            clearInterval(interval);
        }
    }, 30000);
});
</script>

<template>
    <Head title="Configuration du compte de paiement" />

    <div class="min-h-screen bg-gray-50 py-8">
        <div class="mx-auto max-w-4xl px-4">
            <!-- Header -->
            <div class="mb-8">
                <Button variant="ghost" @click="goBackToProfile" class="mb-4">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Retour au profil
                </Button>

                <div class="text-center">
                    <CreditCard class="mx-auto mb-4 h-12 w-12 text-blue-600" />
                    <h1 class="text-3xl font-bold text-gray-900">Configuration du compte de paiement</h1>
                    <p class="mt-2 text-gray-600">Configurez votre compte pour recevoir vos paiements de manière sécurisée</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Colonne principale -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Statut du compte -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle class="text-lg">Statut de votre compte</CardTitle>
                                <span :class="`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${statusConfig.color}`">
                                    <component :is="statusConfig.icon" class="mr-1 h-4 w-4" />
                                    {{ statusConfig.label }}
                                </span>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p class="mb-4 text-gray-600">{{ statusConfig.description }}</p>

                            <!-- Informations du compte -->
                            <div class="rounded-lg bg-gray-50 p-4">
                                <h3 class="mb-3 text-sm font-medium text-gray-900">Informations du compte</h3>
                                <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                                    <div><strong>ID du compte :</strong> {{ stripeAccountId }}</div>
                                    <div><strong>Statut :</strong> {{ currentStatus }}</div>
                                    <div v-if="accountDetails">
                                        <strong>Paiements :</strong>
                                        <span :class="accountDetails.charges_enabled ? 'text-green-600' : 'text-red-600'">
                                            {{ accountDetails.charges_enabled ? 'Activés' : 'Désactivés' }}
                                        </span>
                                    </div>
                                    <div v-if="accountDetails">
                                        <strong>Virements :</strong>
                                        <span :class="accountDetails.payouts_enabled ? 'text-green-600' : 'text-red-600'">
                                            {{ accountDetails.payouts_enabled ? 'Activés' : 'Désactivés' }}
                                        </span>
                                    </div>
                                    <div v-if="accountDetails"><strong>Créé le :</strong> {{ formatDate(accountDetails.created) }}</div>
                                    <div v-if="accountDetails?.individual">
                                        <strong>Vérification :</strong>
                                        <span
                                            :class="
                                                accountDetails.individual.verification.status === 'verified' ? 'text-green-600' : 'text-orange-600'
                                            "
                                        >
                                            {{ accountDetails.individual.verification.status === 'verified' ? 'Vérifié' : 'En cours' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Actions requises -->
                    <Card v-if="requirementMessages.length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center">
                                <AlertCircle class="mr-2 h-5 w-5 text-orange-500" />
                                Actions requises
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div
                                v-for="req in requirementMessages"
                                :key="req.title"
                                :class="`rounded-lg p-4 ${
                                    req.type === 'error'
                                        ? 'border border-red-200 bg-red-50'
                                        : req.type === 'warning'
                                          ? 'border border-orange-200 bg-orange-50'
                                          : 'border border-blue-200 bg-blue-50'
                                }`"
                            >
                                <h4
                                    :class="`mb-2 text-sm font-medium ${
                                        req.type === 'error' ? 'text-red-900' : req.type === 'warning' ? 'text-orange-900' : 'text-blue-900'
                                    }`"
                                >
                                    {{ req.title }}
                                </h4>
                                <p
                                    :class="`mb-2 text-xs ${
                                        req.type === 'error' ? 'text-red-700' : req.type === 'warning' ? 'text-orange-700' : 'text-blue-700'
                                    }`"
                                >
                                    {{ req.description }}
                                </p>
                                <ul
                                    :class="`space-y-1 text-xs ${
                                        req.type === 'error' ? 'text-red-700' : req.type === 'warning' ? 'text-orange-700' : 'text-blue-700'
                                    }`"
                                >
                                    <li v-for="item in req.items" :key="item">• {{ formatRequirement(item) }}</li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Configuration requise -->
                    <Card v-if="currentStatus !== 'active'">
                        <CardHeader>
                            <CardTitle>Configuration requise</CardTitle>
                            <CardDescription> Complétez la configuration de votre compte pour commencer à recevoir des paiements. </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Erreur -->
                            <div v-if="error" class="rounded-md border border-red-200 bg-red-50 p-4">
                                <div class="flex items-center">
                                    <AlertCircle class="mr-2 h-4 w-4 text-red-500" />
                                    <p class="text-sm text-red-700">{{ error }}</p>
                                </div>
                            </div>

                            <!-- Informations sur le processus -->
                            <div class="rounded-lg bg-blue-50 p-4">
                                <h3 class="mb-2 text-sm font-medium text-blue-900">🔐 Ce qui vous sera demandé</h3>
                                <div class="grid grid-cols-1 gap-4 text-sm text-blue-700 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <User class="mr-2 h-4 w-4" />
                                            <span>Informations personnelles</span>
                                        </div>
                                        <div class="flex items-center">
                                            <MapPin class="mr-2 h-4 w-4" />
                                            <span>Adresse complète</span>
                                        </div>
                                        <div class="flex items-center">
                                            <Shield class="mr-2 h-4 w-4" />
                                            <span>Pièce d'identité</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <Building class="mr-2 h-4 w-4" />
                                            <span>Coordonnées bancaires</span>
                                        </div>
                                        <div class="flex items-center">
                                            <FileText class="mr-2 h-4 w-4" />
                                            <span>Acceptation conditions</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 border-t border-blue-200 pt-3">
                                    <p class="text-xs text-blue-600">
                                        ℹ️ <strong>Pré-rempli automatiquement :</strong> Votre nom, email, type d'activité (garde d'enfants), et l'URL
                                        de notre site sont déjà configurés pour vous faire gagner du temps.
                                    </p>
                                </div>
                            </div>

                            <Button @click="startOnboarding" :disabled="isLoading" size="lg" class="w-full">
                                <ExternalLink v-if="!isLoading" class="mr-2 h-4 w-4" />
                                <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                {{ isLoading ? 'Préparation...' : 'Configurer mon compte de paiement' }}
                            </Button>

                            <p class="text-center text-xs text-gray-500">
                                Vous serez redirigé vers une page sécurisée Stripe. Vous reviendrez automatiquement ici une fois terminé.
                            </p>

                            <div v-if="currentStatus === 'pending'" class="border-t pt-4">
                                <Button variant="ghost" @click="checkAccountStatus" class="w-full text-sm"> Vérifier le statut de mon compte </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Solde (si compte actif) -->
                    <Card v-if="currentStatus === 'active'">
                        <CardHeader>
                            <CardTitle class="flex items-center text-lg">
                                <Euro class="mr-2 h-5 w-5" />
                                Solde
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm text-gray-600">Disponible</p>
                                    <p class="text-2xl font-bold text-green-600">{{ formatCurrency(totalAvailable) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">En cours</p>
                                    <p class="text-lg font-semibold text-orange-600">{{ formatCurrency(totalPending) }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Transactions récentes -->
                    <Card v-if="currentStatus === 'active' && recentTransactions.length > 0">
                        <CardHeader>
                            <CardTitle class="flex items-center text-lg">
                                <TrendingUp class="mr-2 h-5 w-5" />
                                Transactions récentes
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div
                                    v-for="transaction in recentTransactions"
                                    :key="transaction.id"
                                    class="flex items-center justify-between border-b border-gray-100 py-2 last:border-b-0"
                                >
                                    <div>
                                        <p class="text-sm font-medium">{{ formatCurrency(transaction.amount) }}</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(transaction.created) }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Aide -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Besoin d'aide ?</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3 text-sm text-gray-600">
                                <div>
                                    <strong>Le processus est-il sécurisé ?</strong><br />
                                    Oui, toutes vos informations sont protégées par le chiffrement bancaire de Stripe.
                                </div>
                                <div>
                                    <strong>Combien de temps pour la vérification ?</strong><br />
                                    Configuration : 5-10 minutes<br />
                                    Vérification : 24-48 heures
                                </div>
                                <div>
                                    <strong>Quand vais-je recevoir mes paiements ?</strong><br />
                                    Automatiquement chaque vendredi sur votre compte bancaire.
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </div>
</template>
