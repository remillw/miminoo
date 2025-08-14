<template>
    <DashboardLayout :currentMode="currentMode" :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole">
        <!-- Version Desktop (inchangée) -->
        <div class="hidden h-[calc(100vh-200px)] overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm lg:flex">
            <!-- Sidebar conversations/candidatures -->
            <div class="flex w-80 flex-shrink-0 flex-col border-r border-gray-200 bg-white">
                <!-- Header avec recherche -->
                <div class="border-b border-gray-200 p-4">
                    <h1 class="mb-3 text-xl font-semibold text-gray-900">Messagerie</h1>
                    <p class="mb-3 text-sm text-gray-500">
                        {{ currentMode === 'parent' ? 'Gérez vos candidatures et conversations' : 'Vos candidatures et conversations' }}
                    </p>

                    <!-- Barre de recherche -->
                    <div class="relative">
                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                        <input
                            type="text"
                            placeholder="Rechercher..."
                            class="w-full rounded-lg border border-gray-300 py-2 pr-4 pl-10 text-sm focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Liste des conversations/candidatures -->
                <div class="w-full flex-1 overflow-y-auto min-h-0">
                    <div v-for="conversation in conversations" :key="conversation.id" class="border-b border-gray-100 last:border-b-0">
                        <div
                            @click="selectConversation(conversation)"
                            class="flex cursor-pointer items-start gap-3 p-4 transition-all duration-200 hover:bg-gray-50"
                            :class="{ 'bg-secondary border-r-primary border-r-3 shadow-sm': isSelected(conversation) }"
                        >
                            <!-- Avatar avec badge statut -->
                            <div class="relative flex-shrink-0">
                                <img
                                    :src="conversation.other_user.avatar || '/default-avatar.svg'"
                                    :alt="conversation.other_user.name"
                                    class="h-12 w-12 rounded-full object-cover ring-2 ring-gray-100"
                                />
                                <!-- Badge candidature -->
                                <div
                                    v-if="conversation.type === 'application'"
                                    class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold text-white shadow-sm"
                                    :class="getApplicationStatusColor(conversation.application?.status).icon"
                                >
                                    {{ getApplicationBadgeIcon(conversation.application?.status) }}
                                </div>
                                <!-- Badge en ligne -->
                                <div
                                    v-else-if="conversation.other_user.online"
                                    class="absolute -right-1 -bottom-1 h-3 w-3 rounded-full border-2 border-white bg-green-500"
                                ></div>
                            </div>

                            <!-- Contenu -->
                            <div class="min-w-0 flex-1">
                                <div class="mb-1 flex items-start justify-between">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <h4 class="truncate font-semibold text-gray-900">{{ conversation.other_user.name }}</h4>
                                        <!-- Badge candidature -->
                                        <span
                                            v-if="conversation.type === 'application'"
                                            class="flex-shrink-0 rounded-full bg-orange-100 px-2 py-1 text-xs font-medium text-orange-700"
                                        >
                                            {{ conversation.application?.status === 'pending' ? 'Candidature' : 'Négociation' }}
                                        </span>
                                        <!-- Badge statut conversation -->
                                        <span
                                            v-else-if="conversation.status === 'active' && !conversation.reservation?.status?.includes('cancelled')"
                                            class="flex-shrink-0 rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700"
                                        >
                                            Confirmée
                                        </span>
                                        <!-- Badge annulée -->
                                        <span
                                            v-else-if="conversation.status === 'cancelled' || conversation.reservation?.status?.includes('cancelled')"
                                            class="flex-shrink-0 rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700"
                                        >
                                            Annulée
                                        </span>
                                    </div>
                                    <div class="ml-2 flex flex-shrink-0 items-center gap-2">
                                        <!-- Badge non lu -->
                                        <span
                                            v-if="conversation.unread_count > 0"
                                            class="min-w-[20px] rounded-full bg-primary px-2 py-1 text-center text-xs font-medium text-white"
                                        >
                                            {{ conversation.unread_count }}
                                        </span>
                                        <!-- Heure -->
                                        <span class="text-xs font-medium text-gray-500">{{ formatTimeAgo(conversation.last_message_at) }}</span>
                                    </div>
                                </div>

                                <!-- Aperçu du contenu -->
                                <p
                                    class="mb-2 text-sm leading-5 text-gray-600"
                                    style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden"
                                >
                                    {{ conversation.last_message }}
                                </p>

                                <!-- Tarif pour candidatures -->
                                <div v-if="conversation.type === 'application' && conversation.application" class="flex items-center gap-2">
                                    <span class="bg-secondary text-primary rounded px-2 py-1 text-sm font-semibold">
                                        {{ conversation.application.proposed_rate }}€/h
                                    </span>
                                    <span v-if="conversation.application.counter_rate" class="text-xs text-gray-500"> → </span>
                                    <span
                                        v-if="conversation.application.counter_rate"
                                        class="bg-primary-opacity rounded px-2 py-1 text-sm font-semibold text-red-600"
                                    >
                                        {{ conversation.application.counter_rate }}€/h
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- État vide -->
                    <div v-if="conversations.length === 0" class="p-6 text-center text-gray-500">
                        <MessagesSquare class="mx-auto mb-3 h-12 w-12 text-gray-300" />
                        <p class="text-sm">{{ currentMode === 'parent' ? 'Aucune candidature reçue' : 'Aucune candidature envoyée' }}</p>
                    </div>
                </div>
            </div>

            <!-- Zone de chat -->
            <div class="flex flex-1 flex-col">
                <div v-if="selectedConversation" class="flex h-full flex-col">
                    <!-- En-tête de chat avec liens -->
                    <ConversationHeader
                        :conversation="selectedConversation"
                        :user-role="currentMode"
                        :reservation="selectedConversation.reservation"
                        @reservation-updated="handleReservationUpdate"
                    />

                    <!-- Zone de messages avec scroll - hauteur limitée -->
                    <div class="flex min-h-0 flex-1 flex-col overflow-hidden bg-gray-50">
                        <!-- Candidature avec chat intégré (visible pour parents ET babysitters) -->
                        <div v-if="selectedConversation.type === 'application'" class="flex h-full flex-col">
                            <!-- En-tête candidature -->
                            <div class="bg-secondary flex-shrink-0 border-b border-orange-200 p-4">
                                <CandidatureChat
                                    :application="selectedConversation.application"
                                    :user-role="currentMode"
                                    @reserve="reserveApplication"
                                    @decline="archiveConversation"
                                    @counter-offer="submitCounterOffer"
                                    @respond-counter="respondToCounterOffer"
                                    @babysitter-counter="submitBabysitterCounterOffer"
                                />
                            </div>

                            <!-- Messages de chat - zone scrollable -->
                            <div class="min-h-0 flex-1 overflow-y-auto">
                                <ChatMessages :conversation="selectedConversation" :user-role="currentMode" ref="chatMessagesRef" />
                            </div>
                        </div>

                        <!-- Conversation normale (pour les conversations non-application) -->
                        <div v-else class="flex h-full flex-col">
                            <div class="min-h-0 flex-1 overflow-y-auto">
                                <ChatMessages :conversation="selectedConversation" :user-role="currentMode" ref="chatMessagesRef" />
                            </div>
                        </div>
                    </div>

                    <!-- Zone de saisie - TOUJOURS VISIBLE -->
                    <div class="flex-shrink-0 border-t border-gray-200 bg-white p-4">
                        <ChatInput
                            @send="sendMessage"
                            @message-sent="onMessageSent"
                            @message-sent-optimistic="onMessageSentOptimistic"
                            @message-confirmed="onMessageConfirmed"
                            @message-failed="onMessageFailed"
                            @typing="onTyping"
                            :disabled="isChatDisabled"
                            :placeholder="chatPlaceholder"
                            :conversation-id="selectedConversation.id"
                            :current-user-id="$page?.props?.auth?.user?.id"
                            :conversation-status="selectedConversation.status"
                            :is-payment-completed="selectedConversation.status === 'active'"
                            :has-active-reservation="hasActiveReservation"
                        />
                    </div>
                </div>

                <!-- État vide -->
                <div v-else class="flex flex-1 items-center justify-center bg-gray-50">
                    <div class="text-center text-gray-500">
                        <MessageSquare class="mx-auto mb-4 h-16 w-16 text-gray-300" />
                        <p class="mb-2 text-lg font-medium">Sélectionnez une conversation</p>
                        <p class="text-sm">Choisissez une candidature ou conversation pour commencer</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Version Mobile -->
        <div class="flex h-[calc(100vh-140px)] flex-col bg-white pt-4 lg:hidden">
            <!-- Liste des conversations (vue par défaut sur mobile) -->
            <div v-if="!selectedConversation || showConversationsList" class="flex h-full flex-col">
                <!-- Header mobile -->
                <div class="flex-shrink-0 border-b border-gray-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h1 class="text-xl font-semibold text-gray-900">Messages</h1>
                        <button v-if="selectedConversation" @click="showConversationsList = false" class="text-sm font-medium text-blue-600">
                            Retour au chat
                        </button>
                    </div>

                    <!-- Barre de recherche mobile -->
                    <div class="relative">
                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                        <input
                            type="text"
                            placeholder="Rechercher..."
                            class="w-full rounded-lg border border-gray-300 py-2 pr-4 pl-10 text-sm focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Liste des conversations mobile -->
                <div class="flex-1 overflow-y-auto">
                    <div v-for="conversation in conversations" :key="conversation.id" class="border-b border-gray-100 last:border-b-0">
                        <div
                            @click="selectConversationMobile(conversation)"
                            class="flex cursor-pointer items-start gap-3 p-4 transition-all duration-200 active:bg-gray-100"
                        >
                            <!-- Avatar avec badge statut -->
                            <div class="relative flex-shrink-0">
                                <img
                                    :src="conversation.other_user.avatar || '/default-avatar.svg'"
                                    :alt="conversation.other_user.name"
                                    class="h-12 w-12 rounded-full object-cover ring-2 ring-gray-100"
                                />
                                <!-- Badge candidature -->
                                <div
                                    v-if="conversation.type === 'application'"
                                    class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold text-white shadow-sm"
                                    :class="getApplicationStatusColor(conversation.application?.status).icon"
                                >
                                    {{ getApplicationBadgeIcon(conversation.application?.status) }}
                                </div>
                                <!-- Badge en ligne -->
                                <div
                                    v-else-if="conversation.other_user.online"
                                    class="absolute -right-1 -bottom-1 h-3 w-3 rounded-full border-2 border-white bg-green-500"
                                ></div>
                            </div>

                            <!-- Contenu -->
                            <div class="min-w-0 flex-1">
                                <div class="mb-1 flex items-start justify-between">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <h4 class="truncate font-semibold text-gray-900">{{ conversation.other_user.name }}</h4>
                                        <!-- Badge candidature -->
                                        <span
                                            v-if="conversation.type === 'application'"
                                            class="flex-shrink-0 rounded-full bg-orange-100 px-2 py-1 text-xs font-medium text-orange-700"
                                        >
                                            {{ conversation.application?.status === 'pending' ? 'Candidature' : 'Négociation' }}
                                        </span>
                                        <!-- Badge annulée mobile -->
                                        <span
                                            v-else-if="conversation.status === 'cancelled' || conversation.reservation?.status?.includes('cancelled')"
                                            class="flex-shrink-0 rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700"
                                        >
                                            Annulée
                                        </span>
                                    </div>
                                    <div class="ml-2 flex flex-shrink-0 items-center gap-2">
                                        <!-- Badge non lu -->
                                        <span
                                            v-if="conversation.unread_count > 0"
                                            class="min-w-[20px] rounded-full bg-primary  px-2 py-1 text-center text-xs font-medium text-white"
                                        >
                                            {{ conversation.unread_count }}
                                        </span>
                                        <!-- Heure -->
                                        <span class="text-xs font-medium text-gray-500">{{ formatTimeAgo(conversation.last_message_at) }}</span>
                                    </div>
                                </div>

                                <!-- Aperçu du contenu -->
                                <p class="mb-2 line-clamp-2 text-sm leading-5 text-gray-600">
                                    {{ conversation.last_message }}
                                </p>

                                <!-- Tarif pour candidatures -->
                                <div v-if="conversation.type === 'application' && conversation.application" class="flex items-center gap-2">
                                    <span class="bg-secondary text-primary rounded px-2 py-1 text-sm font-semibold">
                                        {{ conversation.application.proposed_rate }}€/h
                                    </span>
                                    <span v-if="conversation.application.counter_rate" class="text-xs text-gray-500"> → </span>
                                    <span
                                        v-if="conversation.application.counter_rate"
                                        class="bg-primary-opacity rounded px-2 py-1 text-sm font-semibold text-red-600"
                                    >
                                        {{ conversation.application.counter_rate }}€/h
                                    </span>
                                </div>
                            </div>

                            <!-- Flèche -->
                            <div class="flex-shrink-0 self-center">
                                <ChevronRight class="h-5 w-5 text-gray-400" />
                            </div>
                        </div>
                    </div>

                    <!-- État vide mobile -->
                    <div v-if="conversations.length === 0" class="p-6 text-center text-gray-500">
                        <MessagesSquare class="mx-auto mb-3 h-12 w-12 text-gray-300" />
                        <p class="text-sm">{{ currentMode === 'parent' ? 'Aucune candidature reçue' : 'Aucune candidature envoyée' }}</p>
                    </div>
                </div>
            </div>

            <!-- Vue de chat mobile -->
            <div v-else class="flex h-full flex-col">
                <!-- Header de chat mobile -->
                <div class="flex-shrink-0 border-b border-gray-200 bg-white p-4">
                    <div class="flex items-center gap-3">
                        <button @click="backToConversationsList" class="-ml-1 p-1 text-gray-600 hover:text-gray-900">
                            <ArrowLeft class="h-6 w-6" />
                        </button>

                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <img
                                :src="selectedConversation.other_user.avatar || '/default-avatar.svg'"
                                :alt="selectedConversation.other_user.name"
                                class="h-10 w-10 rounded-full object-cover ring-2 ring-gray-100"
                            />
                            <div class="min-w-0 flex-1">
                                <h2 class="truncate font-semibold text-gray-900">{{ selectedConversation.other_user.name }}</h2>
                                <p class="text-sm text-gray-500">
                                    {{ selectedConversation.other_user.online ? 'En ligne' : 'Hors ligne' }}
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <button class="p-2 text-gray-600 hover:text-gray-900">
                            <MoreVertical class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                <!-- Candidature mobile (visible pour parents ET babysitters) -->
                <div v-if="selectedConversation.type === 'application'" class="flex-shrink-0 border-b border-orange-200 bg-orange-50 p-4">
                    <CandidatureChat
                        :application="selectedConversation.application"
                        :user-role="currentMode"
                        :mobile="true"
                        @reserve="reserveApplication"
                        @decline="archiveConversation"
                        @counter-offer="submitCounterOffer"
                        @respond-counter="respondToCounterOffer"
                        @babysitter-counter="submitBabysitterCounterOffer"
                    />
                </div>

                <!-- Messages mobile -->
                <div class="flex-1 overflow-hidden">
                    <ChatMessages :conversation="selectedConversation" :user-role="currentMode" :mobile="true" ref="chatMessagesRef" />
                </div>

                <!-- Zone de saisie mobile -->
                <div class="flex-shrink-0 border-t border-gray-200 bg-white p-4">
                    <ChatInput
                        @send="sendMessage"
                        @message-sent="onMessageSent"
                        @message-sent-optimistic="onMessageSentOptimistic"
                        @message-confirmed="onMessageConfirmed"
                        @message-failed="onMessageFailed"
                        @typing="onTyping"
                        :disabled="isChatDisabled"
                        :placeholder="chatPlaceholder"
                        :conversation-id="selectedConversation.id"
                        :current-user-id="$page?.props?.auth?.user?.id"
                        :mobile="true"
                        :conversation-status="selectedConversation.status"
                        :is-payment-completed="selectedConversation.status === 'active' || selectedConversation.deposit_paid"
                        :has-active-reservation="hasActiveReservation"
                    />
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup lang="ts">
import { useStatusColors } from '@/composables/useStatusColors';
import { useUserMode } from '@/composables/useUserMode';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import type { Application, Conversation, User } from '@/types';
import { router } from '@inertiajs/vue3';
import { ArrowLeft, ChevronRight, MessageSquare, MessagesSquare, MoreVertical, Search } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { route } from 'ziggy-js';
import CandidatureChat from './Components/CandidatureChat.vue';
import ChatInput from './Components/ChatInput.vue';
import ChatMessages from './Components/ChatMessages.vue';
import ConversationHeader from './Components/ConversationHeader.vue';

