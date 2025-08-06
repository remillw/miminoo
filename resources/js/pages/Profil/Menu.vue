<template>
    <DashboardLayout :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole">
        <div class="min-h-screen bg-gray-50">
            <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                <!-- En-tête avec avatar et nom -->
                <div class="mb-8 rounded-2xl bg-white p-6 shadow-lg">
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <img
                                :src="user.avatar || '/default-avatar.png'"
                                :alt="user.firstname"
                                class="h-20 w-20 rounded-full border-2 border-orange-200 object-cover cursor-pointer hover:opacity-80 transition-opacity"
                                @click="showAvatarSelector = true"
                            />
                            <!-- Indicateur que l'avatar est cliquable -->
                            <div class="absolute -bottom-1 -right-1 bg-orange-500 rounded-full p-1.5">
                                <Camera class="h-3 w-3 text-white" />
                            </div>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ user.firstname }} {{ user.lastname }}</h1>
                            <p class="text-gray-600">{{ user.email }}</p>
                            <div class="mt-2 flex space-x-2">
                                <span v-if="hasParentRole" class="rounded-full bg-pink-100 px-3 py-1 text-xs font-medium text-pink-800">
                                    Parent
                                </span>
                                <span v-if="hasBabysitterRole" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                                    Babysitter
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Menu de navigation -->
                <div class="space-y-4">
                    <!-- Section Profil -->
                    <div class="rounded-2xl bg-white p-6 shadow-lg">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">Mon compte</h2>
                        <div class="space-y-3">
                            <a href="/profil" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <Settings class="mr-3 h-5 w-5 text-orange-500" />
                                    <span class="font-medium">Modifier mon profil</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                            <a href="/settings" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <Shield class="mr-3 h-5 w-5 text-orange-500" />
                                    <span class="font-medium">Paramètres</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                        </div>
                    </div>

                    <!-- Section Parent -->
                    <div v-if="hasParentRole" class="rounded-2xl bg-white p-6 shadow-lg">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">Espace Parent</h2>
                        <div class="space-y-3">
                            <a href="/parent/announcements-reservations" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <Calendar class="mr-3 h-5 w-5 text-pink-500" />
                                    <span class="font-medium">Mes annonces & réservations</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                            <a href="/create-announcement" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <Plus class="mr-3 h-5 w-5 text-pink-500" />
                                    <span class="font-medium">Créer une annonce</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                            <a href="/messaging" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <MessageCircle class="mr-3 h-5 w-5 text-pink-500" />
                                    <span class="font-medium">Messagerie</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                        </div>
                    </div>

                    <!-- Section Babysitter -->
                    <div v-if="hasBabysitterRole" class="rounded-2xl bg-white p-6 shadow-lg">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">Espace Babysitter</h2>
                        <div class="space-y-3">
                            <a href="/babysitting" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <Briefcase class="mr-3 h-5 w-5 text-blue-500" />
                                    <span class="font-medium">Mes candidatures</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                            <a href="/annonces" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <Search class="mr-3 h-5 w-5 text-blue-500" />
                                    <span class="font-medium">Chercher des gardes</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                            <a href="/babysitter/payments" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <CreditCard class="mr-3 h-5 w-5 text-blue-500" />
                                    <span class="font-medium">Mes paiements</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                        </div>
                    </div>

                    <!-- Section Aide -->
                    <div class="rounded-2xl bg-white p-6 shadow-lg">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">Aide & Support</h2>
                        <div class="space-y-3">
                            <a href="/comment-ca-marche" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <BookOpen class="mr-3 h-5 w-5 text-green-500" />
                                    <span class="font-medium">Comment ça marche</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                            <a href="/contact" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <Phone class="mr-3 h-5 w-5 text-green-500" />
                                    <span class="font-medium">Support client</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                        </div>
                    </div>

                    <!-- Section Déconnexion -->
                    <div class="rounded-2xl bg-white p-6 shadow-lg">
                        <button 
                            @click="logout" 
                            class="flex w-full items-center justify-between rounded-lg p-3 text-red-600 hover:bg-red-50"
                        >
                            <div class="flex items-center">
                                <LogOut class="mr-3 h-5 w-5" />
                                <span class="font-medium">Se déconnecter</span>
                            </div>
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal de sélection d'avatar -->
            <div
                v-if="showAvatarSelector"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                @click="showAvatarSelector = false"
            >
                <div
                    class="mx-4 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
                    @click.stop
                >
                    <h2 class="mb-4 text-xl font-bold text-gray-900 text-center">Choisissez votre avatar</h2>
                    
                    <!-- Bouton pour uploader une photo personnalisée -->
                    <div class="mb-4">
                        <button
                            @click="uploadCustomAvatar"
                            class="w-full flex items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 py-3 text-white font-medium hover:bg-orange-600 transition-colors"
                        >
                            <Camera class="h-5 w-5" />
                            {{ isMobileApp() ? 'Prendre une photo / Galerie' : 'Choisir une photo' }}
                        </button>
                    </div>
                    
                    <div class="relative mb-4">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300" />
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="bg-white px-2 text-gray-500">ou choisissez un avatar</span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div
                            v-for="avatar in availableAvatars"
                            :key="avatar"
                            @click="selectAvatar(avatar)"
                            class="cursor-pointer rounded-xl border-2 border-gray-200 p-4 hover:border-orange-500 hover:shadow-md transition-all"
                        >
                            <img
                                :src="avatar"
                                :alt="'Avatar'"
                                class="h-16 w-16 mx-auto rounded-full object-cover"
                            />
                        </div>
                    </div>
                    
                    <button
                        @click="showAvatarSelector = false"
                        class="w-full rounded-lg bg-gray-100 px-4 py-2 text-gray-700 font-medium hover:bg-gray-200 transition-colors"
                    >
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Briefcase,
    Calendar,
    ChevronRight,
    CreditCard,
    LogOut,
    MessageCircle,
    Phone,
    Plus,
    Search,
    Settings,
    Shield,
    Camera,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useDeviceToken } from '@/composables/useDeviceToken';
