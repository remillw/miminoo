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
import {
    AlertCircle,
    Baby,
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
    Users,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import type { 
    User, 
    Child, 
    Address, 
    Language, 
    Skill, 
    AgeRange,
    BabysitterProfile as BabysitterProfileType 
} from '@/types';

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

// Variables réactives
const isEditing = ref(false);
const isLoading = ref(false);
const isGoogleLoaded = ref(false);
const isRequestingVerification = ref(false);
const avatarPreview = ref(''); // Pour l'aperçu de l'avatar
const isVerificationBannerVisible = ref(true); // Pour contrôler l'affichage de la bannière de vérification
let autocomplete: any;

const babysitterProfileRef = ref();
const avatarInput = ref();

// Initialiser le mode au montage du composant
onMounted(() => {
    initializeMode(props.hasParentRole, props.hasBabysitterRole, props.requestedMode);

    // Vérifier si la bannière de vérification a été fermée précédemment
    const verificationBannerClosed = localStorage.getItem('verification-banner-closed');
    if (verificationBannerClosed === 'true') {
        isVerificationBannerVisible.value = false;
    }

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
        // Si c'est déjà un objet date
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';

        // Retourner au format YYYY-MM-DD pour l'input date
        return date.toISOString().split('T')[0];
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
            showError('Impossible d\'afficher le profil public - Rôle incompatible');
            return;
        }
        
        // Ouvrir le profil dans un nouvel onglet
        window.open(url, '_blank');
    } catch (error) {
        console.error('❌ Erreur création profil:', error);
        showError('Impossible d\'afficher le profil public - Erreur de génération du lien');
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
                    avatarPreview.value = ''; // Réinitialiser l'aperçu
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

const verificationStatus = computed(() => {
    // Essayer d'abord props.user.babysitterProfile, puis props.babysitterProfile
    const babysitterProfile = props.user.babysitterProfile || props.babysitterProfile;

    if (!babysitterProfile) {
        return null;
    }

    const status = babysitterProfile.verification_status;
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

    // Vérifier que le profil est complété à au moins 50%
    if (currentMode.value === 'babysitter' && babysitterProfileCompletion.value < 50) {
        showError(
            'Profil incomplet', 
            `Votre profil doit être complété à au moins 50% pour demander une vérification. Actuellement: ${babysitterProfileCompletion.value}%`
        );
        return;
    }

    isRequestingVerification.value = true;

    console.log('🚀 Demande de vérification - Début');
    console.log('📋 Statut actuel avant demande:', verificationStatus.value);

    try {
        router.post(
            route('babysitter.request-verification'),
            {},
            {
                onSuccess: (page: any) => {
                    console.log('✅ Réponse serveur onSuccess:', page);
                    
                    // Vérifier d'abord les flash messages dans la page Inertia
                    if (page.props?.flash?.success) {
                        showSuccess('✅ Demande envoyée !', page.props.flash.success);
                    } else if (page.props?.flash?.error) {
                        showError('❌ Erreur', page.props.flash.error);
                        return;
                    } else {
                        // Fallback si pas de flash message
                        showSuccess(
                            '✅ Demande envoyée !', 
                            'Votre demande de vérification a été envoyée avec succès. Nos modérateurs vont examiner votre profil sous 24h.'
                        );
                    }

                    // Force la mise à jour du statut localement IMMÉDIATEMENT
                    if (babysitterProfile.value) {
                        babysitterProfile.value.verification_status = 'pending';
                    }

                    console.log('📋 Statut forcé à pending localement');

                    // Rafraîchir la page après un court délai pour synchroniser avec le serveur
                    setTimeout(() => {
                        router.reload();
                    }, 1500);
                },
                onError: (errors: any) => {
                    console.error('❌ Erreur demande vérification:', errors);

                    if (errors.message) {
                        showError(errors.message);
                    } else {
                        showError("Une erreur est survenue lors de l'envoi de la demande");
                    }
                },
            },
        );
    } catch (error: any) {
        console.error('❌ Erreur inattendue:', error);
        showError('Une erreur inattendue est survenue');
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

// Fonction pour fermer la bannière de vérification
const closeVerificationBanner = () => {
    isVerificationBannerVisible.value = false;
    localStorage.setItem('verification-banner-closed', 'true');
};

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
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Mon profil</h1>
                <p class="mt-1 sm:mt-2 text-sm sm:text-base text-gray-600">Gérez vos informations personnelles</p>
            </div>

            <!-- ENCADRÉ VÉRIFICATION EN HAUT -->
            <div v-if="currentMode === 'babysitter' && isVerificationBannerVisible" class="mb-4 sm:mb-6">
                <div class="flex flex-col gap-3 sm:gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex-1">
                        <div class="relative rounded border-l-4 border-blue-400 bg-blue-50 p-3 sm:p-4">
                            <!-- Bouton de fermeture -->
                            <button
                                @click="closeVerificationBanner"
                                class="absolute top-1 right-1 sm:top-2 sm:right-2 flex h-5 w-5 sm:h-6 sm:w-6 items-center justify-center rounded-full text-blue-400 transition-colors hover:bg-blue-100 hover:text-blue-600"
                                title="Masquer cette bannière"
                            >
                                <X class="h-3 w-3 sm:h-4 sm:w-4" />
                            </button>

                            <p class="pr-6 sm:pr-8 text-sm sm:text-base text-blue-800">
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
                            class="flex items-center gap-1 sm:gap-2 rounded-md bg-yellow-100 px-2 sm:px-4 py-1 sm:py-2 text-xs sm:text-sm text-yellow-800"
                        >
                            <div class="h-1.5 w-1.5 sm:h-2 sm:w-2 animate-pulse rounded-full bg-yellow-500"></div>
                            <span class="hidden sm:inline">Vérification en cours</span>
                            <span class="sm:hidden">En cours</span>
                        </div>
                        <Button
                            v-else-if="verificationStatus === 'rejected'"
                            @click="requestVerification"
                            :disabled="isRequestingVerification"
                            class="bg-blue-600 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50 text-xs sm:text-sm px-2 sm:px-4 py-1 sm:py-2"
                        >
                            <span class="hidden sm:inline">{{ isRequestingVerification ? 'Envoi en cours...' : 'Soumettre une nouvelle demande' }}</span>
                            <span class="sm:hidden">{{ isRequestingVerification ? 'Envoi...' : 'Nouvelle demande' }}</span>
                        </Button>
                        <Button
                            v-else
                            @click="requestVerification"
                            :disabled="isRequestingVerification || verificationStatus === 'pending' || (currentMode === 'babysitter' && babysitterProfileCompletion < 50)"
                            :class="[
                                'text-xs sm:text-sm px-2 sm:px-4 py-1 sm:py-2 disabled:cursor-not-allowed disabled:opacity-50',
                                currentMode === 'babysitter' && babysitterProfileCompletion < 50 
                                    ? 'bg-gray-400 text-gray-700 cursor-not-allowed' 
                                    : 'bg-blue-600 text-white hover:bg-blue-700'
                            ]"
                            :title="currentMode === 'babysitter' && babysitterProfileCompletion < 50 ? `Profil complété à ${babysitterProfileCompletion}% (minimum 50% requis)` : ''"
                        >
                            <span class="hidden sm:inline">
                                {{ isRequestingVerification ? 'Envoi en cours...' : 
                                   (currentMode === 'babysitter' && babysitterProfileCompletion < 50) ? `Compléter le profil (${babysitterProfileCompletion}%)` : 
                                   'Demander la vérification' }}
                            </span>
                            <span class="sm:hidden">
                                {{ isRequestingVerification ? 'Envoi...' : 
                                   (currentMode === 'babysitter' && babysitterProfileCompletion < 50) ? `${babysitterProfileCompletion}%` : 
                                   'Vérification' }}
                            </span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- BARRE DE PROGRESSION DU PROFIL BABYSITTER -->
            <div v-if="currentMode === 'babysitter' && props.hasBabysitterRole" class="mb-4 sm:mb-6">
                <div
                    :class="[
                        'rounded-lg border p-4 sm:p-6 shadow-sm transition-all duration-300',
                        babysitterProfileCompletion >= 80
                            ? 'border-green-200 bg-gradient-to-r from-green-50 to-emerald-50'
                            : babysitterProfileCompletion >= 50
                              ? 'border-yellow-200 bg-gradient-to-r from-yellow-50 to-orange-50'
                              : 'border-red-200 bg-gradient-to-r from-red-50 to-pink-50',
                    ]"
                >
                    <div class="mb-3 sm:mb-4 flex items-center gap-2 sm:gap-3">
                        <div
                            :class="[
                                'flex h-6 w-6 sm:h-8 sm:w-8 items-center justify-center rounded-full transition-all duration-300',
                                babysitterProfileCompletion >= 80
                                    ? 'bg-green-100'
                                    : babysitterProfileCompletion >= 50
                                      ? 'bg-yellow-100'
                                      : 'bg-red-100',
                            ]"
                        >
                            <svg v-if="babysitterProfileCompletion >= 80" class="h-3 w-3 sm:h-5 sm:w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <svg
                                v-else-if="babysitterProfileCompletion >= 50"
                                class="h-3 w-3 sm:h-5 sm:w-5 text-yellow-600"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <svg v-else class="h-3 w-3 sm:h-5 sm:w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-lg sm:text-xl font-bold text-gray-900">🚀 Complétez votre profil pour attirer plus de parents !</h2>
                            <p
                                :class="[
                                    'text-xs sm:text-sm font-medium',
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
                        <div class="relative h-2 sm:h-3 w-full rounded-full bg-white/50 shadow-inner">
                            <div
                                :class="[
                                    'h-2 sm:h-3 rounded-full shadow-sm transition-all duration-500 ease-out',
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
                            <div class="absolute top-0 left-1/2 h-2 sm:h-3 w-0.5 -translate-x-0.5 transform rounded-full bg-white/60"></div>
                            <div class="absolute top-0 left-4/5 h-2 sm:h-3 w-0.5 -translate-x-0.5 transform rounded-full bg-white/60"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>0%</span>
                            <span class="hidden sm:inline">50%</span>
                            <span class="hidden sm:inline">80%</span>
                            <span>100%</span>
                        </div>
                    </div>

                    <!-- Bouton de demande de vérification -->
                    <div class="mt-6 flex justify-center">
                        <Button
                            v-if="verificationStatus === 'rejected'"
                            @click="requestVerification"
                            :disabled="isRequestingVerification || babysitterProfileCompletion < 50"
                            :class="[
                                'relative px-8 py-3 rounded-lg font-medium text-sm shadow-lg transform transition-all duration-200 hover:scale-105',
                                babysitterProfileCompletion < 50 
                                    ? 'bg-gray-400 text-gray-700 cursor-not-allowed shadow-gray-200' 
                                    : 'bg-gradient-to-r from-primary to-orange-500 text-white hover:from-orange-500 hover:to-primary shadow-orange-200'
                            ]"
                            :title="babysitterProfileCompletion < 50 ? `Profil complété à ${babysitterProfileCompletion}% (minimum 50% requis)` : ''"
                        >
                            <div v-if="isRequestingVerification" class="flex items-center gap-2">
                                <div class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                <span>Envoi en cours...</span>
                            </div>
                            <div v-else class="flex items-center gap-2">
                                <Shield class="h-4 w-4" />
                                <span>{{ babysitterProfileCompletion < 50 ? `Compléter le profil (${babysitterProfileCompletion}%)` : 'Soumettre une nouvelle demande' }}</span>
                            </div>
                        </Button>
                        <Button
                            v-else
                            @click="requestVerification"
                            :disabled="isRequestingVerification || babysitterProfileCompletion < 50"
                            :class="[
                                'relative px-8 py-3 rounded-lg font-medium text-sm shadow-lg transform transition-all duration-200 hover:scale-105',
                                babysitterProfileCompletion < 50 
                                    ? 'bg-gray-400 text-gray-700 cursor-not-allowed shadow-gray-200' 
                                    : 'bg-gradient-to-r from-primary to-orange-500 text-white hover:from-orange-500 hover:to-primary shadow-orange-200'
                            ]"
                            :title="babysitterProfileCompletion < 50 ? `Profil complété à ${babysitterProfileCompletion}% (minimum 50% requis)` : ''"
                        >
                            <div v-if="isRequestingVerification" class="flex items-center gap-2">
                                <div class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                <span>Envoi en cours...</span>
                            </div>
                            <div v-else class="flex items-center gap-2">
                                <Shield class="h-4 w-4" />
                                <span>{{ babysitterProfileCompletion < 50 ? `Compléter le profil (${babysitterProfileCompletion}%)` : '🚀 Demander la vérification' }}</span>
                            </div>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Header Card -->
            <Card class="mb-4 sm:mb-6 py-0">
                <CardHeader class="from-primary/40 to-secondary/10 rounded-t-xl bg-gradient-to-b py-3 sm:py-5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-3 sm:gap-6">
                            <div class="relative">
                                <img
                                    :src="avatarPreview || user.avatar_url || user.avatar || '/storage/babysitter-test.png'"
                                    :alt="`Avatar de ${fullName}`"
                                    class="h-16 w-16 sm:h-24 sm:w-24 rounded-full border-2 sm:border-4 border-white object-cover shadow-lg"
                                />
                                <div
                                    v-if="isEditing"
                                    @click="triggerAvatarInput"
                                    class="absolute right-0 bottom-0 cursor-pointer rounded-full bg-white p-1 sm:p-2 shadow-md transition-colors hover:bg-gray-50"
                                >
                                    <Camera class="h-3 w-3 sm:h-4 sm:w-4 text-gray-500" />
                                </div>
                                <input ref="avatarInput" type="file" accept="image/*" @change="handleAvatarChange" class="hidden" />
                            </div>
                            <div>
                                <h2 class="text-xl sm:text-2xl font-semibold text-gray-900">{{ fullName }}</h2>
                                <p class="text-xs sm:text-sm text-gray-500">{{ userInfo }}</p>
                                <div class="mt-1 flex flex-col sm:flex-row items-start sm:items-center gap-1 sm:gap-2">
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

                        <div v-if="!isEditing" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 w-full sm:w-auto">
                            <Button @click="viewMyProfile" variant="outline" class="flex items-center justify-center gap-2 text-sm">
                                <ExternalLink class="h-3 w-3 sm:h-4 sm:w-4" />
                                <span class="hidden sm:inline">Voir mon profil</span>
                                <span class="sm:hidden">Mon profil</span>
                            </Button>
                            <Button @click="toggleEdit" class="bg-primary hover:bg-orange-500 text-sm"> 
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
                                <Mail class="absolute top-1/2 left-2 sm:left-3 h-3 w-3 sm:h-4 sm:w-4 -translate-y-1/2 text-gray-400" />
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    :disabled="!isEditing || isGoogleOnlyUser"
                                    class="pl-8 sm:pl-10 text-sm"
                                    required
                                />
                            </div>
                            <p v-if="isGoogleOnlyUser" class="text-xs text-green-600">✓ Géré par Google</p>
                        </div>

                        <!-- Message informatif pour les utilisateurs Google -->
                        <div v-if="isGoogleOnlyUser" class="rounded-lg border border-green-200 bg-green-50 p-3 sm:p-4">
                            <div class="flex items-start sm:items-center space-x-2 sm:space-x-3">
                                <div class="flex-shrink-0">
                                    <svg class="h-4 w-4 sm:h-5 sm:w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-green-900">Compte connecté via Google</h4>
                                    <p class="mt-1 text-xs sm:text-sm text-green-700">
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
                                <MapPin class="absolute top-1/2 left-2 sm:left-3 h-3 w-3 sm:h-4 sm:w-4 -translate-y-1/2 text-gray-400" />
                                <Input
                                    id="address-input"
                                    v-model="addressData.address"
                                    :disabled="!isEditing"
                                    placeholder="Votre adresse complète"
                                    class="pr-8 sm:pr-10 pl-8 sm:pl-10 text-sm"
                                    @input="onAddressChange"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Enfants (seulement en mode parent) -->
                        <div v-if="currentMode === 'parent' && hasParentRole" class="space-y-3 sm:space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <Label class="text-base sm:text-lg font-medium">Enfants</Label>
                                <Button v-if="isEditing" type="button" @click="addChild" variant="outline" size="sm" class="flex items-center gap-1 sm:gap-2 text-sm self-start sm:self-auto">
                                    <Plus class="h-3 w-3 sm:h-4 sm:w-4" />
                                    <span class="hidden sm:inline">Ajouter un enfant</span>
                                    <span class="sm:hidden">Ajouter</span>
                                </Button>
                            </div>

                            <div class="space-y-2 sm:space-y-3">
                                <div
                                    v-for="(enfant, index) in form.children"
                                    :key="index"
                                    class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 rounded-lg border bg-gray-50 p-2 sm:p-3"
                                >
                                    <div class="flex-1">
                                        <Input v-model="enfant.nom" :disabled="!isEditing" placeholder="Prénom de l'enfant (ex: Sophie)" required class="text-sm" />
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
                                            class="rounded-md border border-gray-300 bg-white px-2 sm:px-3 py-1 sm:py-2 text-xs sm:text-sm disabled:bg-gray-100"
                                        >
                                            <option value="mois">mois</option>
                                            <option value="ans">ans</option>
                                        </select>
                                        <Button v-if="isEditing" type="button" @click="removeChild(index)" variant="destructive" size="sm" class="p-1 sm:p-2">
                                            <Trash2 class="h-3 w-3 sm:h-4 sm:w-4" />
                                        </Button>
                                    </div>
                                </div>

                                <div
                                    v-if="form.children.length === 0 && isEditing"
                                    class="cursor-pointer rounded-lg border border-dashed border-gray-300 p-4 sm:p-6 text-center text-gray-500 transition-colors hover:bg-gray-50"
                                    @click="addChild"
                                >
                                    <Plus class="mx-auto mb-1 sm:mb-2 h-5 w-5 sm:h-6 sm:w-6 text-gray-400" />
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

                            <!-- Section compte de paiement pour babysitters vérifiés -->
                            <div
                                v-if="user.role === 'babysitter' && babysitterProfile?.verification_status === 'verified'"
                                class="border-b border-gray-200 pb-4 sm:pb-6"
                            >
                                <h3 class="mb-3 sm:mb-4 text-base sm:text-lg font-semibold text-gray-900">Compte de paiement</h3>

                                <!-- Compte configuré -->
                                <div v-if="(user as any).stripe_account_status === 'active'" class="space-y-3 sm:space-y-4">
                                    <div class="rounded-lg border border-green-200 bg-green-50 p-3 sm:p-4">
                                        <div class="flex items-center">
                                            <CheckCircle class="mr-1 sm:mr-2 h-4 w-4 sm:h-5 sm:w-5 text-green-600" />
                                            <span class="text-sm font-medium text-green-800">Compte configuré et vérifié</span>
                                        </div>
                                        <p class="mt-1 text-xs sm:text-sm text-green-700">
                                            Vous pouvez recevoir des paiements. Les virements sont effectués chaque vendredi.
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3 sm:gap-4 md:grid-cols-3">
                                        <div class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4">
                                            <div class="flex items-center">
                                                <CreditCard class="mr-1 sm:mr-2 h-4 w-4 sm:h-5 sm:w-5 text-blue-600" />
                                                <span class="text-sm font-medium text-gray-900">Paiements</span>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-600">Activés</p>
                                        </div>

                                        <div class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4">
                                            <div class="flex items-center">
                                                <Building class="mr-1 sm:mr-2 h-4 w-4 sm:h-5 sm:w-5 text-green-600" />
                                                <span class="text-sm font-medium text-gray-900">Virements</span>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-600">Activés</p>
                                        </div>

                                        <div class="rounded-lg border border-gray-200 bg-white p-3 sm:p-4">
                                            <div class="flex items-center">
                                                <Shield class="mr-1 sm:mr-2 h-4 w-4 sm:h-5 sm:w-5 text-blue-600" />
                                                <span class="text-sm font-medium text-gray-900">Vérification</span>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-600">Complète</p>
                                        </div>
                                    </div>

                                    <div class="flex gap-2 sm:gap-3">
                                        <Button variant="outline" @click="router.visit('/stripe/connect')" class="flex-1 text-sm">
                                            <TrendingUp class="mr-1 sm:mr-2 h-3 w-3 sm:h-4 sm:w-4" />
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
                        <div v-if="isEditing" class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 border-t pt-4 sm:pt-6">
                            <Button type="button" @click="toggleEdit" variant="outline" :disabled="isLoading" class="order-2 sm:order-1 text-sm"> 
                                Annuler 
                            </Button>
                            <Button type="submit" class="bg-primary hover:bg-orange-500 order-1 sm:order-2 text-sm" :disabled="isLoading">
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
