<template>
    <DashboardLayout :currentMode="currentMode" :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole">
        <div class="min-h-screen bg-gray-50 -m-3 -mt-4 sm:-m-4 sm:-mt-6 lg:m-0 lg:mt-0 lg:bg-white">
            <!-- Header avec photo de profil -->
            <div class="relative bg-gradient-to-br from-primary to-primary/80 text-white">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative px-4 pt-8 pb-20 sm:px-6 lg:px-8">
                    <div class="flex flex-col items-center text-center">
                        <!-- Photo de profil -->
                        <div class="relative mb-4">
                            <div class="relative h-20 w-20 sm:h-24 sm:w-24">
                                <img 
                                    v-if="user.profile_photo_url" 
                                    :src="user.profile_photo_url" 
                                    :alt="user.firstname" 
                                    class="h-full w-full rounded-full object-cover ring-4 ring-white/20"
                                >
                                <div 
                                    v-else 
                                    class="flex h-full w-full items-center justify-center rounded-full bg-white/20 ring-4 ring-white/20"
                                >
                                    <User class="h-8 w-8 text-white/70" />
                                </div>
                                
                                <!-- Bouton upload mobile -->
                                <button 
                                    @click="triggerPhotoUpload"
                                    class="absolute -bottom-1 -right-1 flex h-7 w-7 items-center justify-center rounded-full bg-white text-gray-600 shadow-lg transition-colors hover:bg-gray-50"
                                >
                                    <Camera class="h-4 w-4" />
                                </button>
                                
                                <!-- Input file caché -->
                                <input 
                                    ref="fileInput"
                                    type="file" 
                                    accept="image/*" 
                                    capture="environment"
                                    @change="handlePhotoUpload" 
                                    class="hidden"
                                >
                            </div>
                        </div>
                        
                        <div>
                            <h1 class="text-xl font-bold sm:text-2xl">{{ user.firstname }} {{ user.lastname }}</h1>
                            <p class="text-sm text-white/80 sm:text-base">{{ user.email }}</p>
                            <div class="mt-2 inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs font-medium">
                                <component :is="currentMode === 'parent' ? Users : Baby" class="mr-1 h-3 w-3" />
                                {{ currentMode === 'parent' ? 'Parent' : 'Babysitter' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative -mt-12 space-y-4 px-4 pb-20 sm:px-6 lg:px-8">
                <!-- Notifications Card -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                    <div class="border-l-4 border-primary bg-primary/5 p-4">
                        <div class="flex items-center">
                            <Bell class="mr-3 h-5 w-5 text-primary" />
                            <div>
                                <h2 class="font-semibold text-gray-900">Notifications</h2>
                                <p class="text-sm text-gray-600">Gérez vos préférences de notification</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 space-y-4">
                        <!-- Email -->
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3">
                            <div class="flex items-center">
                                <Mail class="mr-3 h-5 w-5 text-gray-500" />
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900">Email</h3>
                                    <p class="text-xs text-gray-500">Messages et réservations</p>
                                </div>
                            </div>
                            <Switch 
                                v-model:checked="notificationForm.email_notifications" 
                                @update:checked="updateNotifications"
                            />
                        </div>
                        
                        <!-- Push -->
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3">
                            <div class="flex items-center">
                                <Smartphone class="mr-3 h-5 w-5 text-gray-500" />
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900">Notifications push</h3>
                                    <p class="text-xs text-gray-500">Alertes en temps réel</p>
                                </div>
                            </div>
                            <Switch 
                                v-model:checked="notificationForm.push_notifications" 
                                @update:checked="updateNotifications"
                            />
                        </div>
                        
                        <!-- SMS -->
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3">
                            <div class="flex items-center">
                                <MessageSquare class="mr-3 h-5 w-5 text-gray-500" />
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900">SMS</h3>
                                    <p class="text-xs text-gray-500">Alertes importantes</p>
                                </div>
                            </div>
                            <Switch 
                                v-model:checked="notificationForm.sms_notifications" 
                                @update:checked="updateNotifications"
                            />
                        </div>
                    </div>
                </div>

                <!-- Sécurité Card -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                    <div class="border-l-4 border-green-500 bg-green-50 p-4">
                        <div class="flex items-center">
                            <Shield class="mr-3 h-5 w-5 text-green-600" />
                            <div>
                                <h2 class="font-semibold text-gray-900">Sécurité</h2>
                                <p class="text-sm text-gray-600">Protégez votre compte</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 space-y-4">
                        <!-- Comptes sociaux -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Comptes connectés</h3>
                            <div class="space-y-2">
                                <!-- Google -->
                                <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100">
                                            <svg class="h-4 w-4 text-red-600" viewBox="0 0 24 24">
                                                <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                                <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                                <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                                <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Google</p>
                                            <p class="text-xs text-gray-500">
                                                {{ user.google_id ? 'Connecté' : 'Non connecté' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div v-if="user.google_id">
                                        <span v-if="isGoogleOnlyUser" 
                                              class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">
                                            Connecté
                                        </span>
                                        <Button
                                            v-else
                                            @click="unlinkProvider('google')"
                                            variant="ghost"
                                            size="sm"
                                            class="text-red-600 hover:text-red-700"
                                        >
                                            Délier
                                        </Button>
                                    </div>
                                    <Button
                                        v-else
                                        :as="Link"
                                        :href="route('google.redirect')"
                                        variant="outline"
                                        size="sm"
                                    >
                                        Connecter
                                    </Button>
                                </div>
                            </div>
                            
                            <div v-if="user.is_social_account && !user.password" class="mt-3 rounded-lg bg-amber-50 p-3">
                                <div class="flex items-start">
                                    <AlertTriangle class="mr-2 h-4 w-4 text-amber-600 mt-0.5" />
                                    <p class="text-xs text-amber-700">
                                        Définissez un mot de passe pour délier vos comptes sociaux
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Changement de mot de passe -->
                        <div v-if="!isGoogleOnlyUser">
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Mot de passe</h3>
                            <form @submit.prevent="updatePassword" class="space-y-3">
                                <div v-if="user.password" class="space-y-1">
                                    <Label for="current_password">Mot de passe actuel</Label>
                                    <Input
                                        id="current_password"
                                        type="password"
                                        v-model="passwordForm.current_password"
                                        required
                                    />
                                </div>

                                <div class="space-y-1">
                                    <Label for="new_password">
                                        {{ user.password ? 'Nouveau mot de passe' : 'Mot de passe' }}
                                    </Label>
                                    <Input
                                        id="new_password"
                                        type="password"
                                        v-model="passwordForm.password"
                                        required
                                    />
                                </div>

                                <div class="space-y-1">
                                    <Label for="confirm_password">Confirmer le mot de passe</Label>
                                    <Input
                                        id="confirm_password"
                                        type="password"
                                        v-model="passwordForm.password_confirmation"
                                        required
                                    />
                                </div>

                                <Button
                                    type="submit"
                                    :disabled="isUpdatingPassword"
                                    class="w-full"
                                >
                                    <Loader2 v-if="isUpdatingPassword" class="mr-2 h-4 w-4 animate-spin" />
                                    {{ isUpdatingPassword ? 'Mise à jour...' : (user.password ? 'Mettre à jour' : 'Définir') }}
                                </Button>
                            </form>
                        </div>
                        
                        <!-- Message pour les utilisateurs Google -->
                        <div v-if="isGoogleOnlyUser" class="text-center py-6">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100 mb-3">
                                <CheckCircle class="h-6 w-6 text-green-600" />
                            </div>
                            <p class="text-sm font-medium text-gray-900 mb-1">
                                Authentification sécurisée par Google
                            </p>
                            <p class="text-xs text-gray-500">
                                Votre connexion est gérée par Google
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Préférences Card -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                    <div class="border-l-4 border-purple-500 bg-purple-50 p-4">
                        <div class="flex items-center">
                            <Palette class="mr-3 h-5 w-5 text-purple-600" />
                            <div>
                                <h2 class="font-semibold text-gray-900">Préférences</h2>
                                <p class="text-sm text-gray-600">Personnalisez votre expérience</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="space-y-2">
                            <Label for="language">Langue</Label>
                            <Select v-model="languageForm.language" @update:modelValue="updateLanguage">
                                <SelectTrigger>
                                    <SelectValue placeholder="Sélectionner une langue" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem 
                                        v-for="lang in availableLanguages" 
                                        :key="lang.code" 
                                        :value="lang.code"
                                    >
                                        {{ lang.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <!-- Zone de danger -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm">
                    <div class="border-l-4 border-red-500 bg-red-50 p-4">
                        <div class="flex items-center">
                            <AlertTriangle class="mr-3 h-5 w-5 text-red-600" />
                            <div>
                                <h2 class="font-semibold text-gray-900">Zone de danger</h2>
                                <p class="text-sm text-gray-600">Actions irréversibles</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="space-y-3">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">Supprimer mon compte</h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    Cette action supprimera définitivement toutes vos données.
                                </p>
                                <div v-if="hasActiveReservations" class="mt-2 rounded-lg bg-red-50 p-2">
                                    <div class="flex items-start">
                                        <AlertTriangle class="mr-2 h-3 w-3 text-red-600 mt-0.5" />
                                        <p class="text-xs text-red-700">
                                            Impossible avec des réservations actives
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <Button
                                @click="showDeleteConfirmation = true"
                                :disabled="hasActiveReservations || isDeletingAccount"
                                variant="destructive"
                                size="sm"
                            >
                                <Trash2 class="mr-2 h-4 w-4" />
                                {{ isDeletingAccount ? 'Suppression...' : 'Supprimer mon compte' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de confirmation de suppression -->
        <Dialog v-model:open="showDeleteConfirmation">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center text-red-600">
                        <AlertTriangle class="mr-2 h-5 w-5" />
                        Confirmer la suppression
                    </DialogTitle>
                    <DialogDescription>
                        Cette action est irréversible. Toutes vos données seront définitivement supprimées.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="space-y-4">
                    <div>
                        <Label for="confirmation">Tapez <strong>SUPPRIMER</strong> pour confirmer</Label>
                        <Input
                            id="confirmation"
                            v-model="deleteConfirmationText"
                            placeholder="SUPPRIMER"
                            class="mt-1"
                        />
                    </div>
                </div>
                
                <DialogFooter class="gap-2">
                    <Button
                        @click="showDeleteConfirmation = false; deleteConfirmationText = ''"
                        variant="outline"
                    >
                        Annuler
                    </Button>
                    <Button
                        @click="confirmDeleteAccount"
                        :disabled="deleteConfirmationText !== 'SUPPRIMER' || isDeletingAccount"
                        variant="destructive"
                    >
                        <Loader2 v-if="isDeletingAccount" class="mr-2 h-4 w-4 animate-spin" />
                        {{ isDeletingAccount ? 'Suppression...' : 'Supprimer définitivement' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </DashboardLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { useToast } from '@/composables/useToast';

// UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

// Icons
import { 
    AlertTriangle, 
    Bell, 
    Camera, 
    CheckCircle, 
    Loader2, 
    Mail, 
    MessageSquare, 
    Palette, 
    Shield, 
    Smartphone, 
    Trash2, 
    User, 
    Users, 
    Baby 
} from 'lucide-vue-next';

// Types
import type { User } from '@/types';

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

// Computed pour les rôles
const hasParentRole = computed(() => props.user.roles?.some(role => role.name === 'parent') ?? false);
const hasBabysitterRole = computed(() => props.user.roles?.some(role => role.name === 'babysitter') ?? false);

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

// Upload de photo
const fileInput = ref<HTMLInputElement>();
const isUploadingPhoto = ref(false);

// États de chargement
const isUpdatingPassword = ref(false);
const isDeletingAccount = ref(false);
const showDeleteConfirmation = ref(false);
const deleteConfirmationText = ref('');

// Computed
const hasActiveReservations = ref(props.has_active_reservations);
const availableLanguages = ref(props.available_languages);

// Méthodes upload photo
const triggerPhotoUpload = () => {
    fileInput.value?.click();
};

const handlePhotoUpload = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (!file) return;
    
    // Vérifier le type de fichier
    if (!file.type.startsWith('image/')) {
        showError('Format invalide', 'Veuillez sélectionner une image.');
        return;
    }
    
    // Vérifier la taille (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        showError('Fichier trop volumineux', 'La taille maximale est de 5MB.');
        return;
    }
    
    isUploadingPhoto.value = true;
    
    try {
        const formData = new FormData();
        formData.append('profile_photo', file);
        
        // Utiliser fetch pour l'upload
        const response = await fetch(route('profile.photo'), {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showSuccess('Photo mise à jour', 'Votre photo de profil a été mise à jour.');
            // Recharger la page pour voir la nouvelle photo
            setTimeout(() => window.location.reload(), 1000);
        } else {
            throw new Error(data.message || 'Erreur lors de l\'upload');
        }
    } catch (error) {
        console.error('Erreur upload photo:', error);
        showError('Erreur upload', error instanceof Error ? error.message : 'Erreur lors de l\'upload de la photo');
    } finally {
        isUploadingPhoto.value = false;
        // Reset input
        if (target) target.value = '';
    }
};

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