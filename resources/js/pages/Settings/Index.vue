<template>
    <DashboardLayout :currentMode="currentMode">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Debug temporaire -->
        

            <!-- En-tête -->
            <div class="bg-white rounded-lg shadow p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Paramètres</h1>
                <p class="text-gray-600">Gérez vos préférences et paramètres de compte</p>
            </div>

            <!-- Notifications -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Notifications</h2>
                    <p class="text-sm text-gray-600 mt-1">Choisissez comment vous souhaitez être notifié</p>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">Notifications par email</h3>
                            <p class="text-sm text-gray-500">Recevez des notifications par email</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="notificationForm.email_notifications"
                                @change="updateNotifications"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">Notifications push</h3>
                            <p class="text-sm text-gray-500">Recevez des notifications push dans votre navigateur</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="notificationForm.push_notifications"
                                @change="updateNotifications"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">Notifications SMS</h3>
                            <p class="text-sm text-gray-500">Recevez des notifications par SMS</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="notificationForm.sms_notifications"
                                @change="updateNotifications"
                                class="sr-only peer"
                            />
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Sécurité -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Sécurité</h2>
                    <p class="text-sm text-gray-600 mt-1">Gérez la sécurité de votre compte</p>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Comptes sociaux -->
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-4">Comptes connectés</h3>
                        <div class="space-y-3">
                            <!-- Google -->
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5 text-red-500" viewBox="0 0 24 24">
                                        <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                        <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Google</p>
                                        <p class="text-xs text-gray-500">
                                            {{ user.google_id ? 'Connecté' : 'Non connecté' }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="user.google_id">
                                    <span v-if="isGoogleOnlyUser" 
                                          class="text-xs text-green-600 bg-green-100 px-3 py-1 rounded-full font-medium">
                                        Compte connecté
                                    </span>
                                    <button
                                        v-else
                                        @click="unlinkProvider('google')"
                                        class="text-sm text-red-600 hover:text-red-700"
                                    >
                                        Délier
                                    </button>
                                </div>
                                <a
                                    v-else
                                    :href="route('google.redirect')"
                                    class="text-sm text-blue-600 hover:text-blue-700"
                                >
                                    Connecter
                                </a>
                            </div>

                            <!-- Apple - Commenté pour l'instant -->
                            <!--
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Apple</p>
                                        <p class="text-xs text-gray-500">
                                            {{ user.apple_id ? 'Connecté' : 'Non connecté' }}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    v-if="user.apple_id"
                                    @click="unlinkProvider('apple')"
                                    :disabled="user.is_social_account && !user.password"
                                    class="text-sm text-red-600 hover:text-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Délier
                                </button>
                                <a
                                    v-else
                                    :href="route('social.redirect', 'apple')"
                                    class="text-sm text-blue-600 hover:text-blue-700"
                                >
                                    Connecter
                                </a>
                            </div>
                            -->
                        </div>
                        
                        <p v-if="user.is_social_account && !user.password" class="text-xs text-amber-600 mt-2">
                            ⚠️ Vous devez définir un mot de passe avant de pouvoir délier vos comptes sociaux
                        </p>
                        
                        <p v-if="user.is_social_account && user.provider === 'google' && !user.password" class="text-xs text-blue-600 mt-2">
                            ℹ️ Votre compte est géré par Google. Pour votre sécurité, vous ne pouvez pas vous déconnecter de Google. 
                            Vous pouvez seulement supprimer complètement votre compte si nécessaire.
                        </p>
                    </div>

                    <!-- Changement de mot de passe -->
                    <div v-if="!isGoogleOnlyUser">
                        <h3 class="text-sm font-medium text-gray-900 mb-4">Mot de passe</h3>
                        <form @submit.prevent="updatePassword" class="space-y-4">
                            <div v-if="user.password">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Mot de passe actuel
                                </label>
                                <input
                                    type="password"
                                    v-model="passwordForm.current_password"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    required
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ user.password ? 'Nouveau mot de passe' : 'Mot de passe' }}
                                </label>
                                <input
                                    type="password"
                                    v-model="passwordForm.password"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    required
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Confirmer le mot de passe
                                </label>
                                <input
                                    type="password"
                                    v-model="passwordForm.password_confirmation"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    required
                                />
                            </div>

                            <button
                                type="submit"
                                :disabled="isUpdatingPassword"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="isUpdatingPassword">Mise à jour...</span>
                                <span v-else>{{ user.password ? 'Mettre à jour le mot de passe' : 'Définir un mot de passe' }}</span>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Message pour les utilisateurs Google -->
                    <div v-if="isGoogleOnlyUser" class="text-center py-6">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-green-100 rounded-full mb-3">
                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-900 mb-1">
                            Authentification sécurisée par Google
                        </p>
                        <p class="text-xs text-gray-500">
                            Votre connexion est entièrement gérée par Google de manière sécurisée
                        </p>
                    </div>
                </div>
            </div>

            <!-- Préférences -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Préférences</h2>
                    <p class="text-sm text-gray-600 mt-1">Personnalisez votre expérience</p>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Langue
                        </label>
                        <select
                            v-model="languageForm.language"
                            @change="updateLanguage"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option
                                v-for="lang in availableLanguages"
                                :key="lang.code"
                                :value="lang.code"
                            >
                                {{ lang.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Suppression de compte -->
            <div class="bg-white rounded-lg shadow border-l-4 border-red-500">
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">Supprimer mon compte</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Cette action est irréversible. Toutes vos données seront définitivement supprimées.
                            </p>
                            <p v-if="hasActiveReservations" class="text-sm text-red-600 mt-2">
                                ⚠️ Vous avez des réservations en cours. Vous ne pouvez pas supprimer votre compte.
                            </p>
                        </div>
                        
                        <button
                            @click="showDeleteConfirmation = true"
                            :disabled="hasActiveReservations || isDeletingAccount"
                            class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="isDeletingAccount">Suppression...</span>
                            <span v-else>Supprimer mon compte</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal de confirmation de suppression -->
            <div v-if="showDeleteConfirmation" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirmer la suppression</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Êtes-vous absolument sûr de vouloir supprimer votre compte ? Cette action est irréversible.
                    </p>
                    <p class="text-sm text-gray-600 mb-6">
                        Tapez <strong>SUPPRIMER</strong> pour confirmer :
                    </p>
                    <input
                        type="text"
                        v-model="deleteConfirmationText"
                        placeholder="SUPPRIMER"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    />
                    <div class="flex gap-3">
                        <button
                            @click="showDeleteConfirmation = false; deleteConfirmationText = ''"
                            class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400"
                        >
                            Annuler
                        </button>
                        <button
                            @click="confirmDeleteAccount"
                            :disabled="deleteConfirmationText !== 'SUPPRIMER' || isDeletingAccount"
                            class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="isDeletingAccount">Suppression...</span>
                            <span v-else>Supprimer définitivement</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { useToast } from '@/composables/useToast';

// Types
interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    language: string;
    google_id?: string;
    apple_id?: string;
    is_social_account: boolean;
    password?: string;
    social_data_locked: boolean;
    provider?: string;
}

interface NotificationSettings {
    email_notifications: boolean;
    push_notifications: boolean;
    sms_notifications: boolean;
}

interface Language {
    code: string;
    name: string;
}

interface Props {
    user: User;
    current_mode: 'babysitter' | 'parent';
    notification_settings: NotificationSettings;
    has_active_reservations: boolean;
    available_languages: Language[];
    debug_user_data?: any; // Debug temporaire
}

const props = defineProps<Props>();

// Composables
const { showSuccess, showError, handleApiResponse } = useToast();

// Mode basé sur les rôles de l'utilisateur (détecté côté serveur)
const currentMode = ref<'babysitter' | 'parent'>(props.current_mode);

// Computed pour détecter si c'est un utilisateur Google uniquement
const isGoogleOnlyUser = computed(() => {
    // Si l'utilisateur a un google_id, c'est un utilisateur Google
    return props.user.google_id && !props.user.password;
});

// Debug pour vérifier les données utilisateur
console.log('🔍 Données utilisateur Settings:', {
    provider: props.user.provider,
    is_social_account: props.user.is_social_account,
    social_data_locked: props.user.social_data_locked,
    has_password: props.user.password,
    google_id: props.user.google_id,
    isGoogleOnlyUser: isGoogleOnlyUser.value,
    debug_from_server: props.debug_user_data
});

// États des formulaires
const notificationForm = reactive({ ...props.notification_settings });
const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: ''
});
const languageForm = reactive({
    language: props.user.language
});

