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
import { useToast } from '@/composables/useToast';

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
const { showVerificationRequired, handleAuthError, showWarning } = useToast();

const isLoading = ref(false);
const currentStatus = ref(props.accountStatus);
const error = ref('');
const isRefreshing = ref(false);

// Variables pour l'upload de documents
const uploading = ref(false);
const uploadedDocuments = ref({ front: null, back: null });

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
const onboardingStatus = ref<{status: string, message?: string} | null>(null);

// Analyse simplifiée des requirements Connect
const connectRequirementsAnalysis = computed(() => {
    if (!props.stripeAccountId || !props.accountDetails) {
        return {
            hasAccount: false,
            hasRequirements: false,
            criticalRequirements: [],
            pendingRequirements: [],
            futureRequirements: []
        };
    }

    const requirements = props.accountDetails.requirements;
    
    return {
        hasAccount: true,
        hasRequirements: 
            (requirements.currently_due?.length || 0) > 0 ||
            (requirements.past_due?.length || 0) > 0 ||
            (requirements.eventually_due?.length || 0) > 0,
        criticalRequirements: [
            ...(requirements.currently_due || []),
            ...(requirements.past_due || [])
        ],
        pendingRequirements: requirements.pending_verification || [],
        futureRequirements: requirements.eventually_due || []
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
            priority: 'none'
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
            requirements: analysis.criticalRequirements
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
            requirements: analysis.pendingRequirements
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
            requirements: analysis.futureRequirements
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
        priority: 'completed'
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
        'individual.verification.document': 'Document d\'identité',
        'individual.verification.additional_document': 'Document supplémentaire',
        'individual.id_number': 'Numéro d\'identification'
    };

    return mapping[requirement] || requirement;
};


const refreshAccountStatus = async () => {
    if (isRefreshing.value) return;

    isRefreshing.value = true;

    router.get('/api/stripe/account-status', {}, {
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
        }
    });
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

// Gestion de l'upload de documents
const handleDocumentUpload = (event, type) => {
    const input = event.target;
    const file = input.files?.[0];
    
    if (file) {
        // Vérifier le type de fichier
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!allowedTypes.includes(file.type)) {
            showWarning('Type de fichier invalide', 'Seuls les fichiers JPEG, PNG et PDF sont acceptés.');
            return;
        }
        
        // Vérifier la taille (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            showWarning('Fichier trop volumineux', 'La taille maximale est de 10MB.');
            return;
        }
        
        uploadedDocuments.value[type] = file;
    }
};

const removeDocument = (type) => {
    uploadedDocuments.value[type] = null;
};

const uploadDocuments = async () => {
    if (!uploadedDocuments.value.front) {
        showWarning('Document manquant', 'Veuillez sélectionner au moins le recto de votre carte d\'identité.');
        return;
    }
    
    uploading.value = true;
    
    try {
        const formData = new FormData();
        formData.append('identity_document_front', uploadedDocuments.value.front);
        if (uploadedDocuments.value.back) {
            formData.append('identity_document_back', uploadedDocuments.value.back);
        }
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch('/stripe/upload-identity-documents', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            const { showSuccess } = useToast();
            showSuccess('✅ Documents uploadés avec succès !', 'Vos documents ont été envoyés pour vérification.');
            
            // Réinitialiser le formulaire
            uploadedDocuments.value = { front: null, back: null };
            
            // Recharger la page pour mettre à jour le statut
            router.reload();
        } else {
            throw new Error(result.error || 'Erreur lors de l\'upload des documents');
        }
    } catch (error) {
        console.error('Erreur upload:', error);
        const { showError } = useToast();
        showError('❌ Erreur lors de l\'upload', error.message);
    } finally {
        uploading.value = false;
    }
};


