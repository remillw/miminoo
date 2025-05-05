<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Calendar, CreditCard, Home, LogOut, MessageCircle, PlusCircle, Settings, User } from 'lucide-vue-next';

const links = [
    { icon: Home, label: 'Tableau de bord', href: '/dashboard' },
    { icon: PlusCircle, label: 'Créer une annonce', href: '/annonces/create' },
    { icon: Calendar, label: 'Mes annonces', href: '/annonces' },
    { icon: Calendar, label: 'Mes réservations', href: '/reservations' },
    { icon: MessageCircle, label: 'Messagerie', href: '/messages' },
    { icon: User, label: 'Mon profil', href: '/profil' },
    { icon: CreditCard, label: 'Paiement | Factures', href: '/paiement' },
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
                class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-orange-50"
            >
                <component :is="link.icon" class="h-5 w-5" /> {{ link.label }}
            </Link>
        </nav>
        <div class="border-t p-4">
            <Link href="/logout" class="hover:text-primary flex items-center gap-2 text-gray-500"> <LogOut class="h-5 w-5" /> Déconnexion </Link>
        </div>
    </aside>
</template>
