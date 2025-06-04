<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle, User, Lock, Mail } from 'lucide-vue-next';
import { ref } from 'vue';

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
  <div class="bg-orange-50 flex flex-col justify-between">
    <Head title="Connexion" />

    <!-- Logo -->
    <div class="pt-10 text-center py-10">
  <img src="/storage/logo_miminoo.png" alt="Miminoo" class="mx-auto h-10 w-auto " />
</div>

    <!-- Form container -->
    <div class="mx-auto w-full max-w-md rounded-3xl bg-white p-8 shadow-md mb-10">
      <h2 class="mb-1 text-center text-2xl font-bold">Connexion</h2>
      <p class="mb-6 text-center text-gray-500">Bienvenue sur Miminoo</p>

      
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <Label for="email" class="mb-1 block text-sm font-medium">Email</Label>
          <div class="relative">
            <Mail class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
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
            <Lock class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <Input
              id="password"
              :type="isPasswordVisible ? 'text' : 'password'"
              v-model="form.password"
              required
              autocomplete="current-password"
              class="pl-10 pr-10"
              placeholder="••••••••"
            />
            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500" @click="togglePasswordVisibility">
              <component :is="isPasswordVisible ? EyeOff : Eye" class="h-4 w-4" />
            </button>
          </div>
          <InputError :message="form.errors.password" />
        </div>

        <div class="flex justify-between items-center text-sm">
          <label class="flex items-center gap-2">
            <Checkbox id="remember" v-model="form.remember" />
            <span>Se souvenir de moi</span>
          </label>
          <TextLink v-if="canResetPassword" :href="route('password.request')">Mot de passe oublié ?</TextLink>
        </div>

        <Button type="submit" class="w-full bg-primary text-white text-base font-bold py-5 hover:bg-orange-500" :disabled="form.processing">
          <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
          <span v-else>Se connecter</span>
        </Button>

        <div class="text-center text-sm">
          Pas encore de compte ?
          <TextLink :href="route('register')" class="text-primary">S’inscrire</TextLink>
        </div>
      </form>
    </div>
    
  </div>
</template>
