<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    Building,
    Calendar,
    CheckCircle,
    Clock,
    CreditCard,
    Download,
    ExternalLink,
    Eye,
    Info,
    RefreshCw,
    Settings,
    Shield,
    TrendingUp,
    User,
    Wallet,
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

interface BabysitterProfile {
    verification_status: string;
    verification_documents?: any;
    verification_notes?: string;
}

interface Props {
    accountStatus: string;
    accountDetails: AccountDetails | null;
    accountBalance: AccountBalance | null;
    recentTransactions: Transaction[];
    stripeAccountId: string;
    babysitterProfile: BabysitterProfile | null;
}

const props = defineProps<Props>();

const isLoading = ref(false);
const currentStatus = ref(props.accountStatus);
const error = ref('');
const isRefreshing = ref(false);

// États réactifs pour la gestion des virements
const transferSettings = ref({
    frequency: 'manual',
    weekly_anchor: 'monday',
    monthly_anchor: 1
});

const isProcessingPayout = ref(false);

// Mode babysitter pour le layout
const currentMode = ref<'babysitter' | 'parent'>('babysitter');

// Configuration du compte Stripe Connect (étape 1)
const connectAccountStatus = computed(() => {
    if (!props.stripeAccountId) {
        return {
            icon: Clock,
            label: 'Compte non créé',
            color: 'bg-gray-100 text-gray-800',
            description: 'Vous devez créer votre compte Stripe Connect',
            step: 'not_created',
        };
    }

    switch (currentStatus.value) {
        case 'active':
            return {
                icon: CheckCircle,
                label: 'Compte configuré',
                color: 'bg-green-100 text-green-800',
                description: 'Votre compte Stripe Connect est entièrement configuré',
                step: 'completed',
            };
        case 'pending':
            return {
                icon: Clock,
                label: 'Configuration en cours',
                color: 'bg-orange-100 text-orange-800',
                description: 'Quelques informations supplémentaires sont nécessaires',
                step: 'pending',
            };
        case 'rejected':
            return {
                icon: AlertCircle,
                label: 'Action requise',
                color: 'bg-red-100 text-red-800',
                description: 'Il y a un problème qui nécessite votre attention',
                step: 'action_required',
            };
        default:
            return {
                icon: Clock,
                label: 'En attente',
                color: 'bg-gray-100 text-gray-800',
                description: 'Initialisation en cours...',
                step: 'pending',
            };
    }
});

// Interface pour le statut d'onboarding intelligent
interface OnboardingStatus {
    status:
        | 'completed'
        | 'identity_sufficient'
        | 'identity_completed_needs_connect'
        | 'requires_onboarding'
        | 'requires_action'
        | 'not_started'
        | 'error';
    method: 'identity' | 'connect' | 'connect_after_identity' | 'none' | 'unknown';
    message: string;
    requires_onboarding: boolean;
    can_receive_payments: boolean;
    identity_verified?: boolean;
    currently_due?: string[];
    eventually_due?: string[];
    remaining_requirements?: string[];
    error?: string;
}

// Statut d'onboarding intelligent qui utilise la nouvelle logique
const onboardingStatus = ref<OnboardingStatus | null>(null);

