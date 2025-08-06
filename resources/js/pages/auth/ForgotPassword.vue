<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, LoaderCircle, Mail } from 'lucide-vue-next';

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GlobalLayout>
        <div class="bg-secondary flex min-h-screen flex-col">
            <Head title="Mot de passe oublié" />

            <!-- Form Card -->
            <div
                class="mx-auto my-8 w-full max-w-xs rounded-2xl bg-white p-6 shadow-md sm:my-12 sm:max-w-sm sm:rounded-3xl sm:p-8 md:my-20 md:max-w-md"
            >
                <h2 class="mb-1 text-center text-xl font-bold sm:text-2xl">Mot de passe oublié</h2>
                <p class="mb-4 text-center text-sm text-gray-500 sm:mb-6 sm:text-base">Entrez votre email pour réinitialiser votre mot de passe</p>

                <form @submit.prevent="submit" class="space-y-4 sm:space-y-6">
                    <div>
                        <Label for="email" class="mb-1 block text-xs font-medium sm:text-sm">Email</Label>
                        <div class="relative">
                            <Mail class="absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-gray-400 sm:left-3 sm:h-4 sm:w-4" />
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autocomplete="off"
                                v-model="form.email"
                                autofocus
                                placeholder="votre@email.com"
                                class="py-2.5 pl-8 text-sm sm:py-3 sm:pl-10 sm:text-base"
                            />
                        </div>
                        <InputError :message="form.errors.email" />
                    </div>

                    <Button
                        type="submit"
                        class="bg-primary hover:bg-primary w-full py-3 text-sm font-bold text-white sm:py-5 sm:text-base"
                        :disabled="form.processing"
                    >
                        <LoaderCircle v-if="form.processing" class="h-3.5 w-3.5 animate-spin sm:h-4 sm:w-4" />
                        <span v-else>Envoyer le mail de réinitialisation</span>
                    </Button>
                </form>

                <div class="mt-4 flex items-center justify-center text-xs sm:mt-6 sm:text-sm">
                    <ArrowLeft class="text-primary mr-1 h-3.5 w-3.5 sm:h-4 sm:w-4" />
                    <TextLink :href="route('connexion')" class="text-primary">Retour à la connexion</TextLink>
                </div>
            </div>
        </div>
    </GlobalLayout>
</template>
