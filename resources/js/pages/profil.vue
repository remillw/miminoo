<script setup lang="ts">
import BabysitterProfile from '@/components/BabysitterProfile.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import { useUserMode } from '@/composables/useUserMode';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    AlertCircle,
    Baby,
    Camera,
    CheckCircle,
    Clock,
    CreditCard,
    ExternalLink,
    Info,
    Mail,
    MapPin,
    Plus,
    Shield,
    Trash2,
    TrendingUp,
    Users,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { route } from 'ziggy-js';

interface Child {
    nom: string;
    age: string;
    unite: 'ans' | 'mois';
}

interface AddressData {
    address: string;
    postal_code: string;
    country: string;
    latitude: number;
    longitude: number;
    google_place_id: string;
}

interface Address extends AddressData {
    id?: number;
}

interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    date_of_birth?: string;
    avatar?: string;
    address?: Address;
    parentProfile?: {
        children_ages: Child[];
    };
    babysitterProfile?: {
        bio?: string;
        experience_years?: number;
        available_radius_km?: number;
        availability?: any;
        hourly_rate?: number;
        documents_verified?: boolean;
        languages?: Language[];
        skills?: Skill[];
        age_ranges?: AgeRange[];
        experiences?: any[];
        verification_status: 'pending' | 'verified' | 'rejected';
        rejection_reason?: string;
    };
    role: string;
    social_data_locked: boolean;
    provider: string;
    avatar_url?: string;
}

interface Language {
    id: number;
    name: string;
    code: string;
}

interface Skill {
    id: number;
    name: string;
    description?: string;
    category?: string;
}

interface AgeRange {
    id: number;
    name: string;
    min_age_months: number;
    max_age_months?: number;
    display_order: number;
}

interface Props {
    user: User;
    userRoles: string[];
    hasParentRole: boolean;
    hasBabysitterRole: boolean;
    requestedMode?: 'parent' | 'babysitter';
    children?: Child[];
    babysitterProfile?: {
        bio?: string;
        experience_years?: number;
        available_radius_km?: number;
        availability?: any;
        hourly_rate?: number;
        documents_verified?: boolean;
        languages?: Language[];
        skills?: Skill[];
        age_ranges?: AgeRange[];
        experiences?: any[];
        verification_status: 'pending' | 'verified' | 'rejected';
        rejection_reason?: string;
    };
    availableLanguages?: Language[];
    availableSkills?: Skill[];
    availableAgeRanges?: AgeRange[];
}

const props = defineProps<Props>();
const { showSuccess, showError } = useToast();
const { currentMode, initializeMode, setMode } = useUserMode();

// Variables réactives
const isEditing = ref(false);
const isLoading = ref(false);
const isGoogleLoaded = ref(false);
const isRequestingVerification = ref(false);
const avatarPreview = ref(''); // Pour l'aperçu de l'avatar
let autocomplete: any;

const babysitterProfileRef = ref();
const avatarInput = ref();

// Initialiser le mode au montage du composant
onMounted(() => {
    initializeMode(props.hasParentRole, props.hasBabysitterRole, props.requestedMode);

    // Debug: Vérifier toutes les props reçues
    console.log('🔍 Props reçues dans profil.vue:', {
        hasParentRole: props.hasParentRole,
        hasBabysitterRole: props.hasBabysitterRole,
        userHasBabysitterProfile: !!props.user.babysitterProfile,
        propsBabysitterProfile: !!props.babysitterProfile,
        userRoles: props.userRoles,
        user: props.user,
        babysitterProfile: props.babysitterProfile,
    });
});

// Computed pour vérifier si l'utilisateur a plusieurs rôles
const hasMultipleRoles = computed(() => {
    return props.hasParentRole && props.hasBabysitterRole;
});

// Fonction pour changer de mode
const switchMode = (mode: 'parent' | 'babysitter') => {
    if (mode === currentMode.value) return;

    // Mettre à jour le localStorage
    setMode(mode);

    // Rediriger vers la même page avec le paramètre mode
    router.get(
        route('profil', { mode }),
        {},
        {
            preserveState: false,
            preserveScroll: true,
        },
    );
};