interface ApplicationConversation extends Conversation {
    type: 'application';
    application: Application;
}

interface RegularConversation extends Conversation {
    type: 'normal';
    application?: never;
}

type ConversationType = ApplicationConversation | RegularConversation;

interface Props {
    conversations: ConversationType[];
    currentMode: 'parent' | 'babysitter';
    requestedMode?: 'parent' | 'babysitter';
    auth: {
        user: User;
    };
    hasParentRole: boolean;
    hasBabysitterRole: boolean;
}

const props = defineProps<Props>();

// Composables
const { getApplicationStatusColor, getStatusText } = useStatusColors();
const { currentMode, initializeMode, setMode } = useUserMode();

// Initialiser le mode IMMÉDIATEMENT (pas dans onMounted)
// Utiliser currentMode du serveur en priorité, puis requestedMode comme fallback
const serverMode = props.currentMode || props.requestedMode;
const initializedMode = initializeMode(props.hasParentRole, props.hasBabysitterRole, serverMode);

console.log('🚀 Mode initialisé:', {
    initializedMode,
    serverMode,
    requestedMode: props.requestedMode,
    currentModeProp: props.currentMode,
    localStorage: localStorage.getItem('babysitter_user_mode'),
    hasParentRole: props.hasParentRole,
    hasBabysitterRole: props.hasBabysitterRole,
});

