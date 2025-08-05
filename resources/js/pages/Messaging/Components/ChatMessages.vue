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
                                    <p :class="mobile ? 'text-sm' : ''" v-html="filterSensitiveInfo(message.message)"></p>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">{{ formatTime(message.created_at) }}</p>
                            </div>
                        </div>

                        <!-- Message de l'utilisateur actuel -->
                        <div v-else class="flex justify-end" :class="mobile ? 'max-w-[85%]' : 'max-w-[70%]'">
                            <div class="flex flex-col items-end">
                                <div 
                                    class="message-bubble rounded-2xl text-white shadow-sm" 
                                    :class="[
                                        mobile ? 'px-3 py-2' : 'px-4 py-3',
                                        message.status === 'sending' ? 'bg-blue-400' : 
                                        message.status === 'failed' ? 'bg-red-500' : 'bg-blue-600'
                                    ]"
                                >
                                    <p :class="mobile ? 'text-sm' : ''" v-html="filterSensitiveInfo(message.message)"></p>
                                </div>
                                <div class="mt-1 flex items-center gap-2">
                                    <p class="text-xs text-gray-500">{{ formatTime(message.created_at) }}</p>
                                    
                                    <!-- Indicateurs de statut -->
                                    <div v-if="message.status === 'sending'" class="flex items-center gap-1">
                                        <div class="h-2 w-2 rounded-full bg-blue-400 animate-pulse"></div>
                                        <span class="text-xs text-blue-500">envoi...</span>
                                    </div>
                                    <div v-else-if="message.status === 'failed'" class="flex items-center gap-1 cursor-pointer" @click="retryMessage(message)">
                                        <span class="text-xs text-red-500">❌ échec - cliquer pour renvoyer</span>
                                    </div>
                                    <span v-else-if="message.read_at" class="text-xs font-medium text-blue-500">lu</span>
                                    <span v-else class="text-xs text-gray-400">envoyé</span>
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
const currentEcho = ref<any>(null);

// Fonction pour attendre Echo
const initEcho = async () => {
    try {
        console.log('🔧 Initialisation Echo...');
        console.log('🔧 Variables env:', {
            VITE_REVERB_APP_KEY: import.meta.env.VITE_REVERB_APP_KEY,
            VITE_REVERB_HOST: import.meta.env.VITE_REVERB_HOST,
            VITE_REVERB_PORT: import.meta.env.VITE_REVERB_PORT,
            VITE_REVERB_SCHEME: import.meta.env.VITE_REVERB_SCHEME
        });
        
        if (window.Echo) {
            currentEcho.value = window.Echo;
            echoReady.value = true;
            console.log('✅ Echo déjà disponible');
            
            // Vérifier l'état de la connexion
            if ((window.Echo as any).connector?.pusher?.connection) {
                const connection = (window.Echo as any).connector.pusher.connection;
                console.log('🔗 État connexion WebSocket:', connection.state);
                console.log('🔗 Socket ID:', connection.socket_id);
            }
            return;
        }

        console.log('⏳ Echo non trouvé, attente...');
        // Attendre jusqu'à 5 secondes
        let attempts = 0;
        while (!window.Echo && attempts < 10) {
            await new Promise((resolve) => setTimeout(resolve, 500));
            attempts++;
            console.log(`⏳ Tentative ${attempts}/10`);
        }

        if (window.Echo) {
            currentEcho.value = window.Echo;
            echoReady.value = true;
            console.log('✅ Echo initialisé avec succès après attente');
            
            // Vérifier l'état de la connexion
            if ((window.Echo as any).connector?.pusher?.connection) {
                const connection = (window.Echo as any).connector.pusher.connection;
                console.log('🔗 État connexion WebSocket:', connection.state);
                console.log('🔗 Socket ID:', connection.socket_id);
            }
        } else {
            console.error('❌ Echo non disponible après attente');
            console.error('❌ window.Echo:', window.Echo);
            console.error('❌ Vérifiez que echo.ts est bien chargé');
        }
    } catch (error) {
        console.error("❌ Erreur lors de l'initialisation d'Echo:", error);
    }
};

