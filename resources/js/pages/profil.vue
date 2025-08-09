<script setup lang="ts">
import BabysitterProfile from '@/components/BabysitterProfile.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useDeviceToken } from '@/composables/useDeviceToken';
import { useToast } from '@/composables/useToast';
import { useUserMode } from '@/composables/useUserMode';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import type { AgeRange, BabysitterProfile as BabysitterProfileType, Child, Language, Skill, User } from '@/types';
import { router } from '@inertiajs/vue3';
import {
    AlertCircle,
    Building,
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
    X,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { route } from 'ziggy-js';

interface ExtendedUser extends User {
    parentProfile?: {
        children_ages: Child[];
    };
    babysitterProfile?: BabysitterProfileType;
    role: string;
    social_data_locked: boolean;
    provider: string;
    avatar_url?: string;
    google_id?: string;
    password?: boolean;
    is_social_account?: boolean;
}

interface AddressData {
    address: string;
    postal_code: string;
    country: string;
    latitude: number;
    longitude: number;
    google_place_id: string;
}

interface Props {
    user: ExtendedUser;
    userRoles: string[];
    hasParentRole: boolean;
    hasBabysitterRole: boolean;
    requestedMode?: 'parent' | 'babysitter';
    children?: Child[];
    babysitterProfile?: BabysitterProfileType;
    availableLanguages?: Language[];
    availableSkills?: Skill[];
    availableAgeRanges?: AgeRange[];
    googlePlacesApiKey?: string;
}

const props = defineProps<Props>();
const { showSuccess, showError, handleAuthError } = useToast();
const { currentMode, initializeMode, setMode } = useUserMode();
const { isMobileApp } = useDeviceToken();

// Variables réactives
const isEditing = ref(false);
const isLoading = ref(false);
const isGoogleLoaded = ref(false);
const avatarPreview = ref(''); // Pour l'aperçu de l'avatar
let autocomplete: any;

const babysitterProfileRef = ref();
const avatarInput = ref();

// Initialiser le mode au montage du composant
onMounted(() => {
    initializeMode(props.hasParentRole, props.hasBabysitterRole, props.requestedMode);

    // Plus besoin de gérer la bannière de vérification

    // Debug: Vérifier toutes les props reçues
    console.log('🔍 Props reçues dans profil.vue:', {
        hasParentRole: props.hasParentRole,
        hasBabysitterRole: props.hasBabysitterRole,
        userHasBabysitterProfile: !!props.user.babysitterProfile,
        propsBabysitterProfile: !!props.babysitterProfile,
        userRoles: props.userRoles,
        user: props.user,
        babysitterProfile: props.babysitterProfile,
        dateOfBirth: props.user.date_of_birth,
        dateOfBirthType: typeof props.user.date_of_birth,
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

// Formatage de la date de naissance pour l'input date (format YYYY-MM-DD)
const formatDateForInput = (dateString: string | null | undefined) => {
    if (!dateString) return '';

    try {
        // Si c'est déjà au format YYYY-MM-DD, le retourner tel quel
        if (typeof dateString === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
            return dateString;
        }

        // Créer un objet date et utiliser les méthodes locales pour éviter les problèmes de fuseau horaire
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';

        // Utiliser getFullYear, getMonth, getDate pour éviter les décalages UTC
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    } catch (error) {
        console.error('Erreur format date:', error);
        return '';
    }
};

// Formulaire
const form = ref({
    firstname: props.user.firstname || '',
    lastname: props.user.lastname || '',
    email: props.user.email || '',
    date_of_birth: formatDateForInput(props.user.date_of_birth),
    avatar: '', // Champ pour l'avatar en base64
    children: (props.children || []).map((child) => ({
        ...child,
        age: String(child.age), // S'assurer que l'âge est une string
    })),
    mode: currentMode.value,
});

console.log('📅 Debug date de naissance:', {
    original: props.user.date_of_birth,
    formatted: form.value.date_of_birth,
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

const viewMyProfile = () => {
    // Créer le slug à la volée
    try {
        let url;
        if (currentMode.value === 'babysitter' && props.hasBabysitterRole) {
            // Créer le slug babysitter
            const slug = createBabysitterSlug(props.user);
            url = route('babysitter.show', { slug });
        } else if (currentMode.value === 'parent' && props.hasParentRole) {
            // Créer le slug parent
            const slug = createParentSlug(props.user);
            url = route('parent.show', { slug });
        } else if (props.hasBabysitterRole) {
            // Fallback: utiliser le mode babysitter si l'utilisateur a ce rôle
            const slug = createBabysitterSlug(props.user);
            url = route('babysitter.show', { slug });
        } else {
            showError("Impossible d'afficher le profil public - Rôle incompatible");
            return;
        }

        // Ouvrir le profil dans un nouvel onglet
        window.open(url, '_blank');
    } catch (error) {
        console.error('❌ Erreur création profil:', error);
        showError("Impossible d'afficher le profil public - Erreur de génération du lien");
    }
};

// Fonctions de création de slugs (inspirées de la messagerie)
const createBabysitterSlug = (user: User) => {
    if (!user || !user.id) {
        console.error('❌ User invalide pour babysitter slug:', user);
        throw new Error('User invalide');
    }

    // Reproduire exactement l'algorithme PHP
    const firstName = user.firstname ? user.firstname.toLowerCase().replace(/[^a-z0-9]/g, '-') : 'babysitter';
    const lastName = user.lastname ? user.lastname.toLowerCase().replace(/[^a-z0-9]/g, '-') : '';

    const slug = (firstName + '-' + lastName + '-' + user.id).replace(/^-+|-+$/g, '');
    const finalSlug = slug.replace(/-+/g, '-');

    return finalSlug;
};

const createParentSlug = (user: User) => {
    if (!user || !user.id) {
        console.error('❌ User invalide pour parent slug:', user);
        throw new Error('User invalide');
    }

    // Reproduire exactement l'algorithme PHP
    const firstName = user.firstname ? user.firstname.toLowerCase().replace(/[^a-z0-9]/g, '-') : 'parent';
    const lastName = user.lastname ? user.lastname.toLowerCase().replace(/[^a-z0-9]/g, '-') : '';

    const slug = (firstName + '-' + lastName + '-' + user.id).replace(/^-+|-+$/g, '');
    const finalSlug = slug.replace(/-+/g, '-');

    return finalSlug;
};

// Charger l'API Google Places
const loadGooglePlaces = () => {
    console.log('🚀 Chargement Google Places API...');

    if (window.google?.maps?.places) {
        console.log('✅ Google Places déjà chargé');
        initAutocomplete();
        return;
    }

    const apiKey = props.googlePlacesApiKey;

    if (!apiKey) {
        console.error('❌ Clé API Google Places manquante - Vérifiez votre variable GOOGLE_PLACES_API_KEY dans .env');
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
            delete (formData as any).firstname;
            delete (formData as any).lastname;
            delete (formData as any).email;
        }

        // Ajouter les données d'adresse
        Object.assign(formData, addressData.value);

        // Ajouter les données du profil babysitter si on est en mode babysitter
        if (currentMode.value === 'babysitter' && babysitterProfileRef.value) {
            const babysitterData = babysitterProfileRef.value.getFormData();
            Object.assign(formData, babysitterData);
        }

        // Assurons-nous que les enfants sont dans le bon format
        if (formData.children && Array.isArray(formData.children)) {
            formData.children = formData.children.map((child) => ({
                nom: String(child.nom || ''),
                age: String(child.age || ''),
                unite: String(child.unite || 'ans'),
            }));
        }

        console.log('📤 Données envoyées:', formData);

        await router.post(
            route('profil.update'),
            {
                ...formData,
                _method: 'PUT',
            },
            {
                preserveState: false,
                onSuccess: () => {
                    showSuccess('Profil mis à jour avec succès !');
                    isEditing.value = false;
                    // Ne pas réinitialiser l'aperçu pour garder la photo visible
                    // avatarPreview.value = ''; // Réinitialiser l'aperçu
                },
                onError: (errors) => {
                    console.error('❌ Erreurs de validation:', errors);
                    // Les erreurs 500 sont maintenant gérées globalement

                    showError('Erreur lors de la mise à jour du profil');
                },
            },
        );
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

// Suppression du système de vérification de profil - remplacé par Stripe Connect

// Suppression de la fonction requestVerification - plus nécessaire

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
            showError("Format d'image non supporté. Utilisez JPG, PNG ou WebP.");
            return;
        }

        if (file.size > maxSize) {
            showError("L'image est trop volumineuse (max 5MB).");
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

// Computed pour détecter si c'est un utilisateur Google uniquement
const isGoogleOnlyUser = computed(() => {
    // Si l'utilisateur a un google_id, c'est un utilisateur Google
    return props.user.google_id && !props.user.password;
});

// Suppression de la fonction closeVerificationBanner - plus nécessaire

// Computed pour calculer le pourcentage de progression du profil babysitter
const babysitterProfileCompletion = computed(() => {
    if (currentMode.value !== 'babysitter' || !props.hasBabysitterRole) return 0;

    try {
        // Utiliser les données du profil babysitter existant
        const profile = babysitterProfile.value;
        if (!profile) return 0;

        let totalFields = 0;
        let completedFields = 0;

        // Bio (10%)
        totalFields += 10;
        if (profile.bio && profile.bio.trim().length > 0) {
            completedFields += 10;
        }

        // Années d'expérience (10%)
        totalFields += 10;
        if (profile.experience_years && profile.experience_years > 0) {
            completedFields += 10;
        }

        // Tarif horaire (15%)
        totalFields += 15;
        if (profile.hourly_rate && profile.hourly_rate > 0) {
            completedFields += 15;
        }

        // Langues (15%)
        totalFields += 15;
        if (profile.languages && profile.languages.length > 0) {
            completedFields += 15;
        }

        // Compétences (15%)
        totalFields += 15;
        if (profile.skills && profile.skills.length > 0) {
            completedFields += 15;
        }

        // Préférences d'âge (15%) - soit "à l'aise avec tous" soit au moins une exclusion
        totalFields += 15;
        if (profile.comfortable_with_all_ages || (profile.age_ranges && profile.age_ranges.length > 0)) {
            completedFields += 15;
        }

        // Rayon de déplacement (10%)
        totalFields += 10;
        if (profile.available_radius_km && profile.available_radius_km > 0) {
            completedFields += 10;
        }

        // Statut de disponibilité (10%) - toujours compté car par défaut à true
        totalFields += 10;
        completedFields += 10; // Toujours complété car il y a une valeur par défaut

        return Math.round((completedFields / totalFields) * 100);
    } catch (error) {
        console.error('Erreur calcul progression profil:', error);
        return 0;
    }
});

// Debug pour vérifier les données utilisateur
console.log('🔍 Données utilisateur Profil:', {
    provider: props.user.provider,
    is_social_account: props.user.is_social_account,
    social_data_locked: props.user.social_data_locked,
    isGoogleOnlyUser: isGoogleOnlyUser.value,
});
</script>

<template>
    <DashboardLayout :currentMode="currentMode" :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <!-- Titre -->
            <div class="mb-4 sm:mb-6">
                <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">Mon profil</h1>
                <p class="mt-1 text-sm text-gray-600 sm:mt-2 sm:text-base">Gérez vos informations personnelles</p>
            </div>

            <!-- ENCADRÉ STRIPE CONNECT POUR BABYSITTERS -->
            <div v-if="currentMode === 'babysitter' && user.role === 'babysitter'" class="mb-4 sm:mb-6">
                <div class="flex flex-col gap-3 sm:gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex-1">
                        <div class="relative rounded border-l-4 border-orange-400 bg-orange-50 p-3 sm:p-4">
                            <p class="pr-6 text-sm text-orange-800 sm:pr-8 sm:text-base">
                                <template v-if="(user as any).stripe_account_status === 'active'">
                                    🎉 Parfait ! Votre compte Stripe est configuré. Vous pouvez maintenant postuler aux annonces et recevoir des paiements.
                                </template>
                                <template v-else>
                                    Pour postuler aux annonces et recevoir des paiements, vous devez configurer votre compte Stripe Connect.<br />
                                    C'est rapide et sécurisé - suivez simplement les étapes dans l'onglet "Paiements".
                                </template>
                            </p>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center gap-2 md:mt-0">
                        <div
                            v-if="(user as any).stripe_account_status === 'active'"
                            class="flex items-center gap-1 rounded-md bg-green-100 px-2 py-1 text-xs text-green-800 sm:gap-2 sm:px-4 sm:py-2 sm:text-sm"
                        >
                            <CheckCircle class="h-3 w-3 sm:h-4 sm:w-4" />
                            <span class="hidden sm:inline">Stripe configuré</span>
                            <span class="sm:hidden">Configuré</span>
                        </div>
                        <div
                            v-else
                            class="flex items-center gap-1 rounded-md bg-orange-100 px-2 py-1 text-xs text-orange-800 sm:gap-2 sm:px-4 sm:py-2 sm:text-sm"
                        >
                            <Clock class="h-3 w-3 sm:h-4 sm:w-4" />
                            <span class="hidden sm:inline">Configuration requise</span>
                            <span class="sm:hidden">Requis</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BARRE DE PROGRESSION DU PROFIL BABYSITTER -->
            <div v-if="currentMode === 'babysitter' && props.hasBabysitterRole" class="mb-4 sm:mb-6">
                <div
                    :class="[
                        'rounded-lg border p-4 shadow-sm transition-all duration-300 sm:p-6',
                        babysitterProfileCompletion >= 80
                            ? 'border-green-200 bg-gradient-to-r from-green-50 to-emerald-50'
                            : babysitterProfileCompletion >= 50
                              ? 'border-yellow-200 bg-gradient-to-r from-yellow-50 to-orange-50'
                              : 'border-red-200 bg-gradient-to-r from-red-50 to-pink-50',
                    ]"
                >
                    <div class="mb-3 flex items-center gap-2 sm:mb-4 sm:gap-3">
                        <div
                            :class="[
                                'flex h-6 w-6 items-center justify-center rounded-full transition-all duration-300 sm:h-8 sm:w-8',
                                babysitterProfileCompletion >= 80
                                    ? 'bg-green-100'
                                    : babysitterProfileCompletion >= 50
                                      ? 'bg-yellow-100'
                                      : 'bg-red-100',
                            ]"
                        >
                            <svg
                                v-if="babysitterProfileCompletion >= 80"
                                class="h-3 w-3 text-green-600 sm:h-5 sm:w-5"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <svg
                                v-else-if="babysitterProfileCompletion >= 50"
                                class="h-3 w-3 text-yellow-600 sm:h-5 sm:w-5"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <svg v-else class="h-3 w-3 text-red-600 sm:h-5 sm:w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-lg font-bold text-gray-900 sm:text-xl">🚀 Complétez votre profil pour attirer plus de parents !</h2>
                            <p
                                :class="[
                                    'text-xs font-medium sm:text-sm',
                                    babysitterProfileCompletion >= 80
                                        ? 'text-green-700'
                                        : babysitterProfileCompletion >= 50
                                          ? 'text-yellow-700'
                                          : 'text-red-700',
                                ]"
                            >
                                <span v-if="babysitterProfileCompletion < 50"
                                    >Continuez de remplir votre profil pour augmenter vos chances d'être contacté.</span
                                >
                                <span v-else-if="babysitterProfileCompletion < 80">⭐ Votre profil est bien avancé, continuez !</span>
                                <span v-else>🎉 Excellent ! Votre profil est complet.</span>
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-xs sm:text-sm">
                            <span class="font-medium text-gray-700">Progression de votre profil babysitter</span>
                            <span class="font-bold text-gray-900">{{ babysitterProfileCompletion }}% complété</span>
                        </div>
                        <div class="relative h-2 w-full rounded-full bg-white/50 shadow-inner sm:h-3">
                            <div
                                :class="[
                                    'h-2 rounded-full shadow-sm transition-all duration-500 ease-out sm:h-3',
                                    babysitterProfileCompletion >= 80
                                        ? 'bg-gradient-to-r from-green-400 to-green-600'
                                        : babysitterProfileCompletion >= 50
                                          ? 'bg-gradient-to-r from-yellow-400 to-orange-500'
                                          : 'bg-gradient-to-r from-red-400 to-red-600',
                                ]"
                                :style="{ width: babysitterProfileCompletion + '%' }"
                            >
                                <div class="h-full w-full rounded-full bg-white/20"></div>
                            </div>
                            <!-- Indicateurs de seuils -->
                            <div class="absolute top-0 left-1/2 h-2 w-0.5 -translate-x-0.5 transform rounded-full bg-white/60 sm:h-3"></div>
                            <div class="absolute top-0 left-4/5 h-2 w-0.5 -translate-x-0.5 transform rounded-full bg-white/60 sm:h-3"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>0%</span>
                            <span class="hidden sm:inline">50%</span>
                            <span class="hidden sm:inline">80%</span>
                            <span>100%</span>
                        </div>
                    </div>

                    <!-- Statut Stripe Connect -->
                    <div class="mt-6 flex justify-center">
                        <div
                            v-if="(user as any).stripe_account_status === 'active'"
                            class="flex items-center gap-3 rounded-lg border border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-3 shadow-sm"
                        >
                            <CheckCircle class="h-5 w-5 text-green-500" />
                            <span class="text-sm font-medium text-green-800">Compte Stripe configuré</span>
                        </div>
                        <div
                            v-else-if="(user as any).stripe_account_status === 'pending'"
                            class="flex items-center gap-3 rounded-lg border border-orange-200 bg-gradient-to-r from-orange-50 to-yellow-50 px-6 py-3 shadow-sm"
                        >
                            <Clock class="h-5 w-5 text-orange-500" />
                            <span class="text-sm font-medium text-orange-800">Configuration en cours</span>
                        </div>
                        <Button
                            v-else
                            @click="router.visit('/paiements')"
                            class="bg-primary text-white hover:bg-orange-500"
                        >
                            Mon compte de paiements
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Header Card -->
            <Card class="mb-4 py-0 sm:mb-6">
                <CardHeader class="from-primary/40 to-secondary/10 rounded-t-xl bg-gradient-to-b py-3 sm:py-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3 sm:gap-6">
                            <div class="relative">
                                <img
                                    :src="avatarPreview || user.avatar_url || user.avatar || '/storage/babysitter-test.png'"
                                    :alt="`Avatar de ${fullName}`"
                                    class="h-16 w-16 rounded-full border-2 border-white object-cover shadow-lg sm:h-24 sm:w-24 sm:border-4"
                                />
                                <div
                                    v-if="isEditing"
                                    @click="triggerAvatarInput"
                                    class="absolute right-0 bottom-0 cursor-pointer rounded-full bg-white p-1 shadow-md transition-colors hover:bg-gray-50 sm:p-2"
                                >
                                    <Camera class="h-3 w-3 text-gray-500 sm:h-4 sm:w-4" />
                                </div>
                                <input
                                    ref="avatarInput"
                                    type="file"
                                    accept="image/*"
                                    :capture="isMobileApp() ? 'environment' : undefined"
                                    @change="handleAvatarChange"
                                    class="hidden"
                                />
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900 sm:text-2xl">{{ fullName }}</h2>
                                <p class="text-xs text-gray-500 sm:text-sm">{{ userInfo }}</p>
                                <div class="mt-1 flex flex-col items-start gap-1 sm:flex-row sm:items-center sm:gap-2">
                                    <div
                                        :class="[
                                            'rounded-full px-2 py-1 text-xs font-medium',
                                            currentMode === 'parent' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800',
                                        ]"
                                    >
                                        Mode {{ currentMode === 'parent' ? 'Parent' : 'Babysitter' }}
                                    </div>
                                    <div
                                        v-if="currentMode === 'babysitter' && (user as any).stripe_account_status"
                                        :class="[
                                            'rounded-full px-2 py-1 text-xs font-medium',
                                            (user as any).stripe_account_status === 'active'
                                                ? 'bg-green-100 text-green-800'
                                                : (user as any).stripe_account_status === 'pending'
                                                  ? 'bg-orange-100 text-orange-800'
                                                  : 'bg-gray-100 text-gray-800',
                                        ]"
                                    >
                                        {{
                                            (user as any).stripe_account_status === 'active'
                                                ? 'Stripe configuré'
                                                : (user as any).stripe_account_status === 'pending'
                                                  ? 'Stripe en cours'
                                                  : 'Stripe requis'
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="!isEditing" class="flex w-full flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:items-center sm:gap-3">
                            <Button @click="viewMyProfile" variant="outline" class="flex items-center justify-center gap-2 text-sm">
                                <ExternalLink class="h-3 w-3 sm:h-4 sm:w-4" />
                                <span class="hidden sm:inline">Voir mon profil</span>
                                <span class="sm:hidden">Mon profil</span>
                            </Button>
                            <Button @click="toggleEdit" class="bg-primary text-sm hover:bg-orange-500">
                                <span class="hidden sm:inline">Modifier</span>
                                <span class="sm:hidden">Éditer</span>
                            </Button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="p-4 sm:p-6">
                    <form @submit.prevent="submitForm" class="space-y-4 sm:space-y-6">
                        <!-- Informations personnelles -->
                        <div class="grid grid-cols-1 gap-3 sm:gap-4 md:grid-cols-2">
                            <div class="space-y-1 sm:space-y-2">
                                <Label for="firstname" class="text-sm">Prénom</Label>
                                <Input id="firstname" v-model="form.firstname" :disabled="!isEditing || isGoogleOnlyUser" required class="text-sm" />
                                <p v-if="isGoogleOnlyUser" class="text-xs text-green-600">✓ Géré par Google</p>
                            </div>
                            <div class="space-y-1 sm:space-y-2">
                                <Label for="lastname" class="text-sm">Nom</Label>
                                <Input id="lastname" v-model="form.lastname" :disabled="!isEditing || isGoogleOnlyUser" required class="text-sm" />
                                <p v-if="isGoogleOnlyUser" class="text-xs text-green-600">✓ Géré par Google</p>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="space-y-1 sm:space-y-2">
                            <Label for="email" class="text-sm">Email</Label>
                            <div class="relative">
                                <Mail class="absolute top-1/2 left-2 h-3 w-3 -translate-y-1/2 text-gray-400 sm:left-3 sm:h-4 sm:w-4" />
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    :disabled="!isEditing || isGoogleOnlyUser"
                                    class="pl-8 text-sm sm:pl-10"
                                    required
                                />
                            </div>
                            <p v-if="isGoogleOnlyUser" class="text-xs text-green-600">✓ Géré par Google</p>
                        </div>

                        <!-- Message informatif pour les utilisateurs Google -->
                        <div v-if="isGoogleOnlyUser" class="rounded-lg border border-green-200 bg-green-50 p-3 sm:p-4">
                            <div class="flex items-start space-x-2 sm:items-center sm:space-x-3">
                                <div class="flex-shrink-0">
                                    <svg class="h-4 w-4 text-green-600 sm:h-5 sm:w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-green-900">Compte connecté via Google</h4>
                                    <p class="mt-1 text-xs text-green-700 sm:text-sm">
                                        Vos informations de connexion sont sécurisées et gérées directement par Google.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Date de naissance (obligatoire pour babysitters) -->
                        <div v-if="currentMode === 'babysitter'" class="space-y-1 sm:space-y-2">
                            <Label for="date_of_birth" class="text-sm">Date de naissance *</Label>
                            <Input
                                id="date_of_birth"
                                type="date"
                                v-model="form.date_of_birth"
                                :disabled="!isEditing"
                                required
                                :max="new Date(new Date().setFullYear(new Date().getFullYear() - 16)).toISOString().split('T')[0]"
                                class="text-sm"
                            />
                            <p class="text-xs text-gray-500">Vous devez avoir au moins 16 ans pour être babysitter</p>
                        </div>

                        <!-- Adresse -->
                        <div class="space-y-1 sm:space-y-2">
                            <Label for="address" class="text-sm">Adresse</Label>
                            <div class="relative">
                                <MapPin class="absolute top-1/2 left-2 h-3 w-3 -translate-y-1/2 text-gray-400 sm:left-3 sm:h-4 sm:w-4" />
                                <Input
                                    id="address-input"
                                    v-model="addressData.address"
                                    :disabled="!isEditing"
                                    placeholder="Votre adresse complète"
                                    class="pr-8 pl-8 text-sm sm:pr-10 sm:pl-10"
                                    @input="onAddressChange"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Enfants (seulement en mode parent) -->
                        <div v-if="currentMode === 'parent' && hasParentRole" class="space-y-3 sm:space-y-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <Label class="text-base font-medium sm:text-lg">Enfants</Label>
                                <Button
                                    v-if="isEditing"
                                    type="button"
                                    @click="addChild"
                                    variant="outline"
                                    size="sm"
                                    class="flex items-center gap-1 self-start text-sm sm:gap-2 sm:self-auto"
                                >
                                    <Plus class="h-3 w-3 sm:h-4 sm:w-4" />
                                    <span class="hidden sm:inline">Ajouter un enfant</span>
                                    <span class="sm:hidden">Ajouter</span>
                                </Button>
                            </div>

                            <div class="space-y-2 sm:space-y-3">
                                <div
                                    v-for="(enfant, index) in form.children"
                                    :key="index"
                                    class="flex flex-col items-stretch gap-2 rounded-lg border bg-gray-50 p-2 sm:flex-row sm:items-center sm:gap-3 sm:p-3"
                                >
                                    <div class="flex-1">
                                        <Input
                                            v-model="enfant.nom"
                                            :disabled="!isEditing"
                                            placeholder="Prénom de l'enfant (ex: Sophie)"
                                            required
                                            class="text-sm"
                                        />
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 sm:w-20">
                                            <Input
                                                v-model="enfant.age"
                                                :disabled="!isEditing"
                                                type="number"
                                                min="1"
                                                max="18"
                                                placeholder="Âge"
                                                class="text-center text-sm"
                                                required
                                            />
                                        </div>
                                        <select
                                            v-model="enfant.unite"
                                            :disabled="!isEditing"
                                            class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs disabled:bg-gray-100 sm:px-3 sm:py-2 sm:text-sm"
                                        >
                                            <option value="mois">mois</option>
                                            <option value="ans">ans</option>
                                        </select>
                                        <Button
                                            v-if="isEditing"
                                            type="button"
                                            @click="removeChild(index)"
                                            variant="destructive"
                                            size="sm"
                                            class="p-1 sm:p-2"
                                        >
                                            <Trash2 class="h-3 w-3 sm:h-4 sm:w-4" />
                                        </Button>
                                    </div>
                                </div>

                                <div
                                    v-if="form.children.length === 0 && isEditing"
                                    class="cursor-pointer rounded-lg border border-dashed border-gray-300 p-4 text-center text-gray-500 transition-colors hover:bg-gray-50 sm:p-6"
                                    @click="addChild"
                                >
                                    <Plus class="mx-auto mb-1 h-5 w-5 text-gray-400 sm:mb-2 sm:h-6 sm:w-6" />
                                    <p class="text-sm">Cliquez ici pour ajouter votre premier enfant</p>
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

                            <!-- Section compte de paiement pour babysitters avec Stripe configuré -->
                            <div
                                v-if="user.role === 'babysitter' && (user as any).stripe_account_status === 'active'"
                                class="border-b border-gray-200 pb-4 sm:pb-6"
                            >
                                <h3 class="mb-3 text-base font-semibold text-gray-900 sm:mb-4 sm:text-lg">Compte de paiement</h3>

                                <!-- Compte configuré -->
                                <div v-if="(user as any).stripe_account_status === 'active'" class="space-y-3 sm:space-y-4">
                                    <div class="rounded-lg border border-green-200 bg-green-50 p-3 sm:p-4">
                                        <div class="flex items-center">
                                            <CheckCircle class="mr-1 h-4 w-4 text-green-600 sm:mr-2 sm:h-5 sm:w-5" />
                                            <span class="text-sm font-medium text-green-800">Compte configuré et vérifié</span>
                                        </div>
                                        <p class="mt-1 text-xs text-green-700 sm:text-sm">
                                            Vous pouvez recevoir des paiements. Les virements sont effectués chaque vendredi.
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3 sm:gap-4 md:grid-cols-3">
                                        <div class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4">
                                            <div class="flex items-center">
                                                <CreditCard class="mr-1 h-4 w-4 text-blue-600 sm:mr-2 sm:h-5 sm:w-5" />
                                                <span class="text-sm font-medium text-gray-900">Paiements</span>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-600">Activés</p>
                                        </div>

                                        <div class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4">
                                            <div class="flex items-center">
                                                <Building class="mr-1 h-4 w-4 text-green-600 sm:mr-2 sm:h-5 sm:w-5" />
                                                <span class="text-sm font-medium text-gray-900">Virements</span>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-600">Activés</p>
                                        </div>

                                        <div class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4">
                                            <div class="flex items-center">
                                                <Shield class="mr-1 h-4 w-4 text-blue-600 sm:mr-2 sm:h-5 sm:w-5" />
                                                <span class="text-sm font-medium text-gray-900">Vérification</span>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-600">Complète</p>
                                        </div>
                                    </div>

                                    <div class="flex gap-2 sm:gap-3">
                                        <Button variant="outline" @click="router.visit('/stripe/connect')" class="flex-1 text-sm">
                                            <TrendingUp class="mr-1 h-3 w-3 sm:mr-2 sm:h-4 sm:w-4" />
                                            <span class="hidden sm:inline">Consulter les revenus</span>
                                            <span class="sm:hidden">Revenus</span>
                                        </Button>
                                    </div>
                                </div>

                                <!-- Configuration en cours -->
                                <div v-else-if="(user as any).stripe_account_status === 'pending'" class="space-y-4">
                                    <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                                        <div class="flex items-center">
                                            <Clock class="text-primary mr-2 h-5 w-5" />
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
                        <div v-if="isEditing" class="flex flex-col justify-end gap-3 border-t pt-4 sm:flex-row sm:gap-4 sm:pt-6">
                            <Button type="button" @click="toggleEdit" variant="outline" :disabled="isLoading" class="order-2 text-sm sm:order-1">
                                Annuler
                            </Button>
                            <Button type="submit" class="bg-primary order-1 text-sm hover:bg-orange-500 sm:order-2" :disabled="isLoading">
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
