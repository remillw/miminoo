<template>
    <Dialog :open="isOpen" @update:open="onClose">
        <DialogContent class="max-h-[80vh] max-w-sm overflow-hidden rounded-2xl p-0">
            <!-- En-tête avec photo de profil -->
            <div class="from-secondary bg-gradient-to-br to-white px-6 py-4">
                <div class="mb-3 flex items-center gap-3">
                    <div class="relative">
                        <img
                            :src="props.avatarUrl || '/default-avatar.png'"
                            :alt="'Photo de la famille ' + familyName"
                            class="h-12 w-12 rounded-full border-2 border-white object-cover shadow-sm"
                        />
                        <div
                            class="absolute -right-1 -bottom-1 flex h-5 w-5 items-center justify-center rounded-full border-2 border-white bg-green-100"
                        >
                            <User class="h-2.5 w-2.5 text-green-600" />
                        </div>
                    </div>
                    <div>
                        <DialogTitle class="text-lg font-semibold text-gray-900"> Postuler à cette annonce </DialogTitle>
                        <DialogDescription class="mt-1 flex items-center gap-1 text-sm text-gray-600">
                            <Users class="h-3 w-3" />
                            Famille {{ familyName }}
                        </DialogDescription>
                    </div>
                </div>
            </div>

            <!-- Corps avec scroll -->
            <div class="max-h-[50vh] space-y-6 overflow-y-auto px-6 py-4">
                <!-- Message de succès -->
                <div v-if="success" class="rounded-lg border border-green-200 bg-green-50 p-3">
                    <p class="flex items-center gap-2 text-sm text-green-700">
                        <CheckCircle class="h-4 w-4" />
                        {{ success }}
                    </p>
                </div>

                <!-- Récapitulatif en cards compactes -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-secondary/50 hover:bg-secondary rounded-lg p-3 transition-all">
                        <div class="mb-1 flex items-center gap-1 text-xs text-gray-600">
                            <Calendar class="h-3 w-3" />
                            <span>Date</span>
                        </div>
                        <div class="text-sm font-medium text-gray-900">{{ formattedDate }}</div>
                    </div>
                    <div class="bg-secondary/50 hover:bg-secondary rounded-lg p-3 transition-all">
                        <div class="mb-1 flex items-center gap-1 text-xs text-gray-600">
                            <Clock class="h-3 w-3" />
                            <span>Horaires</span>
                        </div>
                        <div class="text-sm font-medium text-gray-900">{{ hours }}</div>
                    </div>
                    <div class="bg-secondary/50 hover:bg-secondary rounded-lg p-3 transition-all">
                        <div class="mb-1 flex items-center gap-1 text-xs text-gray-600">
                            <MapPin class="h-3 w-3" />
                            <span>Lieu</span>
                        </div>
                        <div class="text-sm font-medium text-gray-900">{{ location }}</div>
                    </div>
                    <div class="bg-secondary/50 hover:bg-secondary rounded-lg p-3 transition-all">
                        <div class="mb-1 flex items-center gap-1 text-xs text-gray-600">
                            <Baby class="h-3 w-3" />
                            <span>Enfants</span>
                        </div>
                        <div class="text-sm font-medium text-gray-900">{{ childrenCount }}</div>
                    </div>
                </div>

                <!-- Informations supplémentaires si disponibles -->
                <div v-if="props.additionalInfo && props.additionalInfo.trim()" class="rounded-lg border border-blue-100/50 bg-blue-50/30 p-4">
                    <div class="mb-2 flex items-center gap-2 text-sm font-medium text-blue-800">
                        <Info class="h-4 w-4" />
                        Informations particulières
                    </div>
                    <p class="text-sm leading-relaxed text-blue-700">{{ props.additionalInfo }}</p>
                </div>

                <!-- Message de présentation compact -->
                <div class="space-y-2">
                    <Label for="message" class="flex items-center gap-2 text-sm font-medium text-gray-700">
                        <MessageSquare class="h-4 w-4" />
                        Votre message pour la famille
                    </Label>
                    <Textarea
                        id="message"
                        v-model="message"
                        placeholder="Présentez-vous et expliquez pourquoi vous êtes la babysitter idéale pour cette famille…"
                        :maxlength="500"
                        rows="3"
                        :disabled="isLoading"
                        class="resize-none rounded-lg border-gray-200 text-sm transition-all focus:border-orange-300 focus:ring-orange-100"
                    />
                    <p class="text-right text-xs text-gray-400">{{ message.length }}/500 caractères</p>
                </div>

                <!-- Tarif horaire compact -->
                <div class="space-y-2">
                    <Label for="rate" class="flex items-center justify-between text-sm font-medium text-gray-700">
                        <span class="flex items-center gap-2">
                            <Euro class="h-4 w-4" />
                            Votre tarif horaire
                        </span>
                        <span class="text-xs font-normal text-gray-500">Demandé : {{ props.requestedRate }}€/h</span>
                    </Label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-sm text-gray-500">€</span>
                        <Input
                            id="rate"
                            v-model.number="rate"
                            type="number"
                            min="0"
                            step="0.5"
                            :disabled="isLoading"
                            class="rounded-lg border-gray-200 pr-10 pl-8 transition-all focus:border-orange-300 focus:ring-orange-100"
                        />
                        <span class="absolute inset-y-0 right-3 flex items-center text-sm text-gray-500">/h</span>
                    </div>
                </div>

                <!-- Message de contre-proposition compact -->
                <div v-if="isCounterProposal" class="rounded-lg border border-blue-100/50 bg-blue-50/70 p-3 transition-all">
                    <p class="flex items-center gap-2 text-sm text-blue-700">
                        <Info class="h-4 w-4" />
                        <span class="flex-1">Votre proposition : {{ rate }}€/h</span>
                        <span class="text-xs text-blue-500">Initial : {{ props.requestedRate }}€/h</span>
                    </p>
                </div>

                <!-- Estimation compacte -->
                <div class="rounded-lg border bg-gradient-to-br from-gray-50 to-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2 text-sm font-medium text-gray-900">
                                <Calculator class="h-4 w-4" />
                                Estimation totale
                            </div>
                            <div class="mt-1 text-xs text-gray-500">{{ effectiveRate }}€/h × {{ duration }}h</div>
                        </div>
                        <div class="text-lg font-semibold text-gray-900">{{ (effectiveRate * duration).toFixed(2) }}€</div>
                    </div>
                </div>
            </div>

            <!-- Pied de pop-up compact -->
            <div class="border-t bg-gradient-to-br from-gray-50 to-white px-6 py-4">
                <!-- Message d'erreur juste au-dessus des boutons -->
                <div v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3">
                    <p class="flex items-start gap-2 text-sm text-red-700">
                        <AlertCircle class="h-4 w-4 mt-0.5 flex-shrink-0" />
                        <span class="leading-relaxed">{{ error }}</span>
                    </p>
                </div>

                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        @click="closeModal"
                        :disabled="isLoading"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg border-gray-200 py-2 text-sm transition-all duration-200 hover:bg-gray-50"
                    >
                        <X class="h-4 w-4" />
                        {{ success ? 'Fermer' : 'Annuler' }}
                    </Button>

                    <Button
                        v-if="!success"
                        :disabled="!canSubmit || isLoading"
                        @click="submit"
                        class="from-primary hover:to-primary flex flex-1 items-center justify-center gap-2 rounded-lg border-0 bg-gradient-to-r to-orange-400 py-2 text-sm text-white transition-all duration-200 hover:from-primary disabled:opacity-50"
                    >
                        <Loader v-if="isLoading" class="h-4 w-4 animate-spin" />
                        <Send v-else class="h-4 w-4" />
                        {{ isLoading ? 'Envoi...' : 'Envoyer ma candidature' }}
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    AlertCircle,
    Baby,
    Calculator,
    Calendar,
    CheckCircle,
    Clock,
    Euro,
    Info,
    Loader,
    MapPin,
    MessageSquare,
    Send,
    User,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import { route } from 'ziggy-js';

