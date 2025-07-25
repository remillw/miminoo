<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/vue3';
import { Baby, Users } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';

interface Props {
    existingUser?: boolean;
    isGoogleUser?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    existingUser: false,
    isGoogleUser: true, // Par défaut Google, sera false pour les inscriptions normales
});

const selectedRoles = ref<string[]>([]);
const isLoading = ref(false);

const toggleRole = (role: string) => {
    const index = selectedRoles.value.indexOf(role);
    if (index > -1) {
        selectedRoles.value.splice(index, 1);
    } else {
        selectedRoles.value.push(role);
    }
};

const isRoleSelected = (role: string) => {
    return selectedRoles.value.includes(role);
};

const hasSelectedRoles = computed(() => {
    return selectedRoles.value.length > 0;
});

const completeRegistration = () => {
    if (!hasSelectedRoles.value) return;

    isLoading.value = true;

    // Choisir la route selon le type d'utilisateur
    const routeName = props.isGoogleUser ? 'google.complete' : 'role.complete';
    const url = props.isGoogleUser ? '/auth/google/complete' : route('role.complete');

    router.post(
        url,
        {
            roles: selectedRoles.value,
        },
        {
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
};
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-secondary p-3 sm:p-4">
        <Card class="w-full max-w-xs shadow-2xl sm:max-w-lg md:max-w-xl lg:max-w-2xl">
            <CardHeader class="text-center">
                <img src="/storage/trouve-ta-babysitter-logo.svg" alt="Trouve ta babysitter logo" class="mx-auto mb-3 w-32 sm:mb-4 sm:w-40 md:w-48">
                <CardTitle class="mb-2 text-xl font-bold text-gray-800 sm:text-2xl md:text-3xl">
                    {{ existingUser ? 'Finalisons votre profil' : 'Bienvenue !' }}
                </CardTitle>
                <p class="text-sm text-gray-600 sm:text-base md:text-lg">
                    {{
                        existingUser
                            ? 'Pour terminer la configuration de votre compte, choisissez vos rôles'
                            : 'Pour finaliser votre inscription, choisissez vos rôles sur la plateforme'
                    }}
                </p>
                <p class="mt-2 text-xs text-gray-500 sm:text-sm">
                    Vous pouvez choisir un ou plusieurs rôles selon vos besoins
                </p>
            </CardHeader>

            <CardContent class="space-y-4 sm:space-y-6">
                <!-- Informations pour utilisateur existant -->
                <div v-if="existingUser" class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3 sm:mb-6 sm:p-4">
                    <div class="flex items-start gap-2 sm:gap-3">
                        <div class="mt-0.5 flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:h-5 sm:w-5">
                            <svg class="h-2.5 w-2.5 text-blue-600 sm:h-3 sm:w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-blue-800 sm:text-sm">Compte existant détecté</p>
                            <p class="mt-1 text-xs text-blue-700 sm:text-sm">
                                Nous avons trouvé votre compte. Pour continuer, veuillez sélectionner vos rôles ci-dessous.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Options de rôle -->
                <div class="grid grid-cols-1 gap-4 sm:gap-5 md:grid-cols-2">
                    <!-- Parent -->
                    <div
                        @click="toggleRole('parent')"
                        :class="[
                            'relative cursor-pointer rounded-xl border-2 p-4 transition-all duration-300 hover:shadow-lg sm:rounded-2xl sm:p-6',
                            isRoleSelected('parent')
                                ? 'border-primary bg-primary/5 ring-primary/20 shadow-lg ring-2'
                                : 'border-gray-200 hover:border-gray-300',
                        ]"
                    >
                        <div class="text-center">
                            <div
                                :class="[
                                    'mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full sm:mb-4 sm:h-16 sm:w-16',
                                    isRoleSelected('parent') ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600',
                                ]"
                            >
                                <Users class="h-6 w-6 sm:h-8 sm:w-8" />
                            </div>
                            <h3 class="mb-2 text-lg font-semibold text-gray-800 sm:text-xl">Je suis parent</h3>
                            <p class="text-xs text-gray-600 sm:text-sm">Je cherche une babysitter de confiance pour garder mes enfants</p>
                        </div>

                        <!-- Checkmark -->
                        <div
                            v-if="isRoleSelected('parent')"
                            class="bg-primary absolute top-2 right-2 flex h-5 w-5 items-center justify-center rounded-full sm:top-3 sm:right-3 sm:h-6 sm:w-6"
                        >
                            <svg class="h-3 w-3 text-white sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Babysitter -->
                    <div
                        @click="toggleRole('babysitter')"
                        :class="[
                            'relative cursor-pointer rounded-xl border-2 p-4 transition-all duration-300 hover:shadow-lg sm:rounded-2xl sm:p-6',
                            isRoleSelected('babysitter')
                                ? 'border-primary bg-primary/5 ring-primary/20 shadow-lg ring-2'
                                : 'border-gray-200 hover:border-gray-300',
                        ]"
                    >
                        <div class="text-center">
                            <div
                                :class="[
                                    'mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full sm:mb-4 sm:h-16 sm:w-16',
                                    isRoleSelected('babysitter') ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600',
                                ]"
                            >
                                <Baby class="h-6 w-6 sm:h-8 sm:w-8" />
                            </div>
                            <h3 class="mb-2 text-lg font-semibold text-gray-800 sm:text-xl">Je suis babysitter</h3>
                            <p class="text-xs text-gray-600 sm:text-sm">Je souhaite proposer mes services de garde d'enfants</p>
                        </div>

                        <!-- Checkmark -->
                        <div
                            v-if="isRoleSelected('babysitter')"
                            class="bg-primary absolute top-2 right-2 flex h-5 w-5 items-center justify-center rounded-full sm:top-3 sm:right-3 sm:h-6 sm:w-6"
                        >
                            <svg class="h-3 w-3 text-white sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Informations supplémentaires -->
                <div v-if="isRoleSelected('babysitter')" class="rounded-lg border border-grey bg-secondary p-3 sm:p-4">
                    <div class="flex items-start gap-2 sm:gap-3">
                        <div class="mt-0.5 flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full bg-white sm:h-5 sm:w-5">
                            <svg class="h-2.5 w-2.5 text-primary sm:h-3 sm:w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-primary sm:text-sm">À savoir pour les babysitters</p>
                            <p class="mt-1 text-xs text-black sm:text-sm">
                                {{
                                    existingUser && isGoogleUser
                                        ? 'Votre email Google est déjà vérifié ! Votre profil babysitter sera examiné par notre équipe avant validation.'
                                        : 'Votre profil babysitter sera examiné par notre équipe avant validation. Vous recevrez une notification une fois approuvé.'
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Information pour choix multiple -->
                <div v-if="selectedRoles.length > 1" class="rounded-lg border border-green-200 bg-green-50 p-3 sm:p-4">
                    <div class="flex items-start gap-2 sm:gap-3">
                        <div class="mt-0.5 flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full bg-green-100 sm:h-5 sm:w-5">
                            <svg class="h-2.5 w-2.5 text-green-600 sm:h-3 sm:w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-green-800 sm:text-sm">Profil mixte sélectionné</p>
                            <p class="mt-1 text-xs text-green-700 sm:text-sm">
                                Parfait ! Vous pourrez à la fois chercher des babysitters et proposer vos services de garde. 
                                Vous aurez accès à toutes les fonctionnalités de la plateforme.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Bouton de validation -->
                <div class="pt-3 sm:pt-4">
    <Button
        @click="completeRegistration"
        :disabled="!hasSelectedRoles || isLoading"
        class="bg-primary w-full text-white hover:bg-primary cursor-pointer py-2.5 text-sm font-semibold sm:py-3 sm:text-lg"
        size="lg"
    >
        <span v-if="isLoading">{{ existingUser ? 'Mise à jour...' : 'Création en cours...' }}</span>
        <span v-else-if="selectedRoles.length > 1">
            {{ existingUser ? 'Configurer comme parent ET babysitter' : 'Créer mon compte mixte' }}
        </span>
        <span v-else-if="selectedRoles.includes('parent')">
            {{ existingUser ? 'Configurer comme parent' : 'Créer mon compte parent' }}
        </span>
        <span v-else-if="selectedRoles.includes('babysitter')">
            {{ existingUser ? 'Configurer comme babysitter' : 'Créer mon compte babysitter' }}
        </span>
        <span v-else>Sélectionnez au moins un rôle</span>
    </Button>
</div>


                <!-- Retour à la connexion -->
                <div class="border-t pt-3 text-center sm:pt-4">
                    <a href="/connexion" class="text-xs text-gray-500 transition-colors hover:text-gray-700 sm:text-sm"> ← Retour à la connexion </a>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
