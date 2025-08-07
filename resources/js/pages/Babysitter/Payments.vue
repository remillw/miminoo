<script setup lang="ts">
import StripeAccountEditForm from '@/components/StripeAccountEditForm.vue';
import StripeOnboardingForm from '@/components/StripeOnboardingForm.vue';
import StripeServerUpload from '@/components/StripeServerUpload.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useStatusColors } from '@/composables/useStatusColors';
import { useToast } from '@/composables/useToast';
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
    Info,
    Minus,
    RefreshCw,
    Shield,
    TrendingDown,
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
    parent_name?: string;
    service_date?: string;
    funds_status?: string;
    funds_message?: string;
    funds_release_date?: string;
    date?: string;
}

interface BabysitterProfile {
    verification_status: string;
    verification_documents?: any;
    verification_notes?: string;
}

interface BabysitterReservation {
    id: number;
    status: string;
    service_start_at: string;
    service_end_at: string;
    babysitter_amount: number;
    ad: {
        title: string;
        date_start: string;
        date_end: string;
    };
    funds_status: 'available' | 'processing' | 'waiting_service_completion';
    funds_available_at?: string;
}

interface DeductionTransaction {
    id: number;
    type: 'deduction';
    date: string;
    parent_name: string;
    amount: number;
    description: string;
    ad_title: string;
    reservation_id: number;
    metadata: any;
}

interface PayoutTransaction {
    id: string;
    amount: number;
    currency: string;
    arrival_date: number;
    created: number;
    status: string;
    method: string;
    type: string;
}

interface Props {
    accountStatus: string;
    accountDetails: AccountDetails | null;
    accountBalance: AccountBalance | null;
    recentTransactions: Transaction[];
    payoutHistory: PayoutTransaction[];
    reservations: BabysitterReservation[];
    deductionTransactions: DeductionTransaction[];
    stripeAccountId: string;
    babysitterProfile: BabysitterProfile | null;
    googlePlacesApiKey?: string;
    stripePublishableKey?: string;
    user?: {
        id: number;
        firstname: string;
        lastname: string;
        email: string;
        phone?: string;
        date_of_birth?: string;
        address?: {
            address: string;
            postal_code: string;
            city: string;
        };
    };
}

const props = defineProps<Props>();

// Composables
const { getFundsStatusColor, getPayoutStatusColor, getStatusText } = useStatusColors();
const { showVerificationRequired, handleAuthError, showWarning, showSuccess, showError, handleApiResponse } = useToast();

const isLoading = ref(false);
const currentStatus = ref(props.accountStatus);
const error = ref('');
const isRefreshing = ref(false);

// Variables pour l'upload de documents (nouveau système)
const showUploadForm = ref(false);
const isDocumentUploadComplete = ref(false);

// Variable pour afficher le formulaire d'édition
const showEditForm = ref(false);

// États réactifs pour la gestion des virements
const transferSettings = ref({
    frequency: 'manual',
    weekly_anchor: 'monday',
    monthly_anchor: 1,
});

const isProcessingPayout = ref(false);

// Mode babysitter pour le layout
const currentMode = ref<'babysitter' | 'parent'>('babysitter');

// Gestion des erreurs simplifiée

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

// Statut d'onboarding simplifié
const onboardingStatus = ref<{ status: string; message?: string } | null>(null);

// Analyse simplifiée des requirements Connect
const connectRequirementsAnalysis = computed(() => {
    if (!props.stripeAccountId || !props.accountDetails) {
        return {
            hasAccount: false,
            hasRequirements: false,
            criticalRequirements: [],
            pendingRequirements: [],
            futureRequirements: [],
        };
    }

    const requirements = props.accountDetails.requirements;

    return {
        hasAccount: true,
        hasRequirements:
            (requirements.currently_due?.length || 0) > 0 ||
            (requirements.past_due?.length || 0) > 0 ||
            (requirements.eventually_due?.length || 0) > 0,
        criticalRequirements: [...(requirements.currently_due || []), ...(requirements.past_due || [])],
        pendingRequirements: requirements.pending_verification || [],
        futureRequirements: requirements.eventually_due || [],
    };
});

