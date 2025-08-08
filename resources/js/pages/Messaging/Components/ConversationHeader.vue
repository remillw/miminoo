<template>
    <div class="flex-shrink-0 border-b border-gray-200 bg-white px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a :href="getProfileUrl()" target="_blank">
                    <img
                        :src="conversation.other_user.avatar || '/default-avatar.svg'"
                        :alt="conversation.other_user.name"
                        class="hover:ring-primary h-10 w-10 cursor-pointer rounded-full object-cover transition-all hover:ring-2 hover:ring-offset-2"
                    />
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <a :href="getProfileUrl()" target="_blank" class="hover:text-primary transition-colors">
                            <h3 class="font-medium text-gray-900">{{ conversation.other_user.name }}</h3>
                        </a>
                        <!-- Affichage du tarif négocié -->
                        <span v-if="getCurrentRate()" class="bg-primary rounded-full px-2 py-1 text-xs font-medium text-white">
                            {{ getCurrentRate() }}€/h
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">
                        {{ getConversationStatus() }}
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <!-- Lien vers profil -->
                <button
                    @click="openProfileUrl()"
                    class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-800"
                    :title="userRole === 'parent' ? 'Voir le profil de la babysitter' : 'Voir le profil du parent'"
                >
                    <User class="h-4 w-4" />
                    Profil
                </button>

                <!-- Lien vers annonce -->
                <button
                    @click="openAdUrl()"
                    class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-800"
                    :title="userRole === 'parent' ? 'Voir votre annonce' : 'Voir l\'annonce du parent'"
                >
                    <FileText class="h-4 w-4" />
                    Annonce
                </button>

                <!-- Statut de réservation - seulement si payé -->
                <div v-if="reservation && reservation.status !== 'pending_payment'" class="flex items-center gap-2">
                    <div class="flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium" :class="getReservationStatusClass()">
                        <div class="h-2 w-2 rounded-full" :class="getReservationDotClass()"></div>
                        {{ getReservationStatusText() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Barre de statut de réservation - seulement si payé -->
        <div v-if="reservation && reservation.status !== 'completed' && reservation.status !== 'pending_payment'" class="mt-3 rounded-lg p-3" :class="getReservationBannerClass()">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <component :is="getReservationIcon()" class="h-5 w-5" />
                        <span class="font-medium">{{ getReservationMessage() }}</span>
                    </div>
                    <div v-if="reservation.time_until_service" class="text-sm opacity-75">{{ reservation.time_until_service }} avant le début</div>
                </div>

                <!-- Actions de réservation - seulement si payé -->
                <div class="flex items-center gap-2">
                    <button
                        v-if="canCancelReservation && reservation.status !== 'pending_payment'"
                        @click="showCancelModal = true"
                        class="cursor-pointer rounded border border-red-300 px-3 py-1 text-sm text-red-700 transition-colors hover:bg-red-50"
                    >
                        Annuler
                    </button>

                    <!-- Supprimé les boutons Commencer/Terminer pour la babysitter -->
                </div>
            </div>
        </div>

        <!-- Modal annulation -->
        <CancelReservationModal
            :show="showCancelModal"
            :reservation="reservation"
            :user-role="userRole"
            @close="showCancelModal = false"
            @success="handleCancellationSuccess"
        />
    </div>
</template>

<script setup>
import { useStatusColors } from '@/composables/useStatusColors';
import { useToast } from '@/composables/useToast';
import { router } from '@inertiajs/vue3';
import { Calendar, CheckCircle, Clock, CreditCard, FileText, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import CancelReservationModal from './CancelReservationModal.vue';

const props = defineProps({
    conversation: Object,
    userRole: String,
    reservation: Object,
});

const emit = defineEmits(['reservation-updated']);

// État local
const showCancelModal = ref(false);

// Toast et composables
const { showSuccess, showError } = useToast();
const { getReservationStatusColor, getStatusText } = useStatusColors();

// Computed
const canCancelReservation = computed(() => {
    return props.reservation && props.reservation.can_be_cancelled;
});

// Supprimé: startService et completeService
// Le service démarre et se termine automatiquement selon la logique métier
// La babysitter peut seulement annuler avant le début du service

// Méthodes
function getCurrentRate() {
    // Récupérer le tarif depuis l'application dans la conversation
    const application = props.conversation?.application;
    if (!application) return null;

    // Si il y a une contre-offre acceptée, utiliser celle-ci
    if (application.counter_rate && application.status === 'accepted') {
        return application.counter_rate;
    }

    // Sinon utiliser le tarif proposé initial
    return application.proposed_rate;
}

function getProfileUrl() {
    if (!props.conversation?.other_user?.id) {
        console.error("❌ Pas d'other_user.id disponible");
        return '#';
    }

    try {
        let url;
        if (props.userRole === 'parent') {
            // Lien vers profil babysitter
            const slug = createBabysitterSlug(props.conversation.other_user);
            url = route('babysitter.show', { slug });
        } else {
            // Lien vers profil parent
            const slug = createParentSlug(props.conversation.other_user);
            url = route('parent.show', { slug });
        }

        return url;
    } catch (error) {
        console.error('❌ Erreur ouverture profil:', error);
        return '#';
    }
}

function getAdUrl() {
    if (!props.conversation?.ad?.id) {
        console.error("❌ Pas d'ad.id disponible");
        return '#';
    }

    const slug = createAdSlug(props.conversation.ad);
    return `/annonce/${slug}`;
}

function createBabysitterSlug(user) {
    if (!user || !user.id) {
        console.error('❌ User invalide pour babysitter slug:', user);
        return 'babysitter-inconnu';
    }

    // Reproduire exactement l'algorithme PHP : strtolower(preg_replace('/[^a-z0-9]/i', '-', $user->firstname))
    const firstName = user.firstname ? user.firstname.toLowerCase().replace(/[^a-z0-9]/g, '-') : 'babysitter';
    const lastName = user.lastname ? user.lastname.toLowerCase().replace(/[^a-z0-9]/g, '-') : '';

    // trim($firstName . '-' . $lastName . '-' . $user->id, '-')
    const slug = (firstName + '-' + lastName + '-' + user.id).replace(/^-+|-+$/g, '');
    // preg_replace('/-+/', '-', $slug)
    const finalSlug = slug.replace(/-+/g, '-');

    return finalSlug;
}

function createParentSlug(user) {
    if (!user || !user.id) {
        console.error('❌ User invalide pour parent slug:', user);
        return 'parent-inconnu';
    }

    // Reproduire exactement l'algorithme PHP : strtolower(preg_replace('/[^a-z0-9]/i', '-', $user->firstname))
    const firstName = user.firstname ? user.firstname.toLowerCase().replace(/[^a-z0-9]/g, '-') : 'parent';
    const lastName = user.lastname ? user.lastname.toLowerCase().replace(/[^a-z0-9]/g, '-') : '';

    // trim($firstName . '-' . $lastName . '-' . $user->id, '-')
    const slug = (firstName + '-' + lastName + '-' + user.id).replace(/^-+|-+$/g, '');
    // preg_replace('/-+/', '-', $slug)
    const finalSlug = slug.replace(/-+/g, '-');

    return finalSlug;
}

function createAdSlug(ad) {
    if (!ad || !ad.id) {
        console.error('❌ Ad invalide pour slug:', ad);
        return 'annonce-inconnue';
    }

    // Reproduire exactement l'algorithme PHP
    let date = 'date-inconnue';
    if (ad.date_start) {
        try {
            // PHP: $ad->date_start->format('Y-m-d');
            date = new Date(ad.date_start).toISOString().split('T')[0]; // YYYY-MM-DD
        } catch (e) {
            console.error('❌ Erreur parsing date:', ad.date_start);
        }
    }

    // PHP: strtolower(preg_replace('/[^a-z0-9]/i', '-', $ad->title))
    const title = ad.title ? ad.title.toLowerCase().replace(/[^a-z0-9]/g, '-') : 'annonce';

    // PHP: trim($date . '-' . $title . '-' . $ad->id, '-')
    const slug = (date + '-' + title + '-' + ad.id).replace(/^-+|-+$/g, '');
    // PHP: preg_replace('/-+/', '-', $slug)
    const finalSlug = slug.replace(/-+/g, '-');

    return finalSlug;
}

function getConversationStatus() {
    if (props.conversation.type === 'application') {
        return 'Candidature en cours';
    }

    if (props.reservation) {
        switch (props.reservation.status) {
            case 'paid':
                return 'Réservation confirmée';
            case 'active':
                return 'Service en cours';
            case 'service_completed':
                return 'Service terminé';
            case 'completed':
                return 'Terminée';
            case 'cancelled_by_parent':
            case 'cancelled_by_babysitter':
                return 'Réservation annulée';
            default:
                return 'Conversation active';
        }
    }

    return 'Conversation active';
}

function getReservationStatusClass() {
    if (!props.reservation) return '';
    return getReservationStatusColor(props.reservation.status).badge;
}

function getReservationDotClass() {
    if (!props.reservation) return '';

    switch (props.reservation.status) {
        case 'pending_payment':
            return 'bg-yellow-500';
        case 'paid':
            return 'bg-blue-500';
        case 'active':
            return 'bg-green-500';
        case 'service_completed':
            return 'bg-purple-500';
        case 'completed':
            return 'bg-gray-500';
        case 'cancelled_by_parent':
        case 'cancelled_by_babysitter':
            return 'bg-red-500';
        default:
            return 'bg-gray-500';
    }
}

function getReservationStatusText() {
    if (!props.reservation) return '';
    return getStatusText('reservation', props.reservation.status);
}

function getReservationBannerClass() {
    if (!props.reservation) return '';
    return getReservationStatusColor(props.reservation.status).background;
}

function getReservationIcon() {
    if (!props.reservation) return Clock;

    switch (props.reservation.status) {
        case 'pending_payment':
            return CreditCard;
        case 'paid':
            return Calendar;
        case 'active':
            return CheckCircle;
        default:
            return Clock;
    }
}

function getReservationMessage() {
    if (!props.reservation) return '';

    switch (props.reservation.status) {
        case 'paid':
            return 'Réservation confirmée - En attente du début du service';
        case 'active':
            return 'Service de babysitting en cours';
        default:
            return '';
    }
}

// Supprimé les fonctions startService et completeService
// Le cycle de vie du service est maintenant géré automatiquement

function handleCancellationSuccess(result) {
    showCancelModal.value = false;

    if (result.type === 'announcement_cancelled') {
        // Afficher le toast de succès
        showSuccess('Annonce annulée avec succès', 'Toutes les candidatures ont été annulées et les babysitters notifiées');

        // Annonce complète annulée - mettre à jour le statut local avant de rediriger
        if (props.conversation) {
            props.conversation.status = 'cancelled';
            if (props.conversation.reservation) {
                props.conversation.reservation.status = 'cancelled_by_parent';
            }
        }

        // Émettre l'événement de mise à jour pour la sidebar
        emit('reservation-updated', {
            ...result,
            conversation: props.conversation,
            type: 'announcement_cancelled',
        });

        // Redirection différée pour laisser le temps à la sidebar de se mettre à jour
        setTimeout(() => {
            router.visit(route('parent.announcements-reservations'), {
                preserveState: false,
                onSuccess: () => {
                    // Message de succès sera géré par la page de destination
                },
            });
        }, 500);
    } else {
        // Juste cette réservation annulée
        showSuccess('Réservation annulée avec succès');
        emit('reservation-updated', result.reservation || result);
    }
}

function openProfileUrl() {
    if (!props.conversation?.other_user?.id) {
        console.error("❌ Pas d'other_user.id disponible");
        return;
    }

    try {
        let url;
        if (props.userRole === 'parent') {
            // Lien vers profil babysitter
            const slug = createBabysitterSlug(props.conversation.other_user);
            url = route('babysitter.show', { slug });
        } else {
            // Lien vers profil parent
            const slug = createParentSlug(props.conversation.other_user);
            url = route('parent.show', { slug });
        }

        window.open(url, '_blank');
    } catch (error) {
        console.error('❌ Erreur ouverture profil:', error);
    }
}

function openAdUrl() {
    if (!props.conversation?.ad?.id) {
        console.error("❌ Pas d'ad.id disponible");
        return;
    }

    try {
        const slug = createAdSlug(props.conversation.ad);
        const url = route('announcements.show', { slug }) + '?from=messaging';

        window.open(url, '_blank');
    } catch (error) {
        console.error('❌ Erreur ouverture annonce:', error);
    }
}
</script>