// Initialiser le mode au montage du composant
onMounted(async () => {
    // Vérifier si on doit recharger avec le bon mode
    const serverMode = props.currentMode || props.requestedMode;
    const clientMode = currentMode.value;

    console.log('🔍 Vérification au montage:', { serverMode, clientMode });

    if (serverMode && clientMode && serverMode !== clientMode) {
        console.log('🔄 Rechargement nécessaire avec le mode client:', clientMode);
        loadConversationsForMode(clientMode);
    }

    // Attendre que Echo soit disponible avec un timeout
    let attempts = 0;
    const maxAttempts = 10;

    const checkEcho = () => {
        if (window.Echo) {
            console.log('🔧 ✅ Echo maintenant disponible:', !!window.Echo);
            console.log('🔧 Echo connector:', window.Echo.connector);
            console.log('🔧 Echo options:', window.Echo.options);

            // Pour Reverb, vérifier la connexion différemment

            return;
        }

        attempts++;
        if (attempts < maxAttempts) {
            console.log(`🔧 ⏳ Echo pas encore prêt (tentative ${attempts}/${maxAttempts}), attente...`);
            setTimeout(checkEcho, 500);
        } else {
            console.log('🔧 ❌ Echo toujours pas disponible après 5 secondes');
        }
    };

    checkEcho();
});

