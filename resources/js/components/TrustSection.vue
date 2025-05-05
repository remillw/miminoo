<script setup lang="ts">
import { Calendar, MessageCircle, ShieldCheck, Star } from 'lucide-vue-next';

const items = [
    {
        icon: ShieldCheck,
        title: 'Babysitters vérifiées',
        desc: `Toutes nos babysitters sont soigneusement vérifiées manuellement par notre équipe avant d'apparaître sur la plateforme.`,
        color: 'text-orange-400',
    },
    {
        icon: Calendar,
        title: 'Disponibilité immédiate',
        desc: `Trouvez une babysitter disponible aujourd'hui, même en dernière minute. Réservez en quelques clics et gérez vos réservations depuis votre espace.`,
        color: 'text-orange-300',
    },
    {
        icon: Star,
        title: 'Avis 100% authentiques',
        desc: `Les évaluations sont laissées uniquement par des parents ayant réellement effectué une réservation via Miminoo.`,
        color: 'text-orange-400',
    },
    {
        icon: MessageCircle,
        title: 'Communication sécurisée',
        desc: `Échangez directement et en toute sécurité avec les babysitters via notre messagerie.`,
        color: 'text-orange-300',
    },
];
</script>

<template>
    <section class="w-full bg-orange-50 py-12">
        <div class="mx-auto max-w-5xl px-4">
            <h2 class="mb-2 text-center text-2xl font-bold md:text-3xl">Votre tranquillité d'esprit est notre priorité</h2>
            <p class="mb-10 text-center text-gray-600">
                Nous réinventons la garde d'enfants en proposant un service simple, sûr et adapté aux besoins des familles modernes.
            </p>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                <div v-for="item in items" :key="item.title" class="flex flex-col items-center rounded-xl border bg-white p-6 text-center shadow-sm">
                    <div :class="['mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-orange-50', item.color]">
                        <component :is="item.icon" class="h-7 w-7" />
                    </div>
                    <h3 class="mb-2 text-base font-semibold">{{ item.title }}</h3>
                    <p class="text-sm text-gray-600">{{ item.desc }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
