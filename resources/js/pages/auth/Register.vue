<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useToast } from '@/composables/useToast';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle, Lock, Mail, User } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import { z } from 'zod';

const { showSuccess, showError } = useToast();

const isPasswordVisible = ref(false);
const isPasswordConfirmVisible = ref(false);

const role = ref<'parent' | 'babysitter'>('parent');

// Champs du formulaire avec v-model direct
const firstname = ref('');
const lastname = ref('');
const email = ref('');
const password = ref('');
const password_confirmation = ref('');
const accepted = ref(false);

// Tracking des champs touchés pour l'UX
const touchedFields = ref<Record<string, boolean>>({});
const hasAttemptedSubmit = ref(false);
const isSubmitting = ref(false);

// Schema de validation Zod
const registerSchema = z
    .object({
        firstname: z
            .string()
            .min(2, 'Le prénom doit contenir au moins 2 caractères')
            .max(50, 'Le prénom ne peut pas dépasser 50 caractères')
            .regex(/^[a-zA-ZÀ-ÿ\s'-]+$/, 'Le prénom ne peut contenir que des lettres'),
        lastname: z
            .string()
            .min(2, 'Le nom doit contenir au moins 2 caractères')
            .max(50, 'Le nom ne peut pas dépasser 50 caractères')
            .regex(/^[a-zA-ZÀ-ÿ\s'-]+$/, 'Le nom ne peut contenir que des lettres'),
        email: z.string().email('Veuillez entrer une adresse email valide').min(1, "L'email est requis"),
        password: z
            .string()
            .min(8, 'Le mot de passe doit contenir au moins 8 caractères')
            .regex(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/, 'Le mot de passe doit contenir au moins une minuscule, une majuscule et un chiffre'),
        password_confirmation: z.string().min(1, 'La confirmation du mot de passe est requise'),
        role: z.enum(['parent', 'babysitter']),
        accepted: z.boolean().refine((val) => val === true, "Vous devez accepter les conditions d'utilisation"),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Les mots de passe ne correspondent pas',
        path: ['password_confirmation'],
    });

// Erreurs de validation
const errors = ref<Record<string, string>>({});

// Formulaire Inertia pour la soumission
const inertiaForm = useForm({
    firstname: '',
    lastname: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'parent' as 'parent' | 'babysitter',
    accepted: false as boolean,
});

// Mise à jour du rôle
watch(role, (newRole) => {
    inertiaForm.role = newRole;
});

// Marquer un champ comme touché
const markFieldAsTouched = (fieldName: string) => {
    touchedFields.value[fieldName] = true;
};

// Nettoyer l'erreur d'un champ spécifique quand on commence à taper
const clearFieldError = (fieldName: string) => {
    if (errors.value[fieldName]) {
        const newErrors = { ...errors.value };
        delete newErrors[fieldName];
        errors.value = newErrors;
    }
};

// Validation avec Zod - séparée pour les erreurs et la validité du bouton
const validateForm = (showErrors = false) => {
    const formData = {
        firstname: firstname.value,
        lastname: lastname.value,
        email: email.value,
        password: password.value,
        password_confirmation: password_confirmation.value,
        role: role.value,
        accepted: accepted.value,
    };

    const result = registerSchema.safeParse(formData);

    if (showErrors && !result.success) {
        const newErrors: Record<string, string> = {};
        result.error.errors.forEach((error) => {
            const fieldName = error.path[0] as string;
            // N'afficher l'erreur que si le champ a été touché ou si on a tenté de soumettre
            if (fieldName && (touchedFields.value[fieldName] || hasAttemptedSubmit.value)) {
                // Éviter l'erreur de correspondance des mots de passe si l'un est vide après reset
                if (
                    fieldName === 'password_confirmation' &&
                    error.message.includes('correspondent pas') &&
                    (!password.value || !password_confirmation.value)
                ) {
                    return;
                }
                newErrors[fieldName] = error.message;
            }
        });
        errors.value = newErrors;
    }

    return result.success;
};

// Validation pour le bouton (sans affichage d'erreurs)
const isFormValid = computed(() => {
    return validateForm(false);
});

// Fonction de soumission
const onSubmit = () => {
    hasAttemptedSubmit.value = true;
    isSubmitting.value = true;

    if (!validateForm(true)) {
        isSubmitting.value = false;
        showError('Formulaire invalide', 'Veuillez corriger les erreurs avant de continuer');
        return;
    }

    // Synchroniser avec Inertia
    inertiaForm.firstname = firstname.value;
    inertiaForm.lastname = lastname.value;
    inertiaForm.email = email.value;
    inertiaForm.password = password.value;
    inertiaForm.password_confirmation = password_confirmation.value;
    inertiaForm.role = role.value;
    inertiaForm.accepted = accepted.value;

    // Soumettre avec Inertia
    inertiaForm.post(route('register'), {
        onSuccess: () => {
            if (role.value === 'parent') {
                showSuccess(
                    '🎉 Bienvenue sur Miminoo !',
                    'Votre compte parent a été créé avec succès. Vous pouvez maintenant publier vos annonces de garde.',
                );
            } else {
                showSuccess(
                    '📝 Demande envoyée !',
                    'Votre dossier babysitter est en cours de validation. Nos modérateurs examineront votre profil sous 24h.',
                );
            }
        },
        onError: (errors) => {
            console.log('Erreurs:', errors);

            // Dictionnaire de traduction des erreurs courantes
            const errorTranslations: Record<string, string> = {
                'The accepted field is required.': "Vous devez accepter les conditions d'utilisation",
                'The email field is required.': "L'email est requis",
                'The firstname field is required.': 'Le prénom est requis',
                'The lastname field is required.': 'Le nom est requis',
                'The password field is required.': 'Le mot de passe est requis',
                'The email has already been taken.': 'Cette adresse email est déjà utilisée',
                'The password confirmation does not match.': 'Les mots de passe ne correspondent pas',
            };

            // Fonction pour traduire une erreur
            const translateError = (error: string): string => {
                return errorTranslations[error] || error;
            };

            // Gestion spécifique des erreurs de validation Laravel
            if (errors.email) {
                const emailError = Array.isArray(errors.email) ? errors.email[0] : errors.email;
                if (emailError.includes('has already been taken')) {
                    showError('📧 Email déjà utilisé', 'Cette adresse email est déjà associée à un compte');
                    return;
                }
            }

            if (errors.accepted) {
                showError("✅ Conditions d'utilisation", "Vous devez accepter les conditions d'utilisation et la politique de confidentialité");
                return;
            }

            // Gestion générale des erreurs
            const firstErrorKey = Object.keys(errors)[0];
            const firstError = errors[firstErrorKey];
            const errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
            const translatedError = translateError(errorMessage as string);

            showError("❌ Erreur lors de l'inscription", translatedError);
        },
        onFinish: () => {
            isSubmitting.value = false;
            // Ne pas reset les mots de passe pour éviter les erreurs trompeuses
            // password.value = '';
            // password_confirmation.value = '';
            // inertiaForm.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GlobalLayout>
    <div class="flex min-h-screen flex-col justify-between bg-orange-50">
        <Head title="Inscription" />

        <div class="py-10 pt-10 text-center">
            <img src="/storage/logo_miminoo.png" alt="Miminoo" class="mx-auto h-10" />
        </div>

        <div class="mx-auto mb-10 w-full max-w-md rounded-3xl bg-white p-8 shadow-md">
            <h2 class="mb-1 text-center text-2xl font-bold">Inscription</h2>
            <p class="mb-6 text-center text-gray-500">Créez votre compte Miminoo</p>

            <!-- Boutons Google avec rôles -->
            <div class="mb-6 space-y-3">
                <a
                    :href="route('google.redirect', { role: 'parent' })"
                    class="focus:ring-primary inline-flex w-full items-center justify-center gap-3 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 focus:ring-2 focus:ring-offset-2 focus:outline-none"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24">
                        <path
                            fill="#4285F4"
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                        />
                        <path
                            fill="#34A853"
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                        />
                        <path
                            fill="#FBBC05"
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                        />
                        <path
                            fill="#EA4335"
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                        />
                    </svg>
                    Google - Parent
                </a>

                <a
                    :href="route('google.redirect', { role: 'babysitter' })"
                    class="focus:ring-primary inline-flex w-full items-center justify-center gap-3 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 focus:ring-2 focus:ring-offset-2 focus:outline-none"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24">
                        <path
                            fill="#4285F4"
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                        />
                        <path
                            fill="#34A853"
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                        />
                        <path
                            fill="#FBBC05"
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                        />
                        <path
                            fill="#EA4335"
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                        />
                    </svg>
                    Google - Babysitter
                </a>
            </div>

            <!-- Séparateur -->
            <div class="mb-6 flex items-center">
                <div class="flex-1 border-t border-gray-300"></div>
                <div class="mx-4 text-sm text-gray-500">ou créer avec email</div>
                <div class="flex-1 border-t border-gray-300"></div>
            </div>

            <div class="mb-6 flex justify-center">
                <div class="relative flex w-full max-w-xs rounded-full bg-gray-100">
                    <!-- Curseur blanc animé -->
                    <div
                        class="absolute top-0 left-0 z-0 h-full w-1/2 rounded-full bg-white shadow-sm transition-transform duration-300"
                        :class="role === 'babysitter' ? 'translate-x-full' : 'translate-x-0'"
                    ></div>

                    <!-- Bouton Parent -->
                    <button
                        type="button"
                        class="relative z-10 w-1/2 py-2 text-sm font-semibold transition-colors duration-300 focus:outline-none"
                        :class="role === 'parent' ? 'text-primary' : 'text-gray-400'"
                        @click="role = 'parent'"
                    >
                        Parent
                    </button>

                    <!-- Bouton Babysitter -->
                    <button
                        type="button"
                        class="relative z-10 w-1/2 py-2 text-sm font-semibold transition-colors duration-300 focus:outline-none"
                        :class="role === 'babysitter' ? 'text-primary' : 'text-gray-400'"
                        @click="role = 'babysitter'"
                    >
                        Babysitter
                    </button>
                </div>
            </div>

            <form @submit.prevent="onSubmit" class="space-y-4">
                <div class="flex gap-4">
                    <div class="w-1/2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Prénom</label>
                        <div class="relative">
                            <User class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                v-model="firstname"
                                class="pl-10"
                                placeholder="Prénom"
                                @input="clearFieldError('firstname')"
                                @blur="
                                    markFieldAsTouched('firstname');
                                    validateForm(true);
                                "
                            />
                        </div>
                        <p v-if="errors.firstname" class="mt-1 text-sm text-red-500">{{ errors.firstname }}</p>
                    </div>
                    <div class="w-1/2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nom</label>
                        <div class="relative">
                            <User class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                v-model="lastname"
                                class="pl-10"
                                placeholder="Nom"
                                @input="clearFieldError('lastname')"
                                @blur="
                                    markFieldAsTouched('lastname');
                                    validateForm(true);
                                "
                            />
                        </div>
                        <p v-if="errors.lastname" class="mt-1 text-sm text-red-500">{{ errors.lastname }}</p>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                    <div class="relative">
                        <Mail class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="email"
                            type="email"
                            class="pl-10"
                            placeholder="votre@email.com"
                            @input="clearFieldError('email')"
                            @blur="
                                markFieldAsTouched('email');
                                validateForm(true);
                            "
                        />
                    </div>
                    <p v-if="errors.email" class="mt-1 text-sm text-red-500">{{ errors.email }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Mot de passe</label>
                    <div class="relative">
                        <Lock class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="password"
                            :type="isPasswordVisible ? 'text' : 'password'"
                            class="pr-10 pl-10"
                            placeholder="••••••••"
                            @input="clearFieldError('password')"
                            @blur="
                                markFieldAsTouched('password');
                                validateForm(true);
                            "
                        />
                        <button
                            type="button"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-500"
                            @click="isPasswordVisible = !isPasswordVisible"
                        >
                            <component :is="isPasswordVisible ? EyeOff : Eye" class="h-4 w-4" />
                        </button>
                    </div>
                    <p v-if="errors.password" class="mt-1 text-sm text-red-500">{{ errors.password }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Confirmer le mot de passe</label>
                    <div class="relative">
                        <Lock class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="password_confirmation"
                            :type="isPasswordConfirmVisible ? 'text' : 'password'"
                            class="pr-10 pl-10"
                            placeholder="••••••••"
                            @input="clearFieldError('password_confirmation')"
                            @blur="
                                markFieldAsTouched('password_confirmation');
                                validateForm(true);
                            "
                        />
                        <button
                            type="button"
                            class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-500"
                            @click="isPasswordConfirmVisible = !isPasswordConfirmVisible"
                        >
                            <component :is="isPasswordConfirmVisible ? EyeOff : Eye" class="h-4 w-4" />
                        </button>
                    </div>
                    <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-500">{{ errors.password_confirmation }}</p>
                </div>

                <div>
                    <div class="flex items-start gap-2 text-sm">
                        <input
                            type="checkbox"
                            v-model="accepted"
                            @change="
                                markFieldAsTouched('accepted');
                                validateForm(true);
                            "
                            class="text-primary focus:ring-primary mt-1 h-4 w-4 rounded border-gray-300 focus:ring-2"
                        />
                        <label class="text-gray-700">
                            J'accepte les
                            <a href="#" class="text-primary underline">conditions générales d'utilisation</a>
                            et la
                            <a href="#" class="text-primary underline">politique de confidentialité</a>
                        </label>
                    </div>
                    <p v-if="errors.accepted" class="mt-1 text-sm text-red-500">{{ errors.accepted }}</p>
                </div>

                <Button
                    type="submit"
                    class="w-full font-semibold text-white transition"
                    :class="isFormValid ? 'bg-primary hover:bg-orange-500' : 'cursor-not-allowed bg-[#FFA789] opacity-80'"
                    :disabled="!isFormValid || inertiaForm.processing"
                >
                    <LoaderCircle v-if="inertiaForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                    {{ inertiaForm.processing ? 'Création en cours...' : 'Créer un compte' }}
                </Button>

                <div class="text-center text-sm">
                    Déjà inscrit ?
                    <TextLink :href="route('login')" class="text-primary">Se connecter</TextLink>
                </div>
            </form>
        </div>
    </div>
</GlobalLayout>
</template>
