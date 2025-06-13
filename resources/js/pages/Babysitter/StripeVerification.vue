<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, CheckCircle, Clock, ExternalLink, FileText, Info, RefreshCw, Shield, Upload } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

// Mode babysitter pour le layout
const currentMode = ref<'babysitter' | 'parent'>('babysitter');

interface AccountDetails {
    id: string;
    individual: {
        verification: {
            status: string;
            document: string;
        };
    };
    requirements: {
        currently_due: string[];
        eventually_due: string[];
        past_due: string[];
        pending_verification: string[];
        disabled_reason: string | null;
    };
}

interface Props {
    accountDetails: AccountDetails | null;
    requirements: any;
    stripeAccountId: string;
}

const props = defineProps<Props>();

const isLoading = ref(false);
const isUploading = ref(false);
const isRefreshing = ref(false);
const error = ref('');
const success = ref('');
const documentFront = ref<File | null>(null);
const documentBack = ref<File | null>(null);
const verificationStatus = ref(props.accountDetails?.individual?.verification?.status || 'unverified');
const documentStatus = ref(props.accountDetails?.individual?.verification?.document || 'unverified');

const verificationConfig = computed(() => {
    switch (verificationStatus.value) {
        case 'verified':
            return {
                icon: CheckCircle,
                label: 'Vérifié',
                color: 'bg-green-100 text-green-800',
                description: 'Votre identité a été vérifiée avec succès',
            };
        case 'pending':
            return {
                icon: Clock,
                label: 'En cours de vérification',
                color: 'bg-orange-100 text-orange-800',
                description: 'Nous examinons vos documents',
            };
        case 'requires_action':
            return {
                icon: AlertCircle,
                label: 'Action requise',
                color: 'bg-red-100 text-red-800',
                description: 'Des informations supplémentaires sont nécessaires',
            };
        default:
            return {
                icon: FileText,
                label: 'Vérification requise',
                color: 'bg-blue-100 text-blue-800',
                description: 'Vous devez vérifier votre identité',
            };
    }
});

const documentConfig = computed(() => {
    switch (documentStatus.value) {
        case 'verified':
            return {
                icon: CheckCircle,
                label: 'Document vérifié',
                color: 'bg-green-100 text-green-800',
            };
        case 'pending':
            return {
                icon: Clock,
                label: 'Document en cours de vérification',
                color: 'bg-orange-100 text-orange-800',
            };
        case 'requires_action':
            return {
                icon: AlertCircle,
                label: 'Document refusé',
                color: 'bg-red-100 text-red-800',
            };
        default:
            return {
                icon: Upload,
                label: 'Document requis',
                color: 'bg-blue-100 text-blue-800',
            };
    }
});

const requirementMessages = computed(() => {
    if (!props.requirements) return [];

    const messages = [];
    const reqs = props.requirements;

    if (reqs.currently_due?.length > 0) {
        messages.push({
            type: 'error',
            title: 'Actions requises immédiatement',
            items: reqs.currently_due,
            description: 'Ces informations sont nécessaires pour continuer.',
        });
    }

    if (reqs.past_due?.length > 0) {
        messages.push({
            type: 'error',
            title: 'Actions en retard',
            items: reqs.past_due,
            description: 'Ces informations auraient dû être fournies.',
        });
    }

    if (reqs.pending_verification?.length > 0) {
        messages.push({
            type: 'warning',
            title: 'Vérification en cours',
            items: reqs.pending_verification,
            description: 'Nous vérifions actuellement ces informations.',
        });
    }

    return messages;
});

const formatRequirement = (requirement: string) => {
    const mapping: { [key: string]: string } = {
        'individual.verification.document': "Pièce d'identité",
        'individual.verification.additional_document': 'Document complémentaire',
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

const handleFileSelect = (event: Event, type: 'front' | 'back') => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        if (type === 'front') {
            documentFront.value = file;
        } else {
            documentBack.value = file;
        }
    }
};

