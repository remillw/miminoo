<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle, CreditCard, Home } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

interface Props {
    accountStatus: string;
}

const props = defineProps<Props>();

const isCheckingStatus = ref(true);
const finalStatus = ref(props.accountStatus);

const checkFinalStatus = async () => {
    try {
        const response = await fetch('/api/stripe/account-status');
        const data = await response.json();

        if (response.ok) {
            finalStatus.value = data.status;
        }
    } catch (err) {
        console.error('Erreur lors de la vérification du statut:', err);
    } finally {
        isCheckingStatus.value = false;
    }
};

const goToProfile = () => {
    router.visit('/profil');
};

const goToAnnouncements = () => {
    router.visit('/annonces');
};

const goToDashboard = () => {
    router.visit('/tableau-de-bord');
};

onMounted(() => {
    // Attendre un peu puis vérifier le statut final
    setTimeout(() => {
        checkFinalStatus();
    }, 2000);
});
</script>

<template>
    <Head title="Configuration terminée" />

    <div class="flex min-h-screen items-center justify-center bg-gray-50 py-8">
        <div class="mx-auto max-w-md px-4">
            <Card class="text-center">
                <CardHeader>
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                        <CheckCircle class="h-8 w-8 text-green-600" />
                    </div>
                    <CardTitle class="text-2xl">Configuration terminée !</CardTitle>
                    <CardDescription> Merci d'avoir complété la configuration de votre compte de paiement </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Statut en cours de vérification -->
                    <div v-if="isCheckingStatus" class="space-y-4">
                        <div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></div>
                        <p class="text-sm text-gray-600">Vérification du statut de votre compte...</p>
                    </div>

                    <!-- Statut vérifié -->
                    <div v-else class="space-y-4">
                        <!-- Compte actif -->
                        <div v-if="finalStatus === 'active'" class="space-y-4">
                            <div class="rounded-lg bg-green-50 p-4">
                                <h3 class="mb-2 text-lg font-medium text-green-900">🎉 Compte activé !</h3>
                                <p class="text-sm text-green-700">
                                    Votre compte de paiement est maintenant entièrement configuré. Vous pouvez commencer à postuler aux annonces et
                                    recevoir des paiements.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-3">
                                <Button @click="goToAnnouncements" size="lg" class="w-full">
                                    <CreditCard class="mr-2 h-4 w-4" />
                                    Voir les annonces
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Button>

                                <Button @click="goToProfile" variant="outline" class="w-full"> Retour au profil </Button>
                            </div>
                        </div>

                        <!-- Compte en pending -->
                        <div v-else-if="finalStatus === 'pending'" class="space-y-4">
                            <div class="rounded-lg bg-orange-50 p-4">
                                <h3 class="mb-2 text-lg font-medium text-orange-900">⏳ Configuration en cours</h3>
                                <p class="mb-3 text-sm text-orange-700">
                                    Votre compte est en cours de vérification. Cela peut prendre jusqu'à 24-48 heures.
                                </p>
                                <p class="text-sm text-orange-700">Vous recevrez un email dès que votre compte sera activé.</p>
                            </div>

                            <div class="grid grid-cols-1 gap-3">
                                <Button @click="goToDashboard" size="lg" class="w-full">
                                    <Home class="mr-2 h-4 w-4" />
                                    Aller au tableau de bord
                                </Button>

                                <Button @click="goToProfile" variant="outline" class="w-full"> Retour au profil </Button>
                            </div>
                        </div>

                        <!-- Autres statuts -->
                        <div v-else class="space-y-4">
                            <div class="rounded-lg bg-gray-50 p-4">
                                <h3 class="mb-2 text-lg font-medium text-gray-900">Configuration sauvegardée</h3>
                                <p class="text-sm text-gray-700">
                                    Vos informations ont été sauvegardées. Le traitement peut prendre quelques minutes.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-3">
                                <Button @click="router.visit('/stripe/connect')" size="lg" class="w-full"> Vérifier le statut </Button>

                                <Button @click="goToProfile" variant="outline" class="w-full"> Retour au profil </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Informations supplémentaires -->
                    <div class="space-y-2 border-t pt-4 text-xs text-gray-500">
                        <p>🔒 Vos informations sont protégées par le chiffrement bancaire de Stripe</p>
                        <p>📧 Vous recevrez des notifications par email pour toute mise à jour</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
