<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCapacitor } from '@/composables/useCapacitor';
import { useMobileAuth } from '@/composables/useMobileAuth';
import { usePushNotifications } from '@/composables/usePushNotifications';
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle, Lock, Mail } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { route } from 'ziggy-js';

const isPasswordVisible = ref(false);
const { authenticateWithGoogle } = useMobileAuth();
const { isNative } = useCapacitor();
const { initializePushNotifications, sendTokenWithLogin, deviceToken } = usePushNotifications();

const togglePasswordVisibility = () => {
    isPasswordVisible.value = !isPasswordVisible.value;
};

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    // Préparer les données de base
    const baseData = form.data();

    // Intégrer le token de device si on est sur mobile
    const formData = isNative.value ? sendTokenWithLogin(baseData) : baseData;

    console.log('🔐 Connexion avec données:', {
        isNative: isNative.value,
        hasDeviceToken: !!deviceToken.value,
        formData: formData,
    });

    form.transform(() => formData).post(route('connexion'), {
        onFinish: () => form.reset('password'),
        onSuccess: async () => {
            console.log('✅ Connexion réussie');

            // Si on est sur mobile, déclencher l'enregistrement des notifications push
            if (isNative.value) {
                console.log('📱 Déclenchement manuel des notifications push après connexion');
                try {
                    await initializePushNotifications();
                    console.log('✅ Notifications push initialisées après connexion');
                } catch (error) {
                    console.error("❌ Erreur lors de l'initialisation des push notifications:", error);
                }
            }
        },
        onError: (errors) => {
            console.error('❌ Erreur de connexion:', errors);
        },
    });
};

const handleGoogleAuth = async () => {
    await authenticateWithGoogle();
};

// Initialiser les push notifications dès le chargement de la page de login si on est sur mobile
onMounted(async () => {
    if (isNative.value) {
        console.log('📱 Page de login chargée sur mobile - initialisation des push notifications');
        try {
            await initializePushNotifications();
            console.log('✅ Push notifications initialisées avant login');
        } catch (error) {
            console.error("❌ Erreur lors de l'initialisation des push notifications avant login:", error);
        }
    }
});
</script>

<template>
    <GlobalLayout>
        <div class="bg-secondary flex flex-col justify-between">
            <Head title="Connexion" />

            <!-- Form container -->
            <div class="mx-auto my-20 mb-10 w-full max-w-md rounded-3xl bg-white p-8 shadow-md">
                <h2 class="mb-1 text-center text-2xl font-bold">Connexion</h2>
                <p class="mb-6 text-center text-gray-500">Bienvenue sur la plateforme de babysitting</p>

                <!-- DEBUG: Statut push notifications (mobile seulement) -->
                <div v-if="isNative" class="mb-4 rounded border border-blue-200 bg-blue-50 p-2 text-xs">
                    <div class="flex justify-between">
                        <span>📱 Mobile:</span>
                        <span class="font-mono">{{ isNative ? 'OUI' : 'NON' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>🔔 Token:</span>
                        <span class="font-mono">{{ deviceToken ? '✅ ' + deviceToken.substring(0, 10) + '...' : '❌ Aucun' }}</span>
                    </div>
                </div>

                <!-- Boutons de connexion sociale -->
                <div class="mb-6 space-y-3">
                    <!-- Bouton Google -->
                    <button
                        @click="handleGoogleAuth"
                        type="button"
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
                        Continuer avec Google
                    </button>

                    <!-- Bouton Apple - Commenté pour l'instant -->
                    <!--
                <a
                    :href="route('social.redirect', 'apple')"
                    class="inline-flex w-full items-center justify-center gap-3 rounded-xl border border-gray-300 bg-black px-4 py-3 text-sm font-medium text-white shadow-sm transition-colors hover:bg-gray-800 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:outline-none"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                    </svg>
                    Continuer avec Apple
                </a>
                --></div>

                <!-- Séparateur -->
                <div class="mb-6 flex items-center">
                    <div class="flex-1 border-t border-gray-300"></div>
                    <div class="mx-4 text-sm text-gray-500">ou</div>
                    <div class="flex-1 border-t border-gray-300"></div>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <Label for="email" class="mb-1 block text-sm font-medium">Email</Label>
                        <div class="relative">
                            <Mail class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                id="email"
                                type="email"
                                v-model="form.email"
                                autocomplete="email"
                                required
                                class="pl-10"
                                placeholder="votre@email.com"
                            />
                        </div>
                        <InputError :message="form.errors.email" />
                    </div>

                    <div>
                        <Label for="password" class="mb-1 block text-sm font-medium">Mot de passe</Label>
                        <div class="relative">
                            <Lock class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                id="password"
                                :type="isPasswordVisible ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                class="pr-10 pl-10"
                                placeholder="••••••••"
                            />
                            <button type="button" class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-500" @click="togglePasswordVisibility">
                                <component :is="isPasswordVisible ? EyeOff : Eye" class="h-4 w-4" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2">
                            <Checkbox id="remember" v-model="form.remember" />
                            <span>Se souvenir de moi</span>
                        </label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')">Mot de passe oublié ?</TextLink>
                    </div>

                    <Button type="submit" class="bg-primary hover:bg-primary w-full py-5 text-base font-bold text-white" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <span v-else>Se connecter</span>
                    </Button>

                    <div class="text-center text-sm">
                        Pas encore de compte ?
                        <TextLink :href="route('inscription')" class="text-primary">S'inscrire</TextLink>
                    </div>
                </form>
            </div>
        </div>
    </GlobalLayout>
</template>
