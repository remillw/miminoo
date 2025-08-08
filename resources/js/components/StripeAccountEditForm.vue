<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import { AlertCircle, Building, CreditCard, Info, Shield, User } from 'lucide-vue-next';
import { computed, nextTick, onMounted, reactive, ref } from 'vue';

interface Props {
    accountDetails: {
        id: string;
        individual: {
            first_name: string | null;
            last_name: string | null;
            email: string | null;
            phone: string | null;
            address: {
                line1: string | null;
                city: string | null;
                postal_code: string | null;
                country: string | null;
            } | null;
            dob: {
                day: number | null;
                month: number | null;
                year: number | null;
            } | null;
        } | null;
        external_accounts?: {
            data: Array<{
                id: string;
                object: string;
                account_holder_name: string | null;
                account_holder_type: string | null;
                last4: string | null;
                routing_number: string | null;
                country: string | null;
                currency: string | null;
            }>;
        };
    };
    stripePublishableKey?: string;
    googlePlacesApiKey?: string;
}

const props = defineProps<Props>();

const { showSuccess, showError } = useToast();

const isLoading = ref(false);
const errorMessage = ref('');
const hasInteractedWithPhone = ref(false);

// Stripe.js
let stripe: any = null;

// Autocomplete Google Places
const isGoogleLoaded = ref(false);
let autocomplete: any;

// Données du formulaire pré-remplies avec les informations existantes
const formData = reactive({
    // Informations personnelles
    first_name: props.accountDetails.individual?.first_name || '',
    last_name: props.accountDetails.individual?.last_name || '',
    email: props.accountDetails.individual?.email || '',
    phone: props.accountDetails.individual?.phone || '',

    // Date de naissance
    dob_day: props.accountDetails.individual?.dob?.day?.toString() || '',
    dob_month: props.accountDetails.individual?.dob?.month?.toString() || '',
    dob_year: props.accountDetails.individual?.dob?.year?.toString() || '',

    // Adresse
    address_line1: props.accountDetails.individual?.address?.line1 || '',
    address_city: props.accountDetails.individual?.address?.city || '',
    address_postal_code: props.accountDetails.individual?.address?.postal_code || '',
    address_country: props.accountDetails.individual?.address?.country || 'FR',

    // Informations bancaires (premier compte bancaire s'il existe)
    account_holder_name: props.accountDetails.external_accounts?.data?.[0]?.account_holder_name || '',
    iban: '', // On ne peut pas récupérer l'IBAN complet pour des raisons de sécurité
});

const canUpdate = computed(() => {
    return formData.first_name && formData.last_name && formData.email && isPhoneValid.value;
});

// Validation du téléphone français
const isPhoneValid = computed(() => {
    if (!formData.phone) return false;

    const cleanPhone = formData.phone.replace(/[^0-9]/g, '');

    return (
        (cleanPhone.length === 10 && cleanPhone.startsWith('0')) ||
        (cleanPhone.length === 9 && !cleanPhone.startsWith('0')) ||
        (cleanPhone.startsWith('33') && cleanPhone.length === 11) ||
        formData.phone.startsWith('+33')
    );
});

// Afficher l'erreur seulement si l'utilisateur a interagi avec le champ et qu'il est invalide
const shouldShowPhoneError = computed(() => {
    return hasInteractedWithPhone.value && !isPhoneValid.value;
});

