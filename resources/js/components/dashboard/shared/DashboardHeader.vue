<template>
    <header class="sticky top-0 py-3 z-30 w-full bg-white shadow-sm">
        <nav class="max-w-9xl mx-auto flex h-16 items-center justify-between px-4">
            <!-- Logo -->
            <div class="flex items-center gap-2">
                <Link href="/" class="inline-block">
    <img
      src="/storage/trouve-ta-babysitter-logo.svg"
      alt="Trouve ta Babysitter logo"
      class="h-15 w-auto"
    />
  </Link>
            </div>

            <!-- Navigation principale -->
            <div class="hidden items-center gap-6 md:flex">
                <a href="/" class="hover:text-primary text-base font-medium text-gray-700">Accueil</a>
                <a href="/comment-ca-marche" class="hover:text-primary text-base font-medium text-gray-700">Comment ça marche ?</a>
            </div>

            <!-- Actions à droite -->
            <div class="flex items-center gap-4">
                <!-- Notifications -->
                <div class="notifications-dropdown relative">
                    <button @click="toggleNotifications" class="relative rounded-full p-2 transition-colors hover:bg-gray-100">
                        <Bell class="h-6 w-6 text-gray-500" />
                        <span
                            v-if="unreadNotificationsCount > 0"
                            class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-orange-400 text-xs font-medium text-white"
                        >
                            {{ unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount }}
                        </span>
                    </button>

                    <!-- Dropdown notifications -->
                    <div
                        v-if="showNotifications"
                        class="absolute top-full right-0 z-50 mt-2 w-80 rounded-lg border border-gray-200 bg-white shadow-lg"
                    >
                        <div class="border-b border-gray-200 p-4">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900">Notifications</h3>
                                <button v-if="unreadNotificationsCount > 0" @click="markAllAsRead" class="text-primary hover:text-primary/80 text-sm">
                                    Tout marquer comme lu
                                </button>
                            </div>
                        </div>

                        <div class="scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 max-h-80 overflow-y-auto">
                            <div v-if="unreadNotifications.length === 0" class="p-4 text-center text-gray-500">Aucune notification</div>
                            <div v-else>
                                <div
                                    v-for="notification in unreadNotifications"
                                    :key="notification.id"
                                    class="border-b border-gray-100 p-4 last:border-b-0 hover:bg-gray-50"
                                    :class="{ 'cursor-pointer': getNotificationLink(notification) }"
                                    @click="handleNotificationClick(notification)"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex flex-1 items-start gap-3">
                                            <div class="flex-shrink-0">
                                                <component
                                                    :is="getNotificationIcon(notification.type)"
                                                    class="h-5 w-5"
                                                    :class="getNotificationColor(notification.type)"
                                                />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-gray-900">{{ notification.title }}</p>
                                                <p v-if="notification.message" class="mt-1 text-sm text-gray-600">{{ notification.message }}</p>
                                                <p class="mt-2 text-xs text-gray-500">{{ formatDate(notification.created_at) }}</p>
                                                <div v-if="getNotificationLink(notification)" class="mt-2">
                                                    <span class="inline-flex items-center text-xs text-blue-600 hover:text-blue-800">
                                                        <span>Voir l'annonce</span>
                                                        <svg class="ml-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            @click.stop="markAsRead(notification.id)"
                                            class="flex-shrink-0 rounded px-2 py-1 text-xs text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                            title="Marquer comme lu"
                                        >
                                            ✓
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Avatar -->
                <button v-if="props.user" class="rounded-full">
                    <img
                        :src="props.user?.avatar || '/default-avatar.svg'"
                        :alt="`Avatar de ${props.user?.firstname || 'Utilisateur'}`"
                        class="h-10 w-10 rounded-full object-cover"
                    />
                </button>

                <!-- Bouton "Créer une annonce" -->
                <a href="/creer-une-annonce class="bg-primary hover:bg-primary hidden rounded px-4 py-2 font-semibold text-white md:inline-block">
                    Créer une annonce
                </a>
            </div>
        </nav>
    </header>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { AlertTriangle, Bell, DollarSign, MessageCircle, Star } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { route as ziggyRoute } from 'ziggy-js';
import { Link } from '@inertiajs/vue3';

// Fonction route sécurisée pour SSR
const route = (name: string, params?: any) => {
    try {
        return ziggyRoute(name, params);
    } catch {
        console.warn(`Route "${name}" not found, using fallback`);
        switch (name) {
            case 'notifications.mark-as-read':
                return `/notifications/${params}/mark-as-read`;
            case 'notifications.mark-all-as-read':
                return '/notifications/mark-all-as-read';
            default:
                return '#';
        }
    }
};

interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    avatar?: string;
}

interface Notification {
    id: string;
    type: string;
    title: string;
    message?: string;
    created_at: string;
    read_at?: string;
    data?: {
        ad_id?: number;
        ad_slug?: string;
        conversation_id?: number;
        application_id?: number;
    };
}

interface Props {
    user?: User;
    unreadNotifications?: Notification[];
    unreadNotificationsCount?: number;
}

const props = withDefaults(defineProps<Props>(), {
    unreadNotifications: () => [],
    unreadNotificationsCount: 0,
});

const showNotifications = ref(false);

// Refs locales pour les notifications
const unreadNotifications = ref([...props.unreadNotifications]);
const unreadNotificationsCount = ref(props.unreadNotificationsCount);

// Watcher pour mettre à jour les refs quand les props changent
watch(
    () => props.unreadNotifications,
    (newNotifications) => {
        unreadNotifications.value = [...newNotifications];
    },
);

watch(
    () => props.unreadNotificationsCount,
    (newCount) => {
        unreadNotificationsCount.value = newCount;
    },
);

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value;
};

// Fermer les notifications en cliquant à l'extérieur
const handleClickOutside = (event: Event) => {
    const target = event.target as Element;
    if (!target.closest('.notifications-dropdown')) {
        showNotifications.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const markAsRead = async (notificationId: string) => {
    try {
        await router.post(
            route('notifications.mark-as-read', notificationId),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    // Retirer la notification de la liste locale
                    unreadNotifications.value = unreadNotifications.value.filter((n) => n.id !== notificationId);
                    unreadNotificationsCount.value = Math.max(0, unreadNotificationsCount.value - 1);
                },
            },
        );
    } catch (error) {
        console.error('Erreur lors du marquage comme lu:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await router.post(
            route('notifications.mark-all-as-read'),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    unreadNotifications.value = [];
                    unreadNotificationsCount.value = 0;
                },
            },
        );
    } catch (error) {
        console.error('Erreur lors du marquage de toutes comme lues:', error);
    }
};

const getNotificationIcon = (type: string) => {
    switch (type) {
        case 'new_message':
            return MessageCircle;
        case 'review_request':
            return Star;
        case 'funds_released':
            return DollarSign;
        case 'dispute_created':
            return AlertTriangle;
        case 'new_announcement':
            return Bell;
        default:
            return Bell;
    }
};

const getNotificationColor = (type: string) => {
    switch (type) {
        case 'new_message':
            return 'text-blue-500';
        case 'review_request':
            return 'text-yellow-500';
        case 'funds_released':
            return 'text-green-500';
        case 'dispute_created':
            return 'text-red-500';
        case 'new_announcement':
            return 'text-orange-500';
        default:
            return 'text-gray-500';
    }
};

const formatDate = (dateString: string) => {
    const now = new Date();
    const date = new Date(dateString);
    const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

    if (diffInMinutes < 1) return "À l'instant";
    if (diffInMinutes < 60) return `Il y a ${diffInMinutes} min`;

    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `Il y a ${diffInHours}h`;

    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 7) return `Il y a ${diffInDays}j`;

    return date.toLocaleDateString('fr-FR');
};

const getNotificationLink = (notification: Notification) => {
    // Générer le lien selon le type de notification
    if (notification.data?.ad_id) {
        // Pour les nouvelles annonces, créer le lien vers l'annonce
        let slug = notification.data.ad_slug;
        
        // Si pas de slug fourni, créer un slug basique avec l'ID
        if (!slug) {
            slug = `annonce-${notification.data.ad_id}`;
        }
        
        try {
            return route('announcements.show', slug);
        } catch {
            return `/annonce/${slug}`;
        }
    }
    
    if (notification.data?.conversation_id) {
        // Pour les nouveaux messages, aller vers la messagerie
        try {
            return route('messaging.index');
        } catch {
            return '/messagerie';
        }
    }
    
    return null;
};

const handleNotificationClick = (notification: Notification) => {
    const link = getNotificationLink(notification);
    
    if (link) {
        // Marquer comme lu d'abord
        markAsRead(notification.id);
        
        // Puis rediriger
        try {
            router.visit(link);
        } catch {
            // Fallback: utiliser window.location
            window.location.href = link;
        }
        
        // Fermer le dropdown
        showNotifications.value = false;
    }
};
</script>

<style scoped>
/* Styles pour le scrollbar personnalisé */
.scrollbar-thin {
    scrollbar-width: thin;
}

.scrollbar-thumb-gray-300::-webkit-scrollbar {
    width: 6px;
}

.scrollbar-thumb-gray-300::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 3px;
}

.scrollbar-thumb-gray-300::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.scrollbar-thumb-gray-300::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
