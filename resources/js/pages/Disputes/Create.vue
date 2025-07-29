<template>
    <div class="min-h-screen bg-secondary py-8">
        <div class="mx-auto max-w-2xl px-4">
            <!-- En-tête -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-gray-900">Créer une réclamation</h1>
                <p class="mt-2 text-gray-600">Signalez un problème avec votre service de babysitting</p>
            </div>

            <!-- Carte principale -->
            <div class="rounded-lg bg-white p-8 shadow-sm">
                <!-- Informations sur le service -->
                <div class="mb-8 rounded-lg bg-gray-50 p-6">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Service concerné</h2>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <Calendar class="h-5 w-5 text-gray-400" />
                            <span class="text-gray-700">{{ formatDate(reservation.service_start_at) }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <Clock class="h-5 w-5 text-gray-400" />
                            <span class="text-gray-700"
                                >{{ formatTime(reservation.service_start_at) }} - {{ formatTime(reservation.service_end_at) }}</span
                            >
                        </div>
                        <div class="flex items-center gap-3">
                            <FileText class="h-5 w-5 text-gray-400" />
                            <span class="text-gray-700">{{ reservation.ad_title }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-5 w-5 items-center justify-center">
                                <div class="h-2 w-2 rounded-full" :class="getStatusColor(reservation.status)"></div>
                            </div>
                            <span class="text-gray-700">{{ getStatusText('reservation', reservation.status) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Personne concernée -->
                <div class="mb-8 flex items-center gap-4 rounded-lg border border-gray-200 p-4">
                    <img
                        :src="reported_user.avatar || '/default-avatar.svg'"
                        :alt="reported_user.firstname"
                        class="h-16 w-16 rounded-full object-cover"
                    />
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ reported_user.firstname }} {{ reported_user.lastname }}</h3>
                        <p class="text-gray-600">Personne concernée par la réclamation</p>
                    </div>
                </div>

                <!-- Formulaire de réclamation -->
                <form @submit.prevent="submitDispute" class="space-y-6">
                    <!-- Motif -->
                    <div>
                        <label class="mb-3 block text-sm font-medium text-gray-700"> Motif de la réclamation * </label>
                        <div class="space-y-2">
                            <label
                                v-for="(label, value) in reasons"
                                :key="value"
                                class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50"
                                :class="form.reason === value ? 'border-primary bg-primary/5' : ''"
                            >
                                <input type="radio" :value="value" v-model="form.reason" class="text-primary focus:ring-primary" />
                                <span class="text-gray-700">{{ label }}</span>
                            </label>
                        </div>
                        <div v-if="errors.reason" class="mt-1 text-sm text-red-600">
                            {{ errors.reason }}
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="mb-2 block text-sm font-medium text-gray-700"> Description détaillée * </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="6"
                            class="focus:border-primary focus:ring-primary/20 w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:outline-none"
                            placeholder="Décrivez en détail le problème rencontré..."
                            maxlength="2000"
                        ></textarea>
                        <div class="mt-1 flex justify-between text-sm text-gray-500">
                            <span v-if="errors.description" class="text-red-600">{{ errors.description }}</span>
                            <span class="ml-auto">{{ form.description?.length || 0 }}/2000</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">
                            Plus votre description sera précise, plus notre équipe pourra vous aider efficacement.
                        </p>
                    </div>

                    <!-- Informations importantes -->
                    <div class="rounded-lg bg-yellow-50 p-4">
                        <div class="flex items-start gap-3">
                            <AlertTriangle class="mt-0.5 h-5 w-5 text-yellow-600" />
                            <div>
                                <h4 class="font-medium text-yellow-900">Informations importantes</h4>
                                <ul class="mt-2 space-y-1 text-sm text-yellow-800">
                                    <li>• Notre équipe examinera votre réclamation sous 48h</li>
                                    <li>• Les fonds peuvent être suspendus pendant l'enquête</li>
                                    <li>• Vous recevrez une réponse par email</li>
                                    <li>• Les réclamations abusives peuvent entraîner des sanctions</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Boutons -->
                    <div class="flex gap-4 pt-4">
                        <button
                            type="button"
                            @click="$inertia.visit(route('messaging.index'))"
                            class="flex-1 rounded-lg border border-gray-300 px-4 py-3 text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="!form.reason || !form.description || processing"
                            class="flex-1 rounded-lg bg-red-600 px-4 py-3 text-white transition-colors hover:bg-red-700 disabled:opacity-50"
                        >
                            <span v-if="processing">Création...</span>
                            <span v-else>Créer la réclamation</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { AlertTriangle, Calendar, Clock, FileText } from 'lucide-vue-next';
import { reactive, ref } from 'vue';
import { useStatusColors } from '@/composables/useStatusColors';

const props = defineProps({
    reservation: Object,
    reported_user: Object,
    reasons: Object,
    errors: Object,
});

const processing = ref(false);
const { getReservationStatusColor, getStatusText } = useStatusColors();

const form = reactive({
    reason: '',
    description: '',
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatTime = (dateString) => {
    return new Date(dateString).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Fonctions de statut remplacées par le composable useStatusColors
const getStatusColor = (status) => {
    // Utiliser la couleur de background du composable et extraire la couleur
    const config = getReservationStatusColor(status);
    const colorMatch = config.background.match(/bg-(\w+)-/);
    return colorMatch ? `bg-${colorMatch[1]}-500` : 'bg-gray-500';
};

const submitDispute = () => {
    if (!form.reason || !form.description) return;

    processing.value = true;

    router.post(route('disputes.store', props.reservation.id), form, {
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>