const updateAccount = async () => {
    if (!canUpdate.value || isLoading.value) return;

    isLoading.value = true;
    errorMessage.value = '';

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

            const cleanPhone = phone.replace(/[^0-9]/g, '');

            if (cleanPhone.length === 10 && cleanPhone.startsWith('0')) {
                return '+33' + cleanPhone.substring(1);
            } else if (cleanPhone.length === 9 && !cleanPhone.startsWith('0')) {
                return '+33' + cleanPhone;
            } else if (cleanPhone.startsWith('33') && cleanPhone.length === 11) {
                return '+' + cleanPhone;
            }

            if (phone.startsWith('+33')) {
                return phone;
            }

            return cleanPhone.length >= 9 ? '+33' + cleanPhone.replace(/^0/, '') : undefined;
        };

        // Créer l'account token pour mise à jour avec Stripe.js
        const accountTokenResult = await stripe.createToken('account', {
            business_type: 'individual',
            individual: {
                first_name: formData.first_name,
                last_name: formData.last_name,
                email: formData.email,
                phone: formatPhoneForStripe(formData.phone),
                dob:
                    formData.dob_day && formData.dob_month && formData.dob_year
                        ? {
                              day: parseInt(formData.dob_day),
                              month: parseInt(formData.dob_month),
                              year: parseInt(formData.dob_year),
                          }
                        : undefined,
                address: {
                    line1: formData.address_line1,
                    city: formData.address_city,
                    postal_code: formData.address_postal_code,
                    country: formData.address_country,
                },
            },
        });

        if (accountTokenResult.error) {
            throw new Error(accountTokenResult.error.message);
        }

        // Préparer les données pour l'envoi
        const requestData = new FormData();
        requestData.append('account_token', accountTokenResult.token.id);

        // Ajouter l'IBAN seulement s'il a été fourni (pour mise à jour du compte bancaire)
        if (formData.iban) {
            requestData.append('iban', formData.iban);
            requestData.append('account_holder_name', formData.account_holder_name);
        }

        // Utiliser fetch pour l'update
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch(`/stripe/update-account/${props.accountDetails.id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            body: requestData,
        });

        const result = await response.json();

        if (response.ok && result.success) {
            showSuccess('✅ Compte mis à jour avec succès !', 'Vos informations ont été mises à jour');

            // Recharger la page après mise à jour
            setTimeout(() => {
                if (typeof window !== 'undefined') {
                    window.location.reload();
                }
            }, 1500);
        } else {
            throw new Error(result.error || 'Erreur lors de la mise à jour du compte');
        }
    } catch (err) {
        console.error('Erreur mise à jour compte:', err);
        let errorMsg = 'Erreur lors de la mise à jour du compte';
        if (err instanceof Error) {
            errorMsg = err.message;
        }
        errorMessage.value = errorMsg;
        showError('❌ Erreur de mise à jour', errorMsg);
    } finally {
        isLoading.value = false;
    }
};

// Charger Stripe.js
const loadStripe = async () => {
    if (stripe) return;

    const publishableKey = props.stripePublishableKey;

    if (!publishableKey) {
        throw new Error('Clé publique Stripe manquante');
    }

    if ((window as any).Stripe) {
        stripe = (window as any).Stripe(publishableKey);
        return;
    }

    const script = document.createElement('script');
    script.src = 'https://js.stripe.com/v3/';
    script.async = true;

    return new Promise<void>((resolve, reject) => {
        script.onload = () => {
            if ((window as any).Stripe) {
                stripe = (window as any).Stripe(publishableKey);
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

    (window as any).initGooglePlacesCallbackEdit = () => {
        isGoogleLoaded.value = true;
        initAutocomplete();
    };

    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=initGooglePlacesCallbackEdit`;
    script.async = true;
    script.defer = true;

    document.head.appendChild(script);
};

const initAutocomplete = async () => {
    await nextTick();
    const input = document.getElementById('address-input-edit') as HTMLInputElement;
    if (!input || !window.google?.maps?.places) return;

    autocomplete = new (window as any).google.maps.places.Autocomplete(input, {
        types: ['address'],
        componentRestrictions: { country: 'fr' },
    });

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
                    if (types.includes('locality')) {
                        formData.address_city = component.long_name;
                    } else if (!formData.address_city && types.includes('sublocality')) {
                        formData.address_city = component.long_name;
                    } else if (!formData.address_city && types.includes('administrative_area_level_2')) {
                        formData.address_city = component.long_name;
                    }
                });
            }
        }
    });
};

// Fonction pour vider l'erreur
const clearError = () => {
    if (errorMessage.value) {
        errorMessage.value = '';
    }
};

