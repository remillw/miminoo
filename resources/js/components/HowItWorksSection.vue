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
    desc: `Réglez directement le nombre d'heures souhaitées via la plateforme. Le paiement est sécurisé, et la babysitter reçoit une confirmation instantanée.`,
    bgColor: 'bg-[#FFBF43]', // jaune
  },
];

</script>
<template>
    <section class="w-full bg-white py-8 sm:py-10 md:py-12">
      <div class="mx-auto max-w-5xl px-4">
        <h2 class="mb-2 text-center text-xl font-bold sm:text-2xl md:text-3xl">Comment ça marche ?</h2>
        <p class="mb-6 text-center text-sm text-gray-600 sm:text-base sm:mb-8 md:mb-10">
          En quelques étapes simples, trouvez la babysitter idéale pour vos enfants
        </p>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:gap-8 md:grid-cols-4 md:gap-10">
          <div
            v-for="step in steps"
            :key="step.title"
            class="flex flex-col items-center text-center"
          >
            <div
              :class="['mb-3 flex h-16 w-16 items-center justify-center rounded-full shadow-md sm:mb-4 sm:h-20 sm:w-20', step.bgColor]"
            >
              <component :is="step.icon" class="h-6 w-6 text-white sm:h-8 sm:w-8" />
            </div>
            <h3 class="mb-2 text-sm font-semibold text-gray-900 sm:text-base">
              {{ step.title }}
            </h3>
            <p class="text-xs text-gray-600 sm:text-sm">{{ step.desc }}</p>
          </div>
        </div>
      </div>
    </section>
  </template>
  