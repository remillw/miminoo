<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { router } from '@inertiajs/vue3';
import { Calendar, Check, Clock, CreditCard, FileText, MapPin, Users } from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';


interface Child {
    nom: string;
    age: string;
    unite: 'ans' | 'mois';
}

interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    address?: {
        address: string;
        postal_code: string;
        country: string;
        latitude: number;
        longitude: number;
    };
    parentProfile?: {
        children_ages: Child[];
    };
}
const role = 'parent';

interface Props {
    user: User;
    role: string;
    googlePlacesApiKey?: string;
}

const props = defineProps<Props>();
const { showSuccess, showError } = useToast();

// État du wizard
const currentStep = ref(1);
const totalSteps = 5;
const completedSteps = ref(new Set<number>()); // Track des étapes confirmées

// Données du formulaire
const form = ref({
    // Étape 1: Date et horaires
    date: '',
    start_time: '',
    end_time: '',

    // Étape 2: Enfants
    children: [] as Child[],

    // Étape 3: Lieu
    address: props.user.address?.address || '',
    postal_code: props.user.address?.postal_code || '',
    country: props.user.address?.country || '',
    latitude: props.user.address?.latitude || 0,
    longitude: props.user.address?.longitude || 0,

    // Étape 4: Détails (optionnel)
    additional_info: '',

    // Étape 5: Tarif
    hourly_rate: '',
    estimated_duration: 0,
    estimated_total: 0,
});

// Autocomplétion Google Places
const isGoogleLoaded = ref(false);
let autocomplete: any;

// Données calculées
const stepIcons = [Calendar, Users, MapPin, FileText, CreditCard];
const stepTitles = ['Date et horaires', 'Enfants', 'Lieu', 'Détails', 'Tarif'];

// Options d'heures (de 06:00 à 23:30 par tranches de 30 minutes)
const timeOptions = computed(() => {
    const options = [];
    for (let hour = 6; hour <= 23; hour++) {
        for (let minute = 0; minute < 60; minute += 30) {
            const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            options.push(timeString);
        }
    }
    return options;
});

const isStepCompleted = (step: number) => {
    switch (step) {
        case 1:
            return form.value.date && form.value.start_time && form.value.end_time;
        case 2:
            return form.value.children.length > 0 && form.value.children.every(child => child.nom.trim() !== '');
        case 3:
            return form.value.address.trim() !== '';
        case 4:
            return true; // Étape optionnelle, toujours valide
        case 5:
            return form.value.hourly_rate !== '';
        default:
            return false;
    }
};

const canProceedToNext = computed(() => isStepCompleted(currentStep.value));

const estimatedDuration = computed(() => {
    if (form.value.start_time && form.value.end_time) {
        const [startHour, startMin] = form.value.start_time.split(':').map(Number);
        const [endHour, endMin] = form.value.end_time.split(':').map(Number);
        const startMinutes = startHour * 60 + startMin;
        const endMinutes = endHour * 60 + endMin;
        return Math.max(0, (endMinutes - startMinutes) / 60);
    }
    return 0;
});

const estimatedTotal = computed(() => {
    const rate = parseFloat(form.value.hourly_rate) || 0;
    return (estimatedDuration.value * rate).toFixed(2);
});

// Calculer le pourcentage de progression
const progressPercentage = computed(() => {
    const completedCount = completedSteps.value.size;
    if (currentStep.value > completedCount + 1) {
        return ((completedCount + 1) / totalSteps) * 100;
    }
    return (completedCount / totalSteps) * 100;
});

// Initialiser les enfants depuis le profil
const initializeChildren = () => {
    if (props.user.parentProfile?.children_ages && props.user.parentProfile.children_ages.length > 0) {
        form.value.children = [...props.user.parentProfile.children_ages].map((child) => ({
            ...child,
            age: String(child.age), // S'assurer que l'âge est une string
        }));
    } else {
        // Si pas d'enfants dans le profil, en ajouter un par défaut
        form.value.children = [{ nom: '', age: '2', unite: 'ans' }];
    }
};

