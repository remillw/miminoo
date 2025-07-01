<script setup lang="ts">
import { Mail } from 'lucide-vue-next'
import { Link } from '@inertiajs/vue3'
</script>

<template>
  <footer class="w-full bg-white pt-12 pb-6 text-sm">
    <div class="mx-auto max-w-7xl px-4 grid grid-cols-1 gap-10 md:grid-cols-4 items-center md:items-start text-center md:text-left">
      <!-- Colonne 1 : Logo et contact -->
<div class="flex flex-col items-center md:items-start text-center md:text-left">
  <Link href="/" class="inline-block mb-3">
  <img
    src="/storage/trouve-ta-babysitter-logo.svg"
    alt="Trouve ta Babysitter logo"
    class="h-18 w-auto"
  />
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
          <li><Link href="/creer-annonce" class="hover:text-primary">Créer une annonce</Link></li>
          <li><Link href="/devenir-babysitter" class="hover:text-primary">Devenir babysitter</Link></li>
        </ul>
      </div>

      <!-- Colonne 3 : Informations -->
      <div>
        <h4 class="mb-3 font-bold text-black">Informations</h4>
        <ul class="space-y-2">
          <li><Link href="/faq" class="hover:text-primary">FAQ</Link></li>
          <li><Link href="/blog" class="hover:text-primary">Blog</Link></li>
          <li><Link href="/contact" class="hover:text-primary">Contact</Link></li>
          <li><Link href="/conditions" class="hover:text-primary">Conditions d'utilisation</Link></li>
          <li><Link href="/confidentialite" class="hover:text-primary">Politique de confidentialité</Link></li>
        </ul>
      </div>

      <!-- Colonne 4 : Contact -->
      <div class="flex flex-col items-center md:items-start text-center md:text-left">
  <ul class="space-y-2 mb-7">
    <li><Link href="/login" class="hover:text-primary">Connexion</Link></li>
    <li><Link href="/register" class="hover:text-primary">Inscription</Link></li>
  </ul>
  <h4 class="mb-3 font-bold text-black">Contact</h4>
  <a
    href="mailto:hello@trouvetababysitter.fr"
    class="flex items-center gap-2 mt-3 text-base font-semibold text-gray-900 hover:underline"
  >
    <Mail class="h-5 w-5 text-primary" />
    hello@trouvetababysitter.fr
  </a>
</div>
    </div>

    <div class="mt-10 border-t pt-4 text-center text-xs text-gray-400">
      © 2025 Trouve ta Babysitter. Tous droits réservés.
    </div>
  </footer>
</template>