// Refs
const selectedConversation = ref(null);
const isLoading = ref(true);
const chatMessagesRef = ref(null);
const showConversationsList = ref(false); // Pour la navigation mobile
const isLoadingConversations = ref(false); // Variable réactive pour l'état de chargement

// Utiliser les conversations des props
const conversations = computed(() => {
    const convs = props.conversations || [];

    // Trier les conversations par date de dernier message (plus récent en premier)
    return [...convs].sort((a, b) => {
        const dateA = new Date(a.last_message_at || a.created_at);
        const dateB = new Date(b.last_message_at || b.created_at);
        return dateB - dateA; // Plus récent en premier
    });
});

// Computed pour vérifier si l'utilisateur a plusieurs rôles
const hasMultipleRoles = computed(() => {
    return props.hasParentRole && props.hasBabysitterRole;
});

// Logique temporelle pour désactiver les actions et la saisie
const missionStarted = computed(() => {
    if (!selectedConversation.value?.ad?.date_start) return false;
    const startDate = new Date(selectedConversation.value.ad.date_start);
    const now = new Date();
    return now >= startDate;
});

const missionEnded = computed(() => {
    if (!selectedConversation.value?.ad?.date_end) return false;
    const endDate = new Date(selectedConversation.value.ad.date_end);
    const now = new Date();
    return now >= endDate;
});