interface Props {
    isOpen: boolean;
    onClose: () => void;
    announcementId: number;
    date: string;
    hours: string;
    location: string;
    childrenCount: number;
    avatarUrl?: string;
    familyName: string;
    requestedRate: number;
    additionalInfo?: string | null;
}

const props = defineProps<Props>();
const message = ref('');
const rate = ref(props.requestedRate);
const duration = 4;
const isLoading = ref(false);
const error = ref('');
const success = ref('');

const isCounterProposal = computed(() => rate.value !== props.requestedRate);
const effectiveRate = computed(() => rate.value);

const total = computed(() => effectiveRate.value * duration);
const canSubmit = computed(() => message.value.trim().length > 0 && rate.value > 0);

const formattedDate = computed(() => {
    const d = new Date(props.date);
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
});

const closeModal = () => {
    // Reset complet des données
    message.value = '';
    rate.value = props.requestedRate;
    error.value = '';
    success.value = '';
    isLoading.value = false;

    props.onClose();
};

// Fonction pour réinitialiser quand la modal s'ouvre
const resetModalState = async () => {
    if (props.isOpen) {
        await nextTick();
        message.value = '';
        rate.value = props.requestedRate;
        error.value = '';
        success.value = '';
        isLoading.value = false;
    }
};

// Watch pour s'assurer que la modal est bien réinitialisée à chaque ouverture
watch(() => props.isOpen, resetModalState);