// États de chargement
const isUpdatingPassword = ref(false);
const isDeletingAccount = ref(false);
const showDeleteConfirmation = ref(false);
const deleteConfirmationText = ref('');

// Computed
const hasActiveReservations = ref(props.has_active_reservations);
const availableLanguages = ref(props.available_languages);

// Méthodes
const updateNotifications = () => {
    router.post(route('settings.notifications'), notificationForm, {
        preserveState: true,
        onSuccess: (page: any) => {
            handleApiResponse(page, 'Préférences de notifications mises à jour');
        },
        onError: (errors: any) => {
            console.error('❌ Erreur mise à jour notifications:', errors);
            showError('Erreur', 'Impossible de mettre à jour les préférences de notifications');
        }
    });
};

const updatePassword = () => {
    isUpdatingPassword.value = true;
    
    router.post(route('settings.password'), passwordForm, {
        preserveState: true,
        onSuccess: (page: any) => {
            // Réinitialiser le formulaire
            passwordForm.current_password = '';
            passwordForm.password = '';
            passwordForm.password_confirmation = '';
            isUpdatingPassword.value = false;
            handleApiResponse(page, 'Mot de passe mis à jour avec succès');
        },
        onError: (errors: any) => {
            console.error('❌ Erreur mise à jour mot de passe:', errors);
            isUpdatingPassword.value = false;
            showError('Erreur', 'Impossible de mettre à jour le mot de passe');
        }
    });
};