const isChatDisabled = computed(() => {
    const conversation = selectedConversation.value;
    const isGuardeCancelled = conversation?.status === 'cancelled' || 
                             conversation?.reservation?.status?.includes('cancelled');
    
    return missionEnded.value || 
           conversation?.status === 'archived' || 
           isGuardeCancelled;
});

const chatPlaceholder = computed(() => {
    const conversation = selectedConversation.value;
    const isGuardeCancelled = conversation?.status === 'cancelled' || 
                             conversation?.reservation?.status?.includes('cancelled');
    
    if (isGuardeCancelled) {
        return 'Cette garde a été annulée. Vous ne pouvez plus envoyer de messages.';
    }
    if (missionEnded.value) {
        return 'La mission est terminée. Vous ne pouvez plus envoyer de messages.';
    }
    if (conversation?.status === 'archived') {
        return 'Cette conversation est archivée';
    }
    return 'Écrivez votre message...';
});

// Détermine si une réservation est active (paiement effectué et service pas encore terminé)
const hasActiveReservation = computed(() => {
    if (!selectedConversation.value) return false;
    
    // Vérifier si la conversation a une réservation payée
    const conversation = selectedConversation.value;
    const hasValidReservation = conversation.status === 'active' || 
                               conversation.deposit_paid || 
                               (conversation.reservation && 
                                ['paid', 'active', 'service_completed'].includes(conversation.reservation.status));
    
    return hasValidReservation && !missionEnded.value;
});

// Fonction pour changer de mode
const switchMode = (mode) => {
    if (mode === currentMode.value || isLoadingConversations.value) {
        console.log('⏹️ Switch ignoré:', {
            mode,
            currentMode: currentMode.value,
            isLoading: isLoadingConversations.value,
        });
        return;
    }

    console.log('🔄 Switch mode vers:', mode, 'depuis:', currentMode.value);

    // Mettre à jour le localStorage ET la valeur réactive
    setMode(mode);

    console.log('✅ Mode mis à jour vers:', currentMode.value);

    // Utiliser la nouvelle fonction sécurisée
    loadConversationsForMode(mode);
};

// Helpers
function selectConversation(conversation) {
    console.log('🔄 Changement de conversation:', conversation.id, conversation.type);
    selectedConversation.value = conversation;

    // Marquer comme vue automatiquement pour les candidatures (selon le mode actuel)
    if (conversation.type === 'application' && currentMode.value === 'parent' && !conversation.application?.viewed_at) {
        console.log('👁️ Marquage candidature comme vue:', {
            applicationId: conversation.application.id,
            currentMode: currentMode.value,
            hasParentRole: props.hasParentRole,
            viewedAt: conversation.application?.viewed_at,
            conversationType: conversation.type,
        });

        // Vérifier que l'utilisateur a bien le rôle parent
        if (!props.hasParentRole) {
            console.warn("⚠️ Utilisateur n'a pas le rôle parent, marquage annulé");
            return;
        }

        // Vérifier que l'application existe et a un ID valide
        if (!conversation.application?.id) {
            console.warn('⚠️ Application ID manquant, marquage annulé');
            return;
        }

        // Faire la requête seulement si tout est OK
        router.post(
            route('applications.mark-viewed', conversation.application.id),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                only: [], // Ne pas recharger les données de la page
                onSuccess: (page) => {
                    console.log('✅ Candidature marquée comme vue avec succès');
                    // Mettre à jour localement pour éviter les futures tentatives
                    if (conversation.application) {
                        conversation.application.viewed_at = new Date().toISOString();
                    }
                },
                onError: (errors) => {
                    console.error('❌ Erreur marquage comme vue:', errors);

                    // Gestion spécifique des erreurs
                    if (errors.message?.includes('405')) {
                        console.warn('⚠️ Méthode non autorisée - problème de configuration nginx');
                    } else if (errors.message?.includes('403')) {
                        console.warn('⚠️ Accès refusé - cette candidature ne vous appartient pas');
                    } else if (errors.message?.includes('404')) {
                        console.warn('⚠️ Candidature introuvable');
                    } else {
                        console.warn('⚠️ Erreur lors du marquage:', errors);
                    }
                },
                onFinish: () => {
                    console.log('🏁 Requête mark-viewed terminée');
                },
            },
        );
    }

    // Marquer tous les messages de la conversation comme lus si elle a des messages non lus
    if (conversation.unread_count > 0) {
        console.log('📬 Marquage messages comme lus:', {
            conversationId: conversation.id,
            unreadCount: conversation.unread_count,
        });

        router.post(
            route('conversations.mark-all-read', conversation.id),
            { _method: 'PATCH' },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    console.log('✅ Messages marqués comme lus avec succès');
                    
                    // Mettre à jour l'état local - reset du compteur de messages non lus
                    conversation.unread_count = 0;
                    
                    // Mettre à jour aussi dans la liste props si elle existe
                    const conversationInList = props.conversations.find((c) => c.id === conversation.id);
                    if (conversationInList) {
                        conversationInList.unread_count = 0;
                    }
                    
                    // Forcer une actualisation pour mettre à jour le badge de la sidebar
                    router.reload({
                        only: ['unreadMessagesCount'],
                        preserveState: true
                    });
                },
                onError: (errors) => {
                    console.error('❌ Erreur lors du marquage des messages comme lus:', errors);
                },
            },
        );
    }
}

