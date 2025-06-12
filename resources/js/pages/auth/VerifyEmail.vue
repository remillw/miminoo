<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import TextLink from '@/components/TextLink.vue'
import { LoaderCircle, MailCheck } from 'lucide-vue-next'

defineProps<{
  status?: string
}>()

const form = useForm({})

const submit = () => {
  form.post(route('verification.send'))
}
</script>

<template>
  <div class="bg-secondary flex flex-col justify-between">
    <Head title="Vérification de l’e-mail" />

    <!-- Logo -->
    <div class="pt-10 text-center py-10">
      <img src="/storage/logo_miminoo.png" alt="Miminoo" class="mx-auto h-10 w-auto" />
    </div>

    <!-- Carte de vérification -->
    <div class="mx-auto w-full max-w-md rounded-3xl bg-white p-8 shadow-md mb-10 text-center">
      <h2 class="mb-1 text-2xl font-bold">Vérifie ton adresse e-mail</h2>
      <p class="mb-6 text-gray-500">
        Nous venons de t’envoyer un lien de confirmation.<br>
        Clique dessus pour activer ton compte.
      </p>

      <!-- Message de confirmation si lien renvoyé -->
      <div
        v-if="status === 'verification-link-sent'"
        class="mb-6 flex items-center justify-center gap-2 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-700 border border-green-200"
      >
        <MailCheck class="w-4 h-4 text-green-600" />
        Un nouveau lien a été envoyé à ton adresse e-mail.
      </div>

      <!-- Formulaire -->
      <form @submit.prevent="submit" class="space-y-4">
        <Button
          type="submit"
          class="w-full bg-primary text-white text-base font-bold py-5 hover:bg-primary"
          :disabled="form.processing"
        >
          <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
          <span v-else>Renvoyer l’e-mail de vérification</span>
        </Button>

        <TextLink :href="route('logout')" method="post" as="button" class="text-sm text-gray-500 hover:underline">
          Se déconnecter
        </TextLink>
      </form>
    </div>
  </div>
</template>
