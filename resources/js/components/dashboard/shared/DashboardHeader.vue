<template>
    <header class="sticky top-0 z-30 w-full bg-white shadow-sm">
        <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4">
            <!-- Logo -->
            <div class="text-primary flex items-center gap-2 text-xl font-bold"><span class="text-2xl">❤️</span> Miminoo</div>
            <!-- Navigation principale (exemple) -->
            <div class="hidden items-center gap-6 md:flex">
                <a href="/" class="hover:text-primary text-base font-medium text-gray-700">Accueil</a>
                <a href="/comment-ca-marche" class="hover:text-primary text-base font-medium text-gray-700">Comment ça marche</a>
                <a href="/babysitters" class="hover:text-primary text-base font-medium text-gray-700">Babysitters</a>
            </div>
            <!-- Actions à droite -->
            <div class="flex items-center gap-4">
                <button class="relative">
                    <!-- NotificationBell à intégrer ici -->
                    <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-orange-400 text-xs text-white"
                        >1</span
                    >
                    <svg class="h-6 w-6 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                        />
                    </svg>
                </button>
                <button class="rounded-full bg-orange-100 p-1">
                    <!-- UserAvatar à intégrer ici -->
                    <svg class="h-8 w-8 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M6 20c0-2.21 3.582-4 8-4s8 1.79 8 4v2H6v-2z" />
                    </svg>
                </button>
                <a href="/creer-annonce" class="bg-primary hidden rounded px-4 py-2 font-semibold text-white hover:bg-orange-500 md:inline-block"
                    >Créer une annonce</a
                >
            </div>
        </nav>
    </header>
</template>