// Fonction spécifique pour mobile
function selectConversationMobile(conversation) {
    selectConversation(conversation);
    showConversationsList.value = false; // Masquer la liste et afficher le chat
}

// Retour à la liste des conversations sur mobile
function backToConversationsList() {
    showConversationsList.value = true;
}

function isSelected(conversation) {
    return selectedConversation.value?.id === conversation.id;
}

function formatTimeAgo(dateString) {
    if (!dateString) return 'Maintenant';

    try {
        const date = new Date(dateString);
        const now = new Date();

        // Vérifier si la date est valide
        if (isNaN(date.getTime())) {
            return 'Maintenant';
        }

        const diffInMinutes = Math.floor((now - date) / (1000 * 60));

        if (diffInMinutes < 1) return "À l'instant";
        if (diffInMinutes < 60) return `${diffInMinutes} min`;
        if (diffInMinutes < 1440) {
            const hours = Math.floor(diffInMinutes / 60);
            return `${hours}h`;
        }
        const days = Math.floor(diffInMinutes / 1440);
        return `${days}j`;
    } catch (error) {
        return 'Maintenant';
    }
}

function getApplicationBadgeIcon(status) {
    switch (status) {
        case 'pending':
            return '⏳';
        case 'counter_offered':
            return '↩';
        case 'accepted':
            return '✓';
        case 'declined':
            return '✗';
        default:
            return '?';
    }
}

function getInputPlaceholder() {
    if (!selectedConversation.value) return 'Écrivez votre message...';

    if (selectedConversation.value.status === 'archived') {
        return 'Cette conversation est archivée';
    }

    return 'Écrivez votre message...';
}

// Actions candidatures
function reserveApplication(applicationId, finalRate = null) {
    console.log('📝 Réservation candidature:', applicationId, finalRate);
    router.post(
        route('applications.reserve', applicationId),
        {
            final_rate: finalRate,
        },
        {
            preserveState: true,
            onSuccess: (page) => {
                console.log('✅ Candidature réservée avec succès');
                // Recharger les conversations
                router.get(route('messaging.index'));
            },
            onError: (errors) => {
                console.error('❌ Erreur réservation candidature:', errors);
            },
        },
    );
}

function archiveConversation(applicationId) {
    console.log('❌ Archivage conversation:', selectedConversation.value?.id);

    if (!selectedConversation.value) {
        console.error('❌ Aucune conversation sélectionnée pour archivage');
        return;
    }

    // Utiliser le nouveau modal d'archivage via l'événement émis par le composant enfant
    // Cette fonction sera remplacée par la gestion du modal dans CandidatureChat.vue
}