// Formulaire
const form = ref({
    firstname: props.user.firstname || '',
    lastname: props.user.lastname || '',
    email: props.user.email || '',
    date_of_birth: props.user.date_of_birth || '',
    avatar: '', // Champ pour l'avatar en base64
    children: (props.children || []).map((child) => ({
        ...child,
        age: String(child.age), // S'assurer que l'âge est une string
    })),
    mode: currentMode.value,
});

// Watcher pour mettre à jour le mode dans le formulaire
watch(currentMode, (newMode) => {
    form.value.mode = newMode;
});

// Données d'adresse séparées
const addressData = ref<AddressData>({
    address: props.user.address?.address || '',
    postal_code: props.user.address?.postal_code || '',
    country: props.user.address?.country || '',
    latitude: props.user.address?.latitude || 0,
    longitude: props.user.address?.longitude || 0,
    google_place_id: props.user.address?.google_place_id || '',
});

// Debug : vérifier les données d'adresse au chargement
console.log("🏠 Données d'adresse initiales:", {
    userAddress: props.user.address,
    addressData: addressData.value,
});

// Fonctions pour gérer les enfants (seulement en mode parent)
const addChild = () => {
    if (currentMode.value === 'parent') {
        form.value.children.push({ nom: '', age: '2', unite: 'ans' });
    }
};

const removeChild = (index: number) => {
    if (currentMode.value === 'parent') {
        form.value.children.splice(index, 1);
    }
};

const toggleEdit = () => {
    isEditing.value = !isEditing.value;

    // Mettre à jour le mode d'édition dans BabysitterProfile
    if (babysitterProfileRef.value) {
        babysitterProfileRef.value.isEditing = isEditing.value;
    }

    // Charger Google Places quand on passe en mode édition
    if (isEditing.value && !isGoogleLoaded.value) {
        loadGooglePlaces();
    } else if (isEditing.value && isGoogleLoaded.value) {
        // Réinitialiser l'autocomplete
        nextTick(() => {
            setTimeout(() => {
                initAutocomplete();
            }, 100);
        });
    }
};

