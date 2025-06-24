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
                    <a :href="getProfileUrl()" target="_blank" class="hover:text-primary transition-colors">
                        <h3 class="font-medium text-gray-900">{{ conversation.other_user.name }}</h3>
                    </a>
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
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-800 cursor-pointer"
                    :title="userRole === 'parent' ? 'Voir le profil de la babysitter' : 'Voir le profil du parent'"
                >
                    <User class="h-4 w-4" />
                    Profil
                </button>

                <!-- Lien vers annonce -->
                <button
                    @click="openAdUrl()"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-800 cursor-pointer"
                    :title="userRole === 'parent' ? 'Voir votre annonce' : 'Voir l\'annonce du parent'"
                >
                    <FileText class="h-4 w-4" />
                    Annonce
                </button>

                <!-- Statut de réservation -->
                <div v-if="reservation" class="flex items-center gap-2">
                    <div class="flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium" :class="getReservationStatusClass()">
                        <div class="h-2 w-2 rounded-full" :class="getReservationDotClass()"></div>
                        {{ getReservationStatusText() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Barre de statut de réservation -->
        <div v-if="reservation && reservation.status !== 'completed'" class="mt-3 rounded-lg p-3" :class="getReservationBannerClass()">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <component :is="getReservationIcon()" class="h-5 w-5" />
                        <span class="font-medium">{{ getReservationMessage() }}</span>
                    </div>
                    <div v-if="reservation.time_until_service" class="text-sm opacity-75">{{ reservation.time_until_service }} avant le début</div>
                </div>

                <!-- Actions de réservation -->
                <div class="flex items-center gap-2">
                    <button
                        v-if="canCancelReservation"
                        @click="showCancelModal = true"
                        class="rounded border border-red-300 px-3 py-1 text-sm text-red-700 transition-colors hover:bg-red-50 cursor-pointer"
                    >
                        Annuler
                    </button>

                    <button
                        v-if="canStartService"
                        @click="startService"
                        class="rounded bg-green-600 px-3 py-1 text-sm text-white transition-colors hover:bg-green-700 cursor-pointer"
                    >
                        Commencer
                    </button>

                    <button
                        v-if="canCompleteService"
                        @click="completeService"
                        class="rounded bg-blue-600 px-3 py-1 text-sm text-white transition-colors hover:bg-blue-700 cursor-pointer"
                    >
                        Terminer
                    </button>
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

// Computed
const canCancelReservation = computed(() => {
    return props.reservation && props.reservation.can_be_cancelled;
});

const canStartService = computed(() => {
    return props.reservation && props.reservation.status === 'paid' && props.userRole === 'babysitter';
});

const canCompleteService = computed(() => {
    return props.reservation && props.reservation.status === 'active';
});

// Méthodes
function getProfileUrl() {
    if (!props.conversation?.other_user?.id) {
        console.error('❌ Pas d\'other_user.id disponible');
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
        console.error('❌ Pas d\'ad.id disponible');
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
    const firstName = user.firstname ? 
        user.firstname.toLowerCase().replace(/[^a-z0-9]/g, '-') : 'babysitter';
    const lastName = user.lastname ? 
        user.lastname.toLowerCase().replace(/[^a-z0-9]/g, '-') : '';
    
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
    const firstName = user.firstname ? 
        user.firstname.toLowerCase().replace(/[^a-z0-9]/g, '-') : 'parent';
    const lastName = user.lastname ? 
        user.lastname.toLowerCase().replace(/[^a-z0-9]/g, '-') : '';
    
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
    const title = ad.title ? 
        ad.title.toLowerCase().replace(/[^a-z0-9]/g, '-') : 'annonce';

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
            case 'pending_payment':
                return 'En attente de paiement';
            case 'paid':
                return 'Réservation confirmée';
            case 'active':
                return 'Service en cours';
            case 'completed':
                return 'Service terminé';
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

    switch (props.reservation.status) {
        case 'pending_payment':
            return 'bg-yellow-100 text-yellow-800';
        case 'paid':
            return 'bg-blue-100 text-blue-800';
        case 'active':
            return 'bg-green-100 text-green-800';
        case 'completed':
            return 'bg-gray-100 text-gray-800';
        case 'cancelled_by_parent':
        case 'cancelled_by_babysitter':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
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

    switch (props.reservation.status) {
        case 'pending_payment':
            return 'Paiement requis';
        case 'paid':
            return 'Confirmée';
        case 'active':
            return 'En cours';
        case 'completed':
            return 'Terminée';
        case 'cancelled_by_parent':
            return 'Annulée par le parent';
        case 'cancelled_by_babysitter':
            return 'Annulée par la babysitter';
        default:
            return 'Inconnue';
    }
}

function getReservationBannerClass() {
    if (!props.reservation) return '';

    switch (props.reservation.status) {
        case 'pending_payment':
            return 'bg-yellow-50 border border-yellow-200 text-yellow-800';
        case 'paid':
            return 'bg-blue-50 border border-blue-200 text-blue-800';
        case 'active':
            return 'bg-green-50 border border-green-200 text-green-800';
        default:
            return 'bg-gray-50 border border-gray-200 text-gray-800';
    }
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
        case 'pending_payment':
            return props.userRole === 'parent' ? 'Paiement requis pour confirmer la réservation' : 'En attente du paiement du parent';
        case 'paid':
            return 'Réservation confirmée - En attente du début du service';
        case 'active':
            return 'Service de babysitting en cours';
        default:
            return '';
    }
}

async function startService() {
    if (!props.reservation) return;

    try {
        const response = await fetch(route('reservations.start-service', props.reservation.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        });

        const data = await response.json();

        if (data.success) {
            emit('reservation-updated', data.reservation);
        } else {
            alert(data.error || 'Erreur lors du démarrage du service');
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors du démarrage du service');
    }
}

async function completeService() {
    if (!props.reservation) return;

    if (confirm('Êtes-vous sûr de vouloir terminer le service ? Les fonds seront libérés dans 24h.')) {
        try {
            const response = await fetch(route('reservations.complete-service', props.reservation.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            const data = await response.json();

            if (data.success) {
                emit('reservation-updated', data.reservation);
            } else {
                alert(data.error || 'Erreur lors de la finalisation du service');
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur lors de la finalisation du service');
        }
    }
}

function handleCancellationSuccess(reservation) {
    showCancelModal.value = false;
    emit('reservation-updated', reservation);
}

function openProfileUrl() {
    if (!props.conversation?.other_user?.id) {
        console.error('❌ Pas d\'other_user.id disponible');
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
        console.error('❌ Pas d\'ad.id disponible');
        return;
    }
    
    try {
        const slug = createAdSlug(props.conversation.ad);
        const url = route('announcements.show', { slug });
        
        window.open(url, '_blank');
    } catch (error) {
        console.error('❌ Erreur ouverture annonce:', error);
    }
}
</script>