// Gestion des enfants
const addChild = () => {
    form.value.children.push({ nom: '', age: '2', unite: 'ans' });
};

const removeChild = (index: number) => {
    form.value.children.splice(index, 1);
};

// Navigation du wizard
const nextStep = () => {
  if (currentStep.value < totalSteps) {
    // on passe toujours à la suite si c'est l'étape 4 ou si la validation standard passe
    if (currentStep.value === 4 || canProceedToNext.value) {
      // **si on est à l'étape 4 et que c'est vide**, on marque quand même comme complétée
      if (currentStep.value === 4) {
        completedSteps.value.add(4)
      }
      // **pour les autres**, on ne marque que si c'est validé
      else if (canProceedToNext.value) {
        completedSteps.value.add(currentStep.value)
      }
      currentStep.value++

      if (currentStep.value === 3 && !isGoogleLoaded.value) {
        loadGooglePlaces()
      }
    }
  }
}



const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const goToStep = (step: number) => {
    // Permettre de naviguer vers une étape si elle est complétée ou si c'est la suivante
    if (completedSteps.value.has(step) || step === currentStep.value || (step === currentStep.value + 1 && canProceedToNext.value)) {
        currentStep.value = step;
        
        // Charger Google Places si on va à l'étape 3
        if (step === 3 && !isGoogleLoaded.value) {
            loadGooglePlaces();
        }
    }
};


// Gestion intelligente du champ de date
const handleDateClick = () => {
    const dateInput = document.getElementById('date') as HTMLInputElement;
    if (dateInput && dateInput.showPicker) {
        // Petite temporisation pour permettre le focus
        setTimeout(() => {
            dateInput.showPicker();
        }, 50);
    }
};

const handleDateKeydown = (event: KeyboardEvent) => {
    // Si l'utilisateur commence à taper, on ferme le calendrier s'il est ouvert
    // et on laisse la saisie manuelle se faire
    const dateInput = event.target as HTMLInputElement;
    
    // Caractères de saisie de date (chiffres, slash, etc.)
    if (event.key.match(/[0-9\/\-\.]/)) {
        // L'utilisateur veut taper, on s'assure que le calendrier n'interfère pas
        dateInput.blur();
        setTimeout(() => {
            dateInput.focus();
        }, 10);
    }
};

// Google Places
const loadGooglePlaces = () => {
    if (window.google?.maps?.places) {
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

    (window as any).initGooglePlacesCallback = () => {
        isGoogleLoaded.value = true;
        setTimeout(initAutocomplete, 100);
    };

    document.head.appendChild(script);
};

const initAutocomplete = async () => {
    await nextTick();
    const input = document.getElementById('address-input') as HTMLInputElement;
    if (!input || !window.google?.maps?.places) return;

    autocomplete = new window.google.maps.places.Autocomplete(input, {
        types: ['address'],
        fields: ['formatted_address', 'address_components', 'geometry'],
    });

    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (place.formatted_address) {
            form.value.address = place.formatted_address;
            form.value.postal_code = '';
            form.value.country = '';

            if (place.geometry?.location) {
                form.value.latitude = place.geometry.location.lat();
                form.value.longitude = place.geometry.location.lng();
            }

            if (place.address_components) {
                place.address_components.forEach((component: any) => {
                    const types = component.types;
                    if (types.includes('postal_code')) {
                        form.value.postal_code = component.long_name;
                    }
                    if (types.includes('country')) {
                        form.value.country = component.long_name;
                    }
                });
            }

            if (!form.value.postal_code) form.value.postal_code = '00000';
            if (!form.value.country) form.value.country = 'France';
        }
    });
};

