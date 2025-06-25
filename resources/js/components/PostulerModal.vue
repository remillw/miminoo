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

                <!-- Message de présentation compact avec validation -->
                <div class="space-y-2">
                    <Label for="message" class="flex items-center gap-2 text-sm font-medium text-gray-700">
                        <MessageSquare class="h-4 w-4" />
                        Votre message pour la famille
                        <span class="text-red-500">*</span>
                    </Label>
                    <div class="relative">
                    <Textarea
                        id="message"
                        v-model="message"
                        placeholder="Présentez-vous et expliquez pourquoi vous êtes la babysitter idéale pour cette famille…"
                        :maxlength="500"
                        rows="3"
                        :disabled="isLoading"
                            :class="[
                                'resize-none rounded-lg text-sm transition-all focus:ring-orange-100',
                                messageError || fieldErrors.motivation_note 
                                    ? 'border-red-300 focus:border-red-500 focus:ring-red-100' 
                                    : 'border-gray-200 focus:border-orange-300'
                            ]"
                        />
                        <!-- Indicateur d'erreur visuel -->
                        <div v-if="messageError || fieldErrors.motivation_note" 
                             class="absolute right-3 top-3">
                            <AlertCircle class="h-4 w-4 text-red-500" />
                        </div>
                    </div>
                    
                    <!-- Compteur de caractères et erreurs -->
                    <div class="flex items-center justify-between text-xs">
                        <div>
                            <!-- Erreur de validation en temps réel -->
                            <span v-if="messageError" class="text-red-600 flex items-center gap-1">
                                <AlertCircle class="h-3 w-3" />
                                {{ messageError }}
                            </span>
                            <!-- Erreur du backend -->
                            <span v-else-if="fieldErrors.motivation_note" class="text-red-600 flex items-center gap-1">
                                <AlertCircle class="h-3 w-3" />
                                {{ fieldErrors.motivation_note }}
                            </span>
                            <!-- Message d'aide -->
                            <span v-else class="text-gray-500">
                                Minimum 10 caractères pour une présentation efficace
                            </span>
                        </div>
                        <span :class="message.length > 450 ? 'text-orange-600 font-medium' : 'text-gray-400'">
                            {{ message.length }}/500
                        </span>
                    </div>
                </div>

                <!-- Tarif horaire compact avec validation -->
                <div class="space-y-2">
                    <Label for="rate" class="flex items-center justify-between text-sm font-medium text-gray-700">
                        <span class="flex items-center gap-2">
                            <Euro class="h-4 w-4" />
                            Votre tarif horaire
                            <span class="text-red-500">*</span>
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
                            max="999.99"
                            step="0.5"
                            :disabled="isLoading"
                            :class="[
                                'rounded-lg pr-10 pl-8 transition-all focus:ring-orange-100',
                                rateError || fieldErrors.proposed_rate 
                                    ? 'border-red-300 focus:border-red-500 focus:ring-red-100' 
                                    : 'border-gray-200 focus:border-orange-300'
                            ]"
                        />
                        <span class="absolute inset-y-0 right-3 flex items-center text-sm text-gray-500">/h</span>
                        <!-- Indicateur d'erreur visuel -->
                        <div v-if="rateError || fieldErrors.proposed_rate" 
                             class="absolute right-8 top-1/2 -translate-y-1/2">
                            <AlertCircle class="h-4 w-4 text-red-500" />
                        </div>
                    </div>
                    
                    <!-- Erreurs de validation pour le tarif -->
                    <div v-if="rateError || fieldErrors.proposed_rate" class="text-xs text-red-600 flex items-center gap-1">
                        <AlertCircle class="h-3 w-3" />
                        {{ rateError || fieldErrors.proposed_rate }}
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
                                <span v-if="spansNextDay" class="inline-flex items-center gap-1 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700">
                                    🌙 Garde de nuit
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                {{ effectiveRate }}€/h × {{ duration.toFixed(1) }}h
                                <span v-if="spansNextDay" class="text-orange-600">(sur 2 jours)</span>
                            </div>
                        </div>
                        <div class="text-lg font-semibold text-gray-900">{{ (effectiveRate * duration).toFixed(2) }}€</div>
                    </div>
                </div>
            </div>

            <!-- Pied de pop-up compact -->
            <div class="border-t bg-gradient-to-br from-gray-50 to-white px-6 py-4">
                <!-- Messages d'état améliorés -->
                <div v-if="success || error" class="mb-4 space-y-3">
                    <!-- Message de succès -->
                    <div v-if="success" class="rounded-lg border border-green-200 bg-green-50 p-3 animate-in fade-in duration-300">
                        <p class="flex items-center gap-2 text-sm text-green-700">
                            <CheckCircle class="h-4 w-4" />
                            {{ success }}
                        </p>
                    </div>

                    <!-- Message d'erreur général -->
                    <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 p-3 animate-in fade-in duration-300">
                    <p class="flex items-start gap-2 text-sm text-red-700">
                        <AlertCircle class="h-4 w-4 mt-0.5 flex-shrink-0" />
                        <span class="leading-relaxed">{{ error }}</span>
                    </p>
                </div>

                    <!-- Résumé des erreurs de champs si il y en a -->
                    <div v-if="Object.keys(fieldErrors).length > 0" class="rounded-lg border border-orange-200 bg-orange-50 p-3">
                        <p class="flex items-center gap-2 text-sm text-orange-700 font-medium mb-2">
                            <AlertCircle class="h-4 w-4" />
                            Erreurs de validation :
                        </p>
                        <ul class="text-xs text-orange-600 space-y-1 ml-6">
                            <li v-for="(error, field) in fieldErrors" :key="field" class="list-disc">
                                {{ error }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Boutons d'action -->
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
                        :class="[
                            'flex flex-1 items-center justify-center gap-2 rounded-lg border-0 py-2 text-sm text-white transition-all duration-200',
                            canSubmit && !isLoading
                                ? 'from-primary hover:to-primary bg-gradient-to-r to-orange-400 hover:from-primary shadow-lg hover:shadow-xl transform hover:scale-[1.02]'
                                : 'bg-gray-300 cursor-not-allowed'
                        ]"
                    >
                        <Loader v-if="isLoading" class="h-4 w-4 animate-spin" />
                        <Send v-else class="h-4 w-4" />
                        {{ isLoading ? 'Envoi en cours...' : 'Envoyer ma candidature' }}
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
    startTime?: string;
    endTime?: string;
}