// Statut des requirements Connect simplifiée
const requirementsStatus = computed(() => {
    const analysis = connectRequirementsAnalysis.value;

    if (!analysis.hasAccount) {
        return {
            icon: Clock,
            label: 'En attente',
            color: 'bg-gray-100 text-gray-800',
            description: "Créez d'abord votre compte Stripe Connect",
            step: 'waiting_for_account',
            canComplete: false,
            isBlocking: false,
            priority: 'none',
        };
    }

    // Si des requirements critiques existent
    if (analysis.criticalRequirements.length > 0) {
        return {
            icon: AlertCircle,
            label: 'Action requise',
            color: 'bg-red-100 text-red-800',
            description: 'Stripe exige des informations supplémentaires pour finaliser votre compte.',
            step: 'critical_required',
            canComplete: true,
            isBlocking: true,
            priority: 'critical',
            requirements: analysis.criticalRequirements,
        };
    }

    // Si la vérification est en cours
    if (analysis.pendingRequirements.length > 0) {
        return {
            icon: Clock,
            label: 'Vérification en cours',
            color: 'bg-orange-100 text-orange-800',
            description: 'Stripe vérifie actuellement vos informations.',
            step: 'pending',
            canComplete: false,
            isBlocking: false,
            priority: 'pending',
            requirements: analysis.pendingRequirements,
        };
    }

    // Si des requirements futurs existent
    if (analysis.futureRequirements.length > 0) {
        return {
            icon: Info,
            label: 'Informations à fournir',
            color: 'bg-blue-100 text-blue-800',
            description: 'Des informations supplémentaires seront bientôt requises.',
            step: 'future_required',
            canComplete: true,
            isBlocking: false,
            priority: 'future',
            requirements: analysis.futureRequirements,
        };
    }

    // Compte complet
    return {
        icon: CheckCircle,
        label: 'Compte vérifié',
        color: 'bg-green-100 text-green-800',
        description: 'Votre compte est entièrement configuré et vérifié.',
        step: 'verified',
        canComplete: false,
        isBlocking: false,
        priority: 'completed',
    };
});

const totalAvailable = computed(() => {
    if (!props.accountBalance?.available) return 0;
    return props.accountBalance.available.reduce((sum, balance) => {
        // Les montants Stripe sont en centimes, donc on divise par 100
        return balance.currency === 'eur' ? sum + balance.amount / 100 : sum;
    }, 0);
});

const totalPending = computed(() => {
    // Calculer le total des fonds en attente de futurs transferts
    // Cela inclut les réservations payées dont les fonds seront libérés plus tard
    if (!props.recentTransactions) return 0;

    return props.recentTransactions
        .filter((transaction) => transaction.funds_status === 'pending_service' || transaction.funds_status === 'held_for_validation')
        .reduce((sum, transaction) => sum + transaction.amount, 0);
});

// Computed pour vérifier si on peut déclencher un virement
const canTriggerPayout = computed(() => {
    const balance = props.accountBalance?.available?.[0]?.amount || 0;
    return balance >= 2500; // 25€ en centimes
});

// Calculer la prochaine date de disponibilité des fonds
const nextAvailableDate = computed(() => {
    if (!props.recentTransactions || props.recentTransactions.length === 0) return null;

    // Trouver la prochaine transaction dont les fonds seront libérés
    const now = new Date();
    const nextRelease = props.recentTransactions
        .filter((transaction) => transaction.funds_status === 'held_for_validation' && transaction.funds_release_date)
        .map((transaction) => new Date(transaction.funds_release_date!))
        .filter((releaseDate) => releaseDate > now)
        .sort((a, b) => a.getTime() - b.getTime())[0];

    return nextRelease
        ? nextRelease.toLocaleDateString('fr-FR', {
              weekday: 'short',
              day: 'numeric',
              month: 'short',
              hour: '2-digit',
              minute: '2-digit',
          })
        : null;
});

// Statut de vérification des documents
const documentVerificationStatus = computed(() => {
    const individual = props.accountDetails?.individual;
    if (!individual?.verification) return null;

    const status = individual.verification.status;
    const document = individual.verification.document;

    // Gestion des différents cas
    switch (status) {
        case 'verified':
            return {
                status: 'verified',
                icon: CheckCircle,
                color: 'text-primary',
                bgColor: 'bg-primary/5',
                title: 'Documents vérifiés',
                message: "Vos documents d'identité ont été validés par Stripe.",
            };
        case 'pending':
            return {
                status: 'pending',
                icon: Clock,
                color: 'text-primary',
                bgColor: 'bg-primary/10',
                title: 'Vérification en cours',
                message: 'Vos documents sont en cours de traitement. Cela peut prendre quelques minutes à quelques heures.',
            };
        case 'unverified':
            if (document === 'unverified') {
                return {
                    status: 'required',
                    icon: AlertCircle,
                    color: 'text-primary',
                    bgColor: 'bg-primary/5',
                    title: 'Documents requis',
                    message: "Vous devez télécharger vos documents d'identité pour activer les paiements.",
                };
            }
            break;
        case 'requires_input':
            return {
                status: 'requires_input',
                icon: AlertCircle,
                color: 'text-primary',
                bgColor: 'bg-primary/10',
                title: 'Action requise',
                message: "Stripe a besoin d'informations supplémentaires pour vérifier vos documents.",
            };
        default:
            return {
                status: 'unknown',
                icon: Info,
                color: 'text-primary/60',
                bgColor: 'bg-primary/5',
                title: 'Statut inconnu',
                message: `Statut: ${status}, Document: ${document}`,
            };
    }

    return null;
});

