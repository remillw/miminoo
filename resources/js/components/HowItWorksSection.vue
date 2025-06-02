<script setup lang="ts">
import { Calendar, CheckCircle, CreditCard, Search } from 'lucide-vue-next';

const steps = [
  {
    icon: Search,
    title: 'Créez votre annonce',
    desc: `Décrivez votre besoin : date, horaires, lieu et infos sur vos enfants. Votre annonce est envoyée aux babysitters disponibles autour de vous.`,
    bgColor: 'bg-primary', // orange pastel
  },
  {
    icon: CheckCircle,
    title: 'Choisissez une babysitter',
    desc: `Recevez des candidatures de profils vérifiés. Consultez les avis, les expériences et choisissez celle qui vous inspire confiance.`,
    bgColor: 'bg-[#6EBBD3]', // bleu
  },
  {
    icon: Calendar,
    title: 'Confirmez la réservation',
    desc: `Échangez avec la babysitter sélectionnée via notre messagerie sécurisée. Validez ensemble les derniers détails avant de réserver.`,
    bgColor: 'bg-[#6AB995]', // vert
  },
  {
    icon: CreditCard,
    title: 'Payez en toute sécurité',
    desc: `Réglez directement le nombre d'heures souhaitées via Miminoo. Le paiement est sécurisé, et la babysitter reçoit une confirmation instantanée.`,
    bgColor: 'bg-[#FFBF43]', // jaune
  },
];

</script>
<template>
    <section class="w-full bg-white py-12">
      <div class="mx-auto max-w-5xl px-4">
        <h2 class="mb-2 text-center text-2xl font-bold md:text-3xl">Comment ça marche ?</h2>
        <p class="mb-10 text-center text-gray-600">
          En quelques étapes simples, trouvez la babysitter idéale pour vos enfants
        </p>
        <div class="grid grid-cols-1 gap-10 md:grid-cols-4">
          <div
            v-for="step in steps"
            :key="step.title"
            class="flex flex-col items-center text-center"
          >
            <div
              :class="['mb-4 flex h-20 w-20 items-center justify-center rounded-full shadow-md', step.bgColor]"
            >
              <component :is="step.icon" class="h-8 w-8 text-white" />
            </div>
            <h3 class="mb-2 text-base font-semibold text-gray-900">
              {{ step.title }}
            </h3>
            <p class="text-sm text-gray-600">{{ step.desc }}</p>
          </div>
        </div>
      </div>
    </section>
  </template>
  