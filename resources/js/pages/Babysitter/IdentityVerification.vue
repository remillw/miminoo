<template>
    <DashboardLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800">Vérification d'identité</h2>
                <Badge :variant="getStatusVariant(verificationStatus.status)" class="ml-2">
                    {{ getStatusLabel(verificationStatus.status) }}
                </Badge>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="space-y-6 p-6">
                        <!-- Alerte de succès/erreur -->
                        <div v-if="page.props.flash?.success" class="rounded-md bg-green-50 p-4">
                            <div class="flex">
                                <CheckCircle class="h-5 w-5 text-green-400" />
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">{{ page.props.flash.success }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="page.props.flash?.error" class="rounded-md bg-red-50 p-4">
                            <div class="flex">
                                <AlertCircle class="h-5 w-5 text-red-400" />
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800">{{ page.props.flash.error }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Introduction -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center">
                                    <Shield class="mr-2 h-5 w-5" />
                                    Vérification d'identité avec Stripe Identity
                                </CardTitle>
                                <CardDescription>
                                    Utilisez le système sécurisé de Stripe pour vérifier votre identité et activer pleinement votre compte Connect.
                                </CardDescription>
                            </CardHeader>
                        </Card>

                        <!-- Statut de vérification actuel -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Statut de la vérification</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <!-- Statut principal -->
                                    <div class="flex items-center space-x-3">
                                        <component
                                            :is="getStatusIcon(verificationStatus.status)"
                                            :class="getStatusIconColor(verificationStatus.status)"
                                            class="h-5 w-5"
                                        />
                                        <div>
                                            <p class="font-medium">{{ getStatusLabel(verificationStatus.status) }}</p>
                                            <p class="text-sm text-gray-600">{{ getStatusDescription(verificationStatus.status) }}</p>
                                        </div>
                                    </div>

                                    <!-- Détails de la méthode de vérification -->
                                    <div v-if="verificationStatus.method" class="text-sm text-gray-600">
                                        <p>
                                            <strong>Méthode:</strong>
                                            {{ verificationStatus.method === 'identity' ? 'Stripe Identity' : 'Stripe Connect' }}
                                        </p>
                                        <p v-if="verificationStatus.verified_at">
                                            <strong>Vérifié le:</strong> {{ formatDate(verificationStatus.verified_at) }}
                                        </p>
                                    </div>

                                    <!-- Session Identity existante -->
                                    <div v-if="identitySession" class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                                        <div class="flex items-center">
                                            <Info class="mr-2 h-4 w-4 text-blue-600" />
                                            <span class="text-sm font-medium text-blue-900">Session de vérification en cours</span>
                                        </div>
                                        <p class="mt-1 text-sm text-blue-800">
                                            Une session de vérification est déjà en cours ({{ identitySession.status }}).
                                        </p>
                                        <div v-if="identitySession.last_error" class="mt-2 text-sm text-red-600">
                                            <strong>Dernière erreur:</strong>
                                            {{ identitySession.last_error.reason || identitySession.last_error.code }}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Actions de vérification -->
                        <Card v-if="verificationStatus.status !== 'verified'">
                            <CardHeader>
                                <CardTitle>Vérifier votre identité</CardTitle>
                                <CardDescription>
                                    Stripe Identity utilise une technologie avancée pour vérifier votre identité de manière sécurisée.
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Avantages de Stripe Identity -->
                                <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                                    <div class="mb-2 flex items-center">
                                        <Shield class="mr-2 h-4 w-4 text-green-600" />
                                        <span class="text-sm font-medium text-green-900">Pourquoi Stripe Identity ?</span>
                                    </div>
                                    <ul class="space-y-1 text-sm text-green-800">
                                        <li class="flex items-center">
                                            <CheckCircle class="mr-2 h-3 w-3" />
                                            Vérification rapide et sécurisée (5-10 minutes)
                                        </li>
                                        <li class="flex items-center">
                                            <CheckCircle class="mr-2 h-3 w-3" />
                                            Chiffrement de niveau bancaire
                                        </li>
                                        <li class="flex items-center">
                                            <CheckCircle class="mr-2 h-3 w-3" />
                                            Conformité RGPD et réglementations européennes
                                        </li>
                                        <li class="flex items-center">
                                            <CheckCircle class="mr-2 h-3 w-3" />
                                            Liaison automatique avec votre compte Connect
                                        </li>
                                    </ul>
                                </div>

                                <!-- Informations sur le processus -->
                                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                                    <div class="mb-2 flex items-center">
                                        <Info class="mr-2 h-4 w-4 text-blue-600" />
                                        <span class="text-sm font-medium text-blue-900">Ce dont vous aurez besoin</span>
                                    </div>
                                    <ul class="space-y-1 text-sm text-blue-800">
                                        <li>• Une pièce d'identité officielle (CNI, passeport, permis de conduire)</li>
                                        <li>• Un appareil avec caméra (ordinateur ou téléphone)</li>
                                        <li>• 5-10 minutes de votre temps</li>
                                    </ul>
                                </div>

                                <!-- Widget de vérification intégré -->
                                <div v-if="showIdentityWidget" class="space-y-4">
                                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                        <div class="mb-4 flex items-center">
                                            <Shield class="mr-2 h-4 w-4 text-blue-600" />
                                            <span class="text-sm font-medium text-gray-900">Vérification d'identité en cours</span>
                                        </div>
                                        <!-- Container pour le widget Stripe Identity -->
                                        <div id="stripe-identity-element" class="min-h-[400px]"></div>
                                    </div>
                                </div>

                                <!-- Boutons de vérification -->
                                <div v-else class="space-y-4">
                                    <!-- Continuer session existante -->
                                    <Button
                                        v-if="identitySession && identitySession.status === 'requires_input'"
                                        @click="continueVerification"
                                        :disabled="isLoading"
                                        class="w-full"
                                    >
                                        <Shield class="mr-2 h-4 w-4" />
                                        <span v-if="isLoading">Chargement...</span>
                                        <span v-else>Continuer la vérification</span>
                                    </Button>

                                    <!-- Nouvelle vérification -->
                                    <Button v-else @click="startVerification" :disabled="isLoading" class="w-full">
                                        <Shield class="mr-2 h-4 w-4" />
                                        <span v-if="isLoading">Création de la session...</span>
                                        <span v-else>Commencer la vérification d'identité</span>
                                    </Button>

                                    <!-- Lien manuel vers Connect si Identity échoue -->
                                    <div v-if="verificationStatus.method === 'connect' && verificationStatus.requires_identity" class="text-center">
                                        <p class="mb-2 text-sm text-gray-600">Problème avec Stripe Identity ?</p>
                                        <Button variant="outline" @click="router.visit('/babysitter/verification-stripe')" class="text-sm">
                                            Utiliser le processus Connect traditionnel
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Vérification complétée -->
                        <Card v-if="verificationStatus.status === 'verified'">
                            <CardHeader>
                                <CardTitle class="flex items-center text-green-700">
                                    <CheckCircle class="mr-2 h-5 w-5" />
                                    Vérification d'identité complète !
                                </CardTitle>
                                <CardDescription>
                                    Votre identité a été vérifiée avec succès. Votre compte Connect est maintenant pleinement activé.
                                </CardDescription>
                            </CardHeader>
                            <CardContent v-if="accountDetails">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="flex items-center space-x-2">
                                        <CheckCircle class="h-4 w-4 text-green-500" />
                                        <span class="text-sm">Paiements activés: {{ accountDetails.charges_enabled ? 'Oui' : 'Non' }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <CheckCircle class="h-4 w-4 text-green-500" />
                                        <span class="text-sm">Virements activés: {{ accountDetails.payouts_enabled ? 'Oui' : 'Non' }}</span>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <Button @click="router.visit('/babysitter/paiements')" class="w-full">
                                        <CreditCard class="mr-2 h-4 w-4" />
                                        Accéder à vos paiements
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Informations sur les données -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center">
                                    <Lock class="mr-2 h-5 w-5" />
                                    Protection de vos données
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-3 text-sm text-gray-600">
                                    <p>
                                        <strong>Confidentialité :</strong> Vos documents d'identité sont traités directement par Stripe et ne sont
                                        jamais stockés sur nos serveurs.
                                    </p>
                                    <p>
                                        <strong>Sécurité :</strong> Stripe utilise un chiffrement de niveau bancaire et est conforme aux normes PCI
                                        DSS les plus strictes.
                                    </p>
                                    <p>
                                        <strong>Usage :</strong> Les données vérifiées sont utilisées uniquement pour satisfaire les exigences
                                        réglementaires et activer votre compte de paiement.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle, Clock, CreditCard, Info, Lock, Shield, UserCheck, XCircle } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

