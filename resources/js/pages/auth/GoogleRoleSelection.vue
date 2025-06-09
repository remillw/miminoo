<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/vue3';
import { Baby, Users } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    existingUser?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    existingUser: false,
});

const selectedRole = ref<string>('');
const isLoading = ref(false);

const selectRole = (role: string) => {
    selectedRole.value = role;
};

const completeRegistration = () => {
    if (!selectedRole.value) return;

    isLoading.value = true;

    router.post(
        '/auth/google/complete',
        {
            role: selectedRole.value,
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
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-orange-50 via-red-50 to-pink-50 p-4">
        <Card class="w-full max-w-2xl shadow-2xl">
            <CardHeader class="pb-8 text-center">
                <div class="bg-primary mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full">
                    <svg class="h-8 w-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                        />
                        <path
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                        />
                        <path
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                        />
                        <path
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                        />
                    </svg>
                </div>
                <CardTitle class="mb-2 text-3xl font-bold text-gray-800">
                    {{ existingUser ? 'Finalisons votre profil' : 'Bienvenue sur Miminoo !' }}
                </CardTitle>
                <p class="text-lg text-gray-600">
                    {{
                        existingUser
                            ? 'Pour terminer la configuration de votre compte, veuillez choisir votre profil'
                            : 'Pour finaliser votre inscription, veuillez choisir votre profil'
                    }}
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
                                Nous avons trouvé votre compte. Pour continuer, veuillez sélectionner votre profil ci-dessous.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Options de rôle -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Parent -->
                    <div
                        @click="selectRole('parent')"
                        :class="[
                            'relative cursor-pointer rounded-2xl border-2 p-6 transition-all duration-300 hover:shadow-lg',
                            selectedRole === 'parent'
                                ? 'border-primary bg-primary/5 ring-primary/20 shadow-lg ring-2'
                                : 'border-gray-200 hover:border-gray-300',
                        ]"
                    >
                        <div class="text-center">
                            <div
                                :class="[
                                    'mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full',
                                    selectedRole === 'parent' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600',
                                ]"
                            >
                                <Users class="h-8 w-8" />
                            </div>
                            <h3 class="mb-2 text-xl font-semibold text-gray-800">Je suis parent</h3>
                            <p class="text-sm text-gray-600">Je cherche une babysitter de confiance pour garder mes enfants</p>
                        </div>

                        <!-- Checkmark -->
                        <div
                            v-if="selectedRole === 'parent'"
                            class="bg-primary absolute top-3 right-3 flex h-6 w-6 items-center justify-center rounded-full"
                        >
                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Babysitter -->
                    <div
                        @click="selectRole('babysitter')"
                        :class="[
                            'relative cursor-pointer rounded-2xl border-2 p-6 transition-all duration-300 hover:shadow-lg',
                            selectedRole === 'babysitter'
                                ? 'border-primary bg-primary/5 ring-primary/20 shadow-lg ring-2'
                                : 'border-gray-200 hover:border-gray-300',
                        ]"
                    >
                        <div class="text-center">
                            <div
                                :class="[
                                    'mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full',
                                    selectedRole === 'babysitter' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600',
                                ]"
                            >
                                <Baby class="h-8 w-8" />
                            </div>
                            <h3 class="mb-2 text-xl font-semibold text-gray-800">Je suis babysitter</h3>
                            <p class="text-sm text-gray-600">Je souhaite proposer mes services de garde d'enfants</p>
                        </div>

                        <!-- Checkmark -->
                        <div
                            v-if="selectedRole === 'babysitter'"
                            class="bg-primary absolute top-3 right-3 flex h-6 w-6 items-center justify-center rounded-full"
                        >
                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Informations supplémentaires -->
                <div v-if="selectedRole === 'babysitter'" class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-orange-100">
                            <svg class="h-3 w-3 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-orange-800">À savoir</p>
                            <p class="mt-1 text-sm text-orange-700">
                                {{
                                    existingUser
                                        ? 'Votre email Google est déjà vérifié ! Votre profil babysitter sera examiné par notre équipe avant validation.'
                                        : 'Votre profil babysitter sera examiné par notre équipe avant validation. Vous recevrez une notification une fois approuvé.'
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Bouton de validation -->
                <div class="pt-4">
                    <Button
                        @click="completeRegistration"
                        :disabled="!selectedRole || isLoading"
                        class="bg-primary w-full py-3 text-lg font-semibold text-white hover:bg-orange-500"
                        size="lg"
                    >
                        <span v-if="isLoading">{{ existingUser ? 'Mise à jour...' : 'Création en cours...' }}</span>
                        <span v-else-if="selectedRole === 'parent'">
                            {{ existingUser ? 'Configurer comme parent' : 'Créer mon compte parent' }}
                        </span>
                        <span v-else-if="selectedRole === 'babysitter'">
                            {{ existingUser ? 'Configurer comme babysitter' : 'Créer mon compte babysitter' }}
                        </span>
                        <span v-else>Sélectionnez un profil</span>
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