function submitCounterOffer(applicationId, counterRate, counterMessage = null) {
    console.log('🔄 Contre-offre parent:', applicationId, counterRate, counterMessage);
    router.post(
        route('applications.counter-offer', applicationId),
        {
            counter_rate: counterRate,
            counter_message: counterMessage,
        },
        {
            preserveState: true,
            onSuccess: (response) => {
                console.log('✅ Contre-offre envoyée avec succès:', response);

                // Récupérer les données de l'application depuis la session flash
                const flashData = response.props?.flash;
                const applicationData = flashData?.application;

                // Mettre à jour la candidature locale avec les données du serveur
                if (selectedConversation.value && selectedConversation.value.application && applicationData) {
                    console.log('🔄 Mise à jour avec données serveur:', applicationData);
                    Object.assign(selectedConversation.value.application, applicationData);
                } else if (selectedConversation.value && selectedConversation.value.application) {
                    // Fallback si pas de données serveur
                    console.log('⚠️ Fallback - pas de données serveur');
                    selectedConversation.value.application.status = 'counter_offered';
                    selectedConversation.value.application.counter_rate = counterRate;
                    selectedConversation.value.application.counter_message = counterMessage;
                }

                // Mettre à jour également dans la liste des conversations
                const convInList = props.conversations.find((c) => c.id === selectedConversation.value?.id);
                if (convInList && convInList.application && applicationData) {
                    Object.assign(convInList.application, applicationData);
                }

                // Afficher un message de succès
                window.toast?.success('Contre-offre envoyée !');
            },
            onError: (errors) => {
                console.error('❌ Erreur contre-offre:', errors);
                window.toast?.error("Erreur lors de l'envoi de la contre-offre");
            },
        },
    );
}

function respondToCounterOffer(applicationId, accept, finalRate = null) {
    console.log('🔄 Réponse contre-offre:', applicationId, accept, finalRate);
    router.post(
        route('applications.respond-counter', applicationId),
        {
            accept: accept,
            final_rate: finalRate,
        },
        {
            preserveState: true,
            onSuccess: (response) => {
                console.log('✅ Réponse contre-offre envoyée avec succès:', response);

                // Récupérer les données de l'application depuis la session flash
                const flashData = response.props?.flash;
                const applicationData = flashData?.application;

                // Mettre à jour la candidature locale avec les données du serveur
                if (selectedConversation.value && selectedConversation.value.application && applicationData) {
                    console.log('🔄 Mise à jour avec données serveur:', applicationData);
                    Object.assign(selectedConversation.value.application, applicationData);

                    if (accept) {
                        window.toast?.success('Contre-offre acceptée !');
                    } else {
                        window.toast?.info('Contre-offre refusée, retour au tarif initial');
                    }
                } else if (selectedConversation.value && selectedConversation.value.application) {
                    // Fallback si pas de données serveur
                    console.log('⚠️ Fallback - pas de données serveur');
                    if (accept) {
                        selectedConversation.value.application.status = 'accepted';
                        selectedConversation.value.application.final_rate = finalRate;
                        window.toast?.success('Contre-offre acceptée !');
                    } else {
                        selectedConversation.value.application.status = 'pending';
                        selectedConversation.value.application.counter_rate = null;
                        selectedConversation.value.application.counter_message = null;
                        window.toast?.info('Contre-offre refusée, retour au tarif initial');
                    }
                }

                // Mettre à jour également dans la liste des conversations
                const convInList = props.conversations.find((c) => c.id === selectedConversation.value?.id);
                if (convInList && convInList.application && applicationData) {
                    Object.assign(convInList.application, applicationData);
                }
            },
            onError: (errors) => {
                console.error('❌ Erreur réponse contre-offre:', errors);
                window.toast?.error('Erreur lors de la réponse à la contre-offre');
            },
        },
    );
}

function submitBabysitterCounterOffer(applicationId, counterRate, counterMessage = null) {
    console.log('🔄 Contre-offre babysitter:', applicationId, counterRate, counterMessage);
    router.post(
        route('applications.babysitter-counter', applicationId),
        {
            counter_rate: counterRate,
            counter_message: counterMessage,
        },
        {
            preserveState: true,
            onSuccess: () => {
                console.log('✅ Contre-offre babysitter envoyée avec succès');
                // Recharger les conversations
                router.get(route('messaging.index'));
            },
            onError: (errors) => {
                console.error('❌ Erreur contre-offre babysitter:', errors);
            },
        },
    );
}

// Actions messages
function sendMessage(message) {
    console.log('📤 Envoi message (deprecated):', message);
    // Cette fonction est dépréciée, on utilise maintenant onMessageSent
}

// 🚀 AFFICHAGE OPTIMISTE - Message affiché immédiatement
function onMessageSentOptimistic(message) {
    console.log('🚀 Affichage optimiste du message:', message);

    // Ajouter immédiatement le message optimiste
    if (chatMessagesRef.value) {
        chatMessagesRef.value.addMessageLocally(message);
    }

    // Mettre à jour la sidebar immédiatement
    if (selectedConversation.value) {
        const newTimestamp = new Date().toISOString();

        selectedConversation.value.last_message = message.message;
        selectedConversation.value.last_message_at = newTimestamp;
        selectedConversation.value.last_message_by = message.sender_id;

        // Mettre à jour aussi la conversation dans la liste des props
        const conversationInList = props.conversations.find((c) => c.id === selectedConversation.value.id);
        if (conversationInList) {
            conversationInList.last_message = message.message;
            conversationInList.last_message_at = newTimestamp;
            conversationInList.last_message_by = message.sender_id;
        }

        // Le tri automatique via le computed se charge de remonter la conversation avec le nouveau timestamp
    }
}