const props = defineProps<Props>();
const message = ref('');
const rate = ref(props.requestedRate);

// Calcul dynamique de la durée basé sur les heures de l'annonce
const duration = computed(() => {
    // Si on a les heures de début et fin en props
    if (props.startTime && props.endTime) {
        const [startHour, startMin] = props.startTime.split(':').map(Number);
        const [endHour, endMin] = props.endTime.split(':').map(Number);
        
        const startMinutes = startHour * 60 + startMin;
        let endMinutes = endHour * 60 + endMin;
        
        // Si l'heure de fin est plus petite que l'heure de début, 
        // cela signifie que ça se termine le lendemain
        if (endMinutes <= startMinutes) {
            endMinutes += 24 * 60; // Ajouter 24 heures
        }
        
        const durationInMinutes = endMinutes - startMinutes;
        const durationInHours = durationInMinutes / 60;
        
        return Math.min(24, Math.max(0, durationInHours));
    }
    
    // Fallback: essayer de parser depuis la prop hours (format "14:00 - 18:00")
    if (props.hours && props.hours.includes(' - ')) {
        try {
            const [startTime, endTime] = props.hours.split(' - ');
            const [startHour, startMin] = startTime.split(':').map(Number);
            const [endHour, endMin] = endTime.split(':').map(Number);
            
            const startMinutes = startHour * 60 + startMin;
            let endMinutes = endHour * 60 + endMin;
            
            if (endMinutes <= startMinutes) {
                endMinutes += 24 * 60;
            }
            
            const durationInMinutes = endMinutes - startMinutes;
            const durationInHours = durationInMinutes / 60;
            
            return Math.min(24, Math.max(0, durationInHours));
        } catch (error) {
            console.warn('Erreur lors du parsing des heures:', props.hours);
        }
    }
    
    // Dernier fallback
    return 4;
});

// Vérifier si l'annonce s'étend sur deux jours
const spansNextDay = computed(() => {
    if (props.startTime && props.endTime) {
        const [startHour, startMin] = props.startTime.split(':').map(Number);
        const [endHour, endMin] = props.endTime.split(':').map(Number);
        const startMinutes = startHour * 60 + startMin;
        const endMinutes = endHour * 60 + endMin;
        return endMinutes <= startMinutes;
    }
    
    if (props.hours && props.hours.includes(' - ')) {
        try {
            const [startTime, endTime] = props.hours.split(' - ');
            const [startHour, startMin] = startTime.split(':').map(Number);
            const [endHour, endMin] = endTime.split(':').map(Number);
            const startMinutes = startHour * 60 + startMin;
            const endMinutes = endHour * 60 + endMin;
            return endMinutes <= startMinutes;
        } catch (error) {
            return false;
        }
    }
    
    return false;
});

const isLoading = ref(false);
const error = ref('');
const fieldErrors = ref<Record<string, string>>({});
const success = ref('');

const isCounterProposal = computed(() => rate.value !== props.requestedRate);
const effectiveRate = computed(() => rate.value);

const total = computed(() => effectiveRate.value * duration.value);