onMounted(() => {
    loadStripe();
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center">
                <User class="mr-2 h-5 w-5" />
                Modifier les informations du compte
            </CardTitle>
            <p class="text-sm text-gray-600">Mettez à jour vos informations Stripe Connect</p>
        </CardHeader>
        <CardContent>
            <!-- Affichage des erreurs -->
            <div v-if="errorMessage" class="mb-4 rounded-md border border-red-200 bg-red-50 p-4">
                <div class="flex items-center">
                    <AlertCircle class="mr-2 h-4 w-4 text-red-500" />
                    <p class="text-sm text-red-700">{{ errorMessage }}</p>
                </div>
                <button @click="errorMessage = ''" class="mt-2 text-xs text-red-600 underline hover:text-red-800">Fermer</button>
            </div>

            <div class="space-y-6">
                <!-- Informations personnelles -->
                <div class="space-y-4">
                    <h3 class="flex items-center text-lg font-medium text-gray-900">
                        <User class="mr-2 h-5 w-5" />
                        Informations personnelles
                    </h3>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <Label for="first_name">Prénom</Label>
                            <Input id="first_name" v-model="formData.first_name" placeholder="Votre prénom" @input="clearError" required />
                        </div>
                        <div>
                            <Label for="last_name">Nom</Label>
                            <Input id="last_name" v-model="formData.last_name" placeholder="Votre nom" @input="clearError" required />
                        </div>
                    </div>

                    <div>
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="formData.email" type="email" placeholder="votre.email@exemple.com" @input="clearError" required />
                    </div>

                    <div>
                        <Label for="phone">Téléphone *</Label>
                        <Input
                            id="phone"
                            v-model="formData.phone"
                            type="tel"
                            placeholder="06 12 34 56 78 ou +33 6 12 34 56 78"
                            :class="shouldShowPhoneError ? 'border-red-500' : ''"
                            @input="hasInteractedWithPhone = true"
                            @blur="hasInteractedWithPhone = true"
                            required
                        />
                        <p v-if="shouldShowPhoneError" class="mt-1 text-sm text-red-600">Format de téléphone invalide (ex: 06 12 34 56 78)</p>
                        <p v-else class="mt-1 text-xs text-gray-500">Format français accepté : 06 12 34 56 78</p>
                    </div>
                </div>

                <!-- Adresse -->
                <div class="space-y-4">
                    <h3 class="flex items-center text-lg font-medium text-gray-900">
                        <Building class="mr-2 h-5 w-5" />
                        Adresse
                    </h3>

                    <div>
                        <Label for="address_line1">Adresse</Label>
                        <Input
                            id="address-input-edit"
                            v-model="formData.address_line1"
                            placeholder="123 rue de la République, Paris"
                            autocomplete="address-line1"
                            @focus="
                                () => {
                                    if (!isGoogleLoaded) loadGooglePlaces();
                                }
                            "
                            @input="clearError"
                            required
                        />
                        <p class="mt-1 text-xs text-gray-500">Commencez à taper pour voir les suggestions d'adresses</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <Label for="address_postal_code">Code postal</Label>
                            <Input id="address_postal_code" v-model="formData.address_postal_code" placeholder="75001" maxlength="5" required />
                        </div>
                        <div>
                            <Label for="address_city">Ville</Label>
                            <Input id="address_city" v-model="formData.address_city" placeholder="Paris" required />
                        </div>
                    </div>
                </div>

                <!-- Informations bancaires (optionnel pour mise à jour) -->
                <div class="space-y-4">
                    <h3 class="flex items-center text-lg font-medium text-gray-900">
                        <CreditCard class="mr-2 h-5 w-5" />
                        Informations bancaires (optionnel)
                    </h3>

                    <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                        <div class="mb-2 flex items-center">
                            <Info class="mr-2 h-4 w-4 text-blue-600" />
                            <span class="text-sm font-medium text-blue-900">Mise à jour des coordonnées bancaires</span>
                        </div>
                        <p class="text-sm text-blue-800">
                            Laissez ces champs vides pour conserver votre compte bancaire actuel. Remplissez-les seulement si vous souhaitez changer
                            de compte bancaire.
                        </p>
                    </div>

                    <div>
                        <Label for="account_holder_name">Titulaire du compte</Label>
                        <Input id="account_holder_name" v-model="formData.account_holder_name" placeholder="Prénom Nom" />
                    </div>

                    <div>
                        <Label for="iban">IBAN (nouveau)</Label>
                        <Input id="iban" v-model="formData.iban" placeholder="FR76 1234 5678 9012 3456 7890 123" />
                        <p class="mt-1 text-xs text-gray-500">Seulement si vous souhaitez changer de compte bancaire</p>
                    </div>
                </div>

                <!-- Sécurité -->
                <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="mb-2 flex items-center">
                        <Shield class="mr-2 h-4 w-4 text-blue-600" />
                        <span class="text-sm font-medium text-blue-900">Sécurité avec Account Tokens</span>
                    </div>
                    <p class="text-sm text-blue-800">
                        Cette mise à jour utilise les Account Tokens de Stripe pour sécuriser vos données. Elles sont envoyées directement à Stripe
                        sans passer par nos serveurs.
                    </p>
                </div>

                <!-- Bouton de mise à jour -->
                <Button @click="updateAccount" :disabled="!canUpdate || isLoading" class="w-full" size="lg">
                    <div v-if="isLoading" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                    {{ isLoading ? 'Mise à jour en cours...' : 'Mettre à jour le compte' }}
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