// ✅ CONFIRMATION - Remplacer le message temporaire par le vrai
function onMessageConfirmed({ tempId, realMessage }) {
    console.log('✅ Message confirmé:', { tempId, realMessage });

    if (chatMessagesRef.value) {
        chatMessagesRef.value.confirmMessage(tempId, realMessage);
    }
}

// ❌ ÉCHEC - Marquer le message comme échoué
function onMessageFailed({ tempId, error }) {
    console.error('❌ Message échoué:', { tempId, error });

    if (chatMessagesRef.value) {
        chatMessagesRef.value.markMessageAsFailed(tempId, error);
    }
}

function onMessageSent(message) {
    console.log('⚡ onMessageSent (legacy) appelé avec message:', message);

    // Cette fonction est maintenant utilisée comme fallback
    // La logique principale est dans onMessageSentOptimistic
    if (chatMessagesRef.value) {
        chatMessagesRef.value.addMessageLocally(message);
    }

    // Mettre à jour le dernier message dans la sidebar
    if (selectedConversation.value) {
        const messageTimestamp = message.created_at || new Date().toISOString();

        selectedConversation.value.last_message = message.message;
        selectedConversation.value.last_message_at = messageTimestamp;
        selectedConversation.value.last_message_by = message.sender_id;

        // Mettre à jour aussi la conversation dans la liste des props
        const conversationInList = props.conversations.find((c) => c.id === selectedConversation.value.id);
        if (conversationInList) {
            conversationInList.last_message = message.message;
            conversationInList.last_message_at = messageTimestamp;
            conversationInList.last_message_by = message.sender_id;
        }

        // Le tri automatique via le computed se charge de remonter la conversation
        // (pas besoin de manipulation manuelle du tableau)
    }
}

function onTyping(isTyping) {
    // Envoyer les événements de frappe via WebSocket
    if (chatMessagesRef.value) {
        if (isTyping) {
            chatMessagesRef.value.sendTypingEvent();
        } else {
            chatMessagesRef.value.sendStopTypingEvent();
        }
    }
}

// Gestion des mises à jour de réservation
function handleReservationUpdate(updateData) {
    console.log('🔄 Mise à jour de réservation reçue:', updateData);

    if (selectedConversation.value) {
        // Si c'est une annulation complète d'annonce
        if (updateData.type === 'announcement_cancelled') {
            // Mettre à jour le statut de la conversation et de la réservation
            selectedConversation.value.status = 'cancelled';
            if (selectedConversation.value.reservation) {
                selectedConversation.value.reservation.status = 'cancelled_by_parent';
            }

            // Mettre à jour aussi dans la liste des conversations
            const conversationInList = props.conversations.find((c) => c.id === selectedConversation.value.id);
            if (conversationInList) {
                conversationInList.status = 'cancelled';
                if (conversationInList.reservation) {
                    conversationInList.reservation.status = 'cancelled_by_parent';
                }
            }

            console.log('📢 Annonce annulée - conversation mise à jour');
        } else if (updateData.reservation || updateData.status) {
            // Mise à jour normale de réservation
            const reservationData = updateData.reservation || updateData;

            if (selectedConversation.value.reservation) {
                Object.assign(selectedConversation.value.reservation, reservationData);
            } else {
                selectedConversation.value.reservation = reservationData;
            }

            // Mettre à jour aussi dans la liste des conversations
            const conversationInList = props.conversations.find((c) => c.id === selectedConversation.value.id);
            if (conversationInList) {
                if (conversationInList.reservation) {
                    Object.assign(conversationInList.reservation, reservationData);
                } else {
                    conversationInList.reservation = reservationData;
                }
            }

            console.log('📝 Réservation mise à jour:', reservationData);
        }
    }
}

// Fonction pour recharger les conversations selon le mode
function loadConversationsForMode(mode) {
    if (isLoadingConversations.value) {
        console.log('⏳ Chargement déjà en cours, ignorer');
        return;
    }

    console.log('🔄 Chargement conversations pour mode:', mode);
    console.log('🌐 URL qui sera appelée:', route('messaging.index') + '?mode=' + mode);

    isLoadingConversations.value = true;

    // Réinitialiser la conversation sélectionnée
    selectedConversation.value = null;

    router.get(
        route('messaging.index'),
        { mode: mode },
        {
            preserveState: false,
            preserveScroll: true,
            only: ['conversations', 'currentMode'],
            onSuccess: (page) => {
                console.log('✅ Requête réussie, nouvelles props:', {
                    conversations: page.props.conversations?.length || 0,
                    currentMode: page.props.currentMode,
                    requestedMode: page.props.requestedMode,
                });
            },
            onError: (errors) => {
                console.error('❌ Erreur lors du chargement:', errors);
            },
            onFinish: () => {
                isLoadingConversations.value = false;
                console.log('🏁 Chargement terminé, mode actuel:', currentMode.value);
            },
        },
    );
}

// Le changement de mode se fait uniquement via switchMode() maintenant
// Plus de watcher automatique pour éviter les boucles
</script>