onMounted(() => {
    // Vérifier si l'utilisateur arrive d'une redirection backend (par exemple manque de stripe account)
    const urlParams = new URLSearchParams(window.location.search);
    const redirectedFromPayments = urlParams.get('redirected_from') === 'payments' || 
                                  sessionStorage.getItem('redirected_from_payments') === 'true';
    
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
    const interval = setInterval(() => {
        if (currentStatus.value === 'pending') {
            refreshAccountStatus();
        } else {
            clearInterval(interval);
        }
    }, 30000);

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
                            <Button variant="ghost" size="sm" @click="refreshAccountStatus" :disabled="isRefreshing">
                                <RefreshCw :class="['h-4 w-4', isRefreshing && 'animate-spin']" />
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="mb-4 text-gray-600">{{ connectAccountStatus.description }}</p>

                    <!-- Formulaire d'onboarding interne -->
                    <div v-if="connectAccountStatus.step === 'not_created' || connectAccountStatus.step === 'pending' || connectAccountStatus.step === 'action_required'" class="space-y-4">
                        <!-- Erreur -->
                        <div v-if="error" class="rounded-md border border-red-200 bg-red-50 p-4">
                            <div class="flex items-center">
                                <AlertCircle class="mr-2 h-4 w-4 text-red-500" />
                                <p class="text-sm text-red-700">{{ error }}</p>
                            </div>
                        </div>

                        <div class="rounded-lg border border-primary/20 bg-primary/5 p-4">
                            <div class="mb-2 flex items-center">
                                <Info class="mr-2 h-4 w-4 text-primary" />
                                <span class="text-sm font-medium text-primary">
                                    {{ connectAccountStatus.step === 'not_created' ? 'Configuration de votre compte de paiement' : 'Finalisation de votre compte' }}
                                </span>
                            </div>
                            <p class="text-sm text-primary/80">
                                {{ connectAccountStatus.step === 'not_created' 
                                    ? 'Remplissez les informations ci-dessous pour configurer votre compte Stripe Connect.' 
                                    : 'Complétez les informations manquantes pour finaliser votre compte.' }}
                            </p>
                        </div>

                        <!-- Affichage des requirements manquants -->
                        <div v-if="accountRequirements.length > 0" class="space-y-3">
                            <div v-for="requirement in accountRequirements" :key="requirement.title" 
                                 class="rounded-lg border p-3"
                                 :class="{
                                     'border-red-200 bg-red-50': requirement.type === 'error',
                                     'border-orange-200 bg-orange-50': requirement.type === 'warning'
                                 }">
                                <div class="flex items-center mb-2">
                                    <AlertCircle v-if="requirement.type === 'error'" class="mr-2 h-4 w-4 text-red-600" />
                                    <Clock v-else class="mr-2 h-4 w-4 text-orange-600" />
                                    <span class="text-sm font-medium" 
                                          :class="{
                                              'text-red-800': requirement.type === 'error',
                                              'text-orange-800': requirement.type === 'warning'
                                          }">{{ requirement.title }}</span>
                                </div>
                                <p class="text-sm mb-2" 
                                   :class="{
                                       'text-red-700': requirement.type === 'error',
                                       'text-orange-700': requirement.type === 'warning'
                                   }">{{ requirement.description }}</p>
                                <ul class="space-y-1 text-xs" 
                                    :class="{
                                        'text-red-600': requirement.type === 'error',
                                        'text-orange-600': requirement.type === 'warning'
                                    }">
                                    <li v-for="item in requirement.items" :key="item">
                                        • {{ formatRequirement(item) }}
                                    </li>
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
                            />
                        </div>
                        
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

            <!-- Étape 2: Informations supplémentaires (si nécessaire) -->
            <Card v-if="requirementsStatus.step !== 'verified' && connectAccountStatus.step === 'completed'">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center">
                            <div 
                                class="mr-3 flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
                                :class="{
                                    'bg-red-100 text-red-800': requirementsStatus.isBlocking,
                                    'bg-orange-100 text-orange-800': requirementsStatus.priority === 'future' || requirementsStatus.priority === 'pending',
                                    'bg-green-100 text-green-800': requirementsStatus.priority === 'completed',
                                    'bg-gray-100 text-gray-800': requirementsStatus.priority === 'none'
                                }"
                            >
                                2
                            </div>
                            <div>
                                <div class="flex items-center">
                                    <Shield class="mr-2 h-5 w-5" />
                                    Informations supplémentaires
                                    <span v-if="requirementsStatus.isBlocking" class="ml-2 text-xs font-medium text-red-600">
                                        (REQUIS)
                                    </span>
                                </div>
                                <p class="text-sm font-normal text-gray-600">
                                    Vérification d'identité et documents
                                </p>
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
                            <p class="mt-1 text-sm text-gray-700">
                                Configurez d'abord votre compte Stripe Connect (étape 1).
                            </p>
                        </div>
                    </div>

                    <!-- Requirements critiques -->
                    <div v-else-if="requirementsStatus.step === 'critical_required'" class="space-y-4">
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                            <div class="mb-2 flex items-center">
                                <AlertCircle class="mr-2 h-4 w-4 text-red-600" />
                                <span class="text-sm font-medium text-red-900">🚨 Informations requises</span>
                            </div>
                            <p class="text-sm text-red-800 mb-2">
                                Stripe demande des informations supplémentaires pour finaliser votre compte.
                            </p>
                            
                            <div v-if="requirementsStatus.requirements" class="mt-3">
                                <p class="text-xs font-medium text-red-900 mb-1">Informations manquantes :</p>
                                <ul class="space-y-1 text-xs text-red-800">
                                    <li v-for="req in requirementsStatus.requirements" :key="req">
                                        • {{ formatRequirement(req) }}
                                    </li>
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
                            <p class="text-sm text-blue-800 mb-2">
                                Ces informations seront bientôt requises. Vous pouvez les fournir maintenant.
                            </p>
                            
                            <div v-if="requirementsStatus.requirements" class="mt-3">
                                <p class="text-xs font-medium text-blue-900 mb-1">Informations qui seront requises :</p>
                                <ul class="space-y-1 text-xs text-blue-800">
                                    <li v-for="req in requirementsStatus.requirements" :key="req">
                                        • {{ formatRequirement(req) }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Formulaire d'upload de carte d'identité -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                <svg class="inline-block mr-2 h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                Upload de carte d'identité
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- Recto -->
                                <div class="space-y-3">
                                    <label class="block text-sm font-medium text-gray-700">Carte d'identité (recto)</label>
                                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                                        <input 
                                            type="file" 
                                            id="identity-front" 
                                            class="hidden" 
                                            accept="image/*,.pdf"
                                            @change="handleDocumentUpload($event, 'front')"
                                        />
                                        <div v-if="!uploadedDocuments.front">
                                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                            </svg>
                                            <label for="identity-front" class="cursor-pointer">
                                                <span class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                                    Cliquez pour uploader
                                                </span>
                                                <span class="text-sm text-gray-500"> ou glissez-déposez</span>
                                            </label>
                                            <p class="text-xs text-gray-500 mt-2">PNG, JPG, PDF jusqu'à 10MB</p>
                                        </div>
                                        <div v-else class="space-y-2">
                                            <CheckCircle class="mx-auto h-8 w-8 text-green-500" />
                                            <p class="text-sm font-medium text-gray-900">{{ uploadedDocuments.front.name }}</p>
                                            <p class="text-xs text-gray-500">{{ Math.round(uploadedDocuments.front.size / 1024) }} KB</p>
                                            <button 
                                                type="button"
                                                @click="removeDocument('front')"
                                                class="text-xs text-red-600 hover:text-red-800 underline"
                                            >
                                                Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Verso -->
                                <div class="space-y-3">
                                    <label class="block text-sm font-medium text-gray-700">Carte d'identité (verso - optionnel)</label>
                                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                                        <input 
                                            type="file" 
                                            id="identity-back" 
                                            class="hidden" 
                                            accept="image/*,.pdf"
                                            @change="handleDocumentUpload($event, 'back')"
                                        />
                                        <div v-if="!uploadedDocuments.back">
                                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                            </svg>
                                            <label for="identity-back" class="cursor-pointer">
                                                <span class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                                    Cliquez pour uploader
                                                </span>
                                                <span class="text-sm text-gray-500"> ou glissez-déposez</span>
                                            </label>
                                            <p class="text-xs text-gray-500 mt-2">PNG, JPG, PDF jusqu'à 10MB</p>
                                        </div>
                                        <div v-else class="space-y-2">
                                            <CheckCircle class="mx-auto h-8 w-8 text-green-500" />
                                            <p class="text-sm font-medium text-gray-900">{{ uploadedDocuments.back.name }}</p>
                                            <p class="text-xs text-gray-500">{{ Math.round(uploadedDocuments.back.size / 1024) }} KB</p>
                                            <button 
                                                type="button"
                                                @click="removeDocument('back')"
                                                class="text-xs text-red-600 hover:text-red-800 underline"
                                            >
                                                Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bouton pour envoyer les documents -->
                            <div class="flex justify-center mb-4" v-if="uploadedDocuments.front">
                                <Button 
                                    @click="uploadDocuments" 
                                    :disabled="uploading"
                                    class="bg-green-600 hover:bg-green-700 disabled:opacity-50"
                                >
                                    <svg v-if="uploading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ uploading ? 'Envoi en cours...' : 'Envoyer les documents' }}
                                </Button>
                            </div>

                            <!-- Informations sur les documents acceptés -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-2">Types de documents acceptés</h4>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li>• Carte d'identité française ou européenne</li>
                                    <li>• Passeport en cours de validité</li>
                                    <li>• Permis de conduire français</li>
                                    <li>• Carte de séjour (pour les non-européens)</li>
                                </ul>
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
                            <strong>Comment fonctionne la configuration ?</strong><br />
                            <strong>Étape 1 :</strong> Configuration du compte avec formulaire sécurisé intégré<br />
                            <strong>Étape 2 :</strong> Informations supplémentaires si demandées par Stripe
                        </div>
                        <div>
                            <strong>Pourquoi utiliser le formulaire interne ?</strong><br />
                            Plus simple, plus rapide et 100% sécurisé. Vos données sont envoyées directement à Stripe.
                        </div>
                        <div>
                            <strong>Quand vais-je recevoir mes paiements ?</strong><br />
                            Automatiquement selon votre configuration une fois le compte activé.
                        </div>
                        <div>
                            <strong>Mes données sont-elles sécurisées ?</strong><br />
                            Oui, toutes vos informations sont protégées par le chiffrement bancaire de Stripe.
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