// Fonction pour quitter un canal
const leaveChannel = (channelName: string) => {
    if (currentEcho.value && channelName) {
        try {
            console.log('🚪 Quitter le canal:', channelName);
            currentEcho.value.leave(channelName);
        } catch (error) {
            console.warn('⚠️ Erreur lors de la sortie du canal:', error);
        }
    }
};

// État local
const messages = ref<any[]>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);
const isOtherUserTyping = ref(false);
const typingTimeout = ref<any>(null);
const currentChannel = ref<any>(null);

// Utilisateur actuel
const currentUser = computed(() => (page?.props?.auth as any)?.user);

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

function isMyMessage(message: any) {
    return message.sender_id === currentUser.value?.id;
}

function getMessageSenderAvatar(message: any) {
    // Si c'est mon message, utiliser mon avatar
    if (isMyMessage(message)) {
        return currentUser.value?.avatar || '/default-avatar.svg';
    }
    // Sinon, utiliser l'avatar de l'expéditeur ou l'autre utilisateur
    return message.sender?.avatar || props.conversation?.other_user?.avatar || '/default-avatar.svg';
}

function getMessageSenderName(message: any) {
    if (message.sender?.name) {
        return message.sender.name;
    }
    return props.conversation?.other_user?.name || 'Utilisateur';
}

function formatTime(dateString: string) {
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

function filterSensitiveInfo(text: string): string {
    if (!text) return '';
    
    // Vérifier si le paiement est effectué
    const isPaymentCompleted = props.conversation?.status === 'active' || props.conversation?.deposit_paid;
    
    if (isPaymentCompleted) {
        // Si le paiement est fait, pas de filtrage
        return text;
    }
    
    // Patterns pour détecter les numéros de téléphone
    const phonePatterns = [
        // Numéros français (06, 07, etc.)
        /(?:(?:0|\+33\s?)[1-9](?:[\s.-]?\d{2}){4})/g,
        // Numéros avec indicatifs internationaux
        /(?:\+\d{1,3}[\s.-]?)?(?:\d[\s.-]?){6,14}\d/g,
        // Patterns simples pour 10 chiffres consécutifs
        /\b\d{10}\b/g,
        // Numéros avec espaces ou tirets
        /\b\d{2}[\s.-]?\d{2}[\s.-]?\d{2}[\s.-]?\d{2}[\s.-]?\d{2}\b/g
    ];
    
    let filteredText = text;
    
    // Remplacer les numéros de téléphone par un message de restriction
    phonePatterns.forEach(pattern => {
        filteredText = filteredText.replace(pattern, '<span class="bg-red-100 text-red-600 px-2 py-1 rounded text-xs">🔒 Numéro masqué - Paiement requis</span>');
    });
    
    // Patterns pour détecter d'autres infos sensibles
    const emailPattern = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/g;
    filteredText = filteredText.replace(emailPattern, '<span class="bg-red-100 text-red-600 px-2 py-1 rounded text-xs">🔒 Email masqué - Paiement requis</span>');
    
    return filteredText;
}

function retryMessage(message: any) {
    // TODO: Implémenter la logique de renvoi de message
    console.log('Retry message:', message);
}

async function markNewMessageAsRead(message: any) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            console.error('❌ Token CSRF manquant');
            return;
        }

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
                    'X-CSRF-TOKEN': csrfToken,
                },
            },
        );

        if (response.ok) {
            console.log('✅ Message marqué comme lu:', message.id);
            // Marquer le message comme lu côté client aussi
            const messageInList = messages.value.find((m) => m.id === message.id);
            if (messageInList) {
                messageInList.read_at = new Date().toISOString();
            }
        } else {
            console.error('❌ Erreur marquage message lu:', response.status, await response.text());
        }
    } catch (error) {
        console.error('❌ Erreur lors du marquage du message comme lu:', error);
    }
}

