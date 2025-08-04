<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, CheckCircle, ExternalLink, FileText, Info, Shield } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    verificationStatus: String,
    needsVerification: Boolean,
    accountDetails: Object,
});

const page = usePage();

// Récupérer les informations utilisateur depuis les props globales
const user = computed(() => (page.props.auth as any)?.user);
const userRoles = computed(() => user.value?.roles?.map((role: any) => role.name) || []);

const hasParentRole = computed(() => userRoles.value.includes('parent'));
const hasBabysitterRole = computed(() => userRoles.value.includes('babysitter'));

const isLoading = ref(false);
const isRefreshing = ref(false);
const error = ref('');
const verificationStatus = ref(props.verificationStatus || 'not_started');

// Variables pour l'upload de documents
const isDocumentUploadComplete = ref(false);

// Composables
const { showSuccess, showError } = useToast();

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'verified':
            return 'Vérifié';
        case 'requires_input':
            return 'Action requise';
        case 'requires_action':
            return 'Action urgente requise';
        case 'processing':
            return 'En cours de traitement';
        case 'canceled':
            return 'Annulé';
        case 'not_started':
            return 'Non commencé';
        default:
            return 'Statut inconnu';
    }
};

const getStatusDescription = (status: string) => {
    switch (status) {
        case 'verified':
            return 'Votre identité a été vérifiée avec succès. Vous pouvez recevoir des paiements.';
        case 'requires_input':
            return 'Des informations supplémentaires sont nécessaires pour compléter la vérification.';
        case 'requires_action':
            return 'Action urgente requise - des informations sont en retard.';
        case 'processing':
            return 'Vos informations sont en cours de vérification par Stripe.';
        case 'canceled':
            return 'La vérification a été annulée. Vous pouvez recommencer.';
        case 'not_started':
            return "Vous devez commencer le processus de vérification d'identité.";
        default:
            return 'Statut de vérification inconnu.';
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'verified':
            return 'text-green-700 bg-green-50';
        case 'requires_action':
            return 'text-red-700 bg-red-50';
        case 'requires_input':
            return 'text-orange-700 bg-orange-50';
        case 'processing':
            return 'text-blue-700 bg-blue-50';
        default:
            return 'text-gray-700 bg-gray-50';
    }
};

