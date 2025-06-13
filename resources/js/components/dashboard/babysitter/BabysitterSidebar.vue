<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BarChart2, Briefcase, Calendar, CreditCard, Home, LogOut, MessageCircle, Settings, User } from 'lucide-vue-next';

const links = [
    { icon: Home, label: 'Tableau de bord', href: '/dashboard' },
    { icon: Briefcase, label: 'Offres disponibles', href: '/offres' },
    { icon: Briefcase, label: 'Mes candidatures', href: '/candidatures' },
    { icon: Calendar, label: 'Réservations', href: '/reservations' },
    { icon: MessageCircle, label: 'Messagerie', href: '/messagerie' },
    { icon: CreditCard, label: 'Paiements', href: '/babysitter/paiements' },
    { icon: User, label: 'Mon profil', href: '/profil' },
    { icon: BarChart2, label: 'Statistiques', href: '/stats' },
    { icon: Settings, label: 'Paramètres', href: '/parametres' },
];
</script>

<template>
    <aside class="flex min-h-screen w-64 flex-col border-r bg-white">
        <div class="text-primary flex items-center gap-2 p-6 text-xl font-bold"><span class="text-2xl">❤️</span> Miminoo</div>
        <nav class="flex flex-1 flex-col gap-1 px-4">
            <Link
                v-for="link in links"
                :key="link.label"
                :href="link.href"
                class="hover:bg-secondary flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700"
            >
                <component :is="link.icon" class="h-5 w-5" /> {{ link.label }}
            </Link>
        </nav>
        <div class="border-t p-4">
            <Link href="/logout" method="post" as="button" class="hover:text-primary flex items-center gap-2 text-gray-500">
                <LogOut class="h-5 w-5" /> Déconnexion
            </Link>
        </div>
    </aside>
</template>
