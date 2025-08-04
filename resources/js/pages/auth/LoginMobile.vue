<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useDeviceToken } from '@/composables/useDeviceToken';
import { useMobileAuth } from '@/composables/useMobileAuth';
import MobileAuthLayout from '@/layouts/MobileAuthLayout.vue';
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
    const baseData = {
        email: form.email,
        password: form.password,
        remember: form.remember,
    };

    const deviceTokenData = isMobileApp() ? getDeviceTokenData() : null;

    if (deviceTokenData) {
        form.mobile_auth = 'true';
        form.device_token = deviceTokenData.device_token;
        form.platform = deviceTokenData.platform;
        form.notification_provider = deviceTokenData.notification_provider;
    }

    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        preserveScroll: true,
    });
};

const handleGoogleAuth = async () => {
    const deviceTokenData = isMobileApp() ? getDeviceTokenData() : null;
    await authenticateWithGoogle(deviceTokenData);
};
</script>

<template>
    <MobileAuthLayout>
        <Head title="Connexion" />
        
        <!-- Boutons de connexion sociale optimisés mobile -->
        <div class="space-y-3 mb-6">
            <!-- Bouton Google -->
            <button
                @click="handleGoogleAuth"
                type="button"
                class="w-full flex items-center justify-center gap-3 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none"
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
        </div>

        <!-- Séparateur -->
        <div class="flex items-center mb-6">
            <div class="flex-1 border-t border-gray-300"></div>
            <div class="mx-4 text-sm text-gray-500">ou</div>
            <div class="flex-1 border-t border-gray-300"></div>
        </div>

        <!-- Formulaire de connexion -->
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <Label for="email" class="block text-sm font-medium mb-2">Email</Label>
                <div class="relative">
                    <Mail class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <Input
                        id="email"
                        type="email"
                        v-model="form.email"
                        autocomplete="email"
                        required
                        class="pl-10 py-3 text-base"
                        placeholder="votre@email.com"
                    />
                </div>
                <InputError :message="form.errors.email" />
            </div>

            <div>
                <Label for="password" class="block text-sm font-medium mb-2">Mot de passe</Label>
                <div class="relative">
                    <Lock class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <Input
                        id="password"
                        :type="isPasswordVisible ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        class="pl-10 pr-10 py-3 text-base"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        @click="togglePasswordVisibility"
                        class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                    >
                        <Eye v-if="!isPasswordVisible" class="h-4 w-4" />
                        <EyeOff v-else class="h-4 w-4" />
                    </button>
                </div>
                <InputError :message="form.errors.password" />
            </div>

            <!-- Options -->
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <Checkbox id="remember" v-model:checked="form.remember" />
                    <Label for="remember" class="text-sm text-gray-600">Se souvenir de moi</Label>
                </div>
                
                <TextLink
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-primary hover:text-primary/80"
                >
                    Mot de passe oublié ?
                </TextLink>
            </div>

            <!-- Bouton de connexion -->
            <Button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3 text-base font-medium"
            >
                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                Se connecter
            </Button>
        </form>

        <!-- Lien d'inscription -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Pas encore de compte ?
                <TextLink :href="route('register')" class="text-primary hover:text-primary/80 font-medium">
                    Créer un compte
                </TextLink>
            </p>
        </div>
    </MobileAuthLayout>
</template>