import { useToast } from '@/composables/useToast';

const page = usePage();
const { isMobileApp } = useDeviceToken();
const { showSuccess, showError } = useToast();

// Récupérer les informations utilisateur
const user = computed(() => (page.props.auth as any)?.user);
const userRoles = computed(() => user.value?.roles?.map((role: any) => role.name) || []);
const hasParentRole = computed(() => userRoles.value.includes('parent'));
const hasBabysitterRole = computed(() => userRoles.value.includes('babysitter'));

// États pour la sélection d'avatar
const showAvatarSelector = ref(false);
const availableAvatars = computed(() => {
    const avatars = [];
    
    // Avatars selon les rôles
    if (hasParentRole.value && hasBabysitterRole.value) {
        // Les deux rôles
        for (let i = 1; i <= 4; i++) {
            avatars.push(`/storage/avatars/les deux/parent-babysitter-generique${i}.svg`);
        }
    } else if (hasParentRole.value) {
        // Parent uniquement
        for (let i = 1; i <= 4; i++) {
            avatars.push(`/storage/avatars/parent/parent-generique${i}.svg`);
        }
    } else if (hasBabysitterRole.value) {
        // Babysitter uniquement
        for (let i = 1; i <= 4; i++) {
            avatars.push(`/storage/avatars/babysitter/babysitters-generique${i}.svg`);
        }
    }
    
    return avatars;
});

const selectAvatar = (avatarPath: string) => {
    showAvatarSelector.value = false;
    
    // Utiliser router.post pour un fonctionnement natif avec les applications mobiles
    router.post('/profil/update-avatar', {
        avatar: avatarPath
    }, {
        onSuccess: () => {
            showSuccess('Avatar mis à jour !', 'Votre avatar a été modifié avec succès');
        },
        onError: () => {
            showError('Erreur', 'Impossible de mettre à jour votre avatar');
        }
    });
};

const uploadCustomAvatar = () => {
    if (isMobileApp()) {
        // Utiliser l'image picker natif sur mobile
        if ((window as any).requestNativeImagePicker && (window as any).requestNativeImagePicker()) {
            // Écouter l'événement de sélection d'image
            const handleNativeImageSelection = (event: CustomEvent) => {
                const imageData = event.detail;
                console.log('Image sélectionnée:', imageData);
                
                // Fermer le modal et uploader l'image
                showAvatarSelector.value = false;
                
                router.post('/profil/update-avatar', {
                    avatar: imageData.base64
                }, {
                    onSuccess: () => {
                        showSuccess('Avatar mis à jour !', 'Votre photo a été uploadée avec succès');
                    },
                    onError: () => {
                        showError('Erreur', 'Impossible de mettre à jour votre avatar');
                    }
                });
                
                // Supprimer le listener après utilisation
                window.removeEventListener('nativeImageSelected', handleNativeImageSelection);
            };
            
            // Ajouter le listener pour l'événement
            window.addEventListener('nativeImageSelected', handleNativeImageSelection);
        } else {
            showError('Erreur', 'L\'accès natif aux photos n\'est pas disponible');
        }
    } else {
        // Utiliser l'input file classique sur le web
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = false;
        
        input.onchange = (event: any) => {
            const file = event.target.files[0];
            if (file) {
                // Vérifier la taille du fichier (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showError('Fichier trop volumineux', 'Veuillez choisir une image de moins de 5 MB');
                    return;
                }
                
                // Vérifier le type de fichier
                if (!file.type.startsWith('image/')) {
                    showError('Format invalide', 'Veuillez choisir un fichier image');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = (e: any) => {
                    const base64String = e.target.result;
                    
                    // Fermer le modal et uploader l'image
                    showAvatarSelector.value = false;
                    
                    router.post('/profil/update-avatar', {
                        avatar: base64String
                    }, {
                        onSuccess: () => {
                            showSuccess('Avatar mis à jour !', 'Votre photo a été uploadée avec succès');
                        },
                        onError: () => {
                            showError('Erreur', 'Impossible de mettre à jour votre avatar');
                        }
                    });
                };
                reader.readAsDataURL(file);
            }
        };
        
        input.click();
    }
};

const logout = () => {
    router.post('/logout');
};
</script>