// Fonction pour formater les erreurs de validation
const formatValidationErrors = (errors: Record<string, string | string[]>) => {
    const errorMessages: string[] = [];
    const processedErrors = new Set<string>();
    
    // Détecter les cas spécifiques et donner des messages plus clairs
    const hasAddressErrors = errors.postal_code || errors.country || errors.latitude || errors.longitude;
    
    if (hasAddressErrors) {
        errorMessages.push("• L'adresse saisie n'est pas valide.");
        // Marquer ces erreurs comme traitées
        processedErrors.add('postal_code');
        processedErrors.add('country');
        processedErrors.add('latitude');
        processedErrors.add('longitude');
        processedErrors.add('address');
    }
    
    // Traiter les autres erreurs normalement
    for (const [field, messages] of Object.entries(errors)) {
        if (processedErrors.has(field)) continue;
        
        const errorList = Array.isArray(messages) ? messages : [messages];
        
        errorList.forEach(message => {
            const friendlyMessage = getFriendlyErrorMessage(field, message);
            errorMessages.push(`• ${friendlyMessage}`);
        });
    }
    
    return errorMessages.join('\n');
};

// Fonction pour obtenir des messages d'erreur plus conviviaux
const getFriendlyErrorMessage = (field: string, message: string): string => {
    const fieldName = getFieldDisplayName(field);
    
    // Messages spécifiques selon le type d'erreur
    if (message.includes('required')) {
        return `${fieldName} : Ce champ est obligatoire`;
    }
    
    if (message.includes('email')) {
        return `${fieldName} : Veuillez saisir une adresse email valide`;
    }
    
    if (message.includes('numeric') || message.includes('number')) {
        return `${fieldName} : Veuillez saisir un nombre valide`;
    }
    
    if (message.includes('min:')) {
        const minValue = message.match(/min:(\d+)/)?.[1];
        return `${fieldName} : Doit contenir au moins ${minValue} caractères`;
    }
    
    if (message.includes('max:')) {
        const maxValue = message.match(/max:(\d+)/)?.[1];
        return `${fieldName} : Ne peut pas dépasser ${maxValue} caractères`;
    }
    
    if (message.includes('date')) {
        return `${fieldName} : Veuillez saisir une date valide`;
    }
    
    if (message.includes('after') || message.includes('before')) {
        return `${fieldName} : La date saisie n'est pas valide`;
    }
    
    if (field.includes('children') && message.includes('array')) {
        return 'Enfants : Veuillez renseigner au moins un enfant';
    }
    
    // Message par défaut avec le nom du champ traduit
    return `${fieldName} : ${message}`;
};

// Fonction pour obtenir le nom d'affichage des champs
const getFieldDisplayName = (field: string): string => {
    const fieldNames: Record<string, string> = {
        'date': 'Date',
        'start_time': 'Heure de début',
        'end_time': 'Heure de fin',
        'children': 'Enfants',
        'children.*.nom': 'Prénom de l\'enfant',
        'children.*.age': 'Âge de l\'enfant',
        'children.*.unite': 'Unité d\'âge',
        'address': 'Adresse',
        'postal_code': 'Code postal',
        'country': 'Pays',
        'additional_info': 'Informations complémentaires',
        'hourly_rate': 'Tarif horaire',
        'estimated_duration': 'Durée estimée',
        'estimated_total': 'Coût total estimé'
    };
    
    return fieldNames[field] || field;
};

