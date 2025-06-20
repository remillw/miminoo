<template>
    <div class="flex flex-col">
        <!-- Messages - conteneur avec scroll -->
        <div
            class="messages-container scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 overflow-y-auto"
            :class="mobile ? 'p-4' : 'p-6'"
            :style="mobile ? 'max-height: calc(100vh - 300px);' : 'max-height: calc(100vh - 400px);'"
        >
            <div :class="mobile ? 'space-y-4 pb-4' : 'space-y-6 pb-8'">
                <!-- Indicateur de chargement -->
                <div v-if="isLoading" class="py-4 text-center">
                    <div class="mx-auto h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
                    <p class="mt-2 text-sm text-gray-500">Chargement des messages...</p>
                </div>

                <!-- Erreur de chargement -->
                <div v-else-if="error" class="py-4 text-center">
                    <div class="inline-block rounded-lg border border-red-300 bg-red-100 px-4 py-3 text-red-800">
                        <p class="font-medium">Erreur lors du chargement</p>
                        <p class="text-sm">{{ error }}</p>
                        <button @click="loadMessages" class="mt-2 rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700">Réessayer</button>
                    </div>
                </div>

                <!-- Messages -->
                <template v-else>
                    <!-- Message système de création -->
                    <div class="text-center">
                        <p class="mt-1 text-xs text-gray-500">Vous pouvez maintenant discuter</p>
                    </div>

                    <!-- Messages de la conversation (du plus ancien au plus récent) -->
                    <div v-for="message in messages" :key="message.id" class="flex" :class="isMyMessage(message) ? 'justify-end' : 'justify-start'">
                        <!-- Message de l'autre utilisateur -->
                        <div v-if="!isMyMessage(message)" class="flex gap-3" :class="mobile ? 'max-w-[85%]' : 'max-w-[70%]'">
                            <img
                                :src="getMessageSenderAvatar(message)"
                                :alt="getMessageSenderName(message)"
                                :class="mobile ? 'h-8 w-8' : 'h-8 w-8'"
                                class="flex-shrink-0 rounded-full object-cover"
                            />
                            <div class="flex min-w-0 flex-1 flex-col">
                                <div
                                    class="message-bubble rounded-2xl bg-gray-100 text-gray-900 shadow-sm"
                                    :class="mobile ? 'px-3 py-2' : 'px-4 py-3'"
                                >
                                    <p :class="mobile ? 'text-sm' : ''">{{ message.message }}</p>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">{{ formatTime(message.created_at) }}</p>
                            </div>
                        </div>

                        <!-- Message de l'utilisateur actuel -->
                        <div v-else class="flex justify-end" :class="mobile ? 'max-w-[85%]' : 'max-w-[70%]'">
                            <div class="flex flex-col items-end">
                                <div class="message-bubble rounded-2xl bg-blue-600 text-white shadow-sm" :class="mobile ? 'px-3 py-2' : 'px-4 py-3'">
                                    <p :class="mobile ? 'text-sm' : ''">{{ message.message }}</p>
                                </div>
                                <div class="mt-1 flex items-center gap-2">
                                    <p class="text-xs text-gray-500">{{ formatTime(message.created_at) }}</p>
                                    <span v-if="message.read_at" class="text-xs font-medium text-blue-500">lu</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Indicateur de frappe -->
                    <div v-if="isOtherUserTyping" :class="mobile ? 'mb-2 flex max-w-xs gap-2' : 'mb-2 flex max-w-xs gap-3'">
                        <img
                            :src="conversation.other_user?.avatar || '/images/default-avatar.png'"
                            :alt="conversation.other_user?.name || 'Utilisateur'"
                            :class="mobile ? 'h-6 w-6' : 'h-8 w-8'"
                            class="flex-shrink-0 rounded-full object-cover"
                        />
                        <div class="rounded-2xl bg-gray-100 shadow-sm" :class="mobile ? 'px-3 py-2' : 'px-4 py-3'">
                            <div class="flex items-center space-x-1">
                                <div class="h-2 w-2 animate-bounce rounded-full bg-gray-400"></div>
                                <div class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay: 0.1s"></div>
                                <div class="h-2 w-2 animate-bounce rounded-full bg-gray-400" style="animation-delay: 0.2s"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Message si pas de messages -->
                    <div v-if="messages.length === 0" class="py-8 text-center">
                        <MessageSquare :class="mobile ? 'h-10 w-10' : 'h-12 w-12'" class="mx-auto mb-3 text-gray-300" />
                        <p class="text-gray-500" :class="mobile ? 'text-sm' : ''">Aucun message pour le moment</p>
                        <p class="text-gray-400" :class="mobile ? 'text-xs' : 'text-sm'">Commencez la conversation !</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { MessageSquare } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    conversation?: any;
    userRole?: string;
    mobile?: boolean;
}>();