// Charger l'API Google Places
const loadGooglePlaces = () => {
    console.log('🚀 Chargement Google Places API...');

    if (window.google?.maps?.places) {
        console.log('✅ Google Places déjà chargé');
        initAutocomplete();
        return;
    }

    const apiKey = import.meta.env.VITE_GOOGLE_PLACES_API_KEY;

    if (!apiKey) {
        console.error('❌ Clé API Google Places manquante');
        return;
    }

    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=initGooglePlacesCallback`;
    script.async = true;
    script.defer = true;

    (window as any).initGooglePlacesCallback = () => {
        console.log('✅ Google Places API chargée');
        isGoogleLoaded.value = true;
        setTimeout(() => {
            initAutocomplete();
        }, 100);
    };

    script.onerror = () => {
        console.error('❌ Erreur chargement Google Places API');
    };

    document.head.appendChild(script);
};

// Initialiser l'autocomplétion sur le champ adresse
const initAutocomplete = async () => {
    console.log('🔍 Initialisation autocomplete...');

    await nextTick();

    const input = document.getElementById('address-input') as HTMLInputElement;

    if (!input) {
        console.error('❌ Input adresse non trouvé');
        return;
    }

    if (!window.google?.maps?.places) {
        console.error('❌ Google Places API non disponible');
        return;
    }

    try {
        autocomplete = new window.google.maps.places.Autocomplete(input, {
            types: ['address'],
        });

        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();

            if (place.formatted_address) {
                // Mettre à jour l'adresse
                addressData.value.address = place.formatted_address;

                // Réinitialiser tous les champs
                addressData.value.postal_code = '';
                addressData.value.country = '';
                addressData.value.latitude = 0;
                addressData.value.longitude = 0;

                // Extraire les coordonnées GPS
                if (place.geometry?.location) {
                    addressData.value.latitude = place.geometry.location.lat();
                    addressData.value.longitude = place.geometry.location.lng();
                }

                // Extraire le code postal et le pays depuis les composants d'adresse
                if (place.address_components) {
                    place.address_components.forEach((component: any) => {
                        const types = component.types;

                        if (types.includes('postal_code')) {
                            addressData.value.postal_code = component.long_name;
                        }
                        if (types.includes('country')) {
                            addressData.value.country = component.long_name;
                        }
                    });
                }

                // Valeurs par défaut si manquantes (requis par le backend)
                if (!addressData.value.postal_code) {
                    addressData.value.postal_code = '00000';
                }
                if (!addressData.value.country) {
                    addressData.value.country = 'France';
                }

                console.log('✅ Adresse complète récupérée:', {
                    address: addressData.value.address,
                    postal_code: addressData.value.postal_code,
                    country: addressData.value.country,
                    coordinates: [addressData.value.latitude, addressData.value.longitude],
                });
            }
        });

        console.log('✅ Autocomplete initialisé');
    } catch (error) {
        console.error('❌ Erreur autocomplete:', error);
    }
};

// Gérer la saisie manuelle d'adresse
const onAddressChange = () => {
    // Si l'utilisateur tape manuellement, s'assurer qu'on a des valeurs par défaut
    if (addressData.value.address && (!addressData.value.postal_code || !addressData.value.country)) {
        if (!addressData.value.postal_code) {
            addressData.value.postal_code = '00000';
        }
        if (!addressData.value.country) {
            addressData.value.country = 'France';
        }
        // Coordonnées par défaut pour Paris si saisie manuelle
        if (!addressData.value.latitude || !addressData.value.longitude) {
            addressData.value.latitude = 48.8566; // Paris
            addressData.value.longitude = 2.3522; // Paris
        }
        console.log('📝 Adresse manuelle avec valeurs par défaut:', addressData.value);
    }
};

const submitForm = async () => {
    isLoading.value = true;

    // S'assurer qu'on a des valeurs par défaut pour les champs requis par le backend
    if (addressData.value.address && !addressData.value.postal_code) {
        addressData.value.postal_code = '00000';
    }
    if (addressData.value.address && !addressData.value.country) {
        addressData.value.country = 'France';
    }
    // S'assurer qu'on a des coordonnées par défaut
    if (addressData.value.address && (!addressData.value.latitude || !addressData.value.longitude)) {
        addressData.value.latitude = 48.8566; // Paris par défaut
        addressData.value.longitude = 2.3522; // Paris par défaut
    }

    try {
        // Préparer les données à envoyer
        const formData = { ...form.value };
        
        // Si les données sociales sont verrouillées, ne pas envoyer les champs protégés
        if (props.user.social_data_locked) {
            delete formData.firstname;
            delete formData.lastname;
            delete formData.email;
        }
        
        // Ajouter les données d'adresse
        Object.assign(formData, addressData.value);

        // Ajouter les données du profil babysitter si on est en mode babysitter
        if (currentMode.value === 'babysitter' && babysitterProfileRef.value) {
            const babysitterData = babysitterProfileRef.value.getFormData();
            Object.assign(formData, babysitterData);
        }

        console.log('📤 Données envoyées:', formData);

        await router.post(route('profil.update'), formData, {
            preserveState: false,
            onSuccess: () => {
                showSuccess('Profil mis à jour avec succès !');
                isEditing.value = false;
                avatarPreview.value = ''; // Réinitialiser l'aperçu
            },
            onError: (errors) => {
                console.error('❌ Erreurs de validation:', errors);
                showError('Erreur lors de la mise à jour du profil');
            },
        });
    } catch (error) {
        console.error('❌ Erreur:', error);
        showError('Une erreur est survenue');
    } finally {
        isLoading.value = false;
    }
};

// Données calculées
// Computed pour récupérer le profil babysitter depuis les bonnes props
const babysitterProfile = computed(() => {
    return props.user.babysitterProfile || props.babysitterProfile;
});

const fullName = computed(() => `${props.user.firstname} ${props.user.lastname}`);
const userInfo = computed(() => {
    if (currentMode.value === 'parent') {
        const childCount = props.children?.length || 0;
        return `Parent de ${childCount} enfant${childCount > 1 ? 's' : ''}`;
    }
    return 'Babysitter';
});

const verificationStatus = computed(() => {
    // Essayer d'abord props.user.babysitterProfile, puis props.babysitterProfile
    const babysitterProfile = props.user.babysitterProfile || props.babysitterProfile;

    if (!babysitterProfile) {
        console.log('🔍 Vérification statut: pas de profil babysitter trouvé', {
            userProfile: props.user.babysitterProfile,
            propsProfile: props.babysitterProfile,
            userRoles: props.userRoles,
            hasBabysitterRole: props.hasBabysitterRole,
        });
        return null;
    }

    const status = babysitterProfile.verification_status;
    console.log('🔍 Statut de vérification:', {
        status: status || 'null',
        statusType: typeof status,
        profile: babysitterProfile,
        source: props.user.babysitterProfile ? 'user.babysitterProfile' : 'props.babysitterProfile',
    });

    return status;
});

const verificationStatusText = computed(() => {
    switch (verificationStatus.value) {
        case 'pending':
            return 'En attente de vérification';
        case 'verified':
            return 'Profil vérifié';
        case 'rejected':
            return 'Profil rejeté';
        case null:
        case undefined:
            return 'Vérification non demandée';
        default:
            return 'Statut inconnu';
    }
});

const verificationStatusColor = computed(() => {
    switch (verificationStatus.value) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'verified':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        default:
            return '';
    }
});

const requestVerification = async () => {
    // Protection contre les clics multiples
    if (isRequestingVerification.value) {
        console.log('⚠️ Demande déjà en cours, ignorer...');
        return;
    }

    // Protection supplémentaire contre les statuts déjà en cours ou terminés
    if (verificationStatus.value === 'pending') {
        console.log('⚠️ Statut déjà pending, ignorer...');
        showError('Une demande de vérification est déjà en cours');
        return;
    }

    if (verificationStatus.value === 'verified') {
        console.log('⚠️ Profil déjà vérifié, ignorer...');
        showError('Votre profil est déjà vérifié');
        return;
    }

    isRequestingVerification.value = true;

    console.log('🚀 Demande de vérification - Début');
    console.log('📋 Statut actuel avant demande:', verificationStatus.value);

    try {
        const response = await axios.post('/babysitter/request-verification');
        console.log('✅ Réponse serveur:', response.data);
        showSuccess(response.data.message);

        // Force la mise à jour du statut localement IMMÉDIATEMENT
        if (babysitterProfile.value) {
            babysitterProfile.value.verification_status = 'pending';
        }

        console.log('📋 Statut forcé à pending localement');

        // Rafraîchir la page après un court délai pour synchroniser avec le serveur
        setTimeout(() => {
            router.reload();
        }, 1500);
    } catch (error: any) {
        console.error('❌ Erreur demande vérification:', error);

        if (error.response?.status === 400 && error.response?.data?.message) {
            // Erreur métier (déjà en cours, déjà vérifié, etc.)
            console.log('📋 Erreur 400 - demande déjà en cours ou déjà vérifié');
            showError(error.response.data.message);
        } else if (error.response?.data?.message) {
            showError(error.response.data.message);
        } else if (error.response?.status === 500) {
            showError('Erreur serveur. Veuillez réessayer plus tard.');
        } else if (error.code === 'ERR_NETWORK') {
            showError('Problème de connexion réseau. Vérifiez votre connexion internet.');
        } else {
            showError("Une erreur est survenue lors de l'envoi de la demande");
        }
    } finally {
        isRequestingVerification.value = false;
        console.log('🏁 Demande de vérification - Fin');
        console.log('📋 Statut actuel après demande:', verificationStatus.value);
    }
};

const triggerAvatarInput = () => {
    avatarInput.value.click();
};

const handleAvatarChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        
        // Vérifications
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        
        if (!allowedTypes.includes(file.type)) {
            showError('Format d\'image non supporté. Utilisez JPG, PNG ou WebP.');
            return;
        }
        
        if (file.size > maxSize) {
            showError('L\'image est trop volumineuse (max 5MB).');
            return;
        }
        
        // Convertir en base64
        const reader = new FileReader();
        reader.onload = (e) => {
            if (e.target?.result) {
                // Mettre à jour l'aperçu
                avatarPreview.value = e.target.result as string;
                
                // Stocker pour l'envoi
                form.value.avatar = e.target.result as string;
            }
        };
        reader.readAsDataURL(file);
    }
};
</script>

<template>
    <DashboardLayout :currentMode="currentMode">
        <div class="mx-auto max-w-4xl">
            <!-- Titre avec switch de rôle -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Mon profil</h1>
                        <p class="text-gray-500">Gérez vos informations personnelles</p>
                    </div>
                    <!-- Switch de rôle si l'utilisateur a plusieurs rôles -->
                    <div v-if="hasMultipleRoles" class="flex items-center gap-4">
                        <span class="text-sm font-medium text-gray-700">Mode :</span>
                        <div class="flex rounded-lg border bg-gray-50 p-1">
                            <Button
                                @click="switchMode('parent')"
                                :variant="currentMode === 'parent' ? 'default' : 'ghost'"
                                size="sm"
                                class="flex items-center gap-2"
                                :class="currentMode === 'parent' ? 'bg-primary text-white hover:bg-orange-500' : 'text-gray-600 hover:bg-gray-100'"
                            >
                                <Users class="h-4 w-4" />
                                Parent
                            </Button>
                            <Button
                                @click="switchMode('babysitter')"
                                :variant="currentMode === 'babysitter' ? 'default' : 'ghost'"
                                size="sm"
                                class="flex items-center gap-2"
                                :class="
                                    currentMode === 'babysitter' ? 'bg-primary text-white hover:bg-orange-500' : 'text-gray-600 hover:bg-gray-100'
                                "
                            >
                                <Baby class="h-4 w-4" />
                                Babysitter
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ENCADRÉ VÉRIFICATION EN HAUT -->
            <div v-if="currentMode === 'babysitter'" class="mb-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex-1">
                        <div class="rounded border-l-4 border-blue-400 bg-blue-50 p-4">
                            <p class="text-blue-800">
                                <template v-if="verificationStatus === 'pending'">
                                    Votre demande de vérification est en cours d'examen par nos modérateurs. Vous recevrez un email dès que votre
                                    compte sera validé ou si des modifications sont nécessaires.
                                </template>
                                <template v-else-if="verificationStatus === 'verified'">
                                    Félicitations ! Votre profil est vérifié. Vous pouvez maintenant postuler aux annonces et recevoir des demandes de
                                    garde.
                                </template>
                                <template v-else-if="verificationStatus === 'rejected'">
                                    Votre demande de vérification a été rejetée.
                                    <span v-if="babysitterProfile?.rejection_reason"> Raison : {{ babysitterProfile.rejection_reason }} </span>
                                    Vous pouvez corriger votre profil et soumettre une nouvelle demande.
                                </template>
                                <template v-else>
                                    Pour être visible et accepter des annonces, votre profil doit être vérifié par un modérateur.<br />
                                    Cliquez sur "Demander la vérification". Un modérateur vérifiera manuellement votre profil et vous recevrez un
                                    email dès que votre compte sera validé ou si des modifications sont nécessaires.
                                </template>
                            </p>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center gap-2 md:mt-0">
                        <div
                            v-if="verificationStatus === 'pending'"
                            class="flex items-center gap-2 rounded-md bg-yellow-100 px-4 py-2 text-yellow-800"
                        >
                            <div class="h-2 w-2 animate-pulse rounded-full bg-yellow-500"></div>
                            Vérification en cours
                        </div>
                        <Button v-else-if="verificationStatus === 'verified'" disabled class="cursor-not-allowed bg-green-100 text-green-800">
                            Profil vérifié
                        </Button>
                        <Button
                            v-else-if="verificationStatus === 'rejected'"
                            @click="requestVerification"
                            :disabled="isRequestingVerification"
                            class="bg-blue-600 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ isRequestingVerification ? 'Envoi en cours...' : 'Soumettre une nouvelle demande' }}
                        </Button>
                        <Button
                            v-else
                            @click="requestVerification"
                            :disabled="isRequestingVerification || verificationStatus === 'pending'"
                            class="bg-blue-600 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ isRequestingVerification ? 'Envoi en cours...' : 'Demander la vérification' }}
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Header Card -->
            <Card class="mb-6">
                <CardHeader class="from-primary/10 rounded-t-xl bg-gradient-to-b to-orange-50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div class="relative">
                                <img
                                    :src="avatarPreview || user.avatar_url || user.avatar || '/storage/babysitter-test.png'"
                                    :alt="`Avatar de ${fullName}`"
                                    class="h-24 w-24 rounded-full border-4 border-white object-cover shadow-lg"
                                />
                                <div
                                    v-if="isEditing"
                                    @click="triggerAvatarInput"
                                    class="absolute right-0 bottom-0 cursor-pointer rounded-full bg-white p-2 shadow-md transition-colors hover:bg-gray-50"
                                >
                                    <Camera class="h-4 w-4 text-gray-500" />
                                </div>
                                <input
                                    ref="avatarInput"
                                    type="file"
                                    accept="image/*"
                                    @change="handleAvatarChange"
                                    class="hidden"
                                />
                            </div>
                            <div>
                                <h2 class="text-2xl font-semibold text-gray-900">{{ fullName }}</h2>
                                <p class="text-sm text-gray-500">{{ userInfo }}</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <div
                                        :class="[
                                            'rounded-full px-2 py-1 text-xs font-medium',
                                            currentMode === 'parent' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800',
                                        ]"
                                    >
                                        Mode {{ currentMode === 'parent' ? 'Parent' : 'Babysitter' }}
                                    </div>
                                    <div
                                        v-if="currentMode === 'babysitter' && verificationStatus"
                                        :class="['rounded-full px-2 py-1 text-xs font-medium', verificationStatusColor]"
                                    >
                                        {{ verificationStatusText }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Button v-if="!isEditing" @click="toggleEdit" class="bg-primary hover:bg-orange-500"> Modifier </Button>
                    </div>
                </CardHeader>

                <CardContent class="p-6">
                    <form @submit.prevent="submitForm" class="space-y-6">
                        <!-- Informations personnelles -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="firstname">Prénom</Label>
                                <Input 
                                    id="firstname" 
                                    v-model="form.firstname" 
                                    :disabled="!isEditing || user.social_data_locked" 
                                    required 
                                />
                                <p v-if="user.social_data_locked" class="text-xs text-gray-500">
                                    🔒 Géré par {{ user.provider === 'google' ? 'Google' : user.provider }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="lastname">Nom</Label>
                                <Input 
                                    id="lastname" 
                                    v-model="form.lastname" 
                                    :disabled="!isEditing || user.social_data_locked" 
                                    required 
                                />
                                <p v-if="user.social_data_locked" class="text-xs text-gray-500">
                                    🔒 Géré par {{ user.provider === 'google' ? 'Google' : user.provider }}
                                </p>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <div class="relative">
                                <Mail class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <Input 
                                    id="email" 
                                    type="email" 
                                    v-model="form.email" 
                                    :disabled="!isEditing || user.social_data_locked" 
                                    class="pl-10" 
                                    required 
                                />
                            </div>
                            <p v-if="user.social_data_locked" class="text-xs text-gray-500">
                                🔒 Géré par {{ user.provider === 'google' ? 'Google' : user.provider }}
                            </p>
                        </div>

                        <!-- Message informatif pour les utilisateurs Google -->
                        <div v-if="user.social_data_locked && user.provider === 'google'" class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <div class="flex items-start space-x-3">
                                <svg class="h-5 w-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-blue-900">Compte connecté via Google</h4>
                                    <div class="mt-1 text-sm text-blue-700">
                                        <p>Vos informations personnelles (prénom, nom, email) sont gérées par Google et ne peuvent pas être modifiées ici.</p>
                                        <p class="mt-1">Pour votre sécurité, vous ne pouvez pas vous déconnecter de Google. Si vous souhaitez supprimer votre compte, utilisez l'option dans les paramètres.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date de naissance (obligatoire pour babysitters) -->
                        <div v-if="currentMode === 'babysitter'" class="space-y-2">
                            <Label for="date_of_birth">Date de naissance *</Label>
                            <Input
                                id="date_of_birth"
                                type="date"
                                v-model="form.date_of_birth"
                                :disabled="!isEditing"
                                required
                                :max="new Date(new Date().setFullYear(new Date().getFullYear() - 16)).toISOString().split('T')[0]"
                            />
                            <p class="text-xs text-gray-500">Vous devez avoir au moins 16 ans pour être babysitter</p>
                        </div>

                        <!-- Adresse -->
                        <div class="space-y-2">
                            <Label for="address">Adresse</Label>
                            <div class="relative">
                                <MapPin class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <Input
                                    id="address-input"
                                    v-model="addressData.address"
                                    :disabled="!isEditing"
                                    placeholder="Votre adresse complète"
                                    class="pr-10 pl-10"
                                    @input="onAddressChange"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Enfants (seulement en mode parent) -->
                        <div v-if="currentMode === 'parent' && hasParentRole" class="space-y-4">
                            <div class="flex items-center justify-between">
                                <Label class="text-lg font-medium">Enfants</Label>
                                <Button v-if="isEditing" type="button" @click="addChild" variant="outline" size="sm" class="flex items-center gap-2">
                                    <Plus class="h-4 w-4" />
                                    Ajouter un enfant
                                </Button>
                            </div>

                            <div class="space-y-3">
                                <div
                                    v-for="(enfant, index) in form.children"
                                    :key="index"
                                    class="flex items-center gap-3 rounded-lg border bg-gray-50 p-3"
                                >
                                    <div class="flex-1">
                                        <Input v-model="enfant.nom" :disabled="!isEditing" placeholder="Prénom de l'enfant (ex: Sophie)" required />
                                    </div>
                                    <div class="w-20">
                                        <Input
                                            v-model="enfant.age"
                                            :disabled="!isEditing"
                                            type="number"
                                            min="1"
                                            max="18"
                                            placeholder="Âge"
                                            class="text-center"
                                            required
                                        />
                                    </div>
                                    <select
                                        v-model="enfant.unite"
                                        :disabled="!isEditing"
                                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm disabled:bg-gray-100"
                                    >
                                        <option value="mois">mois</option>
                                        <option value="ans">ans</option>
                                    </select>
                                    <Button v-if="isEditing" type="button" @click="removeChild(index)" variant="destructive" size="sm">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>

                                <div
                                    v-if="form.children.length === 0 && isEditing"
                                    class="cursor-pointer rounded-lg border border-dashed border-gray-300 p-6 text-center text-gray-500 transition-colors hover:bg-gray-50"
                                    @click="addChild"
                                >
                                    <Plus class="mx-auto mb-2 h-6 w-6 text-gray-400" />
                                    <p>Cliquez ici pour ajouter votre premier enfant</p>
                                </div>
                            </div>
                        </div>

                        <!-- Informations babysitter (seulement en mode babysitter) -->
                        <div v-if="currentMode === 'babysitter' && hasBabysitterRole" class="space-y-4">
                            <BabysitterProfile
                                ref="babysitterProfileRef"
                                :babysitter-profile="babysitterProfile"
                                :available-languages="props.availableLanguages"
                                :available-skills="props.availableSkills"
                                :available-age-ranges="props.availableAgeRanges"
                            />

                            <!-- Section compte de paiement pour babysitters vérifiés -->
                            <div
                                v-if="user.role === 'babysitter' && babysitterProfile?.verification_status === 'verified'"
                                class="border-b border-gray-200 pb-6"
                            >
                                <h3 class="mb-4 text-lg font-semibold text-gray-900">Compte de paiement</h3>

                                <!-- Compte configuré -->
                                <div v-if="(user as any).stripe_account_status === 'active'" class="space-y-4">
                                    <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                                        <div class="flex items-center">
                                            <CheckCircle class="mr-2 h-5 w-5 text-green-600" />
                                            <span class="text-sm font-medium text-green-800">Compte configuré et vérifié</span>
                                        </div>
                                        <p class="mt-1 text-sm text-green-700">
                                            Vous pouvez recevoir des paiements. Les virements sont effectués chaque vendredi.
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                                            <div class="flex items-center">
                                                <CreditCard class="mr-2 h-5 w-5 text-blue-600" />
                                                <span class="text-sm font-medium text-gray-900">Paiements</span>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-600">Activés</p>
                                        </div>

                                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                                            <div class="flex items-center">
                                                <Building class="mr-2 h-5 w-5 text-green-600" />
                                                <span class="text-sm font-medium text-gray-900">Virements</span>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-600">Activés</p>
                                        </div>

                                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                                            <div class="flex items-center">
                                                <Shield class="mr-2 h-5 w-5 text-blue-600" />
                                                <span class="text-sm font-medium text-gray-900">Vérification</span>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-600">Complète</p>
                                        </div>
                                    </div>

                                    <div class="flex gap-3">
                                        <Button variant="outline" @click="router.visit('/stripe/connect')" class="flex-1">
                                            <TrendingUp class="mr-2 h-4 w-4" />
                                            Consulter les revenus
                                        </Button>
                                    </div>
                                </div>

                                <!-- Configuration en cours -->
                                <div v-else-if="(user as any).stripe_account_status === 'pending'" class="space-y-4">
                                    <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                                        <div class="flex items-center">
                                            <Clock class="mr-2 h-5 w-5 text-orange-600" />
                                            <span class="text-sm font-medium text-orange-800">Configuration en cours</span>
                                        </div>
                                        <p class="mt-1 text-sm text-orange-700">
                                            Votre compte est créé mais nécessite quelques informations supplémentaires.
                                        </p>
                                    </div>

                                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                                        <div class="mb-2 flex items-center">
                                            <Info class="mr-2 h-4 w-4 text-blue-600" />
                                            <span class="text-sm font-medium text-blue-900">Étapes restantes</span>
                                        </div>
                                        <ul class="space-y-1 text-sm text-blue-800">
                                            <li>• Fournir une pièce d'identité</li>
                                            <li>• Confirmer votre adresse</li>
                                            <li>• Ajouter vos coordonnées bancaires</li>
                                            <li>• Accepter les conditions de service</li>
                                        </ul>
                                    </div>

                                    <div class="flex gap-3">
                                        <Button type="button" @click.prevent="router.visit('/stripe/connect')" class="flex-1">
                                            <ExternalLink class="mr-2 h-4 w-4" />
                                            Continuer la configuration
                                        </Button>
                                    </div>
                                </div>

                                <!-- Problème avec le compte -->
                                <div v-else-if="(user as any).stripe_account_status === 'rejected'" class="space-y-4">
                                    <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                                        <div class="flex items-center">
                                            <AlertCircle class="mr-2 h-5 w-5 text-red-600" />
                                            <span class="text-sm font-medium text-red-800">Action requise</span>
                                        </div>
                                        <p class="mt-1 text-sm text-red-700">Il y a un problème avec votre compte qui nécessite votre attention.</p>
                                    </div>

                                    <div class="flex gap-3">
                                        <Button type="button" @click.prevent="router.visit('/stripe/connect')" class="flex-1" variant="destructive">
                                            <AlertCircle class="mr-2 h-4 w-4" />
                                            Résoudre le problème
                                        </Button>
                                    </div>
                                </div>

                                <!-- Pas encore de compte -->
                                <div v-else class="space-y-4">
                                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                        <div class="flex items-center">
                                            <CreditCard class="mr-2 h-5 w-5 text-gray-600" />
                                            <span class="text-sm font-medium text-gray-800">Compte de paiement non configuré</span>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-700">Configurez votre compte pour commencer à recevoir des paiements.</p>
                                    </div>

                                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                                        <div class="mb-2 flex items-center">
                                            <Shield class="mr-2 h-4 w-4 text-blue-600" />
                                            <span class="text-sm font-medium text-blue-900">Processus sécurisé avec Stripe</span>
                                        </div>
                                        <ul class="space-y-1 text-sm text-blue-800">
                                            <li>• Configuration en 5-10 minutes</li>
                                            <li>• Vérification d'identité sécurisée</li>
                                            <li>• Paiements automatiques chaque vendredi</li>
                                            <li>• Chiffrement bancaire de niveau militaire</li>
                                        </ul>
                                    </div>

                                    <div class="flex gap-3">
                                        <Button type="button" @click.prevent="router.visit('/stripe/connect')" class="flex-1">
                                            <CreditCard class="mr-2 h-4 w-4" />
                                            Configurer mon compte de paiement
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div v-if="isEditing" class="flex justify-end gap-4 border-t pt-6">
                            <Button type="button" @click="toggleEdit" variant="outline" :disabled="isLoading"> Annuler </Button>
                            <Button type="submit" class="bg-primary hover:bg-orange-500" :disabled="isLoading">
                                {{ isLoading ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </DashboardLayout>
</template>

<style scoped>
:deep(.pac-container) {
    z-index: 9999;
}
</style>