// Déclaration du type Stripe globalement
declare global {
    interface Window {
        Stripe: (key: string) => any;
    }
}

// Props depuis le contrôleur
interface VerificationStatus {
    status: string;
    method?: string;
    verified_at?: string;
    requires_identity?: boolean;
    can_use_identity?: boolean;
    session_id?: string;
    error?: string;
}

interface IdentitySession {
    id: string;
    status: string;
    client_secret: string;
    last_error?: any;
    verified_outputs?: any;
}

interface Props {
    verificationStatus: VerificationStatus;
    accountDetails?: any;
    identitySession?: IdentitySession | null;
    stripePublishableKey: string;
}

const props = defineProps<Props>();
const page = usePage();

// État local
const isLoading = ref(false);
const stripe = ref<any>(null);
const showIdentityWidget = ref(false);
const identityElement = ref<any>(null);

// Initialiser Stripe
onMounted(async () => {
    await loadStripeScript();
    if (window.Stripe && props.stripePublishableKey) {
        stripe.value = window.Stripe(props.stripePublishableKey);
    }
});

// Charger le script Stripe
const loadStripeScript = () => {
    return new Promise<void>((resolve) => {
        if (window.Stripe) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://js.stripe.com/v3/';
        script.onload = () => resolve();
        script.onerror = () => {
            console.error('Erreur lors du chargement du script Stripe');
            resolve();
        };
        document.head.appendChild(script);
    });
};