// Requirements du compte Connect
const accountRequirements = computed(() => {
    if (!props.accountDetails?.requirements) return [];

    const messages = [];
    const accountReqs = props.accountDetails.requirements;

    if (accountReqs.currently_due?.length > 0) {
        messages.push({
            type: 'error',
            title: 'Informations requises immédiatement',
            items: accountReqs.currently_due,
            description: 'Ces informations sont nécessaires pour finaliser votre compte.',
        });
    }

    if (accountReqs.past_due?.length > 0) {
        messages.push({
            type: 'error',
            title: 'Informations en retard',
            items: accountReqs.past_due,
            description: 'Ces informations auraient dû être fournies.',
        });
    }

    if (accountReqs.pending_verification?.length > 0) {
        messages.push({
            type: 'warning',
            title: 'Vérification en cours',
            items: accountReqs.pending_verification,
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
        'individual.verification.document': "Document d'identité",
        'individual.verification.additional_document': 'Document supplémentaire',
        'individual.id_number': "Numéro d'identification",
    };

    return mapping[requirement] || requirement;
};

const refreshAccountStatus = async () => {
    if (isRefreshing.value) return;

    isRefreshing.value = true;

    router.get(
        '/api/stripe/account-status',
        {},
        {
            onSuccess: (page) => {
                if (page.props && page.props.status) {
                    currentStatus.value = page.props.status;
                    // Recharger la page pour avoir les dernières données
                    setTimeout(() => {
                        router.reload();
                    }, 1000);
                }
            },
            onError: (errors) => {
                console.error('Erreur lors de la vérification du statut:', errors);
            },
            onFinish: () => {
                isRefreshing.value = false;
            },
        },
    );
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
    // Mapper frequency -> interval pour correspondre au backend
    const payload = {
        interval: transferSettings.value.frequency,
        weekly_anchor: transferSettings.value.weekly_anchor,
        monthly_anchor: transferSettings.value.monthly_anchor,
    };

    router.post('/babysitter/paiements/configure-schedule', payload, {
        preserveState: true,
        onSuccess: (page: any) => {
            handleApiResponse(page, 'Configuration des virements mise à jour avec succès');
        },
        onError: (errors: any) => {
            console.error('❌ Erreur configuration virements:', errors);
            showError('Erreur', 'Impossible de mettre à jour la configuration des virements');
        },
    });
};

const triggerManualPayout = () => {
    if (!canTriggerPayout.value || isProcessingPayout.value) return;

    isProcessingPayout.value = true;
    router.post(
        '/babysitter/paiements/manual-payout',
        {},
        {
            onFinish: () => {
                isProcessingPayout.value = false;
            },
        },
    );
};

// Compléter les informations manquantes via le formulaire interne
const completeRequirements = () => {
    // Afficher le formulaire d'onboarding pour compléter les informations
    // Le formulaire détectera automatiquement les requirements manquants
    if (connectAccountStatus.value.step !== 'completed') {
        // Faire défiler vers le formulaire d'onboarding
        const onboardingSection = document.querySelector('.onboarding-form');
        if (onboardingSection) {
            onboardingSection.scrollIntoView({ behavior: 'smooth' });
        }
    }
};

// Gestion de l'upload de documents (nouveau système)
const handleUploadComplete = (result) => {
    console.log('✅ Upload completed:', result);
    const { showSuccess } = useToast();
    showSuccess(
        '✅ Documents uploadés avec succès !',
        `${result.uploadedFiles.length} document(s) envoyé(s) directement à Stripe pour vérification.`,
    );

    isDocumentUploadComplete.value = true;
    showUploadForm.value = false;

    // Recharger la page pour mettre à jour le statut
    setTimeout(() => {
        router.reload();
    }, 1000);
};

const handleUploadError = (error) => {
    console.error('❌ Upload error:', error);
    const { showError } = useToast();
    showError("❌ Erreur lors de l'upload", error.message || "Une erreur est survenue lors de l'upload");
};

const toggleUploadForm = () => {
    showUploadForm.value = !showUploadForm.value;
};

const toggleEditForm = () => {
    showEditForm.value = !showEditForm.value;
};

onMounted(() => {
    // Vérifier si l'utilisateur arrive d'une redirection backend (par exemple manque de stripe account)
    const urlParams = new URLSearchParams(window.location.search);
    const redirectedFromPayments = urlParams.get('redirected_from') === 'payments' || sessionStorage.getItem('redirected_from_payments') === 'true';

    if (redirectedFromPayments) {
        showVerificationRequired();
        // Nettoyer les paramètres/storage
        urlParams.delete('redirected_from');
        sessionStorage.removeItem('redirected_from_payments');
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, '', newUrl);
        return;
    }

    // La vérification est maintenant gérée par le middleware CheckBabysitterVerification
    // Si on arrive ici, c'est que la babysitter est vérifiée

    // Vérifier le statut toutes les 30 secondes si on est en pending
    // Supprimé pour éviter l'affichage automatique de 'status: no account'
    // const interval = setInterval(() => {
    //     if (currentStatus.value === 'pending') {
    //         refreshAccountStatus();
    //     } else {
    //         clearInterval(interval);
    //     }
    // }, 30000);

    // Détecter si l'utilisateur revient d'une vérification Stripe
    if (urlParams.get('verification') === 'completed') {
        console.log('🎉 Vérification terminée ! Actualisation du statut...');

        // Actualiser le statut après vérification
        setTimeout(() => {
            refreshAccountStatus();
        }, 1000);

        // Nettoyer l'URL
        setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.delete('verification');
            window.history.replaceState({}, '', url.toString());
        }, 2000);
    }
});

const formatServiceDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatFundsDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getFundsStatusVariant = (status: BabysitterReservation['funds_status']) => {
    switch (status) {
        case 'available':
            return 'success';
        case 'processing':
            return 'warning';
        case 'waiting_service_completion':
            return 'secondary';
        default:
            return 'secondary';
    }
};

const formatAmount = (amount: number) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};
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
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="mb-4 text-gray-600">{{ connectAccountStatus.description }}</p>

                    <!-- Formulaire d'onboarding interne -->
                    <div
                        v-if="
                            connectAccountStatus.step === 'not_created' ||
                            connectAccountStatus.step === 'pending' ||
                            connectAccountStatus.step === 'action_required'
                        "
                        class="space-y-4"
                    >
                        <!-- Erreur -->
                        <div v-if="error" class="rounded-md border border-red-200 bg-red-50 p-4">
                            <div class="flex items-center">
                                <AlertCircle class="mr-2 h-4 w-4 text-red-500" />
                                <p class="text-sm text-red-700">{{ error }}</p>
                            </div>
                        </div>

                        <div class="border-primary/20 bg-primary/5 rounded-lg border p-4">
                            <div class="mb-2 flex items-center">
                                <Info class="text-primary mr-2 h-4 w-4" />
                                <span class="text-primary text-sm font-medium">
                                    {{
                                        connectAccountStatus.step === 'not_created'
                                            ? 'Configuration de votre compte de paiement'
                                            : 'Finalisation de votre compte'
                                    }}
                                </span>
                            </div>
                            <p class="text-primary/80 text-sm">
                                {{
                                    connectAccountStatus.step === 'not_created'
                                        ? 'Remplissez les informations ci-dessous pour configurer votre compte Stripe Connect.'
                                        : 'Complétez les informations manquantes pour finaliser votre compte.'
                                }}
                            </p>
                        </div>

                        <!-- Affichage des requirements manquants -->
                        <div v-if="accountRequirements.length > 0" class="space-y-3">
                            <div
                                v-for="requirement in accountRequirements"
                                :key="requirement.title"
                                class="rounded-lg border p-3"
                                :class="{
                                    'border-red-200 bg-red-50': requirement.type === 'error',
                                    'border-orange-200 bg-orange-50': requirement.type === 'warning',
                                }"
                            >
                                <div class="mb-2 flex items-center">
                                    <AlertCircle v-if="requirement.type === 'error'" class="mr-2 h-4 w-4 text-red-600" />
                                    <Clock v-else class="mr-2 h-4 w-4 text-orange-600" />
                                    <span
                                        class="text-sm font-medium"
                                        :class="{
                                            'text-red-800': requirement.type === 'error',
                                            'text-orange-800': requirement.type === 'warning',
                                        }"
                                        >{{ requirement.title }}</span
                                    >
                                </div>
                                <p
                                    class="mb-2 text-sm"
                                    :class="{
                                        'text-red-700': requirement.type === 'error',
                                        'text-orange-700': requirement.type === 'warning',
                                    }"
                                >
                                    {{ requirement.description }}
                                </p>
                                <ul
                                    class="space-y-1 text-xs"
                                    :class="{
                                        'text-red-600': requirement.type === 'error',
                                        'text-orange-600': requirement.type === 'warning',
                                    }"
                                >
                                    <li v-for="item in requirement.items" :key="item">• {{ formatRequirement(item) }}</li>
                                </ul>
                            </div>
                        </div>

                        <div class="onboarding-form">
                            <StripeOnboardingForm
                                v-if="user"
                                :user="user"
                                :account-status="accountStatus"
                                :stripe-account-id="stripeAccountId"
                                :google-places-api-key="googlePlacesApiKey"
                                :stripe-publishable-key="stripePublishableKey"
                            />
                        </div>
                    </div>

                    <!-- Compte configuré -->
                    <div v-else-if="connectAccountStatus.step === 'completed'" class="space-y-4">
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="flex items-center">
                                        <CheckCircle class="mr-2 h-4 w-4 text-green-600" />
                                        <span class="text-sm font-medium text-green-800">Compte Stripe Connect configuré !</span>
                                    </div>
                                    <p class="mt-1 text-sm text-green-700">Votre compte est prêt à recevoir des paiements.</p>
                                </div>
                                <Button @click="toggleEditForm" variant="outline" size="sm">
                                    {{ showEditForm ? 'Masquer l\'édition' : 'Modifier le compte' }}
                                </Button>
                            </div>
                        </div>

                        <!-- Formulaire d'édition du compte -->
                        <div v-if="showEditForm && accountDetails">
                            <StripeAccountEditForm
                                :account-details="accountDetails"
                                :stripe-publishable-key="stripePublishableKey"
                                :google-places-api-key="googlePlacesApiKey"
                            />
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
                                    <div class="flex-1">
                                        <p class="text-sm text-orange-700">En cours</p>
                                        <p class="text-2xl font-bold text-orange-900">{{ formatCurrency(totalPending) }}</p>
                                        <p v-if="nextAvailableDate" class="mt-1 text-xs text-orange-600">Disponible le {{ nextAvailableDate }}</p>
                                    </div>
                                    <Clock class="h-8 w-8 text-orange-600" />
                                </div>
                            </div>
                        </div>

                        <!-- Actions supprimées selon demande utilisateur -->
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
                                <Button
                                    @click="triggerManualPayout"
                                    :disabled="!canTriggerPayout || isProcessingPayout"
                                    size="lg"
                                    class="w-full"
                                    :class="
                                        !canTriggerPayout || isProcessingPayout ? 'cursor-not-allowed bg-gray-400' : 'bg-green-600 hover:bg-green-700'
                                    "
                                >
                                    <Wallet v-if="!isProcessingPayout" class="mr-2 h-4 w-4" />
                                    <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                    {{ isProcessingPayout ? 'Traitement en cours...' : 'Déclencher un virement (min. 25€)' }}
                                </Button>
                                <p v-if="!canTriggerPayout" class="mt-2 text-center text-sm text-red-600">Solde insuffisant (minimum 25€ requis)</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Étape 2: Informations supplémentaires (si nécessaire) -->
            <Card v-if="requirementsStatus.step !== 'verified' && connectAccountStatus.step === 'completed'">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center">
                            <div
                                class="mr-3 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
                                :class="{
                                    'bg-red-100 text-red-800': requirementsStatus.isBlocking,
                                    'bg-orange-100 text-orange-800':
                                        requirementsStatus.priority === 'future' || requirementsStatus.priority === 'pending',
                                    'bg-green-100 text-green-800': requirementsStatus.priority === 'completed',
                                    'bg-gray-100 text-gray-800': requirementsStatus.priority === 'none',
                                }"
                            >
                                2
                            </div>
                            <div>
                                <div class="flex items-center">
                                    <Shield class="mr-2 h-5 w-5" />
                                    Informations supplémentaires
                                    <span v-if="requirementsStatus.isBlocking" class="ml-2 text-xs font-medium text-red-600"> (REQUIS) </span>
                                </div>
                                <p class="text-sm font-normal text-gray-600">Vérification d'identité et documents</p>
                            </div>
                        </CardTitle>
                        <div class="flex items-center gap-2">
                            <Badge :class="requirementsStatus.color">
                                <component :is="requirementsStatus.icon" class="mr-1 h-3 w-3" />
                                {{ requirementsStatus.label }}
                            </Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="mb-4">
                        <p class="text-gray-600">{{ requirementsStatus.description }}</p>
                    </div>

                    <!-- En attente de la configuration du compte -->
                    <div v-if="requirementsStatus.step === 'waiting_for_account'" class="space-y-4">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="flex items-center">
                                <Info class="mr-2 h-4 w-4 text-gray-600" />
                                <span class="text-sm font-medium text-gray-800">Étape suivante</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-700">Configurez d'abord votre compte Stripe Connect (étape 1).</p>
                        </div>
                    </div>

                    <!-- Requirements critiques -->
                    <div v-else-if="requirementsStatus.step === 'critical_required'" class="space-y-4">
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                            <div class="mb-2 flex items-center">
                                <AlertCircle class="mr-2 h-4 w-4 text-red-600" />
                                <span class="text-sm font-medium text-red-900">🚨 Informations requises</span>
                            </div>
                            <p class="mb-2 text-sm text-red-800">Stripe demande des informations supplémentaires pour finaliser votre compte.</p>

                            <div v-if="requirementsStatus.requirements" class="mt-3">
                                <p class="mb-1 text-xs font-medium text-red-900">Informations manquantes :</p>
                                <ul class="space-y-1 text-xs text-red-800">
                                    <li v-for="req in requirementsStatus.requirements" :key="req">• {{ formatRequirement(req) }}</li>
                                </ul>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <Button @click="completeRequirements" class="w-full">
                                <Shield class="mr-2 h-4 w-4" />
                                Compléter les informations
                            </Button>
                        </div>
                    </div>

                    <!-- Requirements futurs -->
                    <div v-else-if="requirementsStatus.step === 'future_required'" class="space-y-4">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <div class="mb-2 flex items-center">
                                <Info class="mr-2 h-4 w-4 text-blue-600" />
                                <span class="text-sm font-medium text-blue-900">📅 Informations à fournir prochainement</span>
                            </div>
                            <p class="mb-2 text-sm text-blue-800">Ces informations seront bientôt requises. Vous pouvez les fournir maintenant.</p>

                            <div v-if="requirementsStatus.requirements" class="mt-3">
                                <p class="mb-1 text-xs font-medium text-blue-900">Informations qui seront requises :</p>
                                <ul class="space-y-1 text-xs text-blue-800">
                                    <li v-for="req in requirementsStatus.requirements" :key="req">• {{ formatRequirement(req) }}</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Statut de vérification des documents d'identité -->
                        <div v-if="documentVerificationStatus" class="rounded-lg border border-gray-200 bg-white p-6">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="flex items-center text-lg font-medium text-gray-900">
                                    <component :is="documentVerificationStatus.icon" class="mr-2 h-5 w-5" :class="documentVerificationStatus.color" />
                                    Vérification des documents d'identité
                                </h3>
                                <!-- Statut badge -->
                                <Badge :class="documentVerificationStatus.bgColor + ' ' + documentVerificationStatus.color">
                                    {{ documentVerificationStatus.title }}
                                </Badge>
                            </div>

                            <!-- Message de statut -->
                            <div
                                class="mb-4 rounded-lg border border-primary/20 p-4"
                                :class="documentVerificationStatus.bgColor"
                            >
                                <p class="text-sm" :class="documentVerificationStatus.color.replace('text-primary', 'text-primary/80')">
                                    {{ documentVerificationStatus.message }}
                                </p>

                                <!-- Messages spécifiques selon le statut -->
                                <div v-if="documentVerificationStatus.status === 'pending'" class="mt-3">
                                    <div class="flex items-center text-xs text-primary/70">
                                        <Clock class="mr-1 h-3 w-3" />
                                        <span>En mode test, la vérification est automatiquement acceptée</span>
                                    </div>
                                </div>

                                <div v-if="documentVerificationStatus.status === 'verified'" class="mt-3">
                                    <div class="flex items-center text-xs text-primary/70">
                                        <CheckCircle class="mr-1 h-3 w-3" />
                                        <span>Vous pouvez maintenant recevoir des paiements</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions selon le statut -->
                            <div v-if="documentVerificationStatus.status === 'required'">
                                <Button @click="toggleUploadForm" :variant="showUploadForm ? 'outline' : 'default'" class="mb-4 w-full">
                                    <User class="mr-2 h-4 w-4" />
                                    {{ showUploadForm ? 'Masquer le formulaire' : 'Télécharger mes documents' }}
                                </Button>

                                <!-- Composant d'upload via serveur -->
                                <div v-if="showUploadForm">
                                    <StripeServerUpload
                                        purpose="identity_document"
                                        @upload-complete="handleUploadComplete"
                                        @upload-error="handleUploadError"
                                    />
                                </div>
                            </div>

                            <div v-else-if="documentVerificationStatus.status === 'requires_input'">
                                <Button @click="completeRequirements" class="w-full">
                                    <AlertCircle class="mr-2 h-4 w-4" />
                                    Fournir des informations supplémentaires
                                </Button>
                            </div>

                            <!-- Informations sur les documents (toujours visible) -->
                            <div
                                class="rounded-lg bg-gray-50 p-4"
                                :class="{ 'mt-6': showUploadForm || documentVerificationStatus?.status !== 'required' }"
                            >
                                <h4 class="mb-2 font-medium text-gray-900">Types de documents acceptés</h4>
                                <ul class="space-y-1 text-sm text-gray-600">
                                    <li>• <strong>Carte d'identité française ou européenne</strong></li>
                                    <li>• <strong>Passeport en cours de validité</strong></li>
                                    <li>• <strong>Permis de conduire français</strong></li>
                                    <li>• <strong>Carte de séjour</strong> (pour les non-européens)</li>
                                </ul>
                                <div class="mt-3 rounded border-l-4 border-primary/40 bg-primary/5 p-2">
                                    <p class="text-xs text-primary/80">
                                        <span class="mr-1">🔒</span> <strong>Upload sécurisé via serveur</strong> : Vos documents sont uploadés avec
                                        la clé secrète et automatiquement liés à votre compte Connect pour résoudre les requirements.
                                    </p>

                                    <!-- Information spécifique mode test -->
                                    <div class="mt-2 rounded border-l-4 border-primary/30 bg-primary/10 p-2">
                                        <p class="text-xs text-primary/70">
                                            <span class="mr-1">🧪</span> <strong>Mode test</strong> : En environnement de test, Stripe accepte
                                            automatiquement les documents pour faciliter les tests. En production, la vérification prend quelques
                                            heures.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vérification en cours -->
                    <div v-else-if="requirementsStatus.step === 'pending'" class="space-y-4">
                        <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                            <div class="flex items-center">
                                <Clock class="mr-2 h-4 w-4 text-orange-600" />
                                <span class="text-sm font-medium text-orange-800">Vérification en cours</span>
                            </div>
                            <p class="mt-1 text-sm text-orange-700">
                                Stripe vérifie actuellement vos informations. Cela peut prendre quelques minutes à quelques heures.
                            </p>
                        </div>
                    </div>

                    <!-- Compte vérifié -->
                    <div v-else-if="requirementsStatus.step === 'verified'" class="space-y-4">
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                            <div class="flex items-center">
                                <CheckCircle class="mr-2 h-4 w-4 text-green-600" />
                                <span class="text-sm font-medium text-green-800">Compte entièrement vérifié !</span>
                            </div>
                            <p class="mt-1 text-sm text-green-700">Votre compte est prêt à recevoir des paiements.</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Mes paiements et statut des fonds -->
            <Card v-if="recentTransactions.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <TrendingUp class="mr-2 h-5 w-5" />
                        Mes paiements et statut des fonds
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div v-for="transaction in recentTransactions" :key="transaction.id" class="rounded-lg border p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900">{{ transaction.description }}</h4>
                                    <p class="text-sm text-gray-600">{{ transaction.parent_name }}</p>
                                    <p v-if="transaction.service_date" class="text-xs text-gray-400">
                                        Service : {{ formatServiceDate(transaction.service_date) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-gray-900">{{ formatCurrency(transaction.amount) }}</div>
                                    <Badge :class="getFundsStatusColor(transaction.funds_status || '').badge" class="text-xs">
                                        {{ getStatusText('funds', transaction.funds_status || '') }}
                                    </Badge>
                                </div>
                            </div>

                            <div v-if="transaction.funds_message" class="mt-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <Info class="h-4 w-4 text-gray-400" />
                                    <span class="text-gray-600">{{ transaction.funds_message }}</span>
                                </div>
                            </div>

                            <div v-if="transaction.funds_release_date && transaction.funds_status === 'held_for_validation'" class="mt-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <Calendar class="h-4 w-4 text-orange-400" />
                                    <span class="text-orange-600">
                                        Libéré le : <span class="font-medium">{{ formatFundsDate(transaction.funds_release_date) }}</span>
                                    </span>
                                </div>
                            </div>

                            <div v-if="transaction.funds_status === 'released'" class="mt-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <CheckCircle class="h-4 w-4 text-green-500" />
                                    <span class="font-medium text-green-600">✓ Fonds disponibles sur votre compte</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="recentTransactions.length === 0" class="py-8 text-center text-gray-500">
                        <Wallet class="mx-auto mb-3 h-12 w-12 text-gray-300" />
                        <p>Aucune transaction pour le moment</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Historique des virements -->
            <Card v-if="currentStatus === 'active' && props.payoutHistory && props.payoutHistory.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Download class="mr-2 h-5 w-5" />
                        Historique des virements
                    </CardTitle>
                    <CardDescription> Les virements effectués vers votre compte bancaire </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="payout in props.payoutHistory"
                            :key="`payout-${payout.id}`"
                            class="flex items-center justify-between border-b border-gray-100 py-3 last:border-b-0"
                        >
                            <div class="flex items-center">
                                <div class="mr-4 flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                                    <Download class="h-5 w-5 text-green-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Virement bancaire</p>
                                    <p class="text-sm text-gray-500">
                                        {{ payout.method === 'standard' ? 'Virement SEPA' : 'Virement instantané' }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Créé le {{ new Date(payout.created * 1000).toLocaleDateString('fr-FR') }}
                                        <span v-if="payout.arrival_date">
                                            • Arrivée le {{ new Date(payout.arrival_date * 1000).toLocaleDateString('fr-FR') }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-green-600">
                                    {{ formatAmount(payout.amount / 100) }}
                                </p>
                                <Badge :class="getPayoutStatusColor(payout.status).badge" class="text-xs">
                                    {{ getStatusText('payout', payout.status) }}
                                </Badge>
                            </div>
                        </div>
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
                            <strong>Comment fonctionne la configuration ?</strong><br />
                            <strong>Étape 1 :</strong> Configuration du compte avec formulaire sécurisé intégré<br />
                            <strong>Étape 2 :</strong> Upload direct de vos documents d'identité vers l'API Stripe
                        </div>
                        <div>
                            <strong>Pourquoi l'upload via serveur avec clé secrète ?</strong><br />
                            Plus fiable que l'upload côté client. Les documents sont uploadés avec la clé secrète et automatiquement liés au compte
                            Connect pour résoudre immédiatement les requirements.
                        </div>
                        <div>
                            <strong>Quand vais-je recevoir mes paiements ?</strong><br />
                            Automatiquement selon votre configuration une fois le compte activé et les documents vérifiés.
                        </div>
                        <div>
                            <strong>Mes données sont-elles sécurisées ?</strong><br />
                            Oui, vos documents sont uploadés via notre serveur sécurisé avec la clé secrète Stripe, puis automatiquement liés à votre
                            compte Connect.
                        </div>
                    </div>
                </CardContent>
            </Card>
            <!-- Transactions de déduction -->
            <Card v-if="currentStatus === 'active' && props.deductionTransactions.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center text-red-600">
                        <TrendingDown class="mr-2 h-5 w-5" />
                        Déductions (remboursements parents)
                    </CardTitle>
                    <CardDescription> Montants déduits de votre compte suite aux remboursements de parents </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="transaction in props.deductionTransactions"
                            :key="`deduction-${transaction.id}`"
                            class="flex items-center justify-between rounded-lg border-b border-gray-100 bg-red-50 px-3 py-3 last:border-b-0"
                        >
                            <div class="flex items-center">
                                <div class="mr-4 flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                                    <Minus class="h-5 w-5 text-red-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ transaction.description }}</p>
                                    <p class="text-sm text-gray-500">Parent: {{ transaction.parent_name }} - {{ transaction.ad_title }}</p>
                                    <p v-if="transaction.date" class="text-xs text-gray-400">
                                        {{ new Date(transaction.date).toLocaleDateString('fr-FR') }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-red-600">
                                    {{ formatAmount(transaction.amount) }}
                                </p>
                                <Badge variant="destructive" class="text-xs"> Déduction </Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </DashboardLayout>
</template>