const updateLanguage = () => {
    router.post(route('settings.language'), languageForm, {
        preserveState: true,
        onSuccess: (page: any) => {
            handleApiResponse(page, 'Langue mise à jour avec succès');
        },
        onError: (errors: any) => {
            console.error('❌ Erreur mise à jour langue:', errors);
            showError('Erreur', 'Impossible de mettre à jour la langue');
        }
    });
};

const unlinkProvider = (provider: string) => {
    router.delete(route('social.unlink', provider), {
        preserveState: true,
        onSuccess: (page: any) => {
            handleApiResponse(page, `Compte ${provider} délié avec succès`);
            // Rafraîchir la page pour mettre à jour les données
            setTimeout(() => window.location.reload(), 1000);
        },
        onError: (errors: any) => {
            console.error('❌ Erreur suppression compte social:', errors);
            showError('Erreur', `Impossible de délier le compte ${provider}`);
        }
    });
};

const confirmDeleteAccount = () => {
    if (deleteConfirmationText.value !== 'SUPPRIMER') {
        showError('Confirmation requise', 'Veuillez taper "SUPPRIMER" pour confirmer');
        return;
    }
    
    isDeletingAccount.value = true;
    
    router.delete(route('settings.delete-account'), {
        data: {
            confirmation: deleteConfirmationText.value
        },
        onSuccess: () => {
            showSuccess('Compte supprimé', 'Votre compte a été supprimé avec succès');
            // L'utilisateur sera redirigé vers la page d'accueil
        },
        onError: (errors: any) => {
            console.error('❌ Erreur suppression compte:', errors);
            isDeletingAccount.value = false;
            showDeleteConfirmation.value = false;
            deleteConfirmationText.value = '';
            showError('Erreur', 'Impossible de supprimer le compte');
        }
    });
};
</script> 