// Validation côté client avant soumission
const validateForm = (): string[] => {
    const errors: string[] = [];
    
    // Validation de la date
    if (!form.value.date) {
        errors.push("• Date : La date est obligatoire");
    } else {
        const selectedDate = new Date(form.value.date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (selectedDate < today) {
            errors.push("• Date : La date ne peut pas être dans le passé");
        }
    }
    
    // Validation des horaires
    if (!form.value.start_time) {
        errors.push("• Heure de début : L'heure de début est obligatoire");
    }
    if (!form.value.end_time) {
        errors.push("• Heure de fin : L'heure de fin est obligatoire");
    }
    if (form.value.start_time && form.value.end_time && form.value.start_time >= form.value.end_time) {
        errors.push("• Horaires : L'heure de fin doit être après l'heure de début");
    }
    
    // Validation des enfants
    if (form.value.children.length === 0) {
        errors.push("• Enfants : Au moins un enfant doit être renseigné");
    }
    form.value.children.forEach((child, index) => {
        if (!child.nom.trim()) {
            errors.push(`• Enfant ${index + 1} : Le prénom est obligatoire`);
        }
        if (!child.age || parseInt(child.age) <= 0) {
            errors.push(`• Enfant ${index + 1} : L'âge doit être supérieur à 0`);
        }
    });
    
    // Validation de l'adresse
    if (!form.value.address.trim()) {
        errors.push("• Adresse : L'adresse est obligatoire");
    }
    
    // Validation du tarif
    if (!form.value.hourly_rate) {
        errors.push("• Tarif horaire : Le tarif horaire est obligatoire");
    } else if (parseFloat(form.value.hourly_rate) <= 0) {
        errors.push("• Tarif horaire : Le tarif doit être supérieur à 0");
    }
    
    return errors;
};

// Soumission
const submitAnnouncement = async () => {
    // Validation côté client d'abord
    const clientErrors = validateForm();
    if (clientErrors.length > 0) {
        showError(`❌ Veuillez corriger les erreurs suivantes :\n${clientErrors.join('\n')}`);
        return;
    }

    const announcementData = {
        ...form.value,
        children: form.value.children.map((child) => ({
            ...child,
            age: String(child.age),
        })),
        estimated_duration: estimatedDuration.value,
        estimated_total: parseFloat(estimatedTotal.value),
    };

    try {
        router.post('/annonces', announcementData, {
            onSuccess: () => {
                showSuccess('🎉 Annonce publiée avec succès !');
                // Redirection après un petit délai pour voir le toast
                setTimeout(() => {
                    router.visit('/annonces');
                }, 1500);
            },
            onError: (errors) => {
                console.error('Erreurs de validation:', errors);
                
                if (errors && Object.keys(errors).length > 0) {
                    // Erreurs de validation spécifiques
                    const formattedErrors = formatValidationErrors(errors);
                    showError(`Erreur dans le formulaire :\n${formattedErrors}`);
                } else {
                    // Erreur générique
                    showError("Erreur lors de la création de l'annonce. Veuillez vérifier vos informations et réessayer.");
                }
            },
            onFinish: () => {
                // Cette fonction est appelée après onSuccess ou onError
                console.log('Requête terminée');
            }
        });
    } catch (error) {
        console.error('Erreur inattendue:', error);
        showError('❌ Une erreur inattendue est survenue. Veuillez rafraîchir la page et réessayer.');
    }
};

// Initialisation
initializeChildren();
</script>

<template>
    <GlobalLayout>
        <div class="mx-auto pt-10 pb-10 max-w-4xl">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Créer une annonce</h1>
                <p class="text-gray-500">Trouvez la babysitter parfaite pour vos enfants</p>
            </div>

            <!-- Stepper Modern UX 2025 -->
            <div class="mb-8">
                <!-- Barre de progression principale -->
                <div class="mb-6">
                    <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                        <div 
                            class="h-full bg-gradient-to-r from-orange-400 to-primary rounded-full transition-all duration-500 ease-out"
                            :style="{ width: `${progressPercentage}%` }"
                        ></div>
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-gray-500">
                        <span>Étape {{ currentStep }} sur {{ totalSteps }}</span>
                        <span>{{ Math.round(progressPercentage) }}% complété</span>
                    </div>
                </div>

                <!-- Étapes interactives -->
                <div class="relative flex items-center justify-between">
                    <div v-for="step in totalSteps" :key="step" class="flex flex-col items-center z-10">
                        <!-- Cercle de l'étape avec animations -->
                        <div
                            class="group flex h-14 w-14 cursor-pointer items-center justify-center rounded-full border-3 transition-all duration-300 transform hover:scale-105"
                            :class="{
                                // Étape actuelle
                                'border-primary bg-primary text-white shadow-lg shadow-orange-200 scale-110': currentStep === step,
                                // Étape complétée
                                'border-green-500 bg-green-500 text-white shadow-lg shadow-green-200': completedSteps.has(step) && currentStep !== step,
                                // Étape non visitée mais accessible
                                'border-orange-200 bg-secondary text-primary hover:border-orange-300 hover:bg-orange-100': currentStep !== step && !completedSteps.has(step) && (step === currentStep + 1 && canProceedToNext),
                                // Étape non accessible
                                'border-gray-200 bg-gray-50 text-gray-300 cursor-not-allowed': currentStep !== step && !completedSteps.has(step) && !(step === currentStep + 1 && canProceedToNext),
                            }"
                            @click="goToStep(step)"
                        >
                            <!-- Icône check pour les étapes complétées -->
                            <div v-if="completedSteps.has(step) && currentStep !== step" 
                                 class="animate-in zoom-in duration-300">
                                <Check class="h-6 w-6" />
                            </div>
                            <!-- Icône de l'étape pour les autres -->
                            <component v-else :is="stepIcons[step - 1]" class="h-6 w-6 transition-all duration-200" />
                        </div>
                        
                        <!-- Titre de l'étape avec meilleur styling -->
                        <div class="mt-3 text-center">
                            <span
                                class="text-sm font-medium transition-all duration-200"
                                :class="{
                                    'text-primary font-semibold': currentStep === step,
                                    'text-green-600 font-medium': completedSteps.has(step) && currentStep !== step,
                                    'text-gray-500': currentStep !== step && !completedSteps.has(step),
                                }"
                            >
                                {{ stepTitles[step - 1] }}
                            </span>
                            <!-- Indicateur de progression sous le titre -->
                            <div class="mt-1 h-1 w-16 mx-auto rounded-full transition-all duration-300"
                                 :class="{
                                     'bg-primary': currentStep === step,
                                     'bg-green-500': completedSteps.has(step) && currentStep !== step,
                                     'bg-transparent': currentStep !== step && !completedSteps.has(step),
                                 }">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ligne de connexion entre les étapes -->
                    <div class="absolute top-7 left-0 right-0 flex items-center -z-10">
                        <div class="flex-1 flex items-center">
                            <div v-for="i in totalSteps - 1" :key="i" class="flex-1 flex items-center">
                                <div class="h-1 w-full transition-all duration-500"
                                     :class="{
                                         'bg-green-500': completedSteps.has(i),
                                         'bg-primary': currentStep > i && !completedSteps.has(i),
                                         'bg-gray-200': currentStep <= i && !completedSteps.has(i),
                                     }">
                                </div>
                                <div class="w-14"></div> <!-- Espace pour le cercle suivant -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu des étapes -->
            <Card class="mb-6 shadow-lg border-0">
                <CardContent class="p-8">
 <!-- Étape 1: Date et horaires -->
