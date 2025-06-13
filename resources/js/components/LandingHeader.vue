<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, Menu, MessageSquare } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const navLinks = [
    { name: 'Accueil', href: '/' },
    { name: 'Comment ça marche', href: '/comment-ca-marche' },
    { name: 'Devenir babysitter', href: '/devenir-babysitter' },
];

const authNavLinks = [
    { name: 'Accueil', href: '/' },
    { name: 'Comment ça marche', href: '/comment-ca-marche' },
    { name: 'Annonces', href: '/annonces' },
    { name: 'Messagerie', href: '/messagerie', icon: MessageSquare },
    { name: 'Notifications', href: '/notifications', icon: Bell },
];
</script>

<template>
    <header class="sticky top-0 z-30 w-full bg-white/90 shadow-sm">
        <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4">
            <!-- Logo -->
            <Link href="/" class="text-primary flex items-center gap-2 text-xl font-bold">
                <span class="text-2xl">❤️</span>
                Miminoo
            </Link>

            <!-- Desktop nav -->
            <div class="hidden items-center gap-6 md:flex">
                <template v-if="user">
                    <template v-for="link in authNavLinks" :key="link.name">
                        <Link
                            :href="link.href"
                            class="hover:text-primary flex items-center gap-2 text-base font-medium text-gray-700 transition-colors"
                        >
                            <component v-if="link.icon" :is="link.icon" class="h-5 w-5" />
                            {{ link.name }}
                        </Link>
                    </template>
                    <div class="ml-4 flex items-center gap-4">
                        <Link :href="`/babysitter/${user.firstname.toLowerCase()}-${user.lastname.toLowerCase()}`" class="flex items-center gap-2">
                            <img
                                :src="user.avatar || '/storage/default-avatar.png'"
                                :alt="`${user.firstname} ${user.lastname}`"
                                class="h-8 w-8 rounded-full object-cover"
                            />
                            <span class="text-sm font-medium text-gray-700">{{ user.firstname }}</span>
                        </Link>
                    </div>
                </template>
                <template v-else>
                    <template v-for="link in navLinks" :key="link.name">
                        <Link :href="link.href" class="hover:text-primary text-base font-medium text-gray-700 transition-colors">
                            {{ link.name }}
                        </Link>
                    </template>
                    <Link href="/login" class="hover:text-primary text-base font-medium text-gray-700 transition-colors"> Connexion </Link>
                    <Link href="/register" class="hover:text-primary text-base font-medium text-gray-700 transition-colors"> Inscription </Link>
                </template>
                <Button as-child class="bg-primary hover:bg-primary ml-2 font-semibold text-white">
                    <Link href="/creer-annonce">Créer une annonce</Link>
                </Button>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center md:hidden">
                <Sheet>
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon">
                            <Menu class="text-primary h-6 w-6" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-64 p-0">
                        <SheetHeader class="border-b p-4">
                            <SheetTitle>
                                <span class="text-primary flex items-center gap-2 text-xl font-bold"> <span class="text-2xl">❤️</span> Miminoo </span>
                            </SheetTitle>
                        </SheetHeader>
                        <div class="flex flex-col gap-2 p-4">
                            <template v-if="user">
                                <template v-for="link in authNavLinks" :key="link.name">
                                    <Link
                                        :href="link.href"
                                        class="hover:text-primary flex items-center gap-2 py-2 text-base font-medium text-gray-700 transition-colors"
                                    >
                                        <component v-if="link.icon" :is="link.icon" class="h-5 w-5" />
                                        {{ link.name }}
                                    </Link>
                                </template>
                                <Link
                                    :href="`/babysitter/${user.firstname.toLowerCase()}-${user.lastname.toLowerCase()}`"
                                    class="flex items-center gap-2 py-2"
                                >
                                    <img
                                        :src="user.avatar || '/storage/default-avatar.png'"
                                        :alt="`${user.firstname} ${user.lastname}`"
                                        class="h-8 w-8 rounded-full object-cover"
                                    />
                                    <span class="text-sm font-medium text-gray-700">{{ user.firstname }}</span>
                                </Link>
                            </template>
                            <template v-else>
                                <template v-for="link in navLinks" :key="link.name">
                                    <Link :href="link.href" class="hover:text-primary py-2 text-base font-medium text-gray-700 transition-colors">
                                        {{ link.name }}
                                    </Link>
                                </template>
                                <Link href="/login" class="hover:text-primary py-2 text-base font-medium text-gray-700 transition-colors">
                                    Connexion
                                </Link>
                                <Link href="/register" class="hover:text-primary py-2 text-base font-medium text-gray-700 transition-colors">
                                    Inscription
                                </Link>
                            </template>
                            <Button as-child class="bg-primary hover:bg-primary mt-4 font-semibold text-white">
                                <Link href="/creer-annonce">Créer une annonce</Link>
                            </Button>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </nav>
    </header>
</template>
