<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle, Mail, Lock, User, Phone } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const isPasswordVisible = ref(false);
const isPasswordConfirmVisible = ref(false);

const role = ref<'parent' | 'babysitter'>('parent');

const form = useForm({
  firstname: '',
  lastname: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  role: role.value,
  accepted: false,
});

const isFormValid = computed(() => {
  return (
    form.firstname &&
    form.lastname &&
    form.email &&
    form.phone &&
    form.password &&
    form.password_confirmation &&
    form.accepted
  );
});

const submit = () => {
  form.role = role.value;
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <div class="bg-orange-50 flex flex-col justify-between">
    <Head title="Inscription" />

    <div class="pt-10 text-center py-10">
      <img src="/storage/logo_miminoo.png" alt="Miminoo" class="mx-auto h-10" />
    </div>

    <div class="mx-auto w-full max-w-md rounded-3xl bg-white p-8 shadow-md mb-10">
      <h2 class="mb-1 text-center text-2xl font-bold">Inscription</h2>
      <p class="mb-6 text-center text-gray-500">Créez votre compte Miminoo</p>

      <div class="mb-6 flex justify-center">
  <div class="relative flex w-full max-w-xs bg-gray-100 rounded-full">
    <!-- Curseur blanc animé -->
    <div
      class="absolute z-0 top-0 left-0 h-full w-1/2 rounded-full bg-white shadow-sm transition-transform duration-300"
      :class="role === 'babysitter' ? 'translate-x-full' : 'translate-x-0'"
    ></div>

    <!-- Bouton Parent -->
    <button
      type="button"
      class="relative z-10 w-1/2 py-2 text-sm font-semibold transition-colors duration-300 focus:outline-none"
      :class="role === 'parent' ? 'text-primary' : 'text-gray-400'"
      @click="role = 'parent'"
    >
      Parent
    </button>

    <!-- Bouton Babysitter -->
    <button
      type="button"
      class="relative z-10 w-1/2 py-2 text-sm font-semibold transition-colors duration-300 focus:outline-none"
      :class="role === 'babysitter' ? 'text-primary' : 'text-gray-400'"
      @click="role = 'babysitter'"
    >
      Babysitter
    </button>
  </div>
</div>


      <form @submit.prevent="submit" class="space-y-4">
        <div class="flex gap-4">
          <div class="w-1/2">
            <Label for="firstname">Prénom</Label>
            <div class="relative">
              <User class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <Input id="firstname" v-model="form.firstname" class="pl-10" placeholder="Prénom" />
            </div>
            <InputError :message="form.errors.firstname" />
          </div>
          <div class="w-1/2">
            <Label for="lastname">Nom</Label>
            <div class="relative">
              <User class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <Input id="lastname" v-model="form.lastname" class="pl-10" placeholder="Nom" />
            </div>
            <InputError :message="form.errors.lastname" />
          </div>
        </div>

        <div>
          <Label for="email">Email</Label>
          <div class="relative">
            <Mail class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <Input id="email" type="email" v-model="form.email" class="pl-10" placeholder="votre@email.com" />
          </div>
          <InputError :message="form.errors.email" />
        </div>

        <div>
          <Label for="phone">Téléphone</Label>
          <div class="relative">
            <Phone class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <Input id="phone" type="tel" v-model="form.phone" class="pl-10" placeholder="06 12 56 43 78" />
          </div>
          <InputError :message="form.errors.phone" />
        </div>

        <div>
          <Label for="password">Mot de passe</Label>
          <div class="relative">
            <Lock class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <Input :type="isPasswordVisible ? 'text' : 'password'" v-model="form.password" id="password" class="pl-10 pr-10" placeholder="••••••••" />
            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500" @click="isPasswordVisible = !isPasswordVisible">
              <component :is="isPasswordVisible ? EyeOff : Eye" class="h-4 w-4" />
            </button>
          </div>
          <InputError :message="form.errors.password" />
        </div>

        <div>
          <Label for="password_confirmation">Confirmer le mot de passe</Label>
          <div class="relative">
            <Lock class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <Input :type="isPasswordConfirmVisible ? 'text' : 'password'" v-model="form.password_confirmation" id="password_confirmation" class="pl-10 pr-10" placeholder="••••••••" />
            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500" @click="isPasswordConfirmVisible = !isPasswordConfirmVisible">
              <component :is="isPasswordConfirmVisible ? EyeOff : Eye" class="h-4 w-4" />
            </button>
          </div>
          <InputError :message="form.errors.password_confirmation" />
        </div>

        <div class="flex items-start gap-2 text-sm">
          <Checkbox id="accept" v-model="form.accepted" />
          <label for="accept" class="text-gray-700">
            J'accepte les
            <a href="#" class="text-primary underline">conditions générales d’utilisation</a>
            et la
            <a href="#" class="text-primary underline">politique de confidentialité</a>
          </label>
        </div>

        <Button
          type="submit"
          class="w-full text-white font-semibold transition"
          :class="isFormValid ? 'bg-primary hover:bg-orange-500' : 'bg-[#FFA789] opacity-80 cursor-not-allowed'"
          :disabled="!isFormValid"
        >
          Créer un compte
        </Button>

        <div class="text-center text-sm">
          Déjà inscrit ?
          <TextLink :href="route('login')" class="text-primary">Se connecter</TextLink>
        </div>
      </form>
    </div>
  </div>
</template>