// Méthodes pour les labels et couleurs de statut
const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        verified: 'Vérifié',
        requires_input: 'Vérification requise',
        requires_action: 'Action requise',
        processing: 'En cours',
        canceled: 'Annulé',
        not_started: 'Non commencé',
        error: 'Erreur',
    };
    return labels[status] || status;
};

const getStatusDescription = (status: string) => {
    const descriptions: Record<string, string> = {
        verified: 'Votre identité a été vérifiée avec succès',
        requires_input: "Des informations d'identité sont nécessaires",
        requires_action: 'Action urgente requise pour la vérification',
        processing: 'Vérification en cours par Stripe',
        canceled: 'Vérification annulée',
        not_started: "Vérification d'identité non commencée",
        error: 'Erreur lors de la vérification',
    };
    return descriptions[status] || 'Statut inconnu';
};

const getStatusVariant = (status: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
        verified: 'default',
        requires_input: 'destructive',
        requires_action: 'destructive',
        processing: 'secondary',
        canceled: 'outline',
        not_started: 'outline',
        error: 'destructive',
    };
    return variants[status] || 'outline';
};

const getStatusIcon = (status: string) => {
    const icons: Record<string, any> = {
        verified: CheckCircle,
        requires_input: AlertCircle,
        requires_action: AlertCircle,
        processing: Clock,
        canceled: XCircle,
        not_started: UserCheck,
        error: XCircle,
    };
    return icons[status] || Info;
};

