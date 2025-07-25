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
  <div class="bg-secondary flex flex-col justify-between min-h-screen">
    <Head title="Vérification de l'e-mail" />

    <!-- Logo -->
    <div class="pt-6 text-center py-6 sm:pt-8 sm:py-8 md:pt-10 md:py-10">
      <img src="/storage/trouve-ta-babysitter-logo.svg" alt="Trouve ta Babysitter" class="mx-auto h-8 w-auto sm:h-10" />
    </div>

    <!-- Carte de vérification -->
    <div class="mx-auto w-full max-w-xs rounded-2xl bg-white p-6 shadow-md mb-6 text-center sm:max-w-sm sm:rounded-3xl sm:p-8 sm:mb-8 md:max-w-md md:mb-10">
      <h2 class="mb-1 text-xl font-bold sm:text-2xl">Vérifie ton adresse e-mail</h2>
      <p class="mb-4 text-sm text-gray-500 sm:mb-6 sm:text-base">
        Nous venons de t'envoyer un lien de confirmation.<br class="hidden sm:block">
        <span class="sm:hidden"> </span>Clique dessus pour activer ton compte.
      </p>

      <!-- Message de confirmation si lien renvoyé -->
      <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 flex items-center justify-center gap-1.5 rounded-lg bg-green-50 px-3 py-2.5 text-xs font-medium text-green-700 border border-green-200 sm:mb-6 sm:gap-2 sm:px-4 sm:py-3 sm:text-sm"
      >
        <MailCheck class="w-3.5 h-3.5 text-green-600 sm:w-4 sm:h-4" />
        Un nouveau lien a été envoyé à ton adresse e-mail.
      </div>

      <!-- Formulaire -->
      <form @submit.prevent="submit" class="space-y-3 sm:space-y-4">
        <Button
          type="submit"
          class="w-full bg-primary text-white font-bold hover:bg-primary py-3 text-sm sm:py-5 sm:text-base"
          :disabled="form.processing"
        >
          <LoaderCircle v-if="form.processing" class="h-3.5 w-3.5 animate-spin sm:h-4 sm:w-4" />
          <span v-else>Renvoyer l'e-mail de vérification</span>
        </Button>

        <TextLink :href="route('deconnexion')" method="post" as="button" class="text-xs text-gray-500 hover:underline sm:text-sm">
          Se déconnecter
        </TextLink>
      </form>
    </div>
  </div>
</template>
