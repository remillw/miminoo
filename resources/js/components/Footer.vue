<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail } from 'lucide-vue-next';
</script>

<template>
    <footer class="w-full bg-white pt-12 pb-6 text-sm">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-10 px-4 text-center md:grid-cols-4 md:items-start md:text-left">
            <!-- Colonne 1 : Logo et contact -->
            <div class="flex flex-col items-center text-center md:items-start md:text-left">
                <Link href="/" class="mb-3 inline-block">
                    <img src="/storage/trouve-ta-babysitter-logo.svg" alt="Trouve ta Babysitter logo" class="h-18 w-auto" />
                </Link>

                <p class="mb-4 text-base text-gray-900">
                    Trouvez une babysitter de confiance<br />
                    en quelques minutes pour vos enfants
                </p>
            </div>

            <!-- Colonne 2 : Trouve ta Babysitter -->
            <div>
                <h4 class="mb-3 font-bold text-black">Babysitting à domicile</h4>
                <ul class="space-y-2">
                    <li><Link href="/a-propos" class="hover:text-primary">À propos</Link></li>
                    <li><Link href="/comment-ca-marche" class="hover:text-primary">Comment ça marche</Link></li>
                    <li><Link href="/creer-une-annonce" class="hover:text-primary">Créer une annonce</Link></li>
                    <li><Link href="/devenir-babysitter" class="hover:text-primary">Devenir babysitter</Link></li>
                </ul>
            </div>

            <!-- Colonne 3 : Informations -->
            <div>
                <h4 class="mb-3 font-bold text-black">Informations</h4>
                <ul class="space-y-2">
                    <li><Link href="/faq" class="hover:text-primary">FAQ</Link></li>
                    <li><Link href="/blog" class="hover:text-primary">Blog</Link></li>
                    <li><Link href="/mentions-legales" class="hover:text-primary">Mentions légales</Link></li>
                    <li><Link href="/conditions-generales-d-utilisation" class="hover:text-primary">Conditions d'utilisation</Link></li>
                    <li><Link href="/politique-de-confidentialite" class="hover:text-primary">Politique de confidentialité</Link></li>
                </ul>
            </div>

            <!-- Colonne 4 : Contact -->
            <div class="flex flex-col items-center text-center md:items-start md:text-left">
                <ul class="mb-7 space-y-2">
                    <li><Link href="/connexion" class="hover:text-primary">Connexion</Link></li>
                    <li><Link href="/inscription" class="hover:text-primary">Inscription</Link></li>
                    <li><Link href="/contact" class="hover:text-primary">Contact</Link></li>
                </ul>
                <h4 class="mb-3 font-bold text-black">Contact</h4>
                <a
                    href="mailto:hello@trouvetababysitter.fr"
                    class="mt-3 flex items-center gap-2 text-base font-semibold text-gray-900 hover:underline"
                >
                    <Mail class="text-primary h-5 w-5" />
                    hello@trouvetababysitter.fr
                </a>
            </div>
        </div>

        <div class="mt-10 border-t pt-4 text-center text-xs text-gray-400">© 2025 Trouve ta Babysitter. Tous droits réservés.</div>
    </footer>
</template>
