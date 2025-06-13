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
                <!-- Message d'erreur -->
                <div v-if="error" class="bg-primary-opacity rounded-lg border border-red-200 p-3">
                    <p class="flex items-center gap-2 text-sm text-red-700">
                        <AlertCircle class="h-4 w-4" />
                        {{ error }}
                    </p>
                </div>

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
            <div class="flex gap-2 border-t bg-gradient-to-br from-gray-50 to-white px-6 py-4">
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
                    class="from-primary hover:to-primary flex flex-1 items-center justify-center gap-2 rounded-lg border-0 bg-gradient-to-r to-orange-400 py-2 text-sm text-white transition-all duration-200 hover:from-orange-600 disabled:opacity-50"
                >
                    <Loader v-if="isLoading" class="h-4 w-4 animate-spin" />
                    <Send v-else class="h-4 w-4" />
                    {{ isLoading ? 'Envoi...' : 'Envoyer ma candidature' }}
                </Button>
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
                message: message.value.trim(),
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
            // Si on ne peut pas parser le JSON, vérifier le status
            if (response.status === 403) {
                error.value =
                    "Votre compte n'est pas vérifié. Vous devez compléter votre profil et demander la vérification avant de pouvoir postuler aux annonces.";
            } else {
                error.value = 'Une erreur serveur est survenue. Veuillez réessayer.';
            }
            return;
        }

        if (response.ok) {
            console.log('✅ Candidature envoyée avec succès');
            success.value = data.message || 'Candidature envoyée avec succès !';

            // Optionnel: rediriger après un délai
            setTimeout(() => {
                closeModal();
            }, 2000);
        } else {
            console.error('❌ Erreur serveur:', data);

            // Gestion spécifique des erreurs de vérification
            if (response.status === 403 && data.error) {
                error.value = data.error;
            } else {
                error.value = data.error || "Une erreur est survenue lors de l'envoi de votre candidature";
            }
        }
    } catch (err) {
        console.error("❌ Erreur réseau lors de l'envoi de la candidature:", err);

        // Gestion plus précise des erreurs réseau
        if (err instanceof TypeError && err.message.includes('Failed to fetch')) {
            error.value = 'Problème de connexion réseau. Vérifiez votre connexion internet et réessayez.';
        } else {
            error.value = 'Une erreur réseau est survenue. Veuillez réessayer.';
        }
    } finally {
        isLoading.value = false;
    }
}
</script>
