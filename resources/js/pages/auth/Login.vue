<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle, Lock, Mail } from 'lucide-vue-next';
import { ref } from 'vue';
import { route } from 'ziggy-js';

const isPasswordVisible = ref(false);

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
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="flex flex-col justify-between bg-orange-50">
        <Head title="Connexion" />

        <!-- Logo -->
        <div class="py-10 pt-10 text-center">
            <img src="/storage/logo_miminoo.png" alt="Miminoo" class="mx-auto h-10 w-auto" />
        </div>

        <!-- Form container -->
        <div class="mx-auto mb-10 w-full max-w-md rounded-3xl bg-white p-8 shadow-md">
            <h2 class="mb-1 text-center text-2xl font-bold">Connexion</h2>
            <p class="mb-6 text-center text-gray-500">Bienvenue sur Miminoo</p>

            <!-- Boutons Google avec choix de rôle -->
            <div class="mb-6 space-y-3">
                <!-- Google Parent -->
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
                    <div class="flex items-center gap-2">
                        <span>Continuer avec Google</span>
                        <span class="rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-600">Parent</span>
                    </div>
                </a>

                <!-- Google Babysitter -->
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
                    <div class="flex items-center gap-2">
                        <span>Continuer avec Google</span>
                        <span class="rounded-full bg-orange-100 px-2 py-1 text-xs text-orange-600">Babysitter</span>
                    </div>
                </a>

                <!-- Google général (sans rôle préféré) -->
                <a
                    :href="route('google.redirect')"
                    class="focus:ring-primary inline-flex w-full items-center justify-center gap-3 rounded-xl border border-gray-300 bg-gray-50 px-4 py-2 text-xs font-medium text-gray-600 shadow-sm transition-colors hover:bg-gray-100 focus:ring-2 focus:ring-offset-2 focus:outline-none"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24">
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
                    <span>Continuer avec Google (choisir ensuite)</span>
                </a>
            </div>

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

                <Button type="submit" class="bg-primary w-full py-5 text-base font-bold text-white hover:bg-orange-500" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <span v-else>Se connecter</span>
                </Button>

                <div class="text-center text-sm">
                    Pas encore de compte ?
                    <TextLink :href="route('register')" class="text-primary">S'inscrire</TextLink>
                </div>
            </form>
        </div>
    </div>
</template>
