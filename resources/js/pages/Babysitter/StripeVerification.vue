<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, CheckCircle, ExternalLink, FileText, Info, Shield } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const props = defineProps({
    verificationStatus: String,
    needsVerification: Boolean,
    accountDetails: Object,
});

const isLoading = ref(false);
const isRefreshing = ref(false);
const error = ref('');
const verificationStatus = ref(props.verificationStatus || 'not_started');

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

    <DashboardLayout :current-mode="'babysitter'">
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
                                <ul class="mt-1 list-inside list-disc text-primary">
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

                    <!-- Bouton de vérification -->
                    <div class="flex justify-center pt-4">
                        <Button @click="startVerification" :disabled="isLoading" size="lg" class="px-8">
                            <ExternalLink class="mr-2 h-5 w-5" />
                            {{ isLoading ? 'Redirection...' : 'Commencer la vérification' }}
                        </Button>
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
