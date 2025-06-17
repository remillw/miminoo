<template>
    <DashboardLayout :currentMode="currentMode">
        <!-- Version Desktop (inchangée) -->
        <div class="hidden lg:flex h-[calc(100vh-200px)] overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <!-- Sidebar conversations/candidatures -->
            <div class="flex w-80 flex-shrink-0 flex-col border-r border-gray-200 bg-white">
                <!-- Header avec recherche -->
                <div class="border-b border-gray-200 p-4">
                    <h1 class="mb-3 text-xl font-semibold text-gray-900">Messagerie</h1>
                    <p class="mb-3 text-sm text-gray-500">
                        {{ userRole === 'parent' ? 'Gérez vos candidatures et conversations' : 'Vos candidatures et conversations' }}
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
                <div class="w-full flex-1 overflow-y-auto">
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
                                    :class="getApplicationBadgeClass(conversation.application?.status)"
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
                                            v-else-if="conversation.status === 'payment_required'"
                                            class="flex-shrink-0 rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700"
                                        >
                                            Paiement requis
                                        </span>
                                    </div>
                                    <div class="ml-2 flex flex-shrink-0 items-center gap-2">
                                        <!-- Badge non lu -->
                                        <span
                                            v-if="conversation.unread_count > 0"
                                            class="min-w-[20px] rounded-full bg-red-500 px-2 py-1 text-center text-xs font-medium text-white"
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
                                    <span class="bg-secondary rounded px-2 py-1 text-sm font-semibold text-primary">
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
                        <p class="text-sm">{{ userRole === 'parent' ? 'Aucune candidature reçue' : 'Aucune candidature envoyée' }}</p>
                    </div>
                </div>
            </div>

            <!-- Zone de chat -->
            <div class="flex flex-1 flex-col">
                <div v-if="selectedConversation" class="flex h-full flex-col">
                    <!-- En-tête de chat avec liens -->
                    <ConversationHeader
                        :conversation="selectedConversation"
                        :user-role="userRole"
                        :reservation="selectedConversation.reservation"
                        @reservation-updated="handleReservationUpdate"
                    />

                    <!-- Zone de messages avec scroll - hauteur limitée -->
                    <div class="flex min-h-0 flex-1 flex-col overflow-hidden bg-gray-50">
                        <!-- Candidature avec chat intégré -->
                        <div v-if="selectedConversation.type === 'application'" class="flex h-full flex-col">
                            <!-- En-tête candidature -->
                            <div class="bg-secondary flex-shrink-0 border-b border-orange-200 p-4">
                                <CandidatureChat
                                    :application="selectedConversation.application"
                                    :user-role="userRole"
                                    @reserve="reserveApplication"
                                    @decline="archiveConversation"
                                    @counter-offer="submitCounterOffer"
                                    @respond-counter="respondToCounterOffer"
                                    @babysitter-counter="submitBabysitterCounterOffer"
                                />
                            </div>

                            <!-- Messages de chat - zone scrollable -->
                            <div class="min-h-0 flex-1 overflow-y-auto">
                                <ChatMessages :conversation="selectedConversation" :user-role="userRole" ref="chatMessagesRef" />
                            </div>
                        </div>

                        <!-- Conversation normale -->
                        <div v-else class="flex h-full flex-col">
                            <div class="min-h-0 flex-1 overflow-y-auto">
                                <ChatMessages :conversation="selectedConversation" :user-role="userRole" ref="chatMessagesRef" />
                            </div>
                        </div>
                    </div>

                    <!-- Zone de saisie - TOUJOURS VISIBLE -->
                    <div class="flex-shrink-0 border-t border-gray-200 bg-white p-4">
                        <ChatInput
                            @send="sendMessage"
                            @message-sent="onMessageSent"
                            @typing="onTyping"
                            :disabled="selectedConversation.status === 'payment_required' || selectedConversation.status === 'archived'"
                            :placeholder="getInputPlaceholder()"
                            :conversation-id="selectedConversation.id"
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
        <div class="lg:hidden h-[calc(100vh-140px)] flex flex-col bg-white">
            <!-- Liste des conversations (vue par défaut sur mobile) -->
            <div v-if="!selectedConversation || showConversationsList" class="flex flex-col h-full">
                <!-- Header mobile -->
                <div class="flex-shrink-0 bg-white border-b border-gray-200 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h1 class="text-xl font-semibold text-gray-900">Messages</h1>
                        <button
                            v-if="selectedConversation"
                            @click="showConversationsList = false"
                            class="text-blue-600 text-sm font-medium"
                        >
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
                                    :class="getApplicationBadgeClass(conversation.application?.status)"
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
                                    </div>
                                    <div class="ml-2 flex flex-shrink-0 items-center gap-2">
                                        <!-- Badge non lu -->
                                        <span
                                            v-if="conversation.unread_count > 0"
                                            class="min-w-[20px] rounded-full bg-red-500 px-2 py-1 text-center text-xs font-medium text-white"
                                        >
                                            {{ conversation.unread_count }}
                                        </span>
                                        <!-- Heure -->
                                        <span class="text-xs font-medium text-gray-500">{{ formatTimeAgo(conversation.last_message_at) }}</span>
                                    </div>
                                </div>

                                <!-- Aperçu du contenu -->
                                <p class="mb-2 text-sm leading-5 text-gray-600 line-clamp-2">
                                    {{ conversation.last_message }}
                                </p>

                                <!-- Tarif pour candidatures -->
                                <div v-if="conversation.type === 'application' && conversation.application" class="flex items-center gap-2">
                                    <span class="bg-secondary rounded px-2 py-1 text-sm font-semibold text-primary">
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
                        <p class="text-sm">{{ userRole === 'parent' ? 'Aucune candidature reçue' : 'Aucune candidature envoyée' }}</p>
                    </div>
                </div>
            </div>

            <!-- Vue de chat mobile -->
            <div v-else class="flex flex-col h-full">
                <!-- Header de chat mobile -->
                <div class="flex-shrink-0 bg-white border-b border-gray-200 p-4">
                    <div class="flex items-center gap-3">
                        <button
                            @click="backToConversationsList"
                            class="p-1 -ml-1 text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeft class="h-6 w-6" />
                        </button>
                        
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <img
                                :src="selectedConversation.other_user.avatar || '/default-avatar.svg'"
                                :alt="selectedConversation.other_user.name"
                                class="h-10 w-10 rounded-full object-cover ring-2 ring-gray-100"
                            />
                            <div class="min-w-0 flex-1">
                                <h2 class="font-semibold text-gray-900 truncate">{{ selectedConversation.other_user.name }}</h2>
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

                <!-- Candidature mobile (si applicable) -->
                <div v-if="selectedConversation.type === 'application'" class="flex-shrink-0 bg-orange-50 border-b border-orange-200 p-4">
                    <CandidatureChat
                        :application="selectedConversation.application"
                        :user-role="userRole"
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
                    <ChatMessages 
                        :conversation="selectedConversation" 
                        :user-role="userRole" 
                        :mobile="true"
                        ref="chatMessagesRef" 
                    />
                </div>

                <!-- Zone de saisie mobile -->
                <div class="flex-shrink-0 border-t border-gray-200 bg-white p-4">
                    <ChatInput
                        @send="sendMessage"
                        @message-sent="onMessageSent"
                        @typing="onTyping"
                        :disabled="selectedConversation.status === 'payment_required' || selectedConversation.status === 'archived'"
                        :placeholder="getInputPlaceholder()"
                        :conversation-id="selectedConversation.id"
                        :mobile="true"
                    />
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup lang="ts">
import { useUserMode } from '@/composables/useUserMode';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { router } from '@inertiajs/vue3';
import { MessageSquare, MessagesSquare, Search, ChevronRight, ArrowLeft, MoreVertical } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import CandidatureChat from './Components/CandidatureChat.vue';
import ConversationHeader from './Components/ConversationHeader.vue';
import ChatInput from './Components/ChatInput.vue';
import ChatMessages from './Components/ChatMessages.vue';