// Validation en temps réel
const messageError = computed(() => {
    if (message.value.length > 500) {
        return 'Le message ne peut pas dépasser 500 caractères.';
    }
    if (message.value.trim().length > 0 && message.value.trim().length < 10) {
        return 'Le message doit contenir au moins 10 caractères significatifs.';
    }
    return '';
});

const rateError = computed(() => {
    if (!rate.value || rate.value <= 0) {
        return 'Le tarif doit être supérieur à 0€.';
    }
    if (rate.value > 999.99) {
        return 'Le tarif ne peut pas dépasser 999,99€.';
    }
    return '';
});

// Mise à jour de canSubmit avec validation améliorée
const canSubmit = computed(() => {
    return message.value.trim().length >= 10 && 
           rate.value > 0 && 
           rate.value <= 999.99 && 
           !messageError.value && 
           !rateError.value;
});

const formattedDate = computed(() => {
    const d = new Date(props.date);
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
});

const closeModal = () => {
    // Reset complet des données
    message.value = '';
    rate.value = props.requestedRate;
    clearErrors();
    isLoading.value = false;

    props.onClose();
};

// Fonction pour réinitialiser quand la modal s'ouvre
const resetModalState = async () => {
    if (props.isOpen) {
        await nextTick();
        message.value = '';
        rate.value = props.requestedRate;
        clearErrors();
        isLoading.value = false;
    }
};

// Watch pour s'assurer que la modal est bien réinitialisée à chaque ouverture
watch(() => props.isOpen, resetModalState);

// Fonction pour parser les erreurs de validation du backend
const parseValidationErrors = (data: any) => {
    fieldErrors.value = {};
    
    if (data.errors) {
        // Format Laravel validation errors
        Object.entries(data.errors).forEach(([field, messages]: [string, any]) => {
            const errorArray = Array.isArray(messages) ? messages : [messages];
            fieldErrors.value[field] = errorArray[0];
        });
    } else if (data.error && typeof data.error === 'string') {
        // Erreur générale
        error.value = data.error;
    }
};

// Fonction pour réinitialiser les erreurs
const clearErrors = () => {
    error.value = '';
    fieldErrors.value = {};
    success.value = '';
};

// Fonction pour obtenir un message d'erreur convivial amélioré
const getFriendlyErrorMessage = (status: number, serverError?: string, validationErrors?: any) => {
    // Si on a des erreurs de validation spécifiques, les traiter séparément
    if (validationErrors) {
        parseValidationErrors(validationErrors);
        return 'Veuillez corriger les erreurs dans le formulaire.';
    }

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
            return "Certaines informations ne sont pas valides. Veuillez corriger les champs en erreur.";
        
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
    clearErrors();

    try {
        console.log('🚀 Envoi candidature pour annonce:', props.announcementId);

        // Utiliser Inertia pour une meilleure gestion des sessions Laravel
        const { router } = await import('@inertiajs/vue3');
        
        router.post(route('announcements.apply', { announcement: props.announcementId }), {
            motivation_note: message.value.trim(),
            proposed_rate: rate.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                console.log('✅ Candidature envoyée avec succès');
                
                // Récupérer le message de succès depuis la session flash
                const successData = page.props.flash?.success;
                if (successData && typeof successData === 'object') {
                    success.value = `${successData.title}\n${successData.message}`;
                } else if (typeof successData === 'string') {
                    success.value = successData;
                } else {
                    success.value = 'Candidature envoyée avec succès ! La famille sera notifiée de votre demande.';
                }

                // Fermer automatiquement après 2.5 secondes
                setTimeout(() => {
                    closeModal();
                }, 2500);
            },
            onError: (errors) => {
                console.error('❌ Erreurs de validation reçues:', errors);
                
                if (errors && Object.keys(errors).length > 0) {
                    // Traiter les erreurs de validation
                    parseValidationErrors({ errors });
                    
                    // Message d'erreur principal basé sur le type d'erreur
                    const errorKeys = Object.keys(errors);
                    if (errorKeys.includes('motivation_note')) {
                        error.value = 'Le message de motivation contient des erreurs.';
                    } else if (errorKeys.includes('proposed_rate')) {
                        error.value = 'Le tarif proposé n\'est pas valide.';
                    } else if (errorKeys.some(key => key.includes('auth') || key.includes('session'))) {
                        error.value = 'Votre session a expiré. Veuillez vous reconnecter et réessayer.';
                    } else {
                        error.value = 'Veuillez corriger les erreurs dans le formulaire.';
                    }
                } else {
                    error.value = 'Une erreur est survenue lors de l\'envoi de votre candidature. Veuillez réessayer.';
                }
            },
            onFinish: () => {
                isLoading.value = false;
                console.log('📤 Requête de candidature terminée');
            }
        });

    } catch (err) {
        console.error("❌ Erreur lors de l'envoi de la candidature:", err);
        error.value = 'Une erreur technique est survenue. Veuillez rafraîchir la page et réessayer.';
        isLoading.value = false;
    }
}
</script>
