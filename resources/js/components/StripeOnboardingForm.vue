<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/composables/useToast';
import { router } from '@inertiajs/vue3';
import {
    AlertCircle,
    Building,
    CheckCircle,
    CreditCard,
    Info,
    Shield,
    User,
} from 'lucide-vue-next';
import { computed, nextTick, reactive, ref } from 'vue';

interface Props {
    user: {
        id: number;
        firstname: string;
        lastname: string;
        email: string;
        phone?: string;
        date_of_birth?: string;
        address?: {
            address: string;
            postal_code: string;
            city: string;
        };
    };
    accountStatus: string;
    stripeAccountId?: string;
    googlePlacesApiKey?: string;
}

const props = defineProps<Props>();

const { showSuccess, showError, handleApiError } = useToast();

const isLoading = ref(false);
const currentStep = ref(1);
const errorMessage = ref('');

// Autocomplete Google Places
const isGoogleLoaded = ref(false);
let autocomplete: any;


// Récupérer les anciennes valeurs depuis la session Laravel (preserved on error)
const oldData = (window as any)?.Laravel?.oldInput || {};

// Données du formulaire pré-remplies avec les informations utilisateur ou les anciennes valeurs
const formData = reactive({
    // Informations personnelles
    first_name: oldData.first_name || props.user.firstname || '',
    last_name: oldData.last_name || props.user.lastname || '',
    email: oldData.email || props.user.email || '',
    phone: oldData.phone || props.user.phone || '',
    
    // Date de naissance
    dob_day: oldData.dob_day || '',
    dob_month: oldData.dob_month || '',
    dob_year: oldData.dob_year || '',
    
    // Adresse (vide pour permettre la saisie libre avec autocomplete)
    address_line1: oldData.address_line1 || '',
    address_city: oldData.address_city || '',
    address_postal_code: oldData.address_postal_code || '',
    address_country: oldData.address_country || 'FR',
    
    // Informations bancaires
    account_holder_name: oldData.account_holder_name || `${props.user.firstname} ${props.user.lastname}`.trim(),
    iban: oldData.iban || '',
    
    // Profil business
    business_description: oldData.business_description || 'Services de garde d\'enfants et babysitting',
    mcc: oldData.mcc || '8299',
    
    // Conditions d'utilisation (jamais pré-remplie pour la sécurité)
    tos_acceptance: false,
});

// Pré-remplir la date de naissance si disponible
if (props.user.date_of_birth) {
    const dob = new Date(props.user.date_of_birth);
    formData.dob_day = dob.getDate().toString();
    formData.dob_month = (dob.getMonth() + 1).toString();
    formData.dob_year = dob.getFullYear().toString();
}

const steps = [
    {
        id: 1,
        title: 'Informations personnelles',
        description: 'Vérifiez vos informations de base',
        icon: User,
    },
    {
        id: 2,
        title: 'Adresse',
        description: 'Confirmez votre adresse de résidence',
        icon: Building,
    },
    {
        id: 3,
        title: 'Informations bancaires',
        description: 'Ajoutez vos coordonnées bancaires',
        icon: CreditCard,
    },
    {
        id: 4,
        title: 'Finalisation',
        description: 'Acceptez les conditions et finalisez',
        icon: Shield,
    },
];

const currentStepData = computed(() => steps.find(step => step.id === currentStep.value));

const canProceedToNext = computed(() => {
    switch (currentStep.value) {
        case 1:
            return formData.first_name && formData.last_name && formData.email && 
                   formData.dob_day && formData.dob_month && formData.dob_year;
        case 2:
            return formData.address_line1 && formData.address_city && formData.address_postal_code;
        case 3:
            return formData.iban && formData.account_holder_name;
        case 4:
            return formData.tos_acceptance;
        default:
            return false;
    }
});

const nextStep = () => {
    if (currentStep.value < 4 && canProceedToNext.value) {
        currentStep.value++;
    }
};

const previousStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const submitOnboarding = async () => {
    if (!canProceedToNext.value || isLoading.value) return;

    isLoading.value = true;

    try {
        // Utiliser une route existante avec un paramètre pour l'onboarding interne
        router.post('/stripe/create-onboarding-link', {
            ...formData,
            internal_onboarding: true
        }, {
            onSuccess: (page) => {
                showSuccess('✅ Compte configuré avec succès !', 'Votre compte Stripe Connect est maintenant configuré');
                
                // Rediriger vers la page de paiements après succès
                setTimeout(() => {
                    router.visit('/babysitter/paiements');
                }, 1500);
            },
            onError: (errors) => {
                console.error('Erreur onboarding:', errors);
                
                // Gérer les erreurs spécifiques
                let errorMsg = '';
                if (typeof errors === 'object' && errors !== null) {
                    errorMsg = Object.values(errors)[0] as string || 'Erreur lors de la configuration du compte';
                } else if (typeof errors === 'string') {
                    errorMsg = errors;
                } else {
                    errorMsg = 'Erreur lors de la configuration du compte';
                }
                
                // Gestion spécifique pour l'erreur de comptes français
                if (errorMsg.includes('Connect platforms based in FR must create accounts via account tokens') || 
                    errorMsg.includes('Configuration requise pour les comptes français') ||
                    errorMsg.includes('Configuration avec account tokens en cours')) {
                    errorMsg = 'Configuration avec account tokens Stripe en cours. Cette méthode est recommandée pour les comptes français.';
                    
                    // Pour la nouvelle implémentation avec account tokens, proposer un retry automatique
                    if (errorMsg.includes('Configuration avec account tokens en cours')) {
                        errorMsg += ' La configuration se fait maintenant automatiquement avec les account tokens Stripe.';
                    } else {
                        // Ancienne erreur - proposer l'onboarding externe
                        setTimeout(() => {
                            if (confirm('Souhaitez-vous être redirigé vers la configuration Stripe externe ?')) {
                                startExternalOnboarding();
                            }
                        }, 2000);
                    }
                }
                
                errorMessage.value = errorMsg;
                showError('❌ Erreur de configuration', errorMsg);
                
                // Les valeurs du formulaire sont automatiquement préservées
            },
            onFinish: () => {
                isLoading.value = false;
            }
        });
    } catch (err) {
        console.error('Erreur onboarding:', err);
        handleApiError(err);
        isLoading.value = false;
    }
};

