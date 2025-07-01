<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Link, router, usePage } from '@inertiajs/vue3';
import { AlertTriangle, Bell, DollarSign, Menu, MessageSquare, Star } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { route } from 'ziggy-js';

interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    avatar?: string;
    roles?: Array<{ name: string; label: string }>;
}

interface Notification {
    id: string;
    type: string;
    title: string;
    message?: string;
    created_at: string;
    read_at?: string;
}

// Plus besoin de props pour les notifications, on utilise les props globales

const page = usePage<{
    auth: {
        user?: User;
    };
    unreadNotifications?: Notification[];
    unreadNotificationsCount?: number;
}>();
const user = computed(() => page.props.auth?.user);

// Récupérer les notifications depuis les props globales
const globalUnreadNotifications = computed(() => page.props.unreadNotifications || []);
const globalUnreadNotificationsCount = computed(() => page.props.unreadNotificationsCount || 0);

// Computed pour déterminer le rôle de l'utilisateur
const userRoles = computed(() => {
    if (!user.value?.roles) return [];
    return user.value.roles.map((role: any) => role.name);
});

const isParent = computed(() => userRoles.value.includes('parent'));
const isBabysitter = computed(() => userRoles.value.includes('babysitter'));

// Computed pour le bouton d'action principal
const primaryButton = computed(() => {
    // Si l'utilisateur est babysitter uniquement ou principalement babysitter
    if (isBabysitter.value && !isParent.value) {
        return {
            text: 'Trouver un babysitting',
            href: route('announcements.index'),
            title: 'Voir les annonces disponibles',
        };
    }
    // Pour les parents ou utilisateurs avec les deux rôles (par défaut parent)
    return {
        text: 'Créer une annonce',
        href: route('creer.une.annonce'),
        title: 'Publier une nouvelle annonce',
    };
});

// État pour les notifications
const showNotifications = ref(false);
const unreadNotifications = ref([...globalUnreadNotifications.value]);
const unreadNotificationsCount = ref(globalUnreadNotificationsCount.value);

// Watcher pour mettre à jour les refs quand les props globales changent
watch(globalUnreadNotifications, (newNotifications) => {
    unreadNotifications.value = [...newNotifications];
});

watch(globalUnreadNotificationsCount, (newCount) => {
    unreadNotificationsCount.value = newCount;
});

const navLinks = [
    { name: 'Accueil', href: '/' },
    { name: 'Comment ça marche', href: '/comment-ca-marche' },
    { name: 'Devenir babysitter', href: '/devenir-babysitter' },
];

const authNavLinks = [
    { name: 'Accueil', href: '/' },
    { name: 'Comment ça marche', href: '/comment-ca-marche' },
    { name: 'Annonces', href: '/annonces' },
];

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
            return MessageSquare;
        case 'review_request':
            return Star;
        case 'funds_released':
            return DollarSign;
        case 'dispute_created':
            return AlertTriangle;
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
</script>

