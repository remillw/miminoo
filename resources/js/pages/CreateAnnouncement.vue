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
import { computed, nextTick, ref, watch } from 'vue';


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

// État des erreurs pour chaque étape
const stepErrors = ref<Record<number, string[]>>({});

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

// Options d'heures (de 06:00 à 23:45 par tranches de 15 minutes)
const timeOptions = computed(() => {
    const options = [];
    for (let hour = 6; hour <= 23; hour++) {
        for (let minute = 0; minute < 60; minute += 15) {
            const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            options.push(timeString);
        }
    }
    return options;
});

// Gestion des champs d'heure avec saisie manuelle ou sélection
const timeInputType = ref<'select' | 'manual'>('select');

const toggleTimeInputType = () => {
    timeInputType.value = timeInputType.value === 'select' ? 'manual' : 'select';
};

// Validation des heures saisies manuellement
const validateTimeFormat = (time: string): boolean => {
    const timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
    return timeRegex.test(time);
};

// Formatage automatique des heures pendant la saisie
const formatTimeInput = (event: Event) => {
    const input = event.target as HTMLInputElement;
    let value = input.value.replace(/[^\d]/g, '');
    
    if (value.length >= 3) {
        value = value.slice(0, 2) + ':' + value.slice(2, 4);
    }
    
    input.value = value;
    
    // Mettre à jour le modèle
    if (input.id === 'start_time_manual') {
        form.value.start_time = value;
    } else if (input.id === 'end_time_manual') {
        form.value.end_time = value;
    }
};

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
        let endMinutes = endHour * 60 + endMin;
        
        // Si l'heure de fin est plus petite que l'heure de début, 
        // cela signifie que ça se termine le lendemain
        if (endMinutes <= startMinutes) {
            // Ajouter 24 heures (1440 minutes) pour passer au lendemain
            endMinutes += 24 * 60;
        }
        
        const durationInMinutes = endMinutes - startMinutes;
        const durationInHours = durationInMinutes / 60;
        
        // Limiter à un maximum raisonnable (par exemple 24 heures)
        return Math.min(24, Math.max(0, durationInHours));
    }
    return 0;
});

const estimatedTotal = computed(() => {
    const rate = parseFloat(form.value.hourly_rate) || 0;
    return (estimatedDuration.value * rate).toFixed(2);
});

// Calculé pour savoir si l'annonce s'étend sur deux jours
const spansNextDay = computed(() => {
    if (form.value.start_time && form.value.end_time) {
        const [startHour, startMin] = form.value.start_time.split(':').map(Number);
        const [endHour, endMin] = form.value.end_time.split(':').map(Number);
        const startMinutes = startHour * 60 + startMin;
        const endMinutes = endHour * 60 + endMin;
        return endMinutes <= startMinutes;
    }
    return false;
});

