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
    desc: `Les évaluations sont laissées uniquement par des parents ayant réellement effectué une réservation via Miminoo.`,
  },
  {
    icon: MessageCircle,
    title: 'Communication sécurisée',
    desc: `Échangez directement et en toute sécurité avec les babysitters via notre messagerie.`,
  },
];

</script>
<template>
    <section class="w-full bg-secondary py-12">
      <div class="mx-auto max-w-6xl px-4">
        <h2 class="mb-2 text-center text-2xl font-bold md:text-3xl">
          Votre tranquillité d'esprit est notre priorité
        </h2>
        <p class="mb-10 text-center text-gray-600">
          Nous réinventons la garde d'enfants en proposant un service simple, sûr et adapté aux besoins des familles modernes.
        </p>
  
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
          <div
            v-for="item in items"
            :key="item.title"
            class="flex flex-col rounded-2xl bg-white p-6 shadow-md text-left"
          >
            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-secondary text-primary">
              <component :is="item.icon" class="h-6 w-6" />
            </div>
            <h3 class="mb-2 text-base font-semibold text-gray-900">
              {{ item.title }}
            </h3>
            <p class="text-sm text-gray-700">
              {{ item.desc }}
            </p>
          </div>
        </div>
      </div>
    </section>
  </template>
  
