<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import { useUserMode } from '@/composables/useUserMode';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { router } from '@inertiajs/vue3';
import { Camera, Mail, MapPin, Plus, Trash2, Users, Baby } from 'lucide-vue-next';
import { computed, nextTick, ref, watch, onMounted } from 'vue';
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
    avatar?: string;
    address?: Address;
    parentProfile?: {
        children_ages: Child[];
    };
}

interface Props {
    user: User;
    userRoles: string[];
    hasParentRole: boolean;
    hasBabysitterRole: boolean;
    requestedMode?: 'parent' | 'babysitter';
    children?: Child[];
}

const props = defineProps<Props>();
const { showSuccess, showError } = useToast();
const { currentMode, initializeMode, setMode } = useUserMode();

const isEditing = ref(false);
const isLoading = ref(false);
const isGoogleLoaded = ref(false);
let autocomplete: any;

// Initialiser le mode au montage du composant
onMounted(() => {
    initializeMode(
        props.hasParentRole, 
        props.hasBabysitterRole, 
        props.requestedMode
    );
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
    router.get(route('profil', { mode }), {}, {
        preserveState: false,
        preserveScroll: true
    });
};

// Formulaire
const form = ref({
    firstname: props.user.firstname || '',
    lastname: props.user.lastname || '',
    email: props.user.email || '',
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
            fields: ['formatted_address', 'address_components', 'geometry'],
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

    // Combiner les données du formulaire avec les données d'adresse
    const formData = {
        ...form.value,
        ...addressData.value,
    };

    // Convertir l'âge des enfants en string (requis par le backend)
    if (formData.children && formData.children.length > 0 && currentMode.value === 'parent') {
        formData.children = formData.children.map((child) => ({
            ...child,
            age: String(child.age), // Convertir en string
        }));
    } else if (currentMode.value === 'babysitter') {
        // En mode babysitter, on n'envoie pas les enfants
        delete formData.children;
    }

    // Validation côté frontend avant soumission
    const requiredFields = ['firstname', 'lastname', 'email', 'address', 'postal_code', 'country'];
    const missingFields: string[] = [];

    requiredFields.forEach((field) => {
        if (!formData[field as keyof typeof formData] || String(formData[field as keyof typeof formData]).trim() === '') {
            missingFields.push(field);
        }
    });

    if (missingFields.length > 0) {
        console.error('❌ Champs manquants:', missingFields);
        showError(`Champs requis manquants: ${missingFields.join(', ')}`);
        isLoading.value = false;
        return;
    }

    console.log('📤 Données du formulaire à envoyer:', formData);

    try {
        router.put(route('profil.update'), formData, {
            onSuccess: (page) => {
                isEditing.value = false;
                showSuccess('Profil mis à jour avec succès !');
                console.log('✅ Profil mis à jour:', page.props);
            },
            onError: (errors) => {
                console.error('❌ Erreurs de validation serveur:', errors);

                // Affichage d'erreurs spécifiques
                const errorMessages = Object.keys(errors)
                    .map((key) => {
                        const messages = Array.isArray(errors[key]) ? errors[key] : [errors[key]];
                        return `${key}: ${messages.join(', ')}`;
                    })
                    .join('\n');

                showError(`Erreurs de validation:\n${errorMessages}`);
            },
        });
    } catch (error) {
        console.error('❌ Erreur:', error);
        showError('Une erreur est survenue lors de la mise à jour');
    } finally {
        isLoading.value = false;
    }
};

// Données calculées
const fullName = computed(() => `${props.user.firstname} ${props.user.lastname}`);
const userInfo = computed(() => {
    if (currentMode.value === 'parent') {
        const childCount = props.children?.length || 0;
        return `Parent de ${childCount} enfant${childCount > 1 ? 's' : ''}`;
    }
    return 'Babysitter';
});
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
                                :class="currentMode === 'babysitter' ? 'bg-primary text-white hover:bg-orange-500' : 'text-gray-600 hover:bg-gray-100'"
                            >
                                <Baby class="h-4 w-4" />
                                Babysitter
                            </Button>
                        </div>
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
                                    :src="user.avatar || '/storage/babysitter-test.png'"
                                    :alt="`Avatar de ${fullName}`"
                                    class="h-24 w-24 rounded-full border-4 border-white object-cover shadow-lg"
                                />
                                <div
                                    class="absolute right-0 bottom-0 cursor-pointer rounded-full bg-white p-2 shadow-md transition-colors hover:bg-gray-50"
                                >
                                    <Camera class="h-4 w-4 text-gray-500" />
                                </div>
                            </div>
                            <div>
                                <h2 class="text-2xl font-semibold text-gray-900">{{ fullName }}</h2>
                                <p class="text-sm text-gray-500">{{ userInfo }}</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <div
                                        :class="[
                                            'rounded-full px-2 py-1 text-xs font-medium',
                                            currentMode === 'parent' 
                                                ? 'bg-blue-100 text-blue-800' 
                                                : 'bg-orange-100 text-orange-800'
                                        ]"
                                    >
                                        Mode {{ currentMode === 'parent' ? 'Parent' : 'Babysitter' }}
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
                                <Input id="firstname" v-model="form.firstname" :disabled="!isEditing" required />
                            </div>
                            <div class="space-y-2">
                                <Label for="lastname">Nom</Label>
                                <Input id="lastname" v-model="form.lastname" :disabled="!isEditing" required />
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <div class="relative">
                                <Mail class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <Input id="email" type="email" v-model="form.email" :disabled="!isEditing" class="pl-10" required />
                            </div>
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
                            <!-- Vérification d'identité -->
                            <Card class="border border-green-200 bg-green-50">
                                <CardContent class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-100">
                                            <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-green-800">Vérification d'identité</p>
                                            <p class="text-sm text-green-700">Votre identité a été vérifiée le <strong>10 mars 2024</strong></p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
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
