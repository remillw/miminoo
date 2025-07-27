<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
import { computed, reactive, ref } from 'vue';

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
}

const props = defineProps<Props>();

const isLoading = ref(false);
const error = ref('');
const currentStep = ref(1);

// Données du formulaire pré-remplies avec les informations utilisateur
const formData = reactive({
    // Informations personnelles
    first_name: props.user.firstname || '',
    last_name: props.user.lastname || '',
    email: props.user.email || '',
    phone: props.user.phone || '',
    
    // Date de naissance
    dob_day: '',
    dob_month: '',
    dob_year: '',
    
    // Adresse
    address_line1: props.user.address?.address || '',
    address_city: props.user.address?.city || '',
    address_postal_code: props.user.address?.postal_code || '',
    address_country: 'FR',
    
    // Informations bancaires
    account_holder_name: `${props.user.firstname} ${props.user.lastname}`.trim(),
    iban: '',
    
    // Profil business
    business_description: 'Services de garde d\'enfants et babysitting',
    mcc: '8299',
    
    // Conditions d'utilisation
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
    error.value = '';

    try {
        const response = await fetch('/stripe/internal-onboarding', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(formData),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Rediriger vers la page de paiements avec un message de succès
            router.visit('/babysitter/paiements', {
                onSuccess: () => {
                    // Le message de succès sera géré par la page de destination
                }
            });
        } else {
            throw new Error(data.error || 'Erreur lors de la configuration du compte');
        }
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Une erreur est survenue';
    } finally {
        isLoading.value = false;
    }
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
</script>

<template>
    <div class="space-y-6">
        <!-- Progress bar -->
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div 
                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: `${(currentStep / 4) * 100}%` }"
            ></div>
        </div>

        <!-- Step indicator -->
        <div class="flex justify-between items-center">
            <div
                v-for="step in steps"
                :key="step.id"
                class="flex items-center space-x-2"
                :class="step.id <= currentStep ? 'text-blue-600' : 'text-gray-400'"
            >
                <div
                    class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
                    :class="step.id <= currentStep ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-400'"
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
                <!-- Erreur globale -->
                <div v-if="error" class="mb-6 rounded-md border border-red-200 bg-red-50 p-4">
                    <div class="flex items-center">
                        <AlertCircle class="mr-2 h-4 w-4 text-red-500" />
                        <p class="text-sm text-red-700">{{ error }}</p>
                    </div>
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
                                required
                            />
                        </div>
                        <div>
                            <Label for="last_name">Nom</Label>
                            <Input
                                id="last_name"
                                v-model="formData.last_name"
                                placeholder="Votre nom"
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
                            id="address_line1"
                            v-model="formData.address_line1"
                            placeholder="123 rue de la République"
                            required
                        />
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
                            @input="onIbanInput"
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
                        class="bg-green-600 hover:bg-green-700"
                    >
                        <div v-if="isLoading" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                        {{ isLoading ? 'Configuration...' : 'Finaliser mon compte' }}
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>