const page = usePage();

// Echo state local
const echoReady = ref(false);
const currentEcho = ref(null);

// Fonction pour attendre Echo
const initEcho = async () => {
    try {
        if (window.Echo) {
            currentEcho.value = window.Echo;
            echoReady.value = true;
            return;
        }

        // Attendre jusqu'à 5 secondes
        let attempts = 0;
        while (!window.Echo && attempts < 10) {
            await new Promise((resolve) => setTimeout(resolve, 500));
            attempts++;
        }

        if (window.Echo) {
            currentEcho.value = window.Echo;
            echoReady.value = true;
            console.log('Echo initialisé avec succès');
        } else {
            console.warn('Echo non disponible après attente');
        }
    } catch (error) {
        console.error("Erreur lors de l'initialisation d'Echo:", error);
    }
};

// Fonction pour écouter un canal
const listenToChannel = (channelName, eventName, callback) => {
    if (!currentEcho.value) return null;
    const channel = currentEcho.value.private(channelName);
    channel.listen(eventName, callback);
    return channel;
};

// Fonction pour quitter un canal
const leaveChannel = (channelName) => {
    if (currentEcho.value) {
        currentEcho.value.leave(channelName);
    }
};

// État local
const messages = ref([]);
const isLoading = ref(false);
const error = ref(null);
const isOtherUserTyping = ref(false);
const typingTimeout = ref(null);
const currentChannel = ref(null);

// Utilisateur actuel
const currentUser = computed(() => page.props.auth.user);

// Initialisation au montage
onMounted(async () => {
    await initEcho();
});

// Watcher pour charger les messages quand la conversation change
watch(
    () => props.conversation?.id,
    async (newConversationId, oldConversationId) => {
        // Quitter l'ancien canal
        if (oldConversationId && currentChannel.value) {
            leaveChannel(`conversation.${oldConversationId}`);
            currentChannel.value = null;
        }

        if (newConversationId) {
            await loadMessages();
            joinConversationChannel();
        }
    },
    { immediate: true },
);

// Fonctions
async function loadMessages() {
    if (!props.conversation?.id) {
        return;
    }

    isLoading.value = true;
    error.value = null;

    try {
        const response = await fetch(route('conversations.messages', { conversation: props.conversation.id }), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            messages.value = data.messages || [];

            // Scroll vers le bas après chargement
            await nextTick();
            scrollToBottom();
        } else {
            const errorData = await response.json();
            error.value = errorData.message || 'Erreur lors du chargement des messages';
        }
    } catch (networkError) {
        console.error('Erreur réseau lors du chargement des messages:', networkError);
        error.value = 'Erreur de connexion';
    } finally {
        isLoading.value = false;
    }
}

function isMyMessage(message) {
    return message.sender_id === currentUser.value?.id;
}

function getMessageSenderAvatar(message) {
    // Si c'est mon message, utiliser mon avatar
    if (isMyMessage(message)) {
        return currentUser.value?.avatar || '/default-avatar.svg';
    }
    // Sinon, utiliser l'avatar de l'expéditeur ou l'autre utilisateur
    return message.sender?.avatar || props.conversation?.other_user?.avatar || '/default-avatar.svg';
}

function getMessageSenderName(message) {
    if (message.sender?.name) {
        return message.sender.name;
    }
    return props.conversation?.other_user?.name || 'Utilisateur';
}