<div v-if="currentStep === 1">
  <h2 class="mb-6 text-xl font-semibold">Quand avez-vous besoin d'une babysitter ?</h2>

  <div class="space-y-6">
    <!-- Date -->
    <div class="space-y-2">
      <Label for="date">Date</Label>
      <div class="relative">
        <Calendar class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400 z-10 pointer-events-none" />
        <Input
          id="date"
          type="date"
          v-model="form.date"
          :min="new Date().toISOString().split('T')[0]"
          class="pl-10 cursor-pointer hover:border-primary transition-colors focus:border-primary"
          required
          @click="handleDateClick"
          @keydown="handleDateKeydown"
        />
      </div>
    </div>

    <!-- Horaires -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
      <div class="space-y-2">
        <Label for="start_time">Heure de début</Label>
        <Select v-model="form.start_time" required>
          <SelectTrigger class="w-full">
            <div class="flex items-center gap-2">
              <Clock class="h-4 w-4 text-gray-400" />
              <SelectValue placeholder="Sélectionner l'heure" />
            </div>
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="hour in timeOptions" :key="hour" :value="hour">
              {{ hour }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="space-y-2">
        <Label for="end_time">Heure de fin</Label>
        <Select v-model="form.end_time" required>
          <SelectTrigger class="w-full">
            <div class="flex items-center gap-2">
              <Clock class="h-4 w-4 text-gray-400" />
              <SelectValue placeholder="Sélectionner l'heure" />
            </div>
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="hour in timeOptions" :key="hour" :value="hour">
              {{ hour }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>

                            <!-- Durée estimée -->
                            <div v-if="estimatedDuration > 0" class="rounded-lg bg-blue-50 p-4">
                                <p class="text-sm text-blue-800"><strong>Durée estimée:</strong> {{ estimatedDuration.toFixed(1) }} heures</p>
                            </div>
                        </div>
                    </div>

                    <!-- Étape 2: Enfants -->
                    <div v-if="currentStep === 2">
                        <h2 class="mb-6 text-xl font-semibold">Informations sur vos enfants</h2>

                        <div class="space-y-6">
                            <!-- Nombre d'enfants -->
                            <div class="space-y-2">
                                <Label>Nombre d'enfants</Label>
                                <Select
                                    :model-value="String(form.children.length)"
                                    @update:model-value="
                                        (value) => {
                                            const count = parseInt(value);
                                            while (form.children.length < count) addChild();
                                            while (form.children.length > count) removeChild(form.children.length - 1);
                                        }
                                    "
                                >
                                    <SelectTrigger>
                                        <SelectValue :placeholder="`${form.children.length} enfant${form.children.length > 1 ? 's' : ''}`" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">1 enfant</SelectItem>
                                        <SelectItem value="2">2 enfants</SelectItem>
                                        <SelectItem value="3">3 enfants</SelectItem>
                                        <SelectItem value="4">4 enfants</SelectItem>
                                        <SelectItem value="5">5 enfants</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Détails des enfants -->
                            <div class="space-y-4">
                                <div
                                    v-for="(child, index) in form.children"
                                    :key="index"
                                    class="grid grid-cols-1 gap-4 rounded-lg border p-4 md:grid-cols-2"
                                >
                                    <div class="space-y-2">
                                        <Label :for="`child-name-${index}`">Prénom de l'enfant  {{ index + 1 }}</Label>
                                        <Input :id="`child-name-${index}`" v-model="child.nom" placeholder="ex: Sophie" required />
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="space-y-2">
                                            <Label :for="`child-age-${index}`">Âge</Label>
                                            <Input :id="`child-age-${index}`" v-model="child.age" type="number" min="1" max="18" required />
                                        </div>
                                        <div class="space-y-2">
                                            <Label :for="`child-unit-${index}`">Âge en </Label>
                                            <Select v-model="child.unite">
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="mois">mois</SelectItem>
                                                    <SelectItem value="ans">ans</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Étape 3: Lieu -->
                    <div v-if="currentStep === 3">
                        <h2 class="mb-6 text-xl font-semibold">Où se déroule le babysitting ?</h2>

                        <div class="space-y-6">
                            <div class="space-y-2">
                                <Label for="address">Adresse</Label>
                                <div class="relative">
                                    <MapPin class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                    <Input
                                        id="address-input"
                                        v-model="form.address"
                                        placeholder="Entrez une adresse complète"
                                        class="pl-10"
                                        required
                                    />
                                </div>
                                <p class="text-xs text-gray-500">
                                    📍 Adresse permettant de géolocaliser et prévenir les babysitters les plus proches. Seuls la ville et le code postal seront affichés publiquement.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Étape 4: Détails (optionnel) -->
                    <div v-if="currentStep === 4">
                        <h2 class="mb-6 text-xl font-semibold">Informations complémentaires (optionnel)</h2>

                        <div class="space-y-6">
                            <div class="space-y-2">
                                <Label for="additional_info">Informations supplémentaires</Label>
                                <Textarea
                                    id="additional_info"
                                    v-model="form.additional_info"
                                    placeholder="Allergies, routines, activités préférées, consignes particulières, autres informations utiles pour les babysitters..."
                                    rows="6"
                                />
                                <p class="text-xs text-gray-500">
                                    ℹ️ Ce champ est optionnel. Vous pouvez passer cette étape si vous n'avez pas d'informations particulières à ajouter.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Étape 5: Tarif -->
                    <div v-if="currentStep === 5">
                        <h2 class="mb-6 text-xl font-semibold">Quel est votre budget ?</h2>

                        <div class="space-y-6">
                            <div class="space-y-2">
                                <Label for="hourly_rate">Tarif horaire (€/h)</Label>
                                <Input
                                    id="hourly_rate"
                                    v-model="form.hourly_rate"
                                    type="number"
                                    min="0"
                                    step="0.50"
                                    placeholder="ex: 12.50"
                                    required
                                />
                                <p class="text-xs text-gray-500">
                                    Ce tarif est indicatif. Les babysitters ont la liberté de proposer leur propre tarif. Vous pourrez l'accepter ou
                                    refuser lors de la mise en relation.
                                </p>
                            </div>

                            <!-- Estimation totale -->
                            <div v-if="parseFloat(estimatedTotal) > 0" class="rounded-lg bg-green-50 p-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-green-800">Coût estimé total:</span>
                                    <span class="text-lg font-semibold text-green-800">{{ estimatedTotal }}€</span>
                                </div>
                                <p class="mt-1 text-xs text-green-600">Basé sur {{ estimatedDuration.toFixed(1) }}h à {{ form.hourly_rate }}€/h</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Navigation avec meilleur styling -->
            <div class="flex items-center justify-between">
                <Button 
                    v-if="currentStep > 1" 
                    variant="outline" 
                    @click="prevStep" 
                    class="flex items-center gap-2 px-6 py-3 hover:bg-gray-50 transition-all duration-200"
                > 
                    ← Précédent 
                </Button>
                <div v-else></div>

           <!-- Bouton « Suivant » / « Ignorer cette étape » -->
<Button
  v-if="currentStep < totalSteps"
  @click="nextStep"
  :disabled="currentStep !== 4 && !canProceedToNext"
  class="flex items-center gap-2 px-6 py-3 bg-primary hover:bg-primary disabled:opacity-50 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
>
  <span v-if="currentStep === 4 && !form.additional_info.trim()">
    Ignorer cette étape →
  </span>
  <span v-else>
    Suivant →
  </span>
</Button>

                <Button
                    v-else
                    @click="submitAnnouncement"
                    :disabled="!canProceedToNext"
                    class="flex items-center gap-2 px-6 py-3 bg-primary hover:bg-primary disabled:opacity-50 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                >
                    Publier l'annonce →
                </Button>
            </div>
        </div>
    </GlobalLayout>
</template>

<style scoped>
:deep(.pac-container) {
    z-index: 9999;
}

/* Animation pour les transitions */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-in {
    animation: slideIn 0.3s ease-out;
}

/* Bordure plus épaisse pour les cercles */
.border-3 {
    border-width: 3px;
}

/*
  Ce code masque l'icône par défaut des navigateurs pour les inputs de type date et time,
  ce qui nous permet d'utiliser notre propre icône sans avoir de doublon.
*/
input[type="date"]::-webkit-calendar-picker-indicator,
input[type="time"]::-webkit-calendar-picker-indicator {
    display: none;
    -webkit-appearance: none;
}
</style>