<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { MapPin } from 'lucide-vue-next';
import { nextTick, onMounted, ref, watch } from 'vue';

interface AddressData {
    address: string;
    postal_code: string;
    country: string;
    latitude: number;
    longitude: number;
    google_place_id: string;
}

interface Props {
    modelValue?: AddressData;
    disabled?: boolean;
    placeholder?: string;
}

interface Emits {
    (e: 'update:modelValue', value: AddressData): void;
    (e: 'address-selected', value: AddressData): void;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    placeholder: 'Commencez à taper votre adresse...',
    modelValue: () => ({
        address: '',
        postal_code: '',
        country: '',
        latitude: 0,
        longitude: 0,
        google_place_id: '',
    }),
});

const emit = defineEmits<Emits>();

const addressInput = ref<HTMLInputElement>();
const isGoogleLoaded = ref(false);
let autocomplete: any;

const initAutocomplete = async () => {
    console.log('🔍 Initialisation Google Places Autocomplete...');

    if (!window.google?.maps?.places) {
        console.error('❌ Google Places API non disponible');
        return;
    }

    await nextTick();

    const inputElement = addressInput.value?.$el?.querySelector('input') || addressInput.value;

    if (!inputElement) {
        console.error('❌ Input element non trouvé');
        return;
    }

    try {
        autocomplete = new window.google.maps.places.Autocomplete(inputElement, {
            types: ['address'],
            fields: ['formatted_address', 'geometry', 'place_id', 'address_components'],
        });

        autocomplete.addListener('place_changed', () => {
            console.log('📍 Place sélectionnée');
            const place = autocomplete.getPlace();

            if (!place.geometry?.location) {
                console.error('❌ Pas de géométrie dans la place');
                return;
            }

            const addressData: AddressData = {
                address: place.formatted_address || '',
                postal_code: '',
                country: '',
                latitude: place.geometry.location.lat(),
                longitude: place.geometry.location.lng(),
                google_place_id: place.place_id || '',
            };

            // Extraction du code postal et pays
            if (place.address_components) {
                place.address_components.forEach((component: any) => {
                    const types = component.types;

                    if (types.includes('postal_code')) {
                        addressData.postal_code = component.long_name;
                    }
                    if (types.includes('country')) {
                        addressData.country = component.long_name;
                    }
                });
            }

            // Valeurs par défaut si manquantes
            if (!addressData.postal_code) addressData.postal_code = '00000';
            if (!addressData.country) addressData.country = 'France';

            console.log('✅ Adresse complète:', addressData);

            // Émettre les événements
            emit('update:modelValue', addressData);
            emit('address-selected', addressData);
        });

        isGoogleLoaded.value = true;
        console.log('✅ Autocomplete initialisé avec succès');
    } catch (error) {
        console.error("❌ Erreur lors de l'initialisation:", error);
    }
};

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
        setTimeout(() => {
            initAutocomplete();
        }, 100);
    };

    script.onerror = () => {
        console.error('❌ Erreur chargement Google Places API');
    };

    document.head.appendChild(script);
};

// Watcher pour réinitialiser l'autocomplete quand on passe en mode édition
watch(
    () => props.disabled,
    (newDisabled) => {
        if (!newDisabled && !isGoogleLoaded.value) {
            loadGooglePlaces();
        } else if (!newDisabled && isGoogleLoaded.value) {
            nextTick(() => {
                setTimeout(() => {
                    initAutocomplete();
                }, 100);
            });
        }
    },
);

onMounted(() => {
    console.log('🔧 AddressAutocomplete monté, props:', {
        disabled: props.disabled,
        modelValue: props.modelValue,
        hasAddress: !!props.modelValue?.address,
    });

    if (!props.disabled) {
        loadGooglePlaces();
    }
});

// Gérer les changements manuels d'adresse
const onAddressInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const updatedValue = {
        address: '',
        postal_code: '',
        country: '',
        latitude: 0,
        longitude: 0,
        google_place_id: '',
        ...props.modelValue,
        address: target.value,
    };
    emit('update:modelValue', updatedValue);
};
</script>

<template>
    <div class="relative">
        <MapPin class="absolute top-1/2 left-3 z-10 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <Input
            ref="addressInput"
            :model-value="modelValue?.address || ''"
            :disabled="disabled"
            :placeholder="disabled && modelValue?.address ? modelValue.address : disabled ? 'Aucune adresse renseignée' : placeholder"
            class="pr-10 pl-10"
            @input="onAddressInput"
            required
        />
        <!-- Indicateur Google Places -->
        <div
            v-if="!disabled"
            class="absolute top-1/2 right-3 z-10 -translate-y-1/2"
            :title="isGoogleLoaded ? 'Google Places actif' : 'Google Places en chargement'"
        >
            <div v-if="isGoogleLoaded" class="h-3 w-3 rounded-full bg-green-500"></div>
            <div v-else class="h-3 w-3 animate-pulse rounded-full bg-orange-400"></div>
        </div>
    </div>

    <!-- Messages d'état -->
    <p v-if="!disabled && !isGoogleLoaded" class="mt-1 text-xs text-amber-600">⏳ Chargement de l'autocomplétion Google Places...</p>
    <p v-if="!disabled && isGoogleLoaded" class="mt-1 text-xs text-green-600">✅ Autocomplétion Google Places active</p>
</template>

<style scoped>
:deep(.pac-container) {
    z-index: 9999;
}
</style>