// Fonction pour obtenir un message d'erreur convivial
const getFriendlyErrorMessage = (status: number, serverError?: string) => {
    switch (status) {
        case 400:
            if (serverError?.includes('déjà postulé')) {
                return "Vous avez déjà envoyé une candidature pour cette annonce. Vous pouvez consulter son statut dans votre espace babysitter.";
            }
            if (serverError?.includes('propre annonce')) {
                return "Vous ne pouvez pas postuler à votre propre annonce. Cette annonce vous appartient !";
            }
            if (serverError?.includes('plus disponible')) {
                return "Cette annonce n'est plus disponible. Elle a peut-être été supprimée ou réservée par quelqu'un d'autre.";
            }
            if (serverError?.includes('déjà eu lieu')) {
                return "Cette annonce a déjà eu lieu ou commence très bientôt. Vous ne pouvez plus y postuler.";
            }
            return serverError || "Les données envoyées ne sont pas valides. Veuillez vérifier votre message et votre tarif.";
        
        case 401:
            return "Votre session a expiré. Veuillez vous reconnecter et réessayer.";
        
        case 403:
            if (serverError?.includes('babysitters')) {
                return "Seuls les comptes babysitter peuvent postuler aux annonces. Vérifiez que vous êtes connecté avec le bon compte.";
            }
            if (serverError?.includes('vérifié') || serverError?.includes('vérification')) {
                return "Votre profil babysitter n'est pas encore vérifié. Complétez votre profil et demandez la vérification dans votre espace personnel avant de postuler.";
            }
            return serverError || "Vous n'avez pas l'autorisation d'effectuer cette action.";
        
        case 404:
            return "Cette annonce n'existe plus ou a été supprimée. Retournez à la liste des annonces pour en voir d'autres.";
        
        case 422:
            return "Certaines informations ne sont pas valides :\n• Vérifiez que votre message fait moins de 1000 caractères\n• Vérifiez que votre tarif est entre 0€ et 999€";
        
        case 429:
            return "Trop de tentatives. Attendez quelques minutes avant de réessayer.";
        
        case 500:
            return "Une erreur technique est survenue sur nos serveurs. Notre équipe a été notifiée. Réessayez dans quelques minutes.";
        
        case 503:
            return "Le service est temporairement indisponible pour maintenance. Réessayez dans quelques minutes.";
        
        default:
            return serverError || `Une erreur inattendue est survenue (Code: ${status}). Contactez le support si le problème persiste.`;
    }
};

async function submit() {
    if (!canSubmit.value || isLoading.value) return;

    isLoading.value = true;
    error.value = '';

    try {
        console.log('🚀 Envoi candidature pour annonce:', props.announcementId);

        const response = await fetch(route('announcements.apply', { announcement: props.announcementId }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                motivation_note: message.value.trim(), // Utiliser le bon nom de champ
                proposed_rate: rate.value,
            }),
        });

        console.log('📡 Réponse serveur - Status:', response.status, 'OK:', response.ok);

        // Essayer de parser la réponse JSON
        let data;
        try {
            data = await response.json();
            console.log('📋 Données reçues:', data);
        } catch (parseError) {
            console.error('❌ Erreur parsing JSON:', parseError);
            // Si on ne peut pas parser le JSON, utiliser le status pour deviner l'erreur
            error.value = getFriendlyErrorMessage(response.status);
            return;
        }

        if (response.ok) {
            // Vérifier si la réponse contient une erreur malgré le status 200
            if (data.error) {
                console.error('❌ Erreur dans réponse 200:', data);
                error.value = getFriendlyErrorMessage(response.status, data.error);
            } else {
                console.log('✅ Candidature envoyée avec succès');
                success.value = data.message || 'Candidature envoyée avec succès !';

                // Optionnel: rediriger après un délai
                setTimeout(() => {
                    closeModal();
                }, 2000);
            }
        } else {
            console.error('❌ Erreur serveur:', data);
            
            // Utiliser le message d'erreur convivial
            error.value = getFriendlyErrorMessage(response.status, data.error);
        }
    } catch (err) {
        console.error("❌ Erreur réseau lors de l'envoi de la candidature:", err);

        // Gestion plus précise des erreurs réseau
        if (err instanceof TypeError && err.message.includes('Failed to fetch')) {
            error.value = 'Problème de connexion réseau. Vérifiez votre connexion internet et réessayez.';
        } else if (err instanceof TypeError && err.message.includes('NetworkError')) {
            error.value = 'Erreur de réseau. Vérifiez votre connexion internet ou réessayez plus tard.';
        } else {
            error.value = 'Une erreur de communication est survenue. Vérifiez votre connexion et réessayez.';
        }
    } finally {
        isLoading.value = false;
    }
}
</script>