// Charger Google Places API
const loadGooglePlaces = () => {
    // Si déjà chargé, initialiser directement
    if (window.google?.maps?.places) {
        isGoogleLoaded.value = true;
        initAutocomplete();
        return;
    }

    const apiKey = props.googlePlacesApiKey;
    if (!apiKey) {
        console.warn('Clé API Google Places manquante');
        return;
    }

    // Créer une fonction de callback globale
    (window as any).initGooglePlacesCallback = () => {
        isGoogleLoaded.value = true;
        initAutocomplete();
    };

    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=initGooglePlacesCallback`;
    script.async = true;
    script.defer = true;

    document.head.appendChild(script);
};

const initAutocomplete = async () => {
    await nextTick();
    const input = document.getElementById('address-input-onboarding') as HTMLInputElement;
    if (!input || !window.google?.maps?.places) return;

    autocomplete = new (window as any).google.maps.places.Autocomplete(input, {
        types: ['address'],
        componentRestrictions: { country: 'fr' }
    });

    // Configuration des champs retournés
    autocomplete.setFields(['formatted_address', 'address_components', 'geometry']);

    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (place.formatted_address) {
            formData.address_line1 = place.formatted_address;
            formData.address_city = '';
            formData.address_postal_code = '';

            if (place.address_components) {
                place.address_components.forEach((component: any) => {
                    const types = component.types;
                    if (types.includes('postal_code')) {
                        formData.address_postal_code = component.long_name;
                    }
                    if (types.includes('locality') || types.includes('administrative_area_level_2')) {
                        formData.address_city = component.long_name;
                    }
                });
            }
        }
    });
};

// Validation IBAN basique
const validateIban = (iban: string) => {
    const ibanRegex = /^[A-Z]{2}[0-9]{2}[A-Z0-9]{4}[0-9]{7}([A-Z0-9]?){0,16}$/;
    return ibanRegex.test(iban.replace(/\s/g, '').toUpperCase());
};

const formatIban = (value: string) => {
    // Supprimer les espaces et mettre en majuscules
    const cleanValue = value.replace(/\s/g, '').toUpperCase();
    // Ajouter des espaces tous les 4 caractères
    return cleanValue.replace(/(.{4})/g, '$1 ').trim();
};

const onIbanInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const formatted = formatIban(target.value);
    formData.iban = formatted;
    target.value = formatted;
};

const isIbanValid = computed(() => {
    if (!formData.iban) return true; // Valide si vide (pas encore saisi)
    return validateIban(formData.iban);
});

// Fonction pour vider l'erreur quand l'utilisateur modifie un champ
const clearError = () => {
    if (errorMessage.value) {
        errorMessage.value = '';
    }
};

// Fonction pour démarrer l'onboarding externe en cas d'erreur française
const startExternalOnboarding = async () => {
    try {
        const response = await fetch('/stripe/create-onboarding-link', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        const data = await response.json();

        if (response.ok && data.onboarding_url) {
            window.location.href = data.onboarding_url;
        } else {
            showError('❌ Erreur', data.error || 'Erreur lors de la création du lien externe');
        }
    } catch (err) {
        showError('❌ Erreur', 'Erreur lors de la redirection vers Stripe');
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Progress bar -->
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div 
                class="bg-primary h-2 rounded-full transition-all duration-300"
                :style="{ width: `${(currentStep / 4) * 100}%` }"
            ></div>
        </div>

        <!-- Step indicator -->
        <div class="flex justify-between items-center">
            <div
                v-for="step in steps"
                :key="step.id"
                class="flex items-center space-x-2"
                :class="step.id <= currentStep ? 'text-primary' : 'text-gray-400'"
            >
                <div
                    class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
                    :class="step.id <= currentStep ? 'bg-primary text-white' : 'bg-gray-200 text-gray-400'"
                >
                    <component v-if="step.id <= currentStep" :is="step.icon" class="w-4 h-4" />
                    <span v-else>{{ step.id }}</span>
                </div>
                <span class="hidden sm:block text-sm font-medium">{{ step.title }}</span>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center">
                    <component :is="currentStepData?.icon" class="mr-2 h-5 w-5" />
                    {{ currentStepData?.title }}
                </CardTitle>
                <p class="text-sm text-gray-600">{{ currentStepData?.description }}</p>
            </CardHeader>
            <CardContent>
                <!-- Affichage des erreurs persistantes -->
                <div v-if="errorMessage" class="mb-4 rounded-md border border-red-200 bg-red-50 p-4">
                    <div class="flex items-center">
                        <AlertCircle class="mr-2 h-4 w-4 text-red-500" />
                        <p class="text-sm text-red-700">{{ errorMessage }}</p>
                    </div>
                    
                    <!-- Bouton de fallback pour l'onboarding externe si erreur française -->
                    <div v-if="errorMessage.includes('Configuration requise pour les comptes français')" class="mt-3 flex gap-2">
                        <Button 
                            @click="startExternalOnboarding" 
                            variant="outline" 
                            size="sm"
                            class="text-xs"
                        >
                            Utiliser la configuration Stripe externe
                        </Button>
                        <button 
                            @click="errorMessage = ''" 
                            class="text-xs text-red-600 hover:text-red-800 underline"
                        >
                            Fermer
                        </button>
                    </div>
                    <button 
                        v-else
                        @click="errorMessage = ''" 
                        class="mt-2 text-xs text-red-600 hover:text-red-800 underline"
                    >
                        Fermer
                    </button>
                </div>

                <!-- Étape 1: Informations personnelles -->
                <div v-if="currentStep === 1" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <Label for="first_name">Prénom</Label>
                            <Input
                                id="first_name"
                                v-model="formData.first_name"
                                placeholder="Votre prénom"
                                @input="clearError"
                                required
                            />
                        </div>
                        <div>
                            <Label for="last_name">Nom</Label>
                            <Input
                                id="last_name"
                                v-model="formData.last_name"
                                placeholder="Votre nom"
                                @input="clearError"
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="formData.email"
                            type="email"
                            placeholder="votre.email@exemple.com"
                            @input="clearError"
                            required
                        />
                    </div>

                    <div>
                        <Label for="phone">Téléphone (optionnel)</Label>
                        <Input
                            id="phone"
                            v-model="formData.phone"
                            type="tel"
                            placeholder="06 12 34 56 78"
                        />
                    </div>

                    <div>
                        <Label>Date de naissance</Label>
                        <div class="grid grid-cols-3 gap-2">
                            <Select v-model="formData.dob_day">
                                <SelectTrigger>
                                    <SelectValue placeholder="Jour" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="day in 31" :key="day" :value="day.toString()">
                                        {{ day }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="formData.dob_month">
                                <SelectTrigger>
                                    <SelectValue placeholder="Mois" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="1">Janvier</SelectItem>
                                    <SelectItem value="2">Février</SelectItem>
                                    <SelectItem value="3">Mars</SelectItem>
                                    <SelectItem value="4">Avril</SelectItem>
                                    <SelectItem value="5">Mai</SelectItem>
                                    <SelectItem value="6">Juin</SelectItem>
                                    <SelectItem value="7">Juillet</SelectItem>
                                    <SelectItem value="8">Août</SelectItem>
                                    <SelectItem value="9">Septembre</SelectItem>
                                    <SelectItem value="10">Octobre</SelectItem>
                                    <SelectItem value="11">Novembre</SelectItem>
                                    <SelectItem value="12">Décembre</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="formData.dob_year">
                                <SelectTrigger>
                                    <SelectValue placeholder="Année" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem 
                                        v-for="year in Array.from({length: 50}, (_, i) => new Date().getFullYear() - 16 - i)"
                                        :key="year"
                                        :value="year.toString()"
                                    >
                                        {{ year }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <!-- Étape 2: Adresse -->
                <div v-if="currentStep === 2" class="space-y-4">
                    <div>
                        <Label for="address_line1">Adresse</Label>
                        <Input
                            id="address-input-onboarding"
                            v-model="formData.address_line1"
                            placeholder="123 rue de la République, Paris"
                            autocomplete="address-line1"
                            @focus="() => { if (!isGoogleLoaded) loadGooglePlaces(); }"
                            @input="clearError"
                            required
                        />
                        <p class="mt-1 text-xs text-gray-500">Commencez à taper pour voir les suggestions d'adresses</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <Label for="address_postal_code">Code postal</Label>
                            <Input
                                id="address_postal_code"
                                v-model="formData.address_postal_code"
                                placeholder="75001"
                                maxlength="5"
                                required
                            />
                        </div>
                        <div>
                            <Label for="address_city">Ville</Label>
                            <Input
                                id="address_city"
                                v-model="formData.address_city"
                                placeholder="Paris"
                                required
                            />
                        </div>
                    </div>
                </div>

                <!-- Étape 3: Informations bancaires -->
                <div v-if="currentStep === 3" class="space-y-4">
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 mb-6">
                        <div class="flex items-center mb-2">
                            <Shield class="mr-2 h-4 w-4 text-blue-600" />
                            <span class="text-sm font-medium text-blue-900">Sécurité bancaire</span>
                        </div>
                        <p class="text-sm text-blue-800">
                            Vos informations bancaires sont transmises directement à Stripe de manière sécurisée 
                            et chiffrée. Nous ne les stockons jamais sur nos serveurs.
                        </p>
                    </div>

                    <div>
                        <Label for="account_holder_name">Titulaire du compte</Label>
                        <Input
                            id="account_holder_name"
                            v-model="formData.account_holder_name"
                            placeholder="Prénom Nom"
                            required
                        />
                    </div>

                    <div>
                        <Label for="iban">IBAN</Label>
                        <Input
                            id="iban"
                            v-model="formData.iban"
                            @input="(e) => { onIbanInput(e); clearError(); }"
                            placeholder="FR76 1234 5678 9012 3456 7890 123"
                            :class="!isIbanValid ? 'border-red-500' : ''"
                            required
                        />
                        <p v-if="!isIbanValid" class="mt-1 text-sm text-red-600">
                            Format IBAN invalide
                        </p>
                    </div>
                </div>

                <!-- Étape 4: Finalisation -->
                <div v-if="currentStep === 4" class="space-y-6">
                    <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                        <div class="flex items-center mb-2">
                            <CheckCircle class="mr-2 h-4 w-4 text-green-600" />
                            <span class="text-sm font-medium text-green-900">Récapitulatif</span>
                        </div>
                        <div class="text-sm text-green-800 space-y-1">
                            <p><strong>Nom:</strong> {{ formData.first_name }} {{ formData.last_name }}</p>
                            <p><strong>Email:</strong> {{ formData.email }}</p>
                            <p><strong>Adresse:</strong> {{ formData.address_line1 }}, {{ formData.address_postal_code }} {{ formData.address_city }}</p>
                            <p><strong>Compte:</strong> {{ formData.account_holder_name }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 mb-4">
                            <div class="flex items-center mb-1">
                                <Shield class="mr-2 h-4 w-4 text-blue-600" />
                                <span class="text-sm font-medium text-blue-900">Sécurité renforcée</span>
                            </div>
                            <p class="text-xs text-blue-800">
                                Nous utilisons les account tokens Stripe, une méthode sécurisée recommandée 
                                pour les plateformes françaises qui permet un transfert direct et sécurisé de vos données vers Stripe.
                            </p>
                        </div>

                        <div class="flex items-start space-x-3">
                            <input
                                id="tos_acceptance"
                                v-model="formData.tos_acceptance"
                                type="checkbox"
                                class="mt-1"
                                required
                            />
                            <Label for="tos_acceptance" class="text-sm">
                                J'accepte les 
                                <a href="#" class="text-blue-600 hover:underline">conditions d'utilisation de Stripe</a>
                                et confirme que les informations fournies sont exactes.
                            </Label>
                        </div>
                    </div>
                </div>

                <!-- Boutons de navigation -->
                <div class="flex justify-between pt-6">
                    <Button
                        v-if="currentStep > 1"
                        variant="outline"
                        @click="previousStep"
                        :disabled="isLoading"
                    >
                        Précédent
                    </Button>
                    <div v-else></div>

                    <Button
                        v-if="currentStep < 4"
                        @click="nextStep"
                        :disabled="!canProceedToNext || isLoading"
                    >
                        Suivant
                    </Button>
                    <Button
                        v-else
                        @click="submitOnboarding"
                        :disabled="!canProceedToNext || isLoading"
                    >
                        <div v-if="isLoading" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                        {{ isLoading ? 'Configuration...' : 'Finaliser mon compte' }}
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>