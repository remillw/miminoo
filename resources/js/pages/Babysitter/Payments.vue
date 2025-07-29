<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import StripeOnboardingForm from '@/components/StripeOnboardingForm.vue';
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
    Minus,
    RefreshCw,
    Settings,
    Shield,
    TrendingDown,
    TrendingUp,
    User,
    Wallet,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useStatusColors } from '@/composables/useStatusColors';

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

const isLoading = ref(false);
const currentStatus = ref(props.accountStatus);
const error = ref('');
const isRefreshing = ref(false);

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

// Analyse détaillée du statut de vérification d'identité
const identityVerificationAnalysis = computed(() => {
    if (!props.stripeAccountId || !props.accountDetails) {
        return {
            hasAccount: false,
            status: 'no_account',
            isBlocking: false,
            description: "Aucun compte Stripe Connect configuré"
        };
    }

    const requirements = props.accountDetails.requirements;
    const individual = props.accountDetails.individual;
    
    // Identifier les requirements d'identité
    const identityRequirements = [
        'individual.verification.document',
        'individual.verification.additional_document', 
        'individual.id_number'
    ];
    
    const criticalIdentityReqs = [
        ...(requirements.currently_due || []),
        ...(requirements.past_due || [])
    ].filter(req => identityRequirements.some(identityReq => req.includes(identityReq)));
    
    const pendingIdentityReqs = (requirements.pending_verification || [])
        .filter(req => identityRequirements.some(identityReq => req.includes(identityReq)));
    
    const futureIdentityReqs = (requirements.eventually_due || [])
        .filter(req => identityRequirements.some(identityReq => req.includes(identityReq)));

    // Analyser le statut de vérification individuelle
    const verificationStatus = individual?.verification?.status || 'unverified';
    const documentStatus = individual?.verification?.document || 'unverified';
    
    return {
        hasAccount: true,
        verificationStatus,
        documentStatus,
        criticalIdentityReqs,
        pendingIdentityReqs,
        futureIdentityReqs,
        isBlocking: criticalIdentityReqs.length > 0,
        hasIdentityRequirements: criticalIdentityReqs.length > 0 || pendingIdentityReqs.length > 0 || futureIdentityReqs.length > 0,
        isVerified: verificationStatus === 'verified',
        isPending: verificationStatus === 'pending' || pendingIdentityReqs.length > 0,
        canReceivePayments: props.accountDetails.charges_enabled && props.accountDetails.payouts_enabled,
        accountActive: currentStatus.value === 'active'
    };
});

