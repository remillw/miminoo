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
    FileText,
    Info,
    Shield,
    Upload,
    User,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, reactive, ref } from 'vue';

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
const documentUploadRequired = ref(false);
const uploadedDocuments = ref<{ front?: File, back?: File }>({});

// Stripe.js
let stripe: any = null;

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
    
    // Documents d'identité (optionnels)
    identity_document_front: null as File | null,
    identity_document_back: null as File | null,
});

// Pré-remplir la date de naissance si disponible
if (props.user.date_of_birth) {
    const dob = new Date(props.user.date_of_birth);
    formData.dob_day = dob.getDate().toString();
    formData.dob_month = (dob.getMonth() + 1).toString();
    formData.dob_year = dob.getFullYear().toString();
}

const steps = computed(() => {
    const baseSteps = [
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
    ];
    
    if (documentUploadRequired.value) {
        baseSteps.push({
            id: 4,
            title: 'Documents d\'identité',
            description: 'Ajoutez vos documents de vérification',
            icon: FileText,
        });
    }
    
    baseSteps.push({
        id: documentUploadRequired.value ? 5 : 4,
        title: 'Finalisation',
        description: 'Acceptez les conditions et finalisez',
        icon: Shield,
    });
    
    return baseSteps;
});

const currentStepData = computed(() => steps.value.find(step => step.id === currentStep.value));

const maxSteps = computed(() => documentUploadRequired.value ? 5 : 4);

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
            // Si step 4 est documents, vérifier si au moins le recto est uploadé (optionnel)
            if (documentUploadRequired.value) {
                return true; // Documents optionnels - on peut continuer sans
            }
            // Si step 4 est finalisation, vérifier acceptance des conditions
            return formData.tos_acceptance;
        case 5:
            // Step 5 est toujours finalisation quand documents requis
            return formData.tos_acceptance;
        default:
            return false;
    }
});

const nextStep = () => {
    if (currentStep.value < maxSteps.value && canProceedToNext.value) {
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
        // Initialiser Stripe si pas déjà fait
        if (!stripe) {
            await loadStripe();
        }

        if (!stripe) {
            throw new Error('Impossible de charger Stripe.js');
        }

        // Formater le numéro de téléphone pour Stripe (format international)
        const formatPhoneForStripe = (phone: string) => {
            if (!phone) return undefined;
            
            // Nettoyer le numéro (garder seulement les chiffres)
            const cleanPhone = phone.replace(/[^0-9]/g, '');
            
            // Convertir les formats français vers +33
            if (cleanPhone.length === 10 && cleanPhone.startsWith('0')) {
                // Format 0781191375 -> +33781191375
                return '+33' + cleanPhone.substring(1);
            } else if (cleanPhone.length === 9 && !cleanPhone.startsWith('0')) {
                // Format 781191375 -> +33781191375
                return '+33' + cleanPhone;
            } else if (cleanPhone.startsWith('33') && cleanPhone.length === 11) {
                // Format 33781191375 -> +33781191375
                return '+' + cleanPhone;
            }
            
            // Si déjà au format +33, le retourner tel quel
            if (phone.startsWith('+33')) {
                return phone;
            }
            
            // Sinon, assumer que c'est un numéro français
            return cleanPhone.length >= 9 ? '+33' + cleanPhone.replace(/^0/, '') : undefined;
        };

        // Créer l'account token avec Stripe.js
        const accountTokenResult = await stripe.createToken('account', {
            business_type: 'individual',
            individual: {
                first_name: formData.first_name,
                last_name: formData.last_name,
                email: formData.email,
                phone: formatPhoneForStripe(formData.phone),
                dob: {
                    day: parseInt(formData.dob_day),
                    month: parseInt(formData.dob_month),
                    year: parseInt(formData.dob_year),
                },
                address: {
                    line1: formData.address_line1,
                    city: formData.address_city,
                    postal_code: formData.address_postal_code,
                    country: formData.address_country,
                },
            },
            tos_shown_and_accepted: formData.tos_acceptance,
        });

        if (accountTokenResult.error) {
            throw new Error(accountTokenResult.error.message);
        }
        
        // Préparer les données pour l'envoi
        const requestData = new FormData();
        requestData.append('account_token', accountTokenResult.token.id);
        requestData.append('internal_onboarding', 'true');
        requestData.append('iban', formData.iban);
        requestData.append('account_holder_name', formData.account_holder_name);
        requestData.append('business_description', formData.business_description);
        requestData.append('mcc', formData.mcc);
        
        // Ajouter les documents si présents
        if (formData.identity_document_front) {
            requestData.append('identity_document_front', formData.identity_document_front);
        }
        if (formData.identity_document_back) {
            requestData.append('identity_document_back', formData.identity_document_back);
        }
        
        // Utiliser fetch au lieu de router.post pour supporter FormData
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch('/stripe/internal-onboarding', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: requestData
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            showSuccess('✅ Compte configuré avec succès !', 'Votre compte Stripe Connect est maintenant configuré');
        } else {
            throw new Error(result.error || 'Erreur lors de la configuration du compte');
        }
    } catch (err) {
        console.error('Erreur onboarding:', err);
        let errorMsg = 'Erreur lors de la configuration du compte';
        if (err instanceof Error) {
            errorMsg = err.message;
        }
        errorMessage.value = errorMsg;
        showError('❌ Erreur de configuration', errorMsg);
        isLoading.value = false;
    }
};