function formatTime(dateString) {
    try {
        const date = new Date(dateString);
        return date.toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch (formatError) {
        console.error('Erreur lors du formatage de la date:', formatError);
        return '00:00';
    }
}

function scrollToBottom() {
    nextTick(() => {
        setTimeout(() => {
            const container = document.querySelector('.messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight + 100;
            }
        }, 200);
    });
}

async function markNewMessageAsRead(message) {
    try {
        const response = await fetch(
            route('conversations.mark-message-read', {
                conversation: props.conversation.id,
                message: message.id,
            }),
            {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            },
        );

        if (response.ok) {
            // Marquer le message comme lu côté client aussi
            const messageInList = messages.value.find((m) => m.id === message.id);
            if (messageInList) {
                messageInList.read_at = new Date().toISOString();
            }
        }
    } catch (error) {
        console.error('Erreur lors du marquage du message comme lu:', error);
    }
}

function joinConversationChannel() {
    if (!props.conversation?.id) {
        return;
    }

    if (!echoReady.value) {
        // Réessayer quand Echo sera prêt
        watch(
            echoReady,
            (ready) => {
                if (ready) {
                    joinConversationChannel();
                }
            },
            { once: true },
        );
        return;
    }

    console.log('Connexion au canal de conversation:', props.conversation.id);

    // Utiliser le composable pour s'abonner au canal
    const channelName = `conversation.${props.conversation.id}`;
    currentChannel.value = listenToChannel(channelName, 'message.sent', onNewMessage);

    if (!currentChannel.value) {
        console.warn('Impossible de créer le canal');
        return;
    }

    // Ajouter les autres écouteurs sur le canal
    addChannelListeners();
}

// Fonction pour gérer les nouveaux messages
function onNewMessage(e) {
    console.log('Nouveau message reçu:', e.message);

    const messageSenderId = String(e.message?.sender_id);
    const currentUserId = String(currentUser.value?.id);
    const isMyMessage = messageSenderId === currentUserId;

    // Ne pas ajouter notre propre message (déjà ajouté localement)
    if (!isMyMessage) {
        messages.value.push(e.message);

        // Marquer automatiquement comme lu
        markNewMessageAsRead(e.message);

        // Scroll vers le bas
        nextTick(() => {
            scrollToBottom();
        });
    }
}

// Fonction pour ajouter les autres écouteurs sur le canal
function addChannelListeners() {
    if (!currentChannel.value) return;

    const channel = currentChannel.value;

    // Écouter les événements "en train d'écrire"
    channel.listenForWhisper('typing', (e) => {
        if (parseInt(e.user_id) !== parseInt(currentUser.value?.id)) {
            isOtherUserTyping.value = true;
            clearTimeout(typingTimeout.value);
            typingTimeout.value = setTimeout(() => {
                isOtherUserTyping.value = false;
            }, 3000);
        }
    });

    // Écouter les événements "arrêt d'écriture"
    channel.listenForWhisper('stop-typing', (e) => {
        if (parseInt(e.user_id) !== parseInt(currentUser.value?.id)) {
            isOtherUserTyping.value = false;
            clearTimeout(typingTimeout.value);
        }
    });

    // Écouter les événements de messages lus
    channel.listen('messages.read', (e) => {
        // Marquer mes messages comme lus si c'est l'autre utilisateur qui les a lus
        if (parseInt(e.read_by) !== parseInt(currentUser.value?.id)) {
            messages.value.forEach((message) => {
                if (message.sender_id === currentUser.value?.id && !message.read_at) {
                    message.read_at = e.read_at;
                }
            });
        }
    });

    // Événements de connexion
    channel.subscribed(() => {
        console.log('Connecté au canal de conversation:', props.conversation.id);
    });

    channel.error((error) => {
        console.error('Erreur de connexion au canal:', error);
    });
}

// Exposer la fonction pour recharger depuis le parent
defineExpose({
    reloadMessages: loadMessages,
    addMessageLocally: (message) => {
        messages.value.push(message);
        nextTick(scrollToBottom);
    },
    sendTypingEvent: () => {
        if (currentChannel.value && props.conversation?.id) {
            currentChannel.value.whisper('typing', { user_id: currentUser.value?.id });
        }
    },
    sendStopTypingEvent: () => {
        if (currentChannel.value && props.conversation?.id) {
            currentChannel.value.whisper('stop-typing', { user_id: currentUser.value?.id });
        }
    },
});

// Nettoyer lors de la destruction
onUnmounted(() => {
    if (props.conversation?.id && currentChannel.value) {
        leaveChannel(`conversation.${props.conversation.id}`);
        currentChannel.value = null;
    }
    if (typingTimeout.value) {
        clearTimeout(typingTimeout.value);
    }
});
</script>

<style scoped>
/* Styles pour la messagerie */
.message-bubble {
    word-wrap: break-word;
    overflow-wrap: break-word;
    hyphens: none;
    -webkit-hyphens: none;
    -moz-hyphens: none;
    -ms-hyphens: none;
    max-width: 100%;
    min-width: 0;
}

/* Éviter la coupure de mots courts */
.message-bubble p {
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: pre-wrap;
    margin: 0;
    line-height: 1.4;
}
</style>