const props = defineProps({
    conversations: Array,
    userRole: String,
    hasParentRole: Boolean,
    hasBabysitterRole: Boolean,
    requestedMode: String,
});

const { currentMode, initializeMode } = useUserMode();

// Initialiser le mode au montage du composant
onMounted(() => {
    initializeMode(props.hasParentRole, props.hasBabysitterRole, props.requestedMode);

    // Debug Echo
    console.log('🔧 Echo disponible:', !!window.Echo);
    console.log('🔧 Echo connector:', window.Echo?.connector?.name);
    console.log('🔧 Echo state:', window.Echo?.connector?.pusher?.connection?.state);
});

// Refs
const selectedConversation = ref(null);
const isLoading = ref(true);
const chatMessagesRef = ref(null);
const showConversationsList = ref(false); // Pour la navigation mobile

// Utiliser les conversations des props
const conversations = computed(() => props.conversations || []);

// Helpers
function selectConversation(conversation) {
    console.log('🔄 Changement de conversation:', conversation.id, conversation.type);
    selectedConversation.value = conversation;

    // Marquer comme vue automatiquement pour les candidatures
    if (conversation.type === 'application' && props.userRole === 'parent' && !conversation.application?.viewed_at) {
        console.log('👁️ Marquage candidature comme vue:', conversation.application.id);
        router.patch(
            route('applications.mark-viewed', conversation.application.id),
            {},
            {
                preserveState: true,
                preserveScroll: true,
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

function getApplicationBadgeClass(status) {
    switch (status) {
        case 'pending':
            return 'bg-yellow-500';
        case 'counter_offered':
            return 'bg-blue-500';
        case 'accepted':
            return 'bg-green-500';
        case 'declined':
            return 'bg-red-500';
        default:
            return 'bg-gray-500';
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

    if (selectedConversation.value.status === 'payment_required') {
        return 'Effectuez le paiement pour débloquer la conversation';
    }

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

    if (confirm('Êtes-vous sûr de vouloir archiver cette conversation ? Elle ne sera plus visible dans votre messagerie.')) {
        router.patch(
            route('conversations.archive', selectedConversation.value.id),
            {},
            {
                preserveState: true,
                onSuccess: () => {
                    console.log('✅ Conversation archivée avec succès');
                    // Réinitialiser la conversation sélectionnée
                    selectedConversation.value = null;
                    // Recharger les conversations
                    router.get(route('messaging.index'));
                },
                onError: (errors) => {
                    console.error('❌ Erreur archivage conversation:', errors);
                },
            },
        );
    }
}

function submitCounterOffer(applicationId, counterRate, counterMessage = null) {
    console.log('🔄 Contre-offre parent:', applicationId, counterRate, counterMessage);
    router.post(
        route('applications.counter', applicationId),
        {
            counter_rate: counterRate,
            counter_message: counterMessage,
        },
        {
            preserveState: true,
            onSuccess: () => {
                console.log('✅ Contre-offre envoyée avec succès');
                // Recharger les conversations
                router.get(route('messaging.index'));
            },
            onError: (errors) => {
                console.error('❌ Erreur contre-offre:', errors);
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
            onSuccess: () => {
                console.log('✅ Réponse contre-offre envoyée avec succès');
                // Recharger les conversations
                router.get(route('messaging.index'));
            },
            onError: (errors) => {
                console.error('❌ Erreur réponse contre-offre:', errors);
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

function onMessageSent(message) {
    // Ajouter le message localement via une référence au composant ChatMessages
    if (chatMessagesRef.value) {
        chatMessagesRef.value.addMessageLocally(message);
    }

    // Mettre à jour le dernier message dans la sidebar
    if (selectedConversation.value) {
        selectedConversation.value.last_message = message.message;
        selectedConversation.value.last_message_at = message.created_at;
        selectedConversation.value.last_message_by = message.sender_id;

        // Remonter cette conversation en haut de la liste
        const conversations = props.conversations;
        const currentIndex = conversations.findIndex((c) => c.id === selectedConversation.value.id);
        if (currentIndex > 0) {
            // Déplacer la conversation vers le haut
            const currentConv = conversations.splice(currentIndex, 1)[0];
            conversations.unshift(currentConv);
        }
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
function handleReservationUpdate(updatedReservation) {
    console.log('🔄 Mise à jour réservation:', updatedReservation);

    // Mettre à jour la réservation dans la conversation sélectionnée
    if (selectedConversation.value) {
        selectedConversation.value.reservation = updatedReservation;
    }

    // Optionnel : recharger les conversations pour synchroniser
    // router.get(route('messaging.index'), {}, { preserveState: true });
}
</script>
