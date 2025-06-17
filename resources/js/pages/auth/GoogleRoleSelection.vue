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
    <div class="flex min-h-screen items-center justify-center bg-secondary p-4">
        <Card class="w-full max-w-2xl shadow-2xl">
            <CardHeader class="text-center">
                <img src="/storage/logo_miminoo.png" alt="Miminoo" class="mx-auto mb-4 w-48">
                <CardTitle class="mb-2 text-3xl font-bold text-gray-800">
                    {{ existingUser ? 'Finalisons votre profil' : 'Bienvenue sur Miminoo !' }}
                </CardTitle>
                <p class="text-lg text-gray-600">
                    {{
                        existingUser
                            ? 'Pour terminer la configuration de votre compte, choisissez vos rôles'
                            : 'Pour finaliser votre inscription, choisissez vos rôles sur la plateforme'
                    }}
                </p>
                <p class="mt-2 text-sm text-gray-500">
                    Vous pouvez choisir un ou plusieurs rôles selon vos besoins
                </p>
            </CardHeader>

            <CardContent class="space-y-6">
                <!-- Informations pour utilisateur existant -->
                <div v-if="existingUser" class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-blue-100">
                            <svg class="h-3 w-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-blue-800">Compte existant détecté</p>
                            <p class="mt-1 text-sm text-blue-700">
                                Nous avons trouvé votre compte. Pour continuer, veuillez sélectionner vos rôles ci-dessous.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Options de rôle -->
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <!-- Parent -->
                    <div
                        @click="toggleRole('parent')"
                        :class="[
                            'relative cursor-pointer rounded-2xl border-2 p-6 transition-all duration-300 hover:shadow-lg',
                            isRoleSelected('parent')
                                ? 'border-primary bg-primary/5 ring-primary/20 shadow-lg ring-2'
                                : 'border-gray-200 hover:border-gray-300',
                        ]"
                    >
                        <div class="text-center">
                            <div
                                :class="[
                                    'mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full',
                                    isRoleSelected('parent') ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600',
                                ]"
                            >
                                <Users class="h-8 w-8" />
                            </div>
                            <h3 class="mb-2 text-xl font-semibold text-gray-800">Je suis parent</h3>
                            <p class="text-sm text-gray-600">Je cherche une babysitter de confiance pour garder mes enfants</p>
                        </div>

                        <!-- Checkmark -->
                        <div
                            v-if="isRoleSelected('parent')"
                            class="bg-primary absolute top-3 right-3 flex h-6 w-6 items-center justify-center rounded-full"
                        >
                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Babysitter -->
                    <div
                        @click="toggleRole('babysitter')"
                        :class="[
                            'relative cursor-pointer rounded-2xl border-2 p-6 transition-all duration-300 hover:shadow-lg',
                            isRoleSelected('babysitter')
                                ? 'border-primary bg-primary/5 ring-primary/20 shadow-lg ring-2'
                                : 'border-gray-200 hover:border-gray-300',
                        ]"
                    >
                        <div class="text-center">
                            <div
                                :class="[
                                    'mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full',
                                    isRoleSelected('babysitter') ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600',
                                ]"
                            >
                                <Baby class="h-8 w-8" />
                            </div>
                            <h3 class="mb-2 text-xl font-semibold text-gray-800">Je suis babysitter</h3>
                            <p class="text-sm text-gray-600">Je souhaite proposer mes services de garde d'enfants</p>
                        </div>

                        <!-- Checkmark -->
                        <div
                            v-if="isRoleSelected('babysitter')"
                            class="bg-primary absolute top-3 right-3 flex h-6 w-6 items-center justify-center rounded-full"
                        >
                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Informations supplémentaires -->
                <div v-if="isRoleSelected('babysitter')" class="rounded-lg border border-grey bg-secondary p-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-white">
                            <svg class="h-20 w-20 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-primary">À savoir pour les babysitters</p>
                            <p class="mt-1 text-sm text-black">
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
                <div v-if="selectedRoles.length > 1" class="rounded-lg border border-green-200 bg-green-50 p-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-green-100">
                            <svg class="h-3 w-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-green-800">Profil mixte sélectionné</p>
                            <p class="mt-1 text-sm text-green-700">
                                Parfait ! Vous pourrez à la fois chercher des babysitters et proposer vos services de garde. 
                                Vous aurez accès à toutes les fonctionnalités de la plateforme.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Bouton de validation -->
                <div class="pt-4">
    <Button
        @click="completeRegistration"
        :disabled="!hasSelectedRoles || isLoading"
        class="bg-primary w-full py-3 text-lg font-semibold text-white hover:bg-primary cursor-pointer"
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
                <div class="border-t pt-4 text-center">
                    <a href="/login" class="text-sm text-gray-500 transition-colors hover:text-gray-700"> ← Retour à la connexion </a>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