<template>
    <header class="sticky top-0 z-30 w-full bg-white/90 py-5 shadow-sm">
        <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4">
            <!-- Logo -->
            <div class="flex flex-col items-center text-center md:items-start md:text-left">
                <Link href="/" class="inline-block">
                    <img src="/storage/trouve-ta-babysitter-logo.svg" alt="Trouve ta Babysitter logo" class="h-15 w-auto" />
                </Link>
            </div>

            <!-- Desktop nav -->
            <div class="hidden items-center gap-6 md:flex">
                <template v-if="user">
                    <template v-for="link in authNavLinks" :key="link.name">
                        <Link :href="link.href" class="hover:text-primary text-base font-medium text-gray-700 transition-colors">
                            {{ link.name }}
                        </Link>
                    </template>

                    <!-- Messagerie - icône seule -->
                    <Link
                        :href="route('messaging.index')"
                        class="hover:text-primary flex items-center gap-2 text-base font-medium text-gray-700 transition-colors"
                        title="Messagerie"
                    >
                        <MessageSquare class="h-5 w-5" />
                    </Link>

                    <!-- Notifications - icône seule avec dropdown -->
                    <div class="notifications-dropdown relative">
                        <button
                            @click="toggleNotifications"
                            class="relative rounded-full p-2 transition-colors hover:bg-gray-100"
                            title="Notifications"
                        >
                            <Bell class="h-5 w-5 text-gray-700" />
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
                                    <button
                                        v-if="unreadNotificationsCount > 0"
                                        @click="markAllAsRead"
                                        class="text-primary hover:text-primary/80 text-sm"
                                    >
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

                    <!-- Avatar cliquable vers dashboard -->
                    <div class="ml-4 flex items-center gap-4">
                        <Link :href="route('dashboard')" class="flex items-center gap-2" title="Mon dashboard">
                            <img
                                :src="user.avatar || '/storage/default-avatar.png'"
                                :alt="`${user.firstname} ${user.lastname}`"
                                class="h-8 w-8 rounded-full object-cover"
                            />
                            <span class="text-sm font-medium text-gray-700">{{ user.firstname }}</span>
                        </Link>
                    </div>
                </template>
                <template v-else>
                    <template v-for="link in navLinks" :key="link.name">
                        <Link :href="link.href" class="hover:text-primary text-base font-medium text-gray-700 transition-colors">
                            {{ link.name }}
                        </Link>
                    </template>
                    <Link href="/connexion" class="hover:text-primary text-base font-medium text-gray-700 transition-colors"> Connexion </Link>
                    <Link href="/inscription" class="hover:text-primary text-base font-medium text-gray-700 transition-colors"> Inscription </Link>
                </template>
                <Button as-child class="bg-primary hover:bg-primary ml-2 font-semibold text-white" :title="primaryButton.title">
                    <Link :href="primaryButton.href">{{ primaryButton.text }}</Link>
                </Button>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center md:hidden">
                <Sheet>
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon">
                            <Menu class="text-primary h-6 w-6" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-64 p-0">
                        <SheetHeader class="border-b p-4">
                            <SheetTitle>
                                <Link href="/" class="inline-block">
                                    <img src="/storage/trouve-ta-babysitter-logo.svg" alt="Trouve ta Babysitter logo" class="h-15 w-auto" />
                                </Link>
                            </SheetTitle>
                        </SheetHeader>
                        <div class="flex flex-col gap-2 p-4">
                            <template v-if="user">
                                <template v-for="link in authNavLinks" :key="link.name">
                                    <Link :href="link.href" class="hover:text-primary py-2 text-base font-medium text-gray-700 transition-colors">
                                        {{ link.name }}
                                    </Link>
                                </template>
                                <Link
                                    :href="route('messaging.index')"
                                    class="hover:text-primary flex items-center gap-2 py-2 text-base font-medium text-gray-700 transition-colors"
                                >
                                    <MessageSquare class="h-5 w-5" />
                                    Messagerie
                                </Link>
                                <Link :href="route('dashboard')" class="flex items-center gap-2 py-2">
                                    <img
                                        :src="user.avatar || '/storage/default-avatar.png'"
                                        :alt="`${user.firstname} ${user.lastname}`"
                                        class="h-8 w-8 rounded-full object-cover"
                                    />
                                    <span class="text-sm font-medium text-gray-700">{{ user.firstname }}</span>
                                </Link>
                            </template>
                            <template v-else>
                                <template v-for="link in navLinks" :key="link.name">
                                    <Link :href="link.href" class="hover:text-primary py-2 text-base font-medium text-gray-700 transition-colors">
                                        {{ link.name }}
                                    </Link>
                                </template>
                                <Link href="/connexion" class="hover:text-primary py-2 text-base font-medium text-gray-700 transition-colors">
                                    Connexion
                                </Link>
                                <Link href="/inscription" class="hover:text-primary py-2 text-base font-medium text-gray-700 transition-colors">
                                    Inscription
                                </Link>
                            </template>
                            <Button as-child class="bg-primary hover:bg-primary mt-4 font-semibold text-white" :title="primaryButton.title">
                                <Link :href="primaryButton.href">{{ primaryButton.text }}</Link>
                            </Button>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </nav>
    </header>
</template>
