<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
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

interface Props {
    user: User;
    role: string;
}

const props = defineProps<Props>();
const { showSuccess, showError } = useToast();

// État du wizard
const currentStep = ref(1);
const totalSteps = 5;

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

    // Étape 4: Détails
    description: '',
    special_instructions: '',

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

const isStepCompleted = (step: number) => {
    switch (step) {
        case 1:
            return form.value.date && form.value.start_time && form.value.end_time;
        case 2:
            return form.value.children.length > 0;
        case 3:
            return form.value.address.trim() !== '';
        case 4:
            return form.value.description.trim() !== '';
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
    if (currentStep.value < totalSteps && canProceedToNext.value) {
        currentStep.value++;

        // Charger Google Places à l'étape 3
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
    if (step <= currentStep.value || isStepCompleted(step - 1)) {
        currentStep.value = step;
    }
};

// Google Places
const loadGooglePlaces = () => {
    if (window.google?.maps?.places) {
        initAutocomplete();
        return;
    }

    const apiKey = import.meta.env.VITE_GOOGLE_PLACES_API_KEY;
    if (!apiKey) return;

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

// Soumission
const submitAnnouncement = async () => {
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
                console.error('Erreurs:', errors);
                showError("❌ Erreur lors de la création de l'annonce");
            },
        });
    } catch (error) {
        console.error('Erreur:', error);
        showError('❌ Une erreur est survenue');
    }
};

// Initialisation
initializeChildren();
</script>

<template>
    <DashboardLayout :role="role">
        <div class="mx-auto max-w-4xl">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Créer une annonce</h1>
                <p class="text-gray-500">Trouvez la babysitter parfaite pour vos enfants</p>
            </div>

            <!-- Stepper -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div v-for="step in totalSteps" :key="step" class="flex items-center" :class="{ 'flex-1': step < totalSteps }">
                        <!-- Icône de l'étape -->
                        <div
                            class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-full border-2 transition-all"
                            :class="{
                                'border-orange-500 bg-orange-500 text-white': currentStep === step,
                                'border-orange-500 bg-orange-500 text-white': isStepCompleted(step),
                                'border-gray-300 text-gray-400': currentStep !== step && !isStepCompleted(step),
                            }"
                            @click="goToStep(step)"
                        >
                            <Check v-if="isStepCompleted(step) && currentStep !== step" class="h-5 w-5" />
                            <component v-else :is="stepIcons[step - 1]" class="h-5 w-5" />
                        </div>

                        <!-- Ligne de connexion -->
                        <div
                            v-if="step < totalSteps"
                            class="mx-4 h-0.5 flex-1 transition-all"
                            :class="{
                                'bg-orange-500': isStepCompleted(step),
                                'bg-gray-300': !isStepCompleted(step),
                            }"
                        />
                    </div>
                </div>

                <!-- Titres des étapes -->
                <div class="mt-4 flex justify-between">
                    <div
                        v-for="(title, index) in stepTitles"
                        :key="index"
                        class="text-sm font-medium"
                        :class="{
                            'text-orange-600': currentStep === index + 1 || isStepCompleted(index + 1),
                            'text-gray-400': currentStep !== index + 1 && !isStepCompleted(index + 1),
                        }"
                    >
                        {{ title }}
                    </div>
                </div>
            </div>

            <!-- Contenu des étapes -->
            <Card class="mb-6">
                <CardContent class="p-8">
                    <!-- Étape 1: Date et horaires -->
                    <div v-if="currentStep === 1">
                        <h2 class="mb-6 text-xl font-semibold">Quand avez-vous besoin d'une babysitter ?</h2>

                        <div class="space-y-6">
                            <!-- Date -->
                            <div class="space-y-2">
                                <Label for="date">Date</Label>
                                <Input id="date" type="date" v-model="form.date" :min="new Date().toISOString().split('T')[0]" required />
                            </div>

                            <!-- Horaires -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="start_time">Heure de début</Label>
                                    <div class="relative">
                                        <Clock class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                        <Input id="start_time" type="time" v-model="form.start_time" class="pl-10" required />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="end_time">Heure de fin</Label>
                                    <div class="relative">
                                        <Clock class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                        <Input id="end_time" type="time" v-model="form.end_time" class="pl-10" required />
                                    </div>
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
                                        <Label :for="`child-name-${index}`">Prénom de l'enfant {{ index + 1 }}</Label>
                                        <Input :id="`child-name-${index}`" v-model="child.nom" placeholder="ex: Sophie" required />
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="space-y-2">
                                            <Label :for="`child-age-${index}`">Âge</Label>
                                            <Input :id="`child-age-${index}`" v-model="child.age" type="number" min="1" max="18" required />
                                        </div>
                                        <div class="space-y-2">
                                            <Label :for="`child-unit-${index}`">Unité</Label>
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
                                        placeholder="Entrez votre adresse complète"
                                        class="pl-10"
                                        required
                                    />
                                </div>
                                <p class="text-xs text-gray-500">
                                    📍 Cette adresse nous permet de géolocaliser et prévenir les babysitters les plus proches. Seuls la ville et le
                                    code postal seront affichés publiquement.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Étape 4: Détails -->
                    <div v-if="currentStep === 4">
                        <h2 class="mb-6 text-xl font-semibold">Informations complémentaires</h2>

                        <div class="space-y-6">
                            <div class="space-y-2">
                                <Label for="description">Instructions particulières</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Allergies, routines, activités préférées, consignes particulières..."
                                    rows="6"
                                    required
                                />
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
                            <div v-if="estimatedTotal > 0" class="rounded-lg bg-green-50 p-4">
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

            <!-- Navigation -->
            <div class="flex items-center justify-between">
                <Button v-if="currentStep > 1" variant="outline" @click="prevStep" class="flex items-center gap-2"> ← Précédent </Button>
                <div v-else></div>

                <Button
                    v-if="currentStep < totalSteps"
                    @click="nextStep"
                    :disabled="!canProceedToNext"
                    class="flex items-center gap-2 bg-orange-500 hover:bg-orange-600"
                >
                    Suivant →
                </Button>
                <Button
                    v-else
                    @click="submitAnnouncement"
                    :disabled="!canProceedToNext"
                    class="flex items-center gap-2 bg-orange-500 hover:bg-orange-600"
                >
                    Publier l'annonce →
                </Button>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
:deep(.pac-container) {
    z-index: 9999;
}
</style>