function joinConversationChannel() {
    if (!props.conversation?.id) {
        console.error('❌ Pas de conversation ID pour rejoindre le canal');
        return;
    }

    if (!echoReady.value) {
        console.log('⏳ Echo pas encore prêt, attente...');
        // Réessayer quand Echo sera prêt
        watch(
            echoReady,
            (ready) => {
                if (ready) {
                    console.log('✅ Echo prêt, nouvelle tentative de connexion canal');
                    joinConversationChannel();
                }
            },
            { once: true },
        );
        return;
    }

    const channelName = `conversation.${props.conversation.id}`;
    
    // Vérifier si un canal existe déjà pour cette conversation
    if (currentChannel.value && currentChannel.value.name === channelName) {
        console.log('✅ Canal déjà connecté:', channelName);
        return;
    }
    
    // Quitter l'ancien canal s'il existe
    if (currentChannel.value) {
        console.log('🚪 Fermeture de l\' ancien canal:', currentChannel.value.name);
        try {
            currentEcho.value.leave(currentChannel.value.name);
        } catch (error) {
            console.warn('⚠️ Erreur lors de la fermeture de l\'ancien canal:', error);
        }
        currentChannel.value = null;
    }

    console.log('🚀 Connexion au canal de conversation:', props.conversation.id);
    console.log('🚀 Echo disponible:', !!currentEcho.value);
    console.log('🚀 Utilisateur actuel:', currentUser.value?.id);
    console.log('🚀 Nom du canal:', channelName);
    
    try {
        // Vérifier si le canal existe déjà dans Echo
        const existingChannels = (currentEcho.value as any).channels || {};
        if (existingChannels[channelName]) {
            console.log('🔄 Canal existant détecté, réutilisation:', channelName);
            currentChannel.value = existingChannels[channelName];
        } else {
            console.log('🆕 Création d\'un nouveau canal:', channelName);
            currentChannel.value = currentEcho.value.private(channelName);
        }
        
        console.log('✅ Canal configuré:', currentChannel.value);
        
        // Ajouter tous les écouteurs sur le canal
        addChannelListeners();
    } catch (error) {
        console.error('❌ Erreur création canal:', error);
        // Essayer une seule fois de nettoyer et recréer
        try {
            console.log('🔧 Tentative de nettoyage et recréation...');
            currentEcho.value.leave(channelName);
            setTimeout(() => {
                currentChannel.value = currentEcho.value.private(channelName);
                console.log('✅ Canal recréé avec succès');
                addChannelListeners();
            }, 1000);
        } catch (retryError) {
            console.error('❌ Échec de la recréation du canal:', retryError);
        }
    }
}

// Fonction pour gérer les nouveaux messages
function onNewMessage(e: any) {
    const messageSenderId = String(e.message?.sender_id);
    const currentUserId = String(currentUser.value?.id);
    const isMyMessage = messageSenderId === currentUserId;
    
    console.log('📨 Nouveau message:', {
        id: e.message.id,
        from: isMyMessage ? 'moi' : `user ${messageSenderId}`,
        message: e.message.message?.substring(0, 50) + '...'
    });

    // Vérifier si le message existe déjà dans la liste
    const messageExists = messages.value.some(msg => msg.id === e.message.id);
    
    if (!messageExists) {
        console.log('✅ Ajout du message à la liste');
        messages.value.push(e.message);

        // Si ce n'est pas mon message, le marquer automatiquement comme lu
        // Temporairement désactivé pour éviter l'erreur 405
        // if (!isMyMessage) {
        //     markNewMessageAsRead(e.message);
        // }

        // Scroll vers le bas
        nextTick(() => {
            scrollToBottom();
        });
    } else {
        console.log('⚠️ Message déjà présent, pas d\'ajout');
    }
}