// Formatage de l'affichage de la durée
const durationDisplayText = computed(() => {
    if (estimatedDuration.value <= 0) return '';
    
    const hours = Math.floor(estimatedDuration.value);
    const minutes = Math.round((estimatedDuration.value - hours) * 60);
    
    let text = '';
    if (hours > 0) {
        text += `${hours}h`;
        if (minutes > 0) text += ` ${minutes}min`;
    } else if (minutes > 0) {
        text += `${minutes}min`;
    }
    
    if (spansNextDay.value) {
        text += ' (sur 2 jours)';
    }
    
    return text;
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

// Navigation du wizard avec validation
const nextStep = () => {
    if (currentStep.value < totalSteps) {
        // Valider l'étape actuelle avant de continuer
        const { isValid, errors } = validateCurrentStep();
        
        if (!isValid && currentStep.value !== 4) { // L'étape 4 est optionnelle
            showError(`Veuillez corriger les erreurs suivantes :\n\n${errors.map(err => `• ${err}`).join('\n')}`);
            return;
        }
        
        // Marquer l'étape comme complétée
        completedSteps.value.add(currentStep.value);
        currentStep.value++;

        // Charger Google Places si on arrive à l'étape 3
        if (currentStep.value === 3 && !isGoogleLoaded.value) {
            loadGooglePlaces();
        }
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const goToStep = (step: number) => {
    // Permettre de naviguer vers une étape si elle est complétée ou si c'est l'étape suivante
    const canNavigate = completedSteps.value.has(step) || 
                       step === currentStep.value || 
                       (step === currentStep.value + 1 && (canProceedToNext.value || currentStep.value === 4));
    
    if (canNavigate) {
        // Si on va vers une étape future, valider l'étape actuelle
        if (step > currentStep.value) {
            const { isValid, errors } = validateCurrentStep();
            
            if (!isValid && currentStep.value !== 4) {
                showError(`Veuillez corriger les erreurs suivantes :\n\n${errors.map(err => `• ${err}`).join('\n')}`);
                return;
            }
            
            // Marquer l'étape actuelle comme complétée
            completedSteps.value.add(currentStep.value);
        }
        
        currentStep.value = step;
        
        // Charger Google Places si on va à l'étape 3
        if (step === 3 && !isGoogleLoaded.value) {
            loadGooglePlaces();
        }
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

// Fonction pour formater les erreurs de validation reçues du backend
const formatBackendErrors = (errors: Record<string, string | string[]>) => {
    const errorsByStep: Record<number, string[]> = {};
    const allErrors: string[] = [];
    
    // Mapper les champs aux étapes
    const fieldStepMapping: Record<string, number> = {
        'date': 1,
        'start_time': 1,
        'end_time': 1,
        'children': 2,
        'children.0.nom': 2,
        'children.1.nom': 2,
        'children.2.nom': 2,
        'children.0.age': 2,
        'children.1.age': 2,
        'children.2.age': 2,
        'children.0.unite': 2,
        'children.1.unite': 2,
        'children.2.unite': 2,
        'address': 3,
        'postal_code': 3,
        'country': 3,
        'latitude': 3,
        'longitude': 3,
        'additional_info': 4,
        'hourly_rate': 5,
        'estimated_duration': 5,
        'estimated_total': 5,
    };
    
    for (const [field, messages] of Object.entries(errors)) {
        const errorList = Array.isArray(messages) ? messages : [messages];
        const step = getStepForField(field, fieldStepMapping);
        
        errorList.forEach(message => {
            const formattedMessage = formatSingleError(field, message);
            allErrors.push(formattedMessage);
            
            if (step) {
                if (!errorsByStep[step]) errorsByStep[step] = [];
                errorsByStep[step].push(formattedMessage);
            }
        });
    }
    
    return { errorsByStep, allErrors };
};

const getStepForField = (field: string, mapping: Record<string, number>): number | null => {
    // Correspondance exacte
    if (mapping[field]) return mapping[field];
    
    // Pour les champs d'enfants avec indices dynamiques
    if (field.startsWith('children.') && field.includes('.nom')) return 2;
    if (field.startsWith('children.') && field.includes('.age')) return 2;
    if (field.startsWith('children.') && field.includes('.unite')) return 2;
    
    return null;
};

const formatSingleError = (field: string, message: string): string => {
    // Messages déjà bien formatés depuis le backend
    if (message.includes(':') || message.includes('obligatoire') || message.includes('doit')) {
        return message;
    }
    
    // Fallback pour des messages non formatés
    const fieldDisplayName = getFieldDisplayName(field);
    return `${fieldDisplayName}: ${message}`;
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
        'latitude': 'Coordonnées',
        'longitude': 'Coordonnées',
        'additional_info': 'Informations complémentaires',
        'hourly_rate': 'Tarif horaire',
        'estimated_duration': 'Durée estimée',
        'estimated_total': 'Coût total estimé'
    };
    
    // Pour les champs d'enfants avec indices
    if (field.includes('children.') && field.includes('.nom')) {
        const index = field.match(/children\.(\d+)\.nom/)?.[1];
        return `Prénom de l'enfant ${parseInt(index || '0') + 1}`;
    }
    if (field.includes('children.') && field.includes('.age')) {
        const index = field.match(/children\.(\d+)\.age/)?.[1];
        return `Âge de l'enfant ${parseInt(index || '0') + 1}`;
    }
    if (field.includes('children.') && field.includes('.unite')) {
        const index = field.match(/children\.(\d+)\.unite/)?.[1];
        return `Unité d'âge de l'enfant ${parseInt(index || '0') + 1}`;
    }
    
    return fieldNames[field] || field;
};

// Gestion des erreurs avec navigation automatique vers l'étape concernée
const handleValidationErrors = (errors: Record<string, string | string[]>) => {
    const { errorsByStep, allErrors } = formatBackendErrors(errors);
    
    // Afficher toutes les erreurs
    const errorMessage = allErrors.length > 1 
        ? `Veuillez corriger les erreurs suivantes :\n\n${allErrors.map(err => `• ${err}`).join('\n')}`
        : allErrors[0];
    
    showError(errorMessage);
    
    // Naviguer vers la première étape avec des erreurs
    const stepsWithErrors = Object.keys(errorsByStep).map(Number).sort();
    if (stepsWithErrors.length > 0) {
        const firstErrorStep = stepsWithErrors[0];
        if (firstErrorStep !== currentStep.value) {
            setTimeout(() => {
                currentStep.value = firstErrorStep;
                showError(`Étape ${firstErrorStep} : ${errorsByStep[firstErrorStep].join(', ')}`);
            }, 500);
        }
    }
    
    return errorsByStep;
};

// Amélioration de la validation côté client
const validateCurrentStep = (): { isValid: boolean; errors: string[] } => {
    const errors: string[] = [];
    
    switch (currentStep.value) {
        case 1:
            if (!form.value.date) {
                errors.push("La date est obligatoire");
            } else {
                const selectedDate = new Date(form.value.date);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (selectedDate < today) {
                    errors.push("La date ne peut pas être dans le passé");
                }
            }
            
            if (!form.value.start_time) {
                errors.push("L'heure de début est obligatoire");
            }
            if (!form.value.end_time) {
                errors.push("L'heure de fin est obligatoire");
            }
            if (form.value.start_time && form.value.end_time && form.value.start_time >= form.value.end_time) {
                errors.push("L'heure de fin doit être après l'heure de début");
            }
            break;
            
        case 2:
            if (form.value.children.length === 0) {
                errors.push("Au moins un enfant doit être renseigné");
            }
            form.value.children.forEach((child, index) => {
                if (!child.nom.trim()) {
                    errors.push(`Enfant ${index + 1} : Le prénom est obligatoire`);
                }
                if (!child.age || parseInt(child.age) <= 0) {
                    errors.push(`Enfant ${index + 1} : L'âge doit être supérieur à 0`);
                }
                
                // Validation des âges selon l'unité
                const age = parseInt(child.age);
                if (child.unite === 'mois' && (age < 1 || age > 36)) {
                    errors.push(`Enfant ${index + 1} : L'âge en mois doit être entre 1 et 36`);
                }
                if (child.unite === 'ans' && (age < 1 || age > 17)) {
                    errors.push(`Enfant ${index + 1} : L'âge en années doit être entre 1 et 17`);
                }
            });
            break;
            
        case 3:
            if (!form.value.address.trim()) {
                errors.push("L'adresse est obligatoire");
            } else if (form.value.address.length < 10) {
                errors.push("L'adresse doit contenir au moins 10 caractères");
            }
            
            if (!form.value.latitude || !form.value.longitude) {
                errors.push("Veuillez sélectionner une adresse dans la liste proposée pour obtenir les coordonnées");
            }
            break;
            
        case 4:
            // Étape optionnelle, toujours valide
            if (form.value.additional_info && form.value.additional_info.length > 2000) {
                errors.push("Les informations complémentaires ne peuvent pas dépasser 2000 caractères");
            }
            break;
            
        case 5:
            if (!form.value.hourly_rate) {
                errors.push("Le tarif horaire est obligatoire");
            } else {
                const rate = parseFloat(form.value.hourly_rate);
                if (rate < 5) {
                    errors.push("Le tarif horaire doit être d'au moins 5€/h");
                }
                if (rate > 100) {
                    errors.push("Le tarif horaire ne peut pas dépasser 100€/h");
                }
            }
            break;
    }
    
    return { isValid: errors.length === 0, errors };
};

// Soumission améliorée avec meilleure gestion d'erreurs
const submitAnnouncement = async () => {
    // Validation côté client pour toutes les étapes
    const allErrors: string[] = [];
    for (let step = 1; step <= totalSteps; step++) {
        const originalStep = currentStep.value;
        currentStep.value = step;
        const { errors } = validateCurrentStep();
        allErrors.push(...errors);
        currentStep.value = originalStep;
    }
    
    if (allErrors.length > 0) {
        showError(`Veuillez corriger les erreurs suivantes :\n\n${allErrors.map(err => `• ${err}`).join('\n')}`);
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
            onSuccess: (page) => {
                // Récupérer le message de succès depuis la session
                const successData = page.props.flash?.success;
                
                if (successData && typeof successData === 'object') {
                    showSuccess(`${successData.title}\n${successData.message}`);
                } else {
                    showSuccess('🎉 Annonce publiée avec succès !');
                }
                
                // Redirection après un délai pour voir le toast
                setTimeout(() => {
                    router.visit('/annonces');
                }, 2000);
            },
            onError: (errors) => {
                console.error('❌ Erreurs de validation reçues:', errors);
                
                if (errors && Object.keys(errors).length > 0) {
                    handleValidationErrors(errors);
                } else {
                    showError("❌ Erreur lors de la création de l'annonce. Veuillez vérifier vos informations et réessayer.");
                }
            },
            onFinish: () => {
                console.log('📤 Requête de création d\'annonce terminée');
            }
        });
    } catch (error) {
        console.error('❌ Erreur inattendue:', error);
        showError('❌ Une erreur inattendue est survenue. Veuillez rafraîchir la page et réessayer.');
    }
};

// Validation en temps réel lors des changements
const validateStepRealTime = (step: number) => {
    const originalStep = currentStep.value;
    currentStep.value = step;
    const { isValid, errors } = validateCurrentStep();
    currentStep.value = originalStep;
    
    if (errors.length > 0) {
        stepErrors.value[step] = errors;
    } else {
        delete stepErrors.value[step];
    }
    
    return isValid;
};

// Watchers pour validation en temps réel
watch([() => form.value.date, () => form.value.start_time, () => form.value.end_time], () => {
    validateStepRealTime(1);
});

watch(() => form.value.children, () => {
    validateStepRealTime(2);
}, { deep: true });

watch([() => form.value.address, () => form.value.latitude, () => form.value.longitude], () => {
    validateStepRealTime(3);
});

watch(() => form.value.additional_info, () => {
    validateStepRealTime(4);
});

watch(() => form.value.hourly_rate, () => {
    validateStepRealTime(5);
});

// Fonction pour vérifier si une étape a des erreurs
const hasStepErrors = (step: number): boolean => {
    return stepErrors.value[step] && stepErrors.value[step].length > 0;
};

// Amélioration du calcul de l'état d'une étape
const getStepState = (step: number) => {
    if (currentStep.value === step) return 'current';
    if (completedSteps.value.has(step) && !hasStepErrors(step)) return 'completed';
    if (hasStepErrors(step)) return 'error';
    if (step === currentStep.value + 1 && canProceedToNext.value) return 'available';
    return 'disabled';
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
                        <!-- Cercle de l'étape avec animations et états -->
                        <div
                            class="group flex h-14 w-14 cursor-pointer items-center justify-center rounded-full border-3 transition-all duration-300 transform hover:scale-105"
                            :class="{
                                // Étape actuelle
                                'border-primary bg-primary text-white shadow-lg shadow-orange-200 scale-110': getStepState(step) === 'current',
                                // Étape complétée sans erreur
                                'border-green-500 bg-green-500 text-white shadow-lg shadow-green-200': getStepState(step) === 'completed',
                                // Étape avec erreurs
                                'border-red-500 bg-red-500 text-white shadow-lg shadow-red-200 animate-pulse': getStepState(step) === 'error',
                                // Étape accessible
                                'border-orange-200 bg-secondary text-primary hover:border-orange-300 hover:bg-orange-100': getStepState(step) === 'available',
                                // Étape non accessible
                                'border-gray-200 bg-gray-50 text-gray-300 cursor-not-allowed': getStepState(step) === 'disabled',
                            }"
                            @click="goToStep(step)"
                        >
                            <!-- Icône check pour les étapes complétées -->
                            <div v-if="getStepState(step) === 'completed'" 
                                 class="animate-in zoom-in duration-300">
                                <Check class="h-6 w-6" />
                            </div>
                            <!-- Icône X pour les étapes avec erreurs -->
                            <div v-else-if="getStepState(step) === 'error'" 
                                 class="animate-in zoom-in duration-300">
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <!-- Icône de l'étape pour les autres -->
                            <component v-else :is="stepIcons[step - 1]" class="h-6 w-6 transition-all duration-200" />
                        </div>
                        
                        <!-- Titre de l'étape avec meilleur styling -->
                        <div class="mt-3 text-center">
                            <span
                                class="text-sm font-medium transition-all duration-200"
                                :class="{
                                    'text-primary font-semibold': getStepState(step) === 'current',
                                    'text-green-600 font-medium': getStepState(step) === 'completed',
                                    'text-red-600 font-medium': getStepState(step) === 'error',
                                    'text-gray-500': getStepState(step) === 'disabled' || getStepState(step) === 'available',
                                }"
                            >
                                {{ stepTitles[step - 1] }}
                            </span>
                            
                            <!-- Indicateur d'erreurs sous le titre -->
                            <div v-if="hasStepErrors(step)" class="mt-1 text-xs text-red-600">
                                {{ stepErrors[step]?.length }} erreur{{ stepErrors[step]?.length > 1 ? 's' : '' }}
                            </div>
                            
                            <!-- Indicateur de progression sous le titre -->
                            <div class="mt-1 h-1 w-16 mx-auto rounded-full transition-all duration-300"
                                 :class="{
                                     'bg-primary': getStepState(step) === 'current',
                                     'bg-green-500': getStepState(step) === 'completed',
                                     'bg-red-500': getStepState(step) === 'error',
                                     'bg-transparent': getStepState(step) === 'disabled' || getStepState(step) === 'available',
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
                                         'bg-green-500': completedSteps.has(i) && !hasStepErrors(i),
                                         'bg-red-500': hasStepErrors(i),
                                         'bg-primary': currentStep > i && !completedSteps.has(i) && !hasStepErrors(i),
                                         'bg-gray-200': currentStep <= i && !completedSteps.has(i) && !hasStepErrors(i),
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
                                        class="pl-10 cursor-text hover:border-primary transition-colors focus:border-primary"
                                        required
                                        placeholder="JJ/MM/AAAA"
                                    />
                                </div>
                                <p class="text-xs text-gray-500">
                                    💡 Vous pouvez cliquer sur l'icône calendrier ou saisir directement la date
                                </p>
                            </div>

                            <!-- Horaires avec mode de saisie -->
                            <div class="space-y-4">
                                <!-- Bouton pour basculer entre sélection et saisie manuelle -->
                                <div class="flex items-center justify-between">
                                    <Label class="text-base font-medium">Horaires</Label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="toggleTimeInputType"
                                        class="text-xs"
                                    >
                                        <Clock class="h-3 w-3 mr-1" />
                                        {{ timeInputType === 'select' ? 'Saisie manuelle' : 'Sélection rapide' }}
                                    </Button>
                                </div>

                                <!-- Mode sélection (par défaut) -->
                                <div v-if="timeInputType === 'select'" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="start_time">Heure de début</Label>
                                        <Select v-model="form.start_time" required>
                                            <SelectTrigger class="w-full">
                                                <div class="flex items-center gap-2">
                                                    <Clock class="h-4 w-4 text-gray-400" />
                                                    <SelectValue placeholder="Sélectionner l'heure" />
                                                </div>
                                            </SelectTrigger>
                                            <SelectContent class="max-h-60">
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
                                            <SelectContent class="max-h-60">
                                                <SelectItem v-for="hour in timeOptions" :key="hour" :value="hour">
                                                    {{ hour }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <!-- Mode saisie manuelle -->
                                <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="start_time_manual">Heure de début</Label>
                                        <div class="relative">
                                            <Clock class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400 z-10 pointer-events-none" />
                                            <Input
                                                id="start_time_manual"
                                                type="time"
                                                v-model="form.start_time"
                                                min="06:00"
                                                max="23:59"
                                                step="900"
                                                class="pl-10"
                                                required
                                                placeholder="HH:MM"
                                                @input="formatTimeInput"
                                            />
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="end_time_manual">Heure de fin</Label>
                                        <div class="relative">
                                            <Clock class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400 z-10 pointer-events-none" />
                                            <Input
                                                id="end_time_manual"
                                                type="time"
                                                v-model="form.end_time"
                                                min="06:00"
                                                max="23:59"
                                                step="900"
                                                class="pl-10"
                                                required
                                                placeholder="HH:MM"
                                                @input="formatTimeInput"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <p class="text-xs text-gray-500">
                                    ⏰ {{ timeInputType === 'select' ? 'Créneaux par tranches de 15 minutes' : 'Saisissez l\'heure au format HH:MM (ex: 14:30)' }}
                                </p>
                            </div>

                            <!-- Durée estimée avec avertissement pour garde de nuit -->
                            <div v-if="estimatedDuration > 0" class="space-y-3">
                                <div class="rounded-lg bg-blue-50 p-4">
                                    <p class="text-sm text-blue-800"><strong>Durée estimée:</strong> {{ durationDisplayText }}</p>
                                </div>
                                
                                <!-- Avertissement pour garde de nuit -->
                                <div v-if="spansNextDay" class="rounded-lg bg-orange-50 border border-orange-200 p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-sm font-medium text-orange-800">Garde de nuit détectée</h3>
                                            <p class="mt-1 text-sm text-orange-700">
                                                Cette annonce s'étend sur deux jours (se termine le lendemain). 
                                                Assurez-vous que c'est bien ce que vous souhaitez et que le tarif proposé correspond à une garde de nuit.
                                            </p>
                                        </div>
                                    </div>
                                </div>
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
                                <div class="mt-1 flex items-center justify-between text-xs">
                                    <span class="text-green-600">{{ durationDisplayText }} à {{ form.hourly_rate }}€/h</span>
                                    <span v-if="spansNextDay" class="text-orange-600 font-medium">⚠️ Garde de nuit</span>
                                </div>
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