// Charger Stripe.js
const loadStripe = async () => {
    if (stripe) return;

    // Vérifier si Stripe est déjà disponible globalement
    if ((window as any).Stripe) {
        stripe = (window as any).Stripe(import.meta.env.VITE_STRIPE_KEY);
        return;
    }

    // Charger Stripe.js dynamiquement
    const script = document.createElement('script');
    script.src = 'https://js.stripe.com/v3/';
    script.async = true;

    return new Promise<void>((resolve, reject) => {
        script.onload = () => {
            if ((window as any).Stripe) {
                stripe = (window as any).Stripe(import.meta.env.VITE_STRIPE_KEY);
                resolve();
            } else {
                reject(new Error('Stripe.js failed to load'));
            }
        };
        script.onerror = () => reject(new Error('Failed to load Stripe.js'));
        document.head.appendChild(script);
    });
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

// Vérifier les requirements au montage
onMounted(() => {
    checkDocumentRequirements();
});

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

// Validation du téléphone français
const isPhoneValid = computed(() => {
    if (!formData.phone) return true; // Optionnel
    
    const cleanPhone = formData.phone.replace(/[^0-9]/g, '');
    
    // Numéro français: 10 chiffres commençant par 0, ou 9 chiffres sans le 0
    return (cleanPhone.length === 10 && cleanPhone.startsWith('0')) ||
           (cleanPhone.length === 9 && !cleanPhone.startsWith('0')) ||
           (cleanPhone.startsWith('33') && cleanPhone.length === 11) ||
           formData.phone.startsWith('+33');
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
        const response = await fetch('/stripe/internal-onboarding', {
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

// Vérifier si des documents sont requis pour ce compte
const checkDocumentRequirements = async () => {
    try {
        if (!props.stripeAccountId) {
            // Pas de compte Stripe encore, supposer que documents peuvent être requis
            documentUploadRequired.value = true;
            return;
        }
        
        const response = await fetch(`/stripe/account-requirements/${props.stripeAccountId}`);
        const data = await response.json();
        
        if (response.ok && data.requirements) {
            // Vérifier si des documents d'identité sont requis
            const allRequirements = [...(data.requirements.currently_due || []), ...(data.requirements.eventually_due || [])];
            documentUploadRequired.value = allRequirements.some(req => 
                req.includes('individual.verification.document') || 
                req.includes('verification.document')
            );
        }
    } catch (error) {
        console.log('Impossible de vérifier les requirements, documents disponibles par défaut');
        documentUploadRequired.value = true;
    }
};

// Gestion de l'upload de documents
const handleDocumentUpload = (event: Event, type: 'front' | 'back') => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    
    if (file) {
        // Vérifier le type de fichier
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!allowedTypes.includes(file.type)) {
            showError('❌ Type de fichier invalide', 'Seuls les fichiers JPEG, PNG et PDF sont acceptés');
            return;
        }
        
        // Vérifier la taille (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            showError('❌ Fichier trop volumineux', 'La taille maximale est de 10MB');
            return;
        }
        
        if (type === 'front') {
            formData.identity_document_front = file;
            uploadedDocuments.value.front = file;
        } else {
            formData.identity_document_back = file;
            uploadedDocuments.value.back = file;
        }
        
        clearError();
    }
};

const removeDocument = (type: 'front' | 'back') => {
    if (type === 'front') {
        formData.identity_document_front = null;
        uploadedDocuments.value.front = undefined;
    } else {
        formData.identity_document_back = null;
        uploadedDocuments.value.back = undefined;
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Progress bar -->
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div 
                class="bg-primary h-2 rounded-full transition-all duration-300"
                :style="{ width: `${(currentStep / maxSteps) * 100}%` }"
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
                            placeholder="06 12 34 56 78 ou +33 6 12 34 56 78"
                            :class="!isPhoneValid ? 'border-red-500' : ''"
                        />
                        <p v-if="!isPhoneValid" class="mt-1 text-sm text-red-600">
                            Format de téléphone invalide (ex: 06 12 34 56 78)
                        </p>
                        <p v-else class="mt-1 text-xs text-gray-500">Format français accepté : 06 12 34 56 78</p>
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

                <!-- Étape 4: Documents d'identité (conditionnelle) -->
                <div v-if="currentStep === 4 && documentUploadRequired" class="space-y-6">
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 mb-6">
                        <div class="flex items-center mb-2">
                            <FileText class="mr-2 h-4 w-4 text-blue-600" />
                            <span class="text-sm font-medium text-blue-900">Documents d'identité (optionnel)</span>
                        </div>
                        <p class="text-sm text-blue-800">
                            Ces documents peuvent être requis par Stripe pour la vérification de votre compte.
                            Vous pouvez les ajouter maintenant ou les envoyer plus tard si nécessaire.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Document recto -->
                        <div class="space-y-3">
                            <Label>Document d'identité (recto)</Label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                                <input 
                                    type="file" 
                                    id="identity-front" 
                                    class="hidden" 
                                    accept="image/*,.pdf"
                                    @change="(e) => handleDocumentUpload(e, 'front')"
                                />
                                <div v-if="!uploadedDocuments.front">
                                    <Upload class="mx-auto h-12 w-12 text-gray-400 mb-3" />
                                    <label for="identity-front" class="cursor-pointer">
                                        <span class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                            Cliquez pour uploader
                                        </span>
                                        <span class="text-sm text-gray-500"> ou glissez-déposez</span>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-2">PNG, JPG, PDF jusqu'à 10MB</p>
                                </div>
                                <div v-else class="space-y-2">
                                    <CheckCircle class="mx-auto h-8 w-8 text-green-500" />
                                    <p class="text-sm font-medium text-gray-900">{{ uploadedDocuments.front.name }}</p>
                                    <p class="text-xs text-gray-500">{{ Math.round(uploadedDocuments.front.size / 1024) }} KB</p>
                                    <Button variant="outline" size="sm" @click="removeDocument('front')">
                                        Supprimer
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Document verso (optionnel) -->
                        <div class="space-y-3">
                            <Label>Document d'identité (verso - optionnel)</Label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                                <input 
                                    type="file" 
                                    id="identity-back" 
                                    class="hidden" 
                                    accept="image/*,.pdf"
                                    @change="(e) => handleDocumentUpload(e, 'back')"
                                />
                                <div v-if="!uploadedDocuments.back">
                                    <Upload class="mx-auto h-12 w-12 text-gray-400 mb-3" />
                                    <label for="identity-back" class="cursor-pointer">
                                        <span class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                            Cliquez pour uploader
                                        </span>
                                        <span class="text-sm text-gray-500"> ou glissez-déposez</span>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-2">PNG, JPG, PDF jusqu'à 10MB</p>
                                </div>
                                <div v-else class="space-y-2">
                                    <CheckCircle class="mx-auto h-8 w-8 text-green-500" />
                                    <p class="text-sm font-medium text-gray-900">{{ uploadedDocuments.back.name }}</p>
                                    <p class="text-xs text-gray-500">{{ Math.round(uploadedDocuments.back.size / 1024) }} KB</p>
                                    <Button variant="outline" size="sm" @click="removeDocument('back')">
                                        Supprimer
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4">
                        <h4 class="font-medium text-gray-900 mb-2">Types de documents acceptés</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• Carte d'identité française ou européenne</li>
                            <li>• Passeport en cours de validité</li>
                            <li>• Permis de conduire français</li>
                            <li>• Carte de séjour (pour les non-européens)</li>
                        </ul>
                    </div>
                </div>

                <!-- Étape 4/5: Finalisation -->
                <div v-if="(currentStep === 4 && !documentUploadRequired) || (currentStep === 5 && documentUploadRequired)" class="space-y-6">
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
                                <span class="text-sm font-medium text-blue-900">Sécurité renforcée avec Account Tokens</span>
                            </div>
                            <ul class="text-xs text-blue-800 space-y-1">
                                <li>• Vos données sont envoyées directement à Stripe (pas via nos serveurs)</li>
                                <li>• Chiffrement bancaire de niveau militaire</li>
                                <li>• Méthode recommandée pour les comptes français (conformité DSP2)</li>
                                <li>• Détection renforcée de la fraude par Stripe</li>
                            </ul>
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
                        v-if="currentStep < maxSteps"
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