// Fonction pour ajouter les écouteurs sur le canal
function addChannelListeners() {
    if (!currentChannel.value) {
        console.error('❌ Pas de canal disponible pour ajouter les écouteurs');
        return;
    }

    const channel = currentChannel.value;
    console.log('🎧 Ajout des écouteurs sur le canal...');
    console.log('🎧 Canal name:', channel.name);
    console.log('🎧 Canal subscription:', channel.subscription);

    // Mode debug réduit - ne garder que pour les événements importants
    if (channel.subscription) {
        console.log('🎯 Debug: écoute des événements sur canal:', channel.name);
    }

    // Écouter les événements de message envoyé (server-side)
    // Utiliser directement Pusher car Laravel Echo ne capture pas l'événement
    if (channel.subscription) {
        console.log('📡 Configuration écoute directe Pusher pour message.sent');
        
        channel.subscription.bind('message.sent', (data: any) => {
            console.log('📨 ÉVÉNEMENT MESSAGE.SENT CAPTURÉ DIRECTEMENT:', data);
            onNewMessage(data);
        });
        
        channel.subscription.bind('messages.read', (data: any) => {
            console.log('👁️ ÉVÉNEMENT MESSAGES.READ CAPTURÉ DIRECTEMENT:', data);
            // Marquer mes messages comme lus si c'est l'autre utilisateur qui les a lus
            if (parseInt(data.read_by) !== parseInt(currentUser.value?.id)) {
                messages.value.forEach((message) => {
                    if (message.sender_id === currentUser.value?.id && !message.read_at) {
                        message.read_at = data.read_at;
                    }
                });
            }
        });
    } else {
        console.error('❌ Pas de subscription Pusher disponible');
    }

    // Écouter les événements "en train d'écrire"
    channel.listenForWhisper('typing', (e: any) => {
        console.log('👀 Événement typing reçu:', e);
        if (parseInt(e.user_id) !== parseInt(currentUser.value?.id)) {
            isOtherUserTyping.value = true;
            clearTimeout(typingTimeout.value);
            typingTimeout.value = setTimeout(() => {
                isOtherUserTyping.value = false;
            }, 3000);
        }
    });

    // Écouter les événements "arrêt d'écriture"
    channel.listenForWhisper('stop-typing', (e: any) => {
        console.log('🛑 Événement stop-typing reçu:', e);
        if (parseInt(e.user_id) !== parseInt(currentUser.value?.id)) {
            isOtherUserTyping.value = false;
            clearTimeout(typingTimeout.value);
        }
    });

    // Événements de connexion
    channel.subscribed(() => {
        console.log('✅ Abonnement réussi au canal:', props.conversation.id);
        console.log('✅ État du canal après abonnement:', {
            name: channel.name,
            subscribed: channel.subscription?.subscribed,
            state: channel.subscription?.state
        });
    });

    channel.error((error: any) => {
        console.error('❌ Erreur de connexion au canal:', error);
    });
}

// Exposer la fonction pour recharger depuis le parent
defineExpose({
    reloadMessages: loadMessages,
    addMessageLocally: (message: any) => {
        // Vérifier si le message existe déjà (même logique que onNewMessage)
        const messageExists = messages.value.some(msg => msg.id === message.id);
        
        if (!messageExists) {
            console.log('⚡ Ajout immédiat du message (local):', message.id);
            messages.value.push(message);
            nextTick(scrollToBottom);
        } else {
            console.log('⚠️ Message local déjà présent, pas d\'ajout');
        }
    },
    confirmMessage: (tempId: string, realMessage: any) => {
        // Remplacer le message temporaire par le vrai message du serveur
        const tempIndex = messages.value.findIndex(msg => msg.id === tempId);
        if (tempIndex >= 0) {
            console.log('✅ Remplacement message temporaire par le vrai:', { tempId, realMessage });
            messages.value[tempIndex] = realMessage;
        } else {
            console.log('⚠️ Message temporaire non trouvé, ajout du vrai message');
            messages.value.push(realMessage);
            nextTick(scrollToBottom);
        }
    },
    markMessageAsFailed: (tempId: string, error: string) => {
        // Marquer le message comme échoué
        const tempIndex = messages.value.findIndex(msg => msg.id === tempId);
        if (tempIndex >= 0) {
            console.error('❌ Marquage message comme échoué:', { tempId, error });
            messages.value[tempIndex].status = 'failed';
            messages.value[tempIndex].error = error;
        }
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
