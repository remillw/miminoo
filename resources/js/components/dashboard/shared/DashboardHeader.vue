<template>
    <header class="sticky top-0 z-30 w-full  bg-white shadow-sm">
        <nav class="mx-auto flex h-16 max-w-9xl items-center justify-between px-4">
            <!-- Logo -->
            <div class="flex items-center gap-2">
                <img src="/storage/logo_miminoo.png" alt="Miminoo" class="h-8 w-auto" />
            </div>

            <!-- Navigation principale -->
            <div class="hidden items-center gap-6 md:flex">
                <a href="/" class="hover:text-primary text-base font-medium text-gray-700">Accueil</a>
                <a href="/comment-ca-marche" class="hover:text-primary text-base font-medium text-gray-700">Comment ça marche ?</a>
                <a href="/babysitters" class="hover:text-primary text-base font-medium text-gray-700">Annonces en cours</a>
            </div>

            <!-- Actions à droite -->
            <div class="flex items-center gap-4">
                <!-- Notifications -->
                <button class="relative">
                    <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-orange-400 text-xs text-white">
                        1
                    </span>
                    <svg class="h-6 w-6 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                        />
                    </svg>
                </button>

                <!-- Avatar -->
                <button class="rounded-full">
                    <img src="/storage/babysitter-test.png" alt="Avatar utilisateur" class="h-10 w-10 rounded-full object-cover" />
                </button>

                <!-- Bouton "Créer une annonce" -->
                <a href="/annonces/create" class="bg-primary hidden rounded px-4 py-2 font-semibold text-white hover:bg-orange-500 md:inline-block">
                    Créer une annonce
                </a>
            </div>
        </nav>
    </header>
</template>