const uploadDocuments = async () => {
    if (!documentFront.value) {
        error.value = 'Veuillez sélectionner au moins le recto de votre document.';
        return;
    }

    isUploading.value = true;
    error.value = '';
    success.value = '';

    try {
        const formData = new FormData();
        formData.append('document_type', 'identity_document');
        formData.append('document_front', documentFront.value);

        if (documentBack.value) {
            formData.append('document_back', documentBack.value);
        }

        const response = await fetch('/babysitter/verification-stripe/upload', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: formData,
        });

        const data = await response.json();

        if (response.ok) {
            success.value = data.message;
            documentFront.value = null;
            documentBack.value = null;

            // Recharger le statut après un délai
            setTimeout(() => {
                checkVerificationStatus();
            }, 2000);
        } else {
            throw new Error(data.error || "Erreur lors de l'upload");
        }
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Une erreur est survenue';
    } finally {
        isUploading.value = false;
    }
};

const createVerificationLink = async () => {
    if (isLoading.value) return;

    isLoading.value = true;
    error.value = '';

    try {
        const response = await fetch('/babysitter/verification-stripe/create-link', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json();

        if (response.ok && data.verification_url) {
            window.location.href = data.verification_url;
        } else {
            throw new Error(data.error || 'Erreur lors de la création du lien de vérification');
        }
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Une erreur est survenue';
    } finally {
        isLoading.value = false;
    }
};

const checkVerificationStatus = async () => {
    if (isRefreshing.value) return;

    isRefreshing.value = true;

    try {
        const response = await fetch('/api/stripe/verification-status');
        const data = await response.json();

        if (response.ok) {
            verificationStatus.value = data.verification_status;
            documentStatus.value = data.document_status;
        }
    } catch (err) {
        console.error('Erreur lors de la vérification du statut:', err);
    } finally {
        isRefreshing.value = false;
    }
};

const goBack = () => {
    router.visit('/babysitter/paiements');
};

onMounted(() => {
    // Vérifier le statut toutes les 30 secondes si en cours
    const interval = setInterval(() => {
        if (verificationStatus.value === 'pending') {
            checkVerificationStatus();
        } else {
            clearInterval(interval);
        }
    }, 30000);
});
</script>

<template>
    <DashboardLayout :currentMode="currentMode">
        <Head title="Vérification d'identité Stripe" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <Button variant="ghost" @click="goBack" class="mb-4">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Retour aux paiements
                </Button>

                <h1 class="text-2xl font-bold text-gray-900">Vérification d'identité</h1>
                <p class="text-gray-600">Complétez votre vérification d'identité pour recevoir des paiements</p>
            </div>

            <!-- Statut de vérification -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="flex items-center">
                            <Shield class="mr-2 h-5 w-5" />
                            Statut de vérification
                        </CardTitle>
                        <div class="flex items-center gap-2">
                            <Badge :class="verificationConfig.color">
                                <component :is="verificationConfig.icon" class="mr-1 h-3 w-3" />
                                {{ verificationConfig.label }}
                            </Badge>
                            <Button variant="ghost" size="sm" @click="checkVerificationStatus" :disabled="isRefreshing">
                                <RefreshCw :class="['h-4 w-4', isRefreshing && 'animate-spin']" />
                            </Button>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="mb-4 text-gray-600">{{ verificationConfig.description }}</p>

                    <!-- Statut du document -->
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-900">Document d'identité</h3>
                            <Badge :class="documentConfig.color">
                                <component :is="documentConfig.icon" class="mr-1 h-3 w-3" />
                                {{ documentConfig.label }}
                            </Badge>
                        </div>
                        <p class="text-sm text-gray-600">Carte d'identité, passeport ou permis de conduire requis</p>
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

            <!-- Upload de documents -->
            <Card v-if="verificationStatus !== 'verified'">
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Upload class="mr-2 h-5 w-5" />
                        Upload de documents
                    </CardTitle>
                    <CardDescription> Uploadez votre pièce d'identité directement depuis votre appareil </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Messages -->
                    <div v-if="error" class="rounded-md border border-red-200 bg-red-50 p-4">
                        <div class="flex items-center">
                            <AlertCircle class="mr-2 h-4 w-4 text-red-500" />
                            <p class="text-sm text-red-700">{{ error }}</p>
                        </div>
                    </div>

                    <div v-if="success" class="rounded-md border border-green-200 bg-green-50 p-4">
                        <div class="flex items-center">
                            <CheckCircle class="mr-2 h-4 w-4 text-green-500" />
                            <p class="text-sm text-green-700">{{ success }}</p>
                        </div>
                    </div>

                    <!-- Informations -->
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                        <div class="mb-2 flex items-center">
                            <Info class="mr-2 h-4 w-4 text-blue-600" />
                            <span class="text-sm font-medium text-blue-900">Documents acceptés</span>
                        </div>
                        <ul class="space-y-1 text-sm text-blue-800">
                            <li>• Carte d'identité française (recto/verso)</li>
                            <li>• Passeport français (page avec photo)</li>
                            <li>• Permis de conduire français (recto/verso)</li>
                            <li>• Formats acceptés : JPG, PNG, PDF (max 10 MB)</li>
                        </ul>
                    </div>

                    <!-- Upload des fichiers -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700"> Recto du document * </label>
                            <input
                                type="file"
                                accept=".jpg,.jpeg,.png,.pdf"
                                @change="handleFileSelect($event, 'front')"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <p v-if="documentFront" class="mt-1 text-xs text-green-600">✓ {{ documentFront.name }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700"> Verso du document </label>
                            <input
                                type="file"
                                accept=".jpg,.jpeg,.png,.pdf"
                                @change="handleFileSelect($event, 'back')"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <p v-if="documentBack" class="mt-1 text-xs text-green-600">✓ {{ documentBack.name }}</p>
                        </div>
                    </div>

                    <Button @click="uploadDocuments" :disabled="!documentFront || isUploading" class="w-full">
                        <Upload v-if="!isUploading" class="mr-2 h-4 w-4" />
                        <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                        {{ isUploading ? 'Upload en cours...' : 'Uploader les documents' }}
                    </Button>
                </CardContent>
            </Card>

            <!-- Vérification via Stripe -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <ExternalLink class="mr-2 h-5 w-5" />
                        Vérification via Stripe
                    </CardTitle>
                    <CardDescription> Alternative : utilisez l'interface sécurisée de Stripe pour la vérification </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <h3 class="mb-2 text-sm font-medium text-gray-900">🔐 Processus sécurisé Stripe</h3>
                        <ul class="space-y-1 text-sm text-gray-700">
                            <li>• Interface optimisée pour la vérification d'identité</li>
                            <li>• Reconnaissance automatique des documents</li>
                            <li>• Chiffrement de niveau bancaire</li>
                            <li>• Retour automatique sur notre site</li>
                        </ul>
                    </div>

                    <Button @click="createVerificationLink" :disabled="isLoading" variant="outline" class="w-full">
                        <ExternalLink v-if="!isLoading" class="mr-2 h-4 w-4" />
                        <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-gray-600 border-t-transparent"></div>
                        {{ isLoading ? 'Préparation...' : 'Continuer avec Stripe' }}
                    </Button>
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
                            <strong>Pourquoi cette vérification ?</strong><br />
                            La vérification d'identité est obligatoire pour recevoir des paiements et respecte les réglementations financières.
                        </div>
                        <div>
                            <strong>Combien de temps cela prend-il ?</strong><br />
                            La vérification automatique prend généralement quelques minutes. La vérification manuelle peut prendre 24-48h.
                        </div>
                        <div>
                            <strong>Mes documents sont-ils sécurisés ?</strong><br />
                            Oui, tous vos documents sont chiffrés et stockés de manière sécurisée par Stripe.
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </DashboardLayout>
</template>