// Vérification d'identité (étape 2) - séparée de la configuration du compte
const identityVerificationStatus = computed(() => {
    // Si on a le statut d'onboarding intelligent, l'utiliser
    if (onboardingStatus.value) {
        const status = onboardingStatus.value;

        switch (status.status) {
            case 'completed':
                return {
                    icon: CheckCircle,
                    label: 'Vérification complétée',
                    color: 'bg-green-100 text-green-800',
                    description: status.message,
                    step: 'completed',
                    canVerify: false,
                    method: status.method,
                };
            case 'identity_sufficient':
                return {
                    icon: CheckCircle,
                    label: 'Identité vérifiée',
                    color: 'bg-green-100 text-green-800',
                    description: status.message,
                    step: 'identity_sufficient',
                    canVerify: false,
                    method: status.method,
                    showResolveButton: true, // Afficher le bouton pour résoudre eventually_due
                };
            case 'identity_completed_needs_connect':
                return {
                    icon: AlertCircle,
                    label: 'Finaliser le compte',
                    color: 'bg-blue-100 text-blue-800',
                    description: status.message,
                    step: 'identity_completed_needs_connect',
                    canVerify: false,
                    method: status.method,
                    showConnectButton: true, // Afficher le bouton pour finaliser Connect
                    identityVerified: status.identity_verified,
                    currentlyDue: status.currently_due,
                    eventuallyDue: status.eventually_due,
                };
            case 'requires_onboarding':
                return {
                    icon: AlertCircle,
                    label: 'Onboarding requis',
                    color: 'bg-red-100 text-red-800',
                    description: status.message,
                    step: 'requires_onboarding',
                    canVerify: true,
                    method: status.method,
                    remainingRequirements: status.remaining_requirements,
                };
            case 'requires_action':
                return {
                    icon: AlertCircle,
                    label: 'Action requise',
                    color: 'bg-red-100 text-red-800',
                    description: status.message,
                    step: 'requires_action',
                    canVerify: true,
                    method: status.method,
                };
            case 'not_started':
                return {
                    icon: Clock,
                    label: 'Non commencé',
                    color: 'bg-gray-100 text-gray-800',
                    description: status.message,
                    step: 'not_started',
                    canVerify: true,
                    method: status.method,
                };
        }
    }

    // Fallback vers l'ancienne logique si pas de statut intelligent
    if (!props.stripeAccountId) {
        return {
            icon: Clock,
            label: 'En attente',
            color: 'bg-gray-100 text-gray-800',
            description: "Créez d'abord votre compte Stripe Connect",
            step: 'waiting_for_account',
            canVerify: false,
        };
    }

    // Vérifier si des documents d'identité sont requis
    const identityRequirements = ['individual.verification.document', 'individual.verification.additional_document', 'individual.id_number'];

    const allRequirements = [
        ...(props.accountDetails?.requirements.currently_due || []),
        ...(props.accountDetails?.requirements.past_due || []),
        ...(props.accountDetails?.requirements.eventually_due || []),
    ];

    const needsIdentityDocs = allRequirements.some((req) => identityRequirements.some((identityReq) => req.includes(identityReq)));

    if (needsIdentityDocs) {
        return {
            icon: AlertCircle,
            label: 'Vérification requise',
            color: 'bg-red-100 text-red-800',
            description: "Stripe demande une vérification d'identité",
            step: 'required',
            canVerify: true,
        };
    }

    // Vérifier le statut de vérification individuelle
    const verificationStatus = props.accountDetails?.individual?.verification?.status;
    if (verificationStatus === 'verified') {
        return {
            icon: CheckCircle,
            label: 'Identité vérifiée',
            color: 'bg-green-100 text-green-800',
            description: 'Votre identité a été vérifiée par Stripe',
            step: 'verified',
            canVerify: false,
        };
    }

    if (verificationStatus === 'pending') {
        return {
            icon: Clock,
            label: 'Vérification en cours',
            color: 'bg-orange-100 text-orange-800',
            description: 'Stripe vérifie actuellement votre identité',
            step: 'pending',
            canVerify: false,
        };
    }

    // Si le compte est configuré mais pas encore de vérification d'identité demandée
    return {
        icon: Info,
        label: 'Pas encore requis',
        color: 'bg-blue-100 text-blue-800',
        description: "La vérification d'identité sera demandée selon vos activités",
        step: 'not_required_yet',
        canVerify: true,
    };
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

// Computed pour vérifier si on peut déclencher un virement
const canTriggerPayout = computed(() => {
    const balance = props.accountBalance?.available?.[0]?.amount || 0;
    return balance >= 2500; // 25€ en centimes
});

// Séparer les requirements entre configuration du compte et vérification d'identité
const accountRequirements = computed(() => {
    if (!props.accountDetails?.requirements) return [];

    const identityRequirements = ['individual.verification.document', 'individual.verification.additional_document', 'individual.id_number'];

    const filterRequirements = (reqs: string[]) => reqs.filter((req) => !identityRequirements.some((identityReq) => req.includes(identityReq)));

    const messages = [];
    const accountReqs = props.accountDetails.requirements;

    const currentlyDue = filterRequirements(accountReqs.currently_due);
    const pastDue = filterRequirements(accountReqs.past_due);
    const pendingVerification = filterRequirements(accountReqs.pending_verification);

    if (currentlyDue.length > 0) {
        messages.push({
            type: 'error',
            title: 'Configuration requise immédiatement',
            items: currentlyDue,
            description: 'Ces informations sont nécessaires pour configurer votre compte.',
        });
    }

    if (pastDue.length > 0) {
        messages.push({
            type: 'error',
            title: 'Configuration en retard',
            items: pastDue,
            description: 'Ces informations auraient dû être fournies.',
        });
    }

    if (pendingVerification.length > 0) {
        messages.push({
            type: 'warning',
            title: 'Vérification en cours',
            items: pendingVerification,
            description: 'Nous vérifions actuellement ces informations.',
        });
    }

    return messages;
});

const formatRequirement = (requirement: string) => {
    const mapping: { [key: string]: string } = {
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

const refreshAccountStatus = async () => {
    if (isRefreshing.value) return;

    isRefreshing.value = true;

    try {
        const response = await fetch('/api/stripe/account-status');
        const data = await response.json();

        if (response.ok) {
            currentStatus.value = data.status;
            // Recharger la page pour avoir les dernières données
            setTimeout(() => {
                router.reload();
            }, 1000);
        }
    } catch (err) {
        console.error('Erreur lors de la vérification du statut:', err);
    } finally {
        isRefreshing.value = false;
    }
};

const formatDate = (timestamp: number) => {
    return new Date(timestamp * 1000).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};

// Méthodes pour la gestion des virements
const updateTransferSettings = () => {
    router.post('/babysitter/paiements/configure-schedule', transferSettings.value);
};

const triggerManualPayout = () => {
    if (!canTriggerPayout.value || isProcessingPayout.value) return;
    
    isProcessingPayout.value = true;
    router.post('/babysitter/paiements/manual-payout', {}, {
        onFinish: () => {
            isProcessingPayout.value = false;
        }
    });
};

// Récupérer le statut d'onboarding intelligent
const fetchOnboardingStatus = async () => {
    try {
        const response = await fetch('/api/stripe/onboarding-status', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            const data = await response.json();
            if (data.success) {
                onboardingStatus.value = data.status;
            }
        }
    } catch (err) {
        console.error("Erreur lors de la récupération du statut d'onboarding:", err);
    }
};

// Démarrer la vérification Connect complète (recommandé)
const startConnectVerification = async () => {
    if (isLoading.value) return;

    isLoading.value = true;
    error.value = '';

    try {
        const response = await fetch('/stripe/create-verification-link', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json();

        if (response.ok && data.verification_url) {
            // Ouvrir dans un nouvel onglet pour une meilleure UX
            const newWindow = window.open(data.verification_url, '_blank');

            // Vérifier si la popup a été bloquée
            if (!newWindow || newWindow.closed || typeof newWindow.closed == 'undefined') {
                // Fallback : redirection directe si popup bloquée
                window.location.href = data.verification_url;
            } else {
                // Démarrer le polling pour vérifier le statut
                startStatusPolling();
            }
        } else {
            throw new Error(data.error || 'Erreur lors de la création du lien de vérification');
        }
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Une erreur est survenue';
    } finally {
        isLoading.value = false;
    }
};

// Démarrer la vérification Identity rapide
const startIdentityVerification = async () => {
    if (isLoading.value) return;

    isLoading.value = true;
    error.value = '';

    try {
        const response = await fetch('/stripe/identity/create-session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json();

        if (response.ok && data.success && data.session) {
            // Rediriger vers la page de vérification Identity intégrée
            router.visit('/babysitter/identity-verification');
        } else {
            throw new Error(data.error || 'Erreur lors de la création de la session Identity');
        }
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Une erreur est survenue';
    } finally {
        isLoading.value = false;
    }
};

// Polling du statut après vérification
const startStatusPolling = () => {
    const pollInterval = setInterval(async () => {
        try {
            await fetchOnboardingStatus();
            await refreshAccountStatus();

            // Arrêter le polling si la vérification est complète
            if (onboardingStatus.value?.status === 'completed') {
                clearInterval(pollInterval);
            }
        } catch (err) {
            console.error('Erreur lors du polling du statut:', err);
        }
    }, 5000); // Vérifier toutes les 5 secondes

    // Arrêter le polling après 5 minutes maximum
    setTimeout(() => {
        clearInterval(pollInterval);
    }, 300000);
};

// Résoudre les exigences eventually_due (méthode de fallback)
const resolveEventuallyDue = async () => {
    if (isLoading.value) return;

    isLoading.value = true;
    error.value = '';

    try {
        const response = await fetch('/stripe/identity/resolve-eventually-due', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Si un AccountLink est fourni, rediriger vers Stripe
            if (data.account_link_url) {
                window.location.href = data.account_link_url;
            } else {
                // Sinon, rafraîchir le statut
                await fetchOnboardingStatus();
                await refreshAccountStatus();
            }
        } else {
            throw new Error(data.error || 'Erreur lors de la résolution des exigences');
        }
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Une erreur est survenue';
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    // Récupérer le statut d'onboarding intelligent au chargement
    fetchOnboardingStatus();

    // Vérifier le statut toutes les 30 secondes si on est en pending
    const interval = setInterval(() => {
        if (currentStatus.value === 'pending') {
            refreshAccountStatus();
            fetchOnboardingStatus();
        } else {
            clearInterval(interval);
        }
    }, 30000);

    // Détecter si l'utilisateur revient d'une vérification
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('verification') === 'completed') {
        // Afficher un message de succès
        console.log('🎉 Vérification terminée ! Mise à jour du statut...');

        // Démarrer le polling pour détecter les changements
        startStatusPolling();

        // Nettoyer l'URL après 2 secondes
        setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.delete('verification');
            window.history.replaceState({}, '', url.toString());
        }, 2000);
    }
});
</script>

<template>
    <DashboardLayout :currentMode="currentMode">
        <Head title="Gestion des paiements" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Gestion des paiements</h1>
                <p class="text-gray-600">Configurez votre compte et gérez vos revenus</p>
            </div>

            <!-- Étape 1: Configuration du compte Stripe Connect -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center">
                            <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-800">
                                1
                            </div>
                            <div>
                                <div class="flex items-center">
                                    <CreditCard class="mr-2 h-5 w-5" />
                                    Configuration du compte Stripe Connect
                                </div>
                                <p class="text-sm font-normal text-gray-600">Informations de base et coordonnées bancaires</p>
                            </div>
                        </CardTitle>
                        <div class="flex items-center gap-2">
                            <Badge :class="connectAccountStatus.color">
                                <component :is="connectAccountStatus.icon" class="mr-1 h-3 w-3" />
                                {{ connectAccountStatus.label }}
                            </Badge>
                            <Button variant="ghost" size="sm" @click="refreshAccountStatus" :disabled="isRefreshing">
                                <RefreshCw :class="['h-4 w-4', isRefreshing && 'animate-spin']" />
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="mb-4 text-gray-600">{{ connectAccountStatus.description }}</p>

                    <!-- Compte non créé -->
                    <div v-if="connectAccountStatus.step === 'not_created'" class="space-y-4">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <div class="mb-2 flex items-center">
                                <Info class="mr-2 h-4 w-4 text-blue-600" />
                                <span class="text-sm font-medium text-blue-900">Créer votre compte de paiement</span>
                            </div>
                            <p class="text-sm text-blue-800">
                                Première étape : créez votre compte Stripe Connect pour pouvoir recevoir des paiements.
                            </p>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <h3 class="mb-2 text-sm font-medium text-gray-900">🔐 Configuration sécurisée avec Stripe</h3>
                            <div class="grid grid-cols-1 gap-4 text-sm text-gray-700 md:grid-cols-2">
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <User class="mr-2 h-4 w-4" />
                                        <span>Informations pré-remplies</span>
                                    </div>
                                    <div class="flex items-center">
                                        <Building class="mr-2 h-4 w-4" />
                                        <span>Coordonnées bancaires</span>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center">
                                        <Calendar class="mr-2 h-4 w-4" />
                                        <span>Paiements hebdomadaires</span>
                                    </div>
                                    <div class="flex items-center">
                                        <Shield class="mr-2 h-4 w-4" />
                                        <span>Chiffrement bancaire</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Button @click="startOnboarding" :disabled="isLoading" size="lg" class="w-full">
                            <ExternalLink v-if="!isLoading" class="mr-2 h-4 w-4" />
                            <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                            {{ isLoading ? 'Préparation...' : 'Créer mon compte de paiement' }}
                        </Button>
                    </div>

                    <!-- Compte en cours de configuration -->
                    <div v-else-if="connectAccountStatus.step === 'pending' || connectAccountStatus.step === 'action_required'" class="space-y-4">
                        <!-- Erreur -->
                        <div v-if="error" class="rounded-md border border-red-200 bg-red-50 p-4">
                            <div class="flex items-center">
                                <AlertCircle class="mr-2 h-4 w-4 text-red-500" />
                                <p class="text-sm text-red-700">{{ error }}</p>
                            </div>
                        </div>

                        <!-- Actions requises pour la configuration du compte -->
                        <div v-if="accountRequirements.length > 0" class="space-y-3">
                            <div
                                v-for="req in accountRequirements"
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
                                <ul
                                    :class="`space-y-1 text-xs ${
                                        req.type === 'error' ? 'text-red-700' : req.type === 'warning' ? 'text-orange-700' : 'text-blue-700'
                                    }`"
                                >
                                    <li v-for="item in req.items" :key="item">• {{ formatRequirement(item) }}</li>
                                </ul>
                            </div>
                        </div>

                        <Button @click="startOnboarding" :disabled="isLoading" size="lg" class="w-full">
                            <ExternalLink v-if="!isLoading" class="mr-2 h-4 w-4" />
                            <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                            {{ isLoading ? 'Préparation...' : 'Continuer la configuration' }}
                        </Button>
                    </div>

                    <!-- Compte configuré -->
                    <div v-else-if="connectAccountStatus.step === 'completed'" class="space-y-4">
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                            <div class="flex items-center">
                                <CheckCircle class="mr-2 h-4 w-4 text-green-600" />
                                <span class="text-sm font-medium text-green-800">Compte Stripe Connect configuré !</span>
                            </div>
                            <p class="mt-1 text-sm text-green-700">Votre compte est prêt à recevoir des paiements.</p>
                        </div>

                        <!-- Solde -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-green-700">Disponible</p>
                                        <p class="text-2xl font-bold text-green-900">{{ formatCurrency(totalAvailable) }}</p>
                                    </div>
                                    <Wallet class="h-8 w-8 text-green-600" />
                                </div>
                            </div>

                            <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-orange-700">En cours</p>
                                        <p class="text-2xl font-bold text-orange-900">{{ formatCurrency(totalPending) }}</p>
                                    </div>
                                    <Clock class="h-8 w-8 text-orange-600" />
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3">
                            <Button variant="outline" @click="startOnboarding" class="flex-1">
                                <Settings class="mr-2 h-4 w-4" />
                                Gérer mon compte
                            </Button>
                            <Button variant="outline" @click="router.visit('/stripe/connect')" class="flex-1">
                                <Eye class="mr-2 h-4 w-4" />
                                Vue détaillée
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Gestion des virements (si compte actif) -->
            <Card v-if="currentStatus === 'active'">
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Building class="mr-2 h-5 w-5" />
                        Configuration des virements
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-6">
                        <!-- Configuration de la fréquence -->
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
                                <Button
                                    @click="triggerManualPayout"
                                    :disabled="!canTriggerPayout || isProcessingPayout"
                                    size="lg"
                                    class="w-full"
                                    :class="!canTriggerPayout || isProcessingPayout ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
                                >
                                    <Wallet v-if="!isProcessingPayout" class="mr-2 h-4 w-4" />
                                    <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                    {{ isProcessingPayout ? 'Traitement en cours...' : 'Déclencher un virement (min. 25€)' }}
                                </Button>
                                <p v-if="!canTriggerPayout" class="text-sm text-red-600 mt-2 text-center">
                                    Solde insuffisant (minimum 25€ requis)
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Étape 2: Vérification d'identité -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center">
                            <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-full bg-orange-100 text-sm font-bold text-orange-800">
                                2
                            </div>
                            <div>
                                <div class="flex items-center">
                                    <Shield class="mr-2 h-5 w-5" />
                                    Vérification d'identité
                                </div>
                                <p class="text-sm font-normal text-gray-600">Pièce d'identité et documents officiels</p>
                            </div>
                        </CardTitle>
                        <Badge :class="identityVerificationStatus.color">
                            <component :is="identityVerificationStatus.icon" class="mr-1 h-3 w-3" />
                            {{ identityVerificationStatus.label }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="mb-4 text-gray-600">{{ identityVerificationStatus.description }}</p>

                    <!-- En attente de la configuration du compte -->
                    <div v-if="identityVerificationStatus.step === 'waiting_for_account'" class="space-y-4">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="flex items-center">
                                <Info class="mr-2 h-4 w-4 text-gray-600" />
                                <span class="text-sm font-medium text-gray-800">Étape suivante</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-700">
                                Vous devez d'abord configurer votre compte Stripe Connect (étape 1) avant de pouvoir vérifier votre identité.
                            </p>
                        </div>
                    </div>

                    <!-- Vérification requise -->
                    <div v-else-if="identityVerificationStatus.step === 'required'" class="space-y-4">
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                            <div class="mb-2 flex items-center">
                                <AlertCircle class="mr-2 h-4 w-4 text-red-600" />
                                <span class="text-sm font-medium text-red-900">Vérification d'identité requise</span>
                            </div>
                            <p class="text-sm text-red-800">Stripe demande une vérification d'identité pour activer pleinement votre compte.</p>
                        </div>

                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <div class="mb-2 flex items-center">
                                <Shield class="mr-2 h-4 w-4 text-blue-600" />
                                <span class="text-sm font-medium text-blue-900">Processus sécurisé Stripe</span>
                            </div>
                            <ul class="space-y-1 text-sm text-blue-800">
                                <li>• Vérification directe par Stripe (plus sécurisé)</li>
                                <li>• Chiffrement bancaire de niveau militaire</li>
                                <li>• Conformité aux réglementations européennes</li>
                                <li>• Processus rapide (5-10 minutes)</li>
                            </ul>
                        </div>

                        <Button @click="router.visit('/babysitter/identity-verification')" class="w-full">
                            <Shield class="mr-2 h-4 w-4" />
                            Vérifier mon identité avec Stripe Identity
                        </Button>
                    </div>

                    <!-- Vérification en cours -->
                    <div v-else-if="identityVerificationStatus.step === 'pending'" class="space-y-4">
                        <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                            <div class="flex items-center">
                                <Clock class="mr-2 h-4 w-4 text-orange-600" />
                                <span class="text-sm font-medium text-orange-800">Vérification en cours</span>
                            </div>
                            <p class="mt-1 text-sm text-orange-700">
                                Stripe vérifie actuellement votre identité. Cela peut prendre quelques minutes à quelques heures.
                            </p>
                        </div>
                    </div>

                    <!-- Identité vérifiée -->
                    <div v-else-if="identityVerificationStatus.step === 'verified'" class="space-y-4">
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                            <div class="flex items-center">
                                <CheckCircle class="mr-2 h-4 w-4 text-green-600" />
                                <span class="text-sm font-medium text-green-800">Identité vérifiée par Stripe !</span>
                            </div>
                            <p class="mt-1 text-sm text-green-700">Votre identité a été vérifiée avec succès par Stripe.</p>
                        </div>
                    </div>

                    <!-- Identity sufficient mais eventually_due à résoudre -->
                    <div v-else-if="identityVerificationStatus.step === 'identity_sufficient'" class="space-y-4">
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                            <div class="flex items-center">
                                <CheckCircle class="mr-2 h-4 w-4 text-green-600" />
                                <span class="text-sm font-medium text-green-800">Identité vérifiée via Stripe Identity !</span>
                            </div>
                            <p class="mt-1 text-sm text-green-700">{{ identityVerificationStatus.description }}</p>
                        </div>

                        <!-- Bouton pour résoudre eventually_due si nécessaire -->
                        <div v-if="identityVerificationStatus.showResolveButton" class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <div class="mb-2 flex items-center">
                                <Info class="mr-2 h-4 w-4 text-blue-600" />
                                <span class="text-sm font-medium text-blue-900">Finaliser la configuration</span>
                            </div>
                            <p class="mb-3 text-sm text-blue-800">
                                Stripe demande encore une vérification de document. Cliquez ci-dessous pour utiliser votre vérification Identity
                                existante.
                            </p>
                            <Button @click="resolveEventuallyDue" :disabled="isLoading" class="w-full">
                                <Shield class="mr-2 h-4 w-4" />
                                {{ isLoading ? 'Résolution en cours...' : 'Finaliser avec Stripe Identity' }}
                            </Button>
                        </div>
                    </div>

                    <!-- Identité complétée, mais Connect a besoin de finalisation -->
                    <div v-else-if="identityVerificationStatus.step === 'identity_completed_needs_connect'" class="space-y-4">
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                            <div class="flex items-center">
                                <CheckCircle class="mr-2 h-4 w-4 text-green-600" />
                                <span class="text-sm font-medium text-green-800">✅ Identité vérifiée avec Stripe Identity !</span>
                            </div>
                            <p class="mt-1 text-sm text-green-700">Votre identité a été vérifiée avec succès.</p>
                        </div>

                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <div class="mb-2 flex items-center">
                                <Info class="mr-2 h-4 w-4 text-blue-600" />
                                <span class="text-sm font-medium text-blue-900">Finalisation requise</span>
                            </div>
                            <p class="mb-3 text-sm text-blue-800">
                                {{ identityVerificationStatus.description }}
                            </p>

                            <!-- Afficher les exigences restantes -->
                            <div
                                v-if="identityVerificationStatus.currentlyDue?.length || identityVerificationStatus.eventuallyDue?.length"
                                class="mb-4"
                            >
                                <p class="mb-1 text-xs font-medium text-blue-900">Informations requises :</p>
                                <ul class="space-y-1 text-xs text-blue-800">
                                    <li
                                        v-for="req in [
                                            ...(identityVerificationStatus.currentlyDue || []),
                                            ...(identityVerificationStatus.eventuallyDue || []),
                                        ]"
                                        :key="req"
                                    >
                                        • {{ formatRequirement(req) }}
                                    </li>
                                </ul>
                            </div>

                            <!-- Option 1: Stripe Connect complet (recommandé) -->
                            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3">
                                <div class="mb-2 flex items-center">
                                    <CheckCircle class="mr-2 h-4 w-4 text-green-600" />
                                    <span class="text-sm font-medium text-green-800">Option 1 : Finalisation complète (Recommandé)</span>
                                </div>
                                <p class="mb-2 text-xs text-green-700">Finalisez tout en une fois : documents d'identité + informations bancaires</p>
                                <Button @click="startConnectVerification" :disabled="isLoading" class="w-full">
                                    <ExternalLink class="mr-2 h-4 w-4" />
                                    {{ isLoading ? 'Préparation...' : 'Finaliser avec Stripe Connect' }}
                                </Button>
                            </div>

                            <!-- Option 2: Identity rapide -->
                            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3">
                                <div class="mb-2 flex items-center">
                                    <Shield class="mr-2 h-4 w-4 text-blue-600" />
                                    <span class="text-sm font-medium text-blue-800">Option 2 : Vérification rapide</span>
                                </div>
                                <p class="mb-2 text-xs text-blue-700">Vérifiez seulement votre identité maintenant (plus rapide)</p>
                                <Button @click="startIdentityVerification" :disabled="isLoading" variant="outline" class="w-full">
                                    <Shield class="mr-2 h-4 w-4" />
                                    {{ isLoading ? 'Préparation...' : 'Vérification Identity' }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Vérification complétée -->
                    <div v-else-if="identityVerificationStatus.step === 'completed'" class="space-y-4">
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                            <div class="flex items-center">
                                <CheckCircle class="mr-2 h-4 w-4 text-green-600" />
                                <span class="text-sm font-medium text-green-800">Configuration complète !</span>
                            </div>
                            <p class="mt-1 text-sm text-green-700">{{ identityVerificationStatus.description }}</p>
                        </div>
                    </div>

                    <!-- Pas encore requis -->
                    <div v-else-if="identityVerificationStatus.step === 'not_required_yet'" class="space-y-4">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <div class="flex items-center">
                                <Info class="mr-2 h-4 w-4 text-blue-600" />
                                <span class="text-sm font-medium text-blue-800">Vérification optionnelle</span>
                            </div>
                            <p class="mt-1 text-sm text-blue-700">
                                La vérification d'identité sera demandée automatiquement par Stripe selon vos activités. Vous pouvez aussi la faire
                                maintenant.
                            </p>
                        </div>

                        <Button variant="outline" @click="router.visit('/babysitter/identity-verification')" class="w-full">
                            <Shield class="mr-2 h-4 w-4" />
                            Vérifier mon identité maintenant (optionnel)
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Transactions récentes -->
            <Card v-if="currentStatus === 'active' && recentTransactions.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <TrendingUp class="mr-2 h-5 w-5" />
                        Transactions récentes
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="transaction in recentTransactions"
                            :key="transaction.id"
                            class="flex items-center justify-between border-b border-gray-100 py-3 last:border-b-0"
                        >
                            <div>
                                <p class="font-medium">{{ formatCurrency(transaction.amount) }}</p>
                                <p class="text-sm text-gray-500">{{ formatDate(transaction.created) }}</p>
                            </div>
                            <Badge variant="outline">
                                <Download class="mr-1 h-3 w-3" />
                                Reçu
                            </Badge>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <Button variant="outline" @click="router.visit('/stripe/connect')" class="w-full">
                            <Eye class="mr-2 h-4 w-4" />
                            Voir toutes les transactions
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Aide -->
            <Card>
                <CardHeader>
                    <CardTitle>Besoin d'aide ?</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3 text-sm text-gray-600">
                        <div>
                            <strong>Quelle est la différence entre les deux étapes ?</strong><br />
                            <strong>Étape 1 :</strong> Configuration de base (coordonnées bancaires, informations personnelles)<br />
                            <strong>Étape 2 :</strong> Vérification d'identité (pièce d'identité officielle)
                        </div>
                        <div>
                            <strong>Quand vais-je recevoir mes paiements ?</strong><br />
                            Automatiquement chaque vendredi sur votre compte bancaire une fois les deux étapes complétées.
                        </div>
                        <div>
                            <strong>Mes données sont-elles sécurisées ?</strong><br />
                            Oui, toutes vos informations sont protégées par le chiffrement bancaire de niveau militaire de Stripe.
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </DashboardLayout>
</template>