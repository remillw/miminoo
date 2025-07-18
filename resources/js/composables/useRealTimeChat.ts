import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { Message, User } from '@/types/global';
import { route as ziggyRoute } from 'ziggy-js';

// Fonction route sécurisée pour SSR
const route = (name: string, params?: any) => {
    try {
        return ziggyRoute(name, params);
    } catch {
        console.warn(`Route "${name}" not found, using fallback`);
        switch (name) {
            case 'conversations.send-message':
                return `/conversations/${params.conversation}/send-message`;
            case 'conversations.typing':
                return `/conversations/${params.conversation}/typing`;
            case 'conversations.messages':
                return `/conversations/${params.conversation}/messages`;
            default:
                return '#';
        }
    }
};

interface TypingUser {
    user_id: number;
    user_name: string;
    is_typing: boolean;
}

export function useRealTimeChat(conversationId: number, currentUser: User) {
    const messages = ref<Message[]>([]);
    const typingUsers = ref<TypingUser[]>([]);
    const isConnected = ref(false);
    const isLoading = ref(false);
    
    let channel: any = null;
    let typingTimer: number | null = null;

    const joinConversation = () => {
        if (!window.Echo || !conversationId) return;

        channel = window.Echo.private(`conversation.${conversationId}`)
            .listen('.message.sent', (e: any) => {
                console.log('Nouveau message reçu:', e);
                
                // Ajouter le message à la liste
                messages.value.push(e.message);
                
                // Notification si le message n'est pas de l'utilisateur actuel
                if (e.message.sender_id !== currentUser.id) {
                    toast.success(`Nouveau message de ${e.message.sender.name}`);
                }
                
                // Scroll automatique vers le bas
                setTimeout(() => {
                    const messagesContainer = document.querySelector('.messages-container');
                    if (messagesContainer) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                }, 100);
            })
            .listen('.user.typing', (e: any) => {
                console.log('Utilisateur en train de taper:', e);
                
                if (e.user_id === currentUser.id) return; // Ignorer ses propres événements
                
                if (e.is_typing) {
                    // Ajouter ou mettre à jour l'utilisateur qui tape
                    const existingIndex = typingUsers.value.findIndex(u => u.user_id === e.user_id);
                    if (existingIndex >= 0) {
                        typingUsers.value[existingIndex] = e;
                    } else {
                        typingUsers.value.push(e);
                    }
                } else {
                    // Supprimer l'utilisateur qui ne tape plus
                    typingUsers.value = typingUsers.value.filter(u => u.user_id !== e.user_id);
                }
            })
            .subscribed(() => {
                console.log('Connecté au canal de conversation');
                isConnected.value = true;
            })
            .error((error: any) => {
                console.error('Erreur de connexion au canal:', error);
                isConnected.value = false;
            });
    };

    const leaveConversation = () => {
        if (channel) {
            window.Echo.leave(`conversation.${conversationId}`);
            channel = null;
        }
        isConnected.value = false;
    };

    const sendMessage = async (messageText: string) => {
        if (!messageText.trim()) return false;

        try {
            const response = await fetch(route('conversations.send-message', { conversation: conversationId }), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: messageText }),
            });

            if (response.ok) {
                const data = await response.json();
                // Le message sera ajouté automatiquement via l'événement broadcast
                return true;
            } else {
                const errorData = await response.json();
                toast.error(errorData.message || 'Erreur lors de l\'envoi du message');
                return false;
            }
        } catch (error) {
            console.error('Erreur lors de l\'envoi du message:', error);
            toast.error('Erreur de connexion');
            return false;
        }
    };

    const sendTypingIndicator = (isTyping: boolean) => {
        if (!window.Echo || !conversationId) return;

        fetch(route('conversations.typing', { conversation: conversationId }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ is_typing: isTyping }),
        }).catch(console.error);
    };

    const handleTyping = () => {
        sendTypingIndicator(true);
        
        // Annuler le timer précédent
        if (typingTimer) {
            clearTimeout(typingTimer);
        }
        
        // Arrêter l'indicateur après 2 secondes d'inactivité
        typingTimer = window.setTimeout(() => {
            sendTypingIndicator(false);
        }, 2000);
    };

    const loadMessages = async () => {
        if (!conversationId) return;
        
        isLoading.value = true;
        try {
            const response = await fetch(route('conversations.messages', { conversation: conversationId }), {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                messages.value = data.messages;
                
                // Scroll vers le bas après chargement
                setTimeout(() => {
                    const messagesContainer = document.querySelector('.messages-container');
                    if (messagesContainer) {
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }
                }, 100);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des messages:', error);
            toast.error('Erreur lors du chargement des messages');
        } finally {
            isLoading.value = false;
        }
    };

    onMounted(() => {
        joinConversation();
        loadMessages();
    });

    onUnmounted(() => {
        leaveConversation();
        if (typingTimer) {
            clearTimeout(typingTimer);
        }
    });

    return {
        messages,
        typingUsers,
        isConnected,
        isLoading,
        sendMessage,
        handleTyping,
        loadMessages,
        joinConversation,
        leaveConversation
    };
} 