<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useDeviceToken } from '@/composables/useDeviceToken';
import { useMobileAuth } from '@/composables/useMobileAuth';
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle, Lock, Mail } from 'lucide-vue-next';
import { ref } from 'vue';
import { route } from 'ziggy-js';

const isPasswordVisible = ref(false);
const { authenticateWithGoogle } = useMobileAuth();
const { getDeviceTokenData, isMobileApp } = useDeviceToken();

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
    mobile_auth: '',
    device_token: '',
    platform: '',
    notification_provider: '',
});

const submit = () => {
    console.log('=== LOGIN SUBMIT DÉMARRÉ ===');
    
    // Version simplifiée sans transform pour éviter les conflits
    const baseData = {
        email: form.email,
        password: form.password,
        remember: form.remember,
    };

    // Debug détaillé
    console.log('Step 1 - Base data:', { email: baseData.email, remember: baseData.remember });
    
    // Vérifier l'état du composable
    console.log('Step 2 - isMobileApp():', isMobileApp());
    console.log('Step 3 - window.ReactNativeWebView exists:', !!window.ReactNativeWebView);
    console.log('Step 4 - window.deviceTokenData exists:', !!(window as any).deviceTokenData);
    console.log('Step 5 - window.deviceTokenData value:', (window as any).deviceTokenData);

    const deviceTokenData = getDeviceTokenData();
    console.log('Step 6 - getDeviceTokenData() result:', deviceTokenData);

    if (isMobileApp() && deviceTokenData) {
        console.log('=== FRONTEND - DEVICE TOKEN DÉTECTÉ ===');
        console.log('Device Token Data:', deviceTokenData);
        
        // Ajouter les données mobile directement
        Object.assign(baseData, deviceTokenData, { mobile_auth: 'true' });
        console.log('Step 7 - Data after mobile token added:', {
            ...baseData,
            password: '[HIDDEN]',
            device_token: deviceTokenData.device_token ? deviceTokenData.device_token.substring(0, 20) + '...' : 'NULL'
        });
    } else {
        console.log('=== FRONTEND - PAS DE TOKEN MOBILE ===');
        console.log('Raisons possibles:');
        console.log('- isMobileApp():', isMobileApp());
        console.log('- deviceTokenData:', deviceTokenData);
    }

    console.log('=== FRONTEND - DONNÉES FINALES ENVOYÉES ===');
    console.log('Final Data:', {
        ...baseData,
        password: '[HIDDEN]' // Ne pas logger le mot de passe
    });

    // Soumission avec les données intégrées dans le form
    // D'abord, ajouter les données mobiles au form object
    if (isMobileApp() && deviceTokenData) {
        form.mobile_auth = 'true';
        form.device_token = deviceTokenData.device_token;
        form.platform = deviceTokenData.platform;
        form.notification_provider = deviceTokenData.notification_provider;
    }
    
    form.post(route('connexion'), {
        onFinish: () => form.reset('password'),
        onError: (errors) => {
            console.log('=== FRONTEND - ERREURS REÇUES ===');
            console.log('Validation Errors:', errors);
        },
        preserveScroll: true,
    });
};

const handleGoogleAuth = async () => {
    // Récupérer les données du device token si on est dans une app mobile
    const deviceTokenData = isMobileApp() ? getDeviceTokenData() : null;

    if (deviceTokenData) {
        console.log('Google Auth: Utilisation du device token:', {
            platform: deviceTokenData.platform,
            provider: deviceTokenData.notification_provider,
            tokenPreview: deviceTokenData.device_token.substring(0, 20) + '...',
        });
    }

    await authenticateWithGoogle(deviceTokenData);
};
</script>

<template>
    <GlobalLayout>
        <div class="bg-secondary flex min-h-screen flex-col justify-between">
            <Head title="Connexion" />

            <!-- Form container -->
            <div class="mx-auto my-20 mb-10 w-full max-w-md rounded-3xl bg-white p-8 shadow-md">
                <h2 class="mb-1 text-center text-2xl font-bold">Connexion</h2>
                <p class="mb-6 text-center text-gray-500">Bienvenue sur la plateforme de babysitting</p>

                <!-- Boutons de connexion sociale -->
                <div class="mb-4 space-y-2.5 sm:mb-6 sm:space-y-3">
                    <!-- Bouton Google -->
                    <button
                        @click="handleGoogleAuth"
                        type="button"
                        class="focus:ring-primary inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-xs font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 focus:ring-2 focus:ring-offset-2 focus:outline-none sm:gap-3 sm:rounded-xl sm:px-4 sm:py-3 sm:text-sm"
                    >
                        <svg class="h-4 w-4 sm:h-5 sm:w-5" viewBox="0 0 24 24">
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
                        <span class="hidden sm:inline">Continuer avec Google</span>
                        <span class="sm:hidden">Google</span>
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
                <div class="mb-4 flex items-center sm:mb-6">
                    <div class="flex-1 border-t border-gray-300"></div>
                    <div class="mx-3 text-xs text-gray-500 sm:mx-4 sm:text-sm">ou</div>
                    <div class="flex-1 border-t border-gray-300"></div>
                </div>

                <form @submit.prevent="submit" class="space-y-3 sm:space-y-4">
                    <div>
                        <Label for="email" class="mb-1 block text-xs font-medium sm:text-sm">Email</Label>
                        <div class="relative">
                            <Mail class="absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-gray-400 sm:left-3 sm:h-4 sm:w-4" />
                            <Input
                                id="email"
                                type="email"
                                v-model="form.email"
                                autocomplete="email"
                                required
                                class="py-2.5 pl-8 text-sm sm:py-3 sm:pl-10 sm:text-base"
                                placeholder="votre@email.com"
                            />
                        </div>
                        <InputError :message="form.errors.email" />
                    </div>

                    <div>
                        <Label for="password" class="mb-1 block text-xs font-medium sm:text-sm">Mot de passe</Label>
                        <div class="relative">
                            <Lock class="absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-gray-400 sm:left-3 sm:h-4 sm:w-4" />
                            <Input
                                id="password"
                                :type="isPasswordVisible ? 'text' : 'password'"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                class="py-2.5 pr-8 pl-8 text-sm sm:py-3 sm:pr-10 sm:pl-10 sm:text-base"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                class="absolute top-1/2 right-2.5 -translate-y-1/2 text-gray-500 sm:right-3"
                                @click="togglePasswordVisibility"
                            >
                                <component :is="isPasswordVisible ? EyeOff : Eye" class="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <label class="flex items-center gap-1.5 sm:gap-2">
                            <Checkbox id="remember" v-model="form.remember" />
                            <span>Se souvenir de moi</span>
                        </label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')">Mot de passe oublié ?</TextLink>
                    </div>

                    <Button
                        type="submit"
                        class="bg-primary hover:bg-primary w-full py-3 text-sm font-bold text-white sm:py-5 sm:text-base"
                        :disabled="form.processing"
                    >
                        <LoaderCircle v-if="form.processing" class="h-3.5 w-3.5 animate-spin sm:h-4 sm:w-4" />
                        <span v-else>Se connecter</span>
                    </Button>

                    <div class="text-center text-xs sm:text-sm">
                        Pas encore de compte ?
                        <TextLink :href="route('inscription')" class="text-primary">S'inscrire</TextLink>
                    </div>
                </form>
            </div>
        </div>
    </GlobalLayout>
</template>
