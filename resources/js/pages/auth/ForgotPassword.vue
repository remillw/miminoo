<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Mail, ArrowLeft } from 'lucide-vue-next';

const form = useForm({
  email: '',
});

const submit = () => {
  form.post(route('password.email'));
};
</script>

<template>
  <div class="bg-secondary flex flex-col justify-between">
    <Head title="Mot de passe oublié" />

    <!-- Logo -->
    <div class="pt-10 text-center py-10">
  <img src="/storage/trouve-ta-babysitter-logo.svg" alt="Trouve ta Babysitter" class="mx-auto h-10 w-auto " />
</div>


    <!-- Form Card -->
    <div class="mx-auto w-full max-w-md rounded-3xl bg-white p-8 shadow-md">
      <h2 class="mb-1 text-center text-2xl font-bold">Mot de passe oublié</h2>
      <p class="mb-6 text-center text-gray-500">Entrez votre email pour réinitialiser votre mot de passe</p>

      <form @submit.prevent="submit" class="space-y-6">
        <div>
          <Label for="email" class="mb-1 block text-sm font-medium">Email</Label>
          <div class="relative">
            <Mail class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <Input
              id="email"
              type="email"
              name="email"
              autocomplete="off"
              v-model="form.email"
              autofocus
              placeholder="votre@email.com"
              class="pl-10"
            />
          </div>
          <InputError :message="form.errors.email" />
        </div>

        <Button type="submit" class="w-full bg-primary py-5 text-white text-base font-bold hover:bg-primary" :disabled="form.processing">
          <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
          <span v-else>Envoyer le mail de réinitialisation</span>
        </Button>
      </form>

      <div class="mt-6 flex justify-center items-center text-sm">
        <ArrowLeft class="h-4 w-4 mr-1 text-primary" />
        <TextLink :href="route('login')" class="text-primary">Retour à la connexion</TextLink>
      </div>
    </div>
  </div>
</template>
