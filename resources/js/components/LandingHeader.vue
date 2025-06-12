<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Link } from '@inertiajs/vue3';
import { Menu } from 'lucide-vue-next';

const navLinks = [
    { name: 'Accueil', href: '/' },
    { name: 'Comment ça marche', href: '/comment-ca-marche' },
    { name: 'Devenir babysitter', href: '/devenir-babysitter' },
    { name: 'Connexion', href: '/login' },
    { name: 'Inscription', href: '/register' },
];
</script>

<template>
    <header class="sticky top-0 z-30 w-full bg-white/90 shadow-sm">
        <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4">
            <!-- Logo -->
            <Link href="/" class="flex items-center gap-2 text-xl font-bold text-primary">
                <span class="text-2xl">❤️</span>
                Miminoo
            </Link>
            <!-- Desktop nav -->
            <div class="hidden items-center gap-6 md:flex">
                <template v-for="link in navLinks.slice(0, 5)" :key="link.name">
                    <Link :href="link.href" class="text-base font-medium text-gray-700 transition-colors hover:text-primary">
                        {{ link.name }}
                    </Link>
                </template>
                <Button as-child class="ml-2 bg-primary font-semibold text-white hover:bg-primary">
                    <Link href="/creer-annonce">Créer une annonce</Link>
                </Button>
            </div>
            <!-- Mobile menu button -->
            <div class="flex items-center md:hidden">
                <Sheet>
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon">
                            <Menu class="h-6 w-6 text-primary" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-64 p-0">
                        <SheetHeader class="border-b p-4">
                            <SheetTitle>
                                <span class="flex items-center gap-2 text-xl font-bold text-primary">
                                    <span class="text-2xl">❤️</span> Miminoo
                                </span>
                            </SheetTitle>
                        </SheetHeader>
                        <div class="flex flex-col gap-2 p-4">
                            <template v-for="link in navLinks" :key="link.name">
                                <Link :href="link.href" class="py-2 text-base font-medium text-gray-700 transition-colors hover:text-primary">
                                    {{ link.name }}
                                </Link>
                            </template>
                            <Button as-child class="mt-4 bg-primary font-semibold text-white hover:bg-primary">
                                <Link href="/creer-annonce">Créer une annonce</Link>
                            </Button>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </nav>
    </header>
</template>