const startVerification = async () => {
    isLoading.value = true;
    error.value = '';

    try {
        // Créer un lien de vérification Stripe Connect
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch(route('babysitter.stripe.verification.link'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Erreur lors de la création du lien de vérification');
        }

        // Rediriger vers Stripe Connect pour la vérification
        window.location.href = data.url;
    } catch (err) {
        error.value = (err as Error).message || 'Une erreur est survenue';
        isLoading.value = false;
    }
};

const checkVerificationStatus = async () => {
    if (isRefreshing.value) return;

    isRefreshing.value = true;

    try {
        const response = await fetch(route('babysitter.stripe.verification.status'));
        const data = await response.json();

        if (response.ok) {
            verificationStatus.value = data.verification_status;

            // Recharger la page si le statut a changé vers vérifié
            if (data.verification_status === 'verified' && props.verificationStatus !== 'verified') {
                router.reload();
            }
        }
    } catch (err) {
        console.error('Erreur lors de la vérification du statut:', err);
    } finally {
        isRefreshing.value = false;
    }
};

// Gestion de l'upload de documents
const handleUploadComplete = (result) => {
    console.log('✅ Upload completed:', result);
    showSuccess("✅ Documents uploadés avec succès !", `${result.uploadedFiles.length} document(s) envoyé(s) directement à Stripe pour vérification.`);
    
    isDocumentUploadComplete.value = true;
    
    // Recharger la page pour mettre à jour le statut
    setTimeout(() => {
        router.reload();
    }, 1000);
};

const handleUploadError = (error) => {
    console.error('❌ Upload error:', error);
    showError("❌ Erreur lors de l'upload", error.message || "Une erreur est survenue lors de l'upload");
};

onMounted(() => {
    // Vérifier le statut toutes les 30 secondes si en cours
    const interval = setInterval(() => {
        if (['processing', 'requires_input'].includes(verificationStatus.value)) {
            checkVerificationStatus();
        } else {
            clearInterval(interval);
        }
    }, 30000);
});
</script>

<template>
    <Head title="Vérification d'identité" />

    <DashboardLayout :current-mode="'babysitter'" :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole">
        <div class="mx-auto max-w-4xl space-y-6 p-6">
            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Vérification d'identité</h1>
                    <p class="mt-1 text-gray-600">Vérifiez votre identité pour recevoir des paiements</p>
                </div>
                <Button variant="ghost" @click="router.visit(route('babysitter.dashboard'))">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Retour
                </Button>
            </div>

            <!-- Messages d'erreur -->
            <div v-if="error" class="rounded-md border border-red-200 bg-red-50 p-4">
                <div class="flex">
                    <AlertCircle class="h-5 w-5 text-red-400" />
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Erreur</h3>
                        <p class="mt-1 text-sm text-red-700">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- Statut de vérification -->
            <Card v-if="verificationStatus === 'verified'">
                <CardHeader>
                    <CardTitle class="flex items-center text-green-700">
                        <CheckCircle class="mr-2 h-5 w-5" />
                        Vérification d'identité complète
                    </CardTitle>
                    <CardDescription> Votre identité a été vérifiée avec succès. Vous pouvez maintenant recevoir des paiements. </CardDescription>
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
                </CardContent>
            </Card>

            <!-- Vérification via Stripe Connect -->
            <Card v-if="verificationStatus !== 'verified'">
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Shield class="mr-2 h-5 w-5" />
                        Vérification d'identité Stripe Connect
                    </CardTitle>
                    <CardDescription> Utilisez le système sécurisé de Stripe Connect pour vérifier votre identité </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Statut actuel -->
                    <div class="flex items-center rounded-lg p-4" :class="getStatusColor(verificationStatus)">
                        <Info class="mr-3 h-5 w-5" />
                        <div>
                            <p class="font-medium">Statut : {{ getStatusLabel(verificationStatus) }}</p>
                            <p class="mt-1 text-sm">
                                {{ getStatusDescription(verificationStatus) }}
                            </p>
                        </div>
                    </div>

                    <!-- Détails du compte si disponibles -->
                    <div v-if="accountDetails && accountDetails.requirements" class="rounded-lg bg-gray-50 p-4">
                        <h4 class="mb-2 font-medium text-gray-900">Informations requises :</h4>
                        <div class="space-y-2 text-sm">
                            <div v-if="accountDetails.requirements.currently_due?.length > 0">
                                <span class="font-medium text-red-700">Actuellement requis :</span>
                                <ul class="mt-1 list-inside list-disc text-red-600">
                                    <li v-for="req in accountDetails.requirements.currently_due" :key="req">
                                        {{ req }}
                                    </li>
                                </ul>
                            </div>
                            <div v-if="accountDetails.requirements.eventually_due?.length > 0">
                                <span class="font-medium text-orange-700">Éventuellement requis :</span>
                                <ul class="text-primary mt-1 list-inside list-disc">
                                    <li v-for="req in accountDetails.requirements.eventually_due" :key="req">
                                        {{ req }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Avantages de Stripe Connect -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="flex items-start space-x-3">
                            <Shield class="mt-0.5 h-5 w-5 text-green-500" />
                            <div>
                                <h4 class="font-medium text-gray-900">Sécurité maximale</h4>
                                <p class="text-sm text-gray-600">Vos documents sont traités de manière sécurisée par Stripe</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <CheckCircle class="mt-0.5 h-5 w-5 text-green-500" />
                            <div>
                                <h4 class="font-medium text-gray-900">Processus guidé</h4>
                                <p class="text-sm text-gray-600">Interface Stripe optimisée pour la vérification</p>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire d'upload direct -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                <svg class="inline-block mr-2 h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                Upload de documents d'identité
                            </h3>
                        </div>
                        
                        <!-- Message de succès -->
                        <div v-if="isDocumentUploadComplete" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center">
                                <CheckCircle class="mr-2 h-5 w-5 text-green-600" />
                                <span class="text-sm font-medium text-green-800">Documents uploadés avec succès !</span>
                            </div>
                            <p class="text-sm text-green-700 mt-1">Vos documents ont été envoyés directement à Stripe pour vérification.</p>
                        </div>
                        
                        <!-- Upload de documents désactivé temporairement -->
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-center">
                            <p class="text-sm text-gray-600">Upload de documents temporairement indisponible</p>
                        </div>
                        
                        <!-- Informations sur les documents -->
                        <div class="bg-gray-50 rounded-lg p-4 mt-6">
                            <h4 class="font-medium text-gray-900 mb-2">Types de documents acceptés</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• <strong>Carte d'identité française ou européenne</strong></li>
                                <li>• <strong>Passeport en cours de validité</strong></li>
                                <li>• <strong>Permis de conduire français</strong></li>
                                <li>• <strong>Carte de séjour</strong> (pour les non-européens)</li>
                            </ul>
                            <div class="mt-3 p-2 bg-blue-50 rounded border-l-4 border-blue-400">
                                <p class="text-xs text-blue-800">
                                    <span class="mr-1">🚀</span> <strong>Nouveau</strong> : Upload direct et sécurisé vers Stripe ! Vos documents ne transitent plus par notre serveur.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Bouton de rafraîchissement du statut -->
                    <div class="flex justify-center">
                        <Button variant="outline" @click="checkVerificationStatus" :disabled="isRefreshing" size="sm">
                            {{ isRefreshing ? 'Vérification...' : 'Actualiser le statut' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Informations supplémentaires -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <FileText class="mr-2 h-5 w-5" />
                        Documents acceptés
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 text-sm md:grid-cols-3">
                        <div class="rounded-lg bg-gray-50 p-3 text-center">
                            <div class="font-medium text-gray-900">Carte d'identité</div>
                            <div class="mt-1 text-gray-600">Française ou européenne</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 text-center">
                            <div class="font-medium text-gray-900">Passeport</div>
                            <div class="mt-1 text-gray-600">En cours de validité</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 text-center">
                            <div class="font-medium text-gray-900">Permis de conduire</div>
                            <div class="mt-1 text-gray-600">Français uniquement</div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </DashboardLayout>
</template>