// Vérification d'identité (étape 2) - version améliorée avec détection du statut réel
const identityVerificationStatus = computed(() => {
    const analysis = identityVerificationAnalysis.value;
    
    if (!analysis.hasAccount) {
        return {
            icon: Clock,
            label: 'En attente',
            color: 'bg-gray-100 text-gray-800',
            description: "Créez d'abord votre compte Stripe Connect",
            step: 'waiting_for_account',
            canVerify: false,
            isBlocking: false,
            priority: 'none'
        };
    }

    // Si des requirements critiques d'identité existent
    if (analysis.criticalIdentityReqs.length > 0) {
        return {
            icon: AlertCircle,
            label: 'Vérification obligatoire',
            color: 'bg-red-100 text-red-800',
            description: `Stripe exige une vérification d'identité immédiate. ${analysis.canReceivePayments ? 'Votre compte peut être suspendu' : 'Votre compte est limité'} jusqu'à ce que cette vérification soit complétée.`,
            step: 'critical_required',
            canVerify: true,
            isBlocking: true,
            priority: 'critical',
            requirements: analysis.criticalIdentityReqs,
            impactMessage: analysis.canReceivePayments 
                ? '⚠️ Votre capacité à recevoir des paiements peut être suspendue'
                : '🚫 Vous ne pouvez pas recevoir de paiements actuellement'
        };
    }

    // Si la vérification est en cours
    if (analysis.isPending) {
        return {
            icon: Clock,
            label: 'Vérification en cours',
            color: 'bg-orange-100 text-orange-800',
            description: 'Stripe vérifie actuellement votre identité. Ce processus peut prendre de quelques minutes à quelques heures.',
            step: 'pending',
            canVerify: false,
            isBlocking: false,
            priority: 'pending',
            requirements: analysis.pendingIdentityReqs
        };
    }

    // Si l'identité est vérifiée
    if (analysis.isVerified) {
        return {
            icon: CheckCircle,
            label: 'Identité vérifiée',
            color: 'bg-green-100 text-green-800',
            description: 'Votre identité a été vérifiée avec succès par Stripe. Vous pouvez recevoir des paiements normalement.',
            step: 'verified',
            canVerify: false,
            isBlocking: false,
            priority: 'completed'
        };
    }

    // Si des requirements futurs existent
    if (analysis.futureIdentityReqs.length > 0) {
        return {
            icon: Info,
            label: 'Vérification à prévoir',
            color: 'bg-blue-100 text-blue-800',
            description: 'Stripe demande une vérification d\'identité prochainement. Vous pouvez la faire maintenant pour éviter toute interruption.',
            step: 'future_required',
            canVerify: true,
            isBlocking: false,
            priority: 'future',
            requirements: analysis.futureIdentityReqs,
            impactMessage: '📅 Cette vérification sera bientôt obligatoire'
        };
    }

    // Si on a un compte actif mais pas encore de demande d'identité
    if (analysis.accountActive) {
        return {
            icon: CheckCircle,
            label: 'Aucune vérification requise',
            color: 'bg-green-100 text-green-800',
            description: 'Aucune vérification d\'identité n\'est actuellement requise pour votre compte.',
            step: 'not_required',
            canVerify: true,
            isBlocking: false,
            priority: 'optional'
        };
    }

    // Cas par défaut
    return {
        icon: Info,
        label: 'Statut à déterminer',
        color: 'bg-gray-100 text-gray-800',
        description: 'La vérification d\'identité sera demandée automatiquement selon vos activités.',
        step: 'not_required_yet',
        canVerify: true,
        isBlocking: false,
        priority: 'optional'
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
        .filter(transaction => 
            transaction.funds_status === 'pending_service' || 
            transaction.funds_status === 'held_for_validation'
        )
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
        .filter(transaction => 
            transaction.funds_status === 'held_for_validation' && 
            transaction.funds_release_date
        )
        .map(transaction => new Date(transaction.funds_release_date!))
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

const startExternalOnboarding = async () => {
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

        // Vérifier le content-type de la réponse
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const htmlContent = await response.text();
            console.error('Réponse non-JSON reçue:', htmlContent.substring(0, 200));
            throw new Error(`Erreur serveur: la réponse n'est pas au format JSON (Status: ${response.status})`);
        }

        const data = await response.json();

        if (response.ok && data.onboarding_url) {
            window.location.href = data.onboarding_url;
        } else {
            throw new Error(data.error || "Erreur lors de la création du lien d'onboarding");
        }
    } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Une erreur est survenue';
        error.value = errorMessage;

        console.error("Erreur lors de la création du lien d'onboarding:", err);
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
    // Mapper frequency -> interval pour correspondre au backend
    const payload = {
        interval: transferSettings.value.frequency,
        weekly_anchor: transferSettings.value.weekly_anchor,
        monthly_anchor: transferSettings.value.monthly_anchor,
    };
    
    router.post('/babysitter/paiements/configure-schedule', payload, {
        onSuccess: () => {
            console.log('✅ Configuration des virements mise à jour');
        },
        onError: (errors) => {
            console.error('❌ Erreur configuration virements:', errors);
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

// Démarrer la vérification d'identité spécifique (pour les requirements d'identité)
const startIdentityVerificationProcess = async () => {
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
        console.log('Réponse API Identity:', data);

        if (response.ok && data.success && data.session) {
            console.log('Session Identity reçue:', data.session);
            
            if (data.session.url) {
                console.log('URL Stripe Identity:', data.session.url);
                // Rediriger directement vers l'URL Stripe Identity
                window.location.href = data.session.url;
            } else {
                console.error('Aucune URL fournie dans la session');
                throw new Error('URL de vérification manquante dans la réponse');
            }
        } else {
            console.error('Erreur API:', data);
            throw new Error(data.error || 'Erreur lors de la création de la session Identity');
        }
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Une erreur est survenue';
    } finally {
        isLoading.value = false;
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

// Supprimé - utiliser startIdentityVerificationProcess pour aller directement vers Stripe

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
                            <Button variant="ghost" size="sm" @click="refreshAccountStatus" :disabled="isRefreshing">
                                <RefreshCw :class="['h-4 w-4', isRefreshing && 'animate-spin']" />
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="mb-4 text-gray-600">{{ connectAccountStatus.description }}</p>

                    <!-- Formulaire d'onboarding interne -->
                    <div v-if="connectAccountStatus.step === 'not_created'" class="space-y-4">
                        <div class="rounded-lg border border-primary/20 bg-primary/5 p-4">
                            <div class="mb-2 flex items-center">
                                <Info class="mr-2 h-4 w-4 text-primary" />
                                <span class="text-sm font-medium text-primary">Configuration de votre compte de paiement</span>
                            </div>
                            <p class="text-sm text-primary/80">
                                Remplissez les informations ci-dessous pour configurer votre compte Stripe Connect.
                            </p>
                        </div>

                        <StripeOnboardingForm 
                            v-if="user" 
                            :user="user" 
                            :account-status="accountStatus" 
                            :stripe-account-id="stripeAccountId"
                            :google-places-api-key="googlePlacesApiKey"
                        />
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

                        <div class="rounded-lg border border-primary/20 bg-primary/5 p-4">
                            <div class="mb-2 flex items-center">
                                <CheckCircle class="mr-2 h-4 w-4 text-primary" />
                                <span class="text-sm font-medium text-primary">Finalisation de votre compte</span>
                            </div>
                            <p class="text-sm text-primary/80">
                                Complétez les informations manquantes pour finaliser votre compte.
                            </p>
                        </div>

                        <StripeOnboardingForm 
                            v-if="user" 
                            :user="user" 
                            :account-status="accountStatus" 
                            :stripe-account-id="stripeAccountId"
                            :google-places-api-key="googlePlacesApiKey"
                        />
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

            <!-- Étape 2: Vérification d'identité -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center">
                            <div 
                                class="mr-3 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
                                :class="{
                                    'bg-red-100 text-red-800': identityVerificationStatus.isBlocking,
                                    'bg-orange-100 text-orange-800': identityVerificationStatus.priority === 'future' || identityVerificationStatus.priority === 'pending',
                                    'bg-green-100 text-green-800': identityVerificationStatus.priority === 'completed',
                                    'bg-gray-100 text-gray-800': identityVerificationStatus.priority === 'none' || identityVerificationStatus.priority === 'optional'
                                }"
                            >
                                2
                            </div>
                            <div>
                                <div class="flex items-center">
                                    <Shield class="mr-2 h-5 w-5" />
                                    Vérification d'identité
                                    <span v-if="identityVerificationStatus.isBlocking" class="ml-2 text-xs font-medium text-red-600">
                                        (BLOQUANT)
                                    </span>
                                </div>
                                <p class="text-sm font-normal text-gray-600">
                                    Pièce d'identité et documents officiels
                                    <span v-if="identityVerificationStatus.priority === 'critical'" class="text-red-600 font-medium">
                                        - Action immédiate requise
                                    </span>
                                </p>
                            </div>
                        </CardTitle>
                        <div class="flex items-center gap-2">
                            <Badge :class="identityVerificationStatus.color">
                                <component :is="identityVerificationStatus.icon" class="mr-1 h-3 w-3" />
                                {{ identityVerificationStatus.label }}
                            </Badge>
                            <Badge v-if="identityVerificationStatus.isBlocking" variant="destructive" class="text-xs">
                                BLOQUANT
                            </Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="mb-4">
                        <p class="text-gray-600">{{ identityVerificationStatus.description }}</p>
                        
                        <!-- Message d'impact si bloquant -->
                        <div v-if="identityVerificationStatus.impactMessage" class="mt-3 rounded-lg border-l-4 border-red-500 bg-red-50 p-3">
                            <p class="text-sm font-medium text-red-800">{{ identityVerificationStatus.impactMessage }}</p>
                        </div>
                    </div>

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

                    <!-- Vérification obligatoire critique -->
                    <div v-else-if="identityVerificationStatus.step === 'critical_required'" class="space-y-4">
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                            <div class="mb-2 flex items-center">
                                <AlertCircle class="mr-2 h-4 w-4 text-red-600" />
                                <span class="text-sm font-medium text-red-900">🚨 Vérification d'identité OBLIGATOIRE</span>
                            </div>
                            <p class="text-sm text-red-800 mb-2">
                                Stripe exige une vérification d'identité immédiate pour votre compte.
                            </p>
                            
                            <!-- Afficher les requirements spécifiques -->
                            <div v-if="identityVerificationStatus.requirements" class="mt-3">
                                <p class="text-xs font-medium text-red-900 mb-1">Documents requis :</p>
                                <ul class="space-y-1 text-xs text-red-800">
                                    <li v-for="req in identityVerificationStatus.requirements" :key="req">
                                        • {{ formatRequirement(req) }}
                                    </li>
                                </ul>
                            </div>
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

                        <Button @click="startIdentityVerificationProcess" :disabled="isLoading" class="w-full bg-red-600 hover:bg-red-700">
                            <Shield v-if="!isLoading" class="mr-2 h-4 w-4" />
                            <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                            {{ isLoading ? 'Redirection...' : '🆔 Vérifier mon identité avec Stripe (URGENT)' }}
                        </Button>
                    </div>

                    <!-- Vérification future (eventually_due) -->
                    <div v-else-if="identityVerificationStatus.step === 'future_required'" class="space-y-4">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <div class="mb-2 flex items-center">
                                <Info class="mr-2 h-4 w-4 text-blue-600" />
                                <span class="text-sm font-medium text-blue-900">📅 Vérification à prévoir</span>
                            </div>
                            <p class="text-sm text-blue-800 mb-2">
                                Stripe demande une vérification d'identité prochainement. Vous pouvez la faire maintenant pour éviter toute interruption.
                            </p>
                            
                            <!-- Afficher les requirements futurs -->
                            <div v-if="identityVerificationStatus.requirements" class="mt-3">
                                <p class="text-xs font-medium text-blue-900 mb-1">Documents qui seront requis :</p>
                                <ul class="space-y-1 text-xs text-blue-800">
                                    <li v-for="req in identityVerificationStatus.requirements" :key="req">
                                        • {{ formatRequirement(req) }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <Button @click="startIdentityVerificationProcess" :disabled="isLoading" class="w-full">
                            <Shield v-if="!isLoading" class="mr-2 h-4 w-4" />
                            <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                            {{ isLoading ? 'Redirection...' : 'Vérifier maintenant' }}
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
                                <Button @click="startIdentityVerificationProcess" :disabled="isLoading" variant="outline" class="w-full">
                                    <Shield v-if="!isLoading" class="mr-2 h-4 w-4" />
                                    <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-gray-400 border-t-transparent"></div>
                                    {{ isLoading ? 'Redirection...' : 'Vérification Identity' }}
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

                        <Button variant="outline" @click="startIdentityVerificationProcess" :disabled="isLoading" class="w-full">
                            <Shield v-if="!isLoading" class="mr-2 h-4 w-4" />
                            <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-gray-400 border-t-transparent"></div>
                            {{ isLoading ? 'Redirection...' : 'Vérifier mon identité maintenant (optionnel)' }}
                        </Button>
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
                        <div
                            v-for="transaction in recentTransactions"
                            :key="transaction.id"
                            class="rounded-lg border p-4"
                        >
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
                                    <span class="text-green-600 font-medium">✓ Fonds disponibles sur votre compte</span>
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
                    <CardDescription>
                        Les virements effectués vers votre compte bancaire
                    </CardDescription>
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
            <!-- Transactions de déduction -->
            <Card v-if="currentStatus === 'active' && props.deductionTransactions.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center text-red-600">
                        <TrendingDown class="mr-2 h-5 w-5" />
                        Déductions (remboursements parents)
                    </CardTitle>
                    <CardDescription>
                        Montants déduits de votre compte suite aux remboursements de parents
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="transaction in props.deductionTransactions"
                            :key="`deduction-${transaction.id}`"
                            class="flex items-center justify-between border-b border-gray-100 py-3 last:border-b-0 bg-red-50 rounded-lg px-3"
                        >
                            <div class="flex items-center">
                                <div class="mr-4 flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                                    <Minus class="h-5 w-5 text-red-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ transaction.description }}</p>
                                    <p class="text-sm text-gray-500">
                                        Parent: {{ transaction.parent_name }} - {{ transaction.ad_title }}
                                    </p>
                                    <p v-if="transaction.date" class="text-xs text-gray-400">
                                        {{ new Date(transaction.date).toLocaleDateString('fr-FR') }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-red-600">
                                    {{ formatAmount(transaction.amount) }}
                                </p>
                                <Badge variant="destructive" class="text-xs">
                                    Déduction
                                </Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>
    </DashboardLayout>
</template>
