<script setup lang="ts">
import { Calendar, MessageCircle, ShieldCheck, Star } from 'lucide-vue-next';

const items = [
  {
    icon: ShieldCheck,
    title: 'Babysitters vérifiées',
    desc: `Toutes nos babysitters sont soigneusement vérifiées manuellement par notre équipe avant d'apparaître sur la plateforme.`,
  },
  {
    icon: Calendar,
    title: 'Disponibilité immédiate',
    desc: `Trouvez une babysitter disponible aujourd'hui, même en dernière minute. Réservez en quelques clics et gérez vos réservations depuis votre espace.`,
  },
  {
    icon: Star,
    title: 'Avis 100% authentiques',
    desc: `Les évaluations sont laissées uniquement par des parents ayant réellement effectué une réservation via la plateforme.`,
  },
  {
    icon: MessageCircle,
    title: 'Communication sécurisée',
    desc: `Échangez directement et en toute sécurité avec les babysitters via notre messagerie.`,
  },
];

</script>
<template>
    <section class="w-full bg-secondary py-8 sm:py-10 md:py-12">
      <div class="mx-auto max-w-6xl px-4">
        <h2 class="mb-2 text-center text-xl font-bold sm:text-2xl md:text-3xl">
          Votre tranquillité d'esprit est notre priorité
        </h2>
        <p class="mb-6 text-center text-sm text-gray-600 sm:text-base sm:mb-8 md:mb-10">
          Nous réinventons la garde d'enfants en proposant un service simple, sûr et adapté aux besoins des familles modernes.
        </p>
  
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
          <div
            v-for="item in items"
            :key="item.title"
            class="flex flex-col rounded-2xl bg-white p-4 shadow-md text-left sm:p-6"
          >
            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-secondary text-primary sm:mb-4 sm:h-12 sm:w-12">
              <component :is="item.icon" class="h-5 w-5 sm:h-6 sm:w-6" />
            </div>
            <h3 class="mb-2 text-sm font-semibold text-gray-900 sm:text-base">
              {{ item.title }}
            </h3>
            <p class="text-xs text-gray-700 sm:text-sm">
              {{ item.desc }}
            </p>
          </div>
        </div>
      </div>
    </section>
  </template>
  