const getStatusIconColor = (status: string) => {
    const colors: Record<string, string> = {
        verified: 'text-green-500',
        requires_input: 'text-red-500',
        requires_action: 'text-red-500',
        processing: 'text-blue-500',
        canceled: 'text-gray-500',
        not_started: 'text-gray-500',
        error: 'text-red-500',
    };
    return colors[status] || 'text-gray-500';
};

// Commencer une nouvelle vérification
const startVerification = async () => {
    if (isLoading.value || !stripe.value) return;

    isLoading.value = true;

    try {
        // Créer une nouvelle session de vérification
        const response = await fetch('/stripe/identity/create-session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Afficher le widget et initialiser Stripe Identity Elements
            showIdentityWidget.value = true;
            await initializeIdentityElements(data.session.client_secret);
        } else {
            throw new Error(data.error || 'Erreur lors de la création de la session');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors du démarrage de la vérification');
        isLoading.value = false;
    }
};

// Initialiser les Elements Stripe Identity
const initializeIdentityElements = async (clientSecret: string) => {
    try {
        // Attendre que le DOM soit mis à jour
        await new Promise((resolve) => setTimeout(resolve, 100));

        const elements = stripe.value.elements({
            clientSecret: clientSecret,
        });

        // Créer l'élément de vérification d'identité
        identityElement.value = elements.create('verificationSession');

        // Monter l'élément dans le DOM
        identityElement.value.mount('#stripe-identity-element');

        // Écouter les événements
        identityElement.value.on('ready', () => {
            console.log('Stripe Identity Element prêt');
            isLoading.value = false;
        });

        identityElement.value.on('change', (event: any) => {
            console.log('Changement dans Identity Element:', event);
        });

        // Écouter la completion via polling (car les events ne sont pas toujours fiables)
        startPollingVerificationStatus();
    } catch (error) {
        console.error('Erreur initialisation Identity Elements:', error);
        alert("Erreur lors de l'initialisation de la vérification");
        showIdentityWidget.value = false;
        isLoading.value = false;
    }
};

// Polling pour vérifier le statut de la vérification
const startPollingVerificationStatus = () => {
    const pollInterval = setInterval(async () => {
        try {
            const response = await fetch('/api/stripe/identity/status');
            const data = await response.json();

            if (data.success && data.status === 'verified') {
                clearInterval(pollInterval);
                await verifyAndLinkToConnect();
            }
        } catch (error) {
            console.error('Erreur polling statut:', error);
        }
    }, 3000); // Vérifier toutes les 3 secondes

    // Arrêter le polling après 10 minutes
    setTimeout(() => {
        clearInterval(pollInterval);
    }, 600000);
};

// Continuer une vérification existante
const continueVerification = async () => {
    if (!props.identitySession?.client_secret || !stripe.value) return;

    isLoading.value = true;

    try {
        const { error } = await stripe.value.verifyIdentity(props.identitySession.client_secret);

        if (error) {
            console.error('Erreur Stripe Identity:', error);
            alert('Erreur lors de la vérification: ' + error.message);
        } else {
            await verifyAndLinkToConnect();
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la continuation de la vérification');
    } finally {
        isLoading.value = false;
    }
};

// Vérifier et lier au compte Connect
const verifyAndLinkToConnect = async () => {
    try {
        const response = await fetch('/stripe/identity/verify-and-link', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json();

        if (data.success) {
            // Rediriger vers la page de succès
            router.visit('/babysitter/identity-verification/success');
        } else {
            console.warn('Vérification non complétée:', data);
            // Rafraîchir la page pour mettre à jour le statut
            router.reload();
        }
    } catch (error) {
        console.error('Erreur lors de la liaison:', error);
        router.reload();
    }
};

// Formater une date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<style scoped>
/* Styles spécifiques si nécessaire */
</style>
