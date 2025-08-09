<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="mx-4 w-full max-w-md transform rounded-xl bg-white p-6 shadow-2xl transition-all">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900">Refuser cette candidature</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Êtes-vous sûr(e) de vouloir refuser définitivement la candidature de <strong>{{ application?.babysitter?.name }}</strong> ?
                </p>
            </div>

            <div class="mb-6 rounded-lg bg-yellow-50 border border-yellow-200 p-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-yellow-800">Action définitive</p>
                        <p class="mt-1 text-sm text-yellow-700">
                            Cette action ne peut pas être annulée. La conversation sera archivée et la babysitter sera notifiée du refus.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label for="decline-reason" class="block text-sm font-medium text-gray-700 mb-2">
                    Raison du refus (optionnel)
                </label>
                <textarea
                    id="decline-reason"
                    v-model="declineReason"
                    rows="3"
                    class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                    placeholder="Expliquez brièvement pourquoi vous refusez cette candidature..."
                ></textarea>
            </div>

            <div class="flex gap-3">
                <button
                    @click="$emit('close')"
                    :disabled="processing"
                    class="flex-1 rounded-lg border border-gray-300 px-4 py-3 text-gray-700 transition-colors hover:bg-gray-50 disabled:opacity-50"
                >
                    Annuler
                </button>
                <button
                    @click="confirmDecline"
                    :disabled="processing"
                    class="flex-1 rounded-lg bg-red-600 px-4 py-3 text-white transition-colors hover:bg-red-700 disabled:opacity-50 flex items-center justify-center gap-2"
                >
                    <svg v-if="processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ processing ? 'Refus en cours...' : 'Confirmer le refus' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    show: Boolean,
    application: Object,
});

const emit = defineEmits(['close', 'confirmed']);

// Toast
const { showSuccess, showError } = useToast();

const processing = ref(false);
const declineReason = ref('');

const confirmDecline = async () => {
    if (!props.application) return;
    
    processing.value = true;
    
    try {
        router.post(route('applications.decline', props.application.id), {
            reason: declineReason.value || null
        }, {
            onSuccess: (page) => {
                // Toast de succès
                showSuccess('Candidature refusée', 'La candidature a été refusée et la babysitter a été notifiée.');
                emit('confirmed', {
                    application: props.application,
                    reason: declineReason.value
                });
                emit('close');
                declineReason.value = '';
            },
            onError: (errors) => {
                console.error('Erreur lors du refus:', errors);
                // Toast d'erreur
                showError('Erreur', 'Une erreur est survenue lors du refus de la candidature. Veuillez réessayer.');
                // Garder le modal ouvert en cas d'erreur
            },
            onFinish: () => {
                processing.value = false;
            }
        });
    } catch (error) {
        console.error('Erreur lors du refus:', error);
        processing.value = false;
    }
};
</script>