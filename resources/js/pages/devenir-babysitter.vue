<script setup lang="ts">
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Icon } from '@iconify/vue';
import { ref } from 'vue';

const faqs = [
  {
    question: "Faut-il une expérience préalable pour devenir babysitter ?",
    answer: "Non, il n'est pas obligatoire d'avoir déjà gardé des enfants, mais toute expérience est un plus pour rassurer les parents.",
  },
  {
    question: "Comment suis-je payé(e) ?",
    answer: "Au moment de la réservation, les parents vous versent un acompte correspondant à 1h de garde. Vous recevez cet argent directement sur votre compte, que vous pouvez retirer à tout moment. Le reste du paiement est effectué directement sur place par les parents à la fin du babysitting.",
  },
  {
    question: "Dois-je déclarer mes revenus ?",
    answer: "Oui, comme toute activité rémunérée, il est nécessaire de déclarer vos revenus selon la législation en vigueur.",
  },
  {
    question: "Comment fonctionne la vérification du profil ?",
    answer: "Nous vérifions votre identité sous 24 à 48h et vos références pour garantir la sécurité des familles et des babysitters.",
  },
];
const openFaq = ref<number|null>(null);
function toggleFaq(index: number) {
  openFaq.value = openFaq.value === index ? null : index;
}
</script>

<template>
  <GlobalLayout>
    <div class="bg-secondary min-h-screen pb-16">
      <!-- HERO -->
      <div class="max-w-7xl mx-auto flex flex-col lg:flex-row items-center justify-between gap-8 pt-12 pb-8 px-4">
        <div class="flex-1">
          <span class="inline-flex items-center px-4 py-1 mb-4 rounded-full bg-primary-opacity text-primary font-semibold text-sm">
            <span class="mr-2">👜</span> Opportunité d'emploi flexible
          </span>
          <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-4 leading-tight">
            Devenez babysitter et <span class="text-primary">gagnez de l'argent</span><br />
            en gardant des enfants
          </h1>
          <p class="text-lg text-gray-600 mb-8 max-w-xl">
            Rejoignez des milliers de babysitters qui nous font confiance pour développer leur activité. Flexibilité, sécurité et revenus attractifs vous attendent.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 mb-6">
            <a href="/inscription" class="bg-primary hover:bg-primary/90 text-white font-semibold rounded-xl px-7 py-3 text-lg shadow transition">
              Commencer maintenant
            </a>
            <a href="/comment-ca-marche" class="inline-block border-primary text-primary hover:bg-primary order-2 rounded-lg border px-8 py-3 text-lg font-medium transition-colors hover:text-white">
              Comment ça marche
            </a>
          </div>
          <div class="flex flex-wrap gap-6 mt-2">
            <div class="flex items-center gap-2 text-green-600 text-sm"><span>✔️</span> Inscription gratuite</div>
            <div class="flex items-center gap-2 text-green-600 text-sm"><span>✔️</span> Paiement garanti</div>
            <div class="flex items-center gap-2 text-green-600 text-sm"><span>✔️</span> Support 24/7</div>
          </div>
        </div>
        <!-- Témoignage -->
        <div class="flex-1 max-w-md w-full bg-white rounded-3xl shadow-2xl p-8 mt-10 lg:mt-0">
          <div class="flex items-center mb-6">
            <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Camille" class="w-12 h-12 rounded-full object-cover mr-3" />
            <div>
              <div class="font-bold text-lg text-gray-900">Camille, 24 ans</div>
              <div class="flex items-center text-sm text-gray-500">
                <span class="text-yellow-400 mr-1">★</span> 4.9 (56 avis)
              </div>
            </div>
          </div>
          <div class="space-y-3 mb-4">
            <div class="flex justify-between text-gray-600 font-medium bg-gray-50 rounded-lg px-4 py-2">
              <span>Revenus ce mois</span><span class="text-primary font-bold">680€</span>
            </div>
            <div class="flex justify-between text-gray-600 font-medium bg-gray-50 rounded-lg px-4 py-2">
              <span>Heures travaillées</span><span class="font-bold">42h</span>
            </div>
            <div class="flex justify-between text-gray-600 font-medium bg-gray-50 rounded-lg px-4 py-2">
              <span>Familles fidèles</span><span class="font-bold">8</span>
            </div>
          </div>
          <div class="bg-secondary text-primary text-sm rounded-xl px-4 py-3 italic">
            "Trouve ta babysitter m'a permis de concilier mes études avec un travail flexible et bien rémunéré. Je recommande !"
          </div>
        </div>
      </div>

      <!-- Pourquoi choisir -->
      <div class="bg-white py-16">
        <div class="max-w-5xl mx-auto px-4">
          <h2 class="text-3xl md:text-4xl font-extrabold text-center mb-4">Pourquoi choisir Trouve ta babysitter ?</h2>
          <p class="text-center text-gray-500 mb-12">Découvrez tous les avantages de rejoindre notre communauté de babysitters</p>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-4 mb-3">
                <Icon icon="mdi:clock-outline" class="text-2xl text-primary" />
              </div>
              <div class="font-bold text-lg mb-1">Flexibilité totale</div>
              <div class="text-gray-500 text-sm">Choisissez vos horaires et votre zone de garde selon vos disponibilités</div>
            </div>
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-4 mb-3">
                <Icon icon="mdi:currency-eur" class="text-2xl text-primary" />
              </div>
              <div class="font-bold text-lg mb-1">Revenus attractifs</div>
              <div class="text-gray-500 text-sm">Vous choisissez votre taux horaire et pouvez-même faire une contre offre aux parents</div>
            </div>
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-4 mb-3">
                <Icon icon="mdi:shield-check" class="text-2xl text-primary" />
              </div>
              <div class="font-bold text-lg mb-1">Paiement sécurisé</div>
              <div class="text-gray-500 text-sm">Recevez votre paiement automatiquement après chaque garde sur votre compte personnel</div>
            </div>
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-4 mb-3">
                <Icon icon="mdi:star-outline" class="text-2xl text-primary" />
              </div>
              <div class="font-bold text-lg mb-1">Construisez votre réputation</div>
              <div class="text-gray-500 text-sm">Recevez des avis positifs et fidélisez vos familles préférées</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Comment ça marche -->
      <div class="bg-secondary py-16">
        <div class="max-w-5xl mx-auto px-4">
          <h2 class="text-3xl md:text-4xl font-extrabold text-center mb-4">Comment ça marche&nbsp;?</h2>
          <p class="text-center text-gray-500 mb-12">4 étapes simples pour commencer à gagner de l'argent</p>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-4 mb-3 text-primary font-bold text-lg">1</div>
              <div class="font-bold mb-1">Créez votre profil</div>
              <div class="text-gray-500 text-sm">Remplissez votre profil avec vos expériences et disponibilités</div>
            </div>
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-4 mb-3 text-primary font-bold text-lg">2</div>
              <div class="font-bold mb-1">Vérification</div>
              <div class="text-gray-500 text-sm">Nous vérifions votre identité et vos références pour rassurer les parents</div>
            </div>
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-4 mb-3 text-primary font-bold text-lg">3</div>
              <div class="font-bold mb-1">Recevez des offres</div>
              <div class="text-gray-500 text-sm">Recevez une notification dès qu'une famille recherche une babysitter près de chez vous.</div>
            </div>
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-4 mb-3 text-primary font-bold text-lg">4</div>
              <div class="font-bold mb-1">Commencez à gagner</div>
              <div class="text-gray-500 text-sm">Postulez aux annonces qui vous intéressent et commencez à gagner de l'argent.</div>
            </div>
          </div>
        </div>
      </div>

       <!-- S'inscrire -->

       <section class="bg-white py-16">
  <div class="max-w-4xl mx-auto px-4 text-center">
    <h3 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">Prêt(e) à commencer l’aventure ?</h3>
    <p class="text-gray-600 text-lg mb-8">
      Crée ton profil gratuitement, reçois des offres près de chez toi, et choisis les gardes qui te correspondent.
    </p>
    <a
      href="/inscription"
      class="inline-block bg-primary hover:bg-primary/90 text-white text-lg font-semibold px-8 py-3 rounded-xl shadow-md transition transform hover:scale-105"
    >
      Je m’inscris gratuitement
    </a>
  </div>
</section>


      <!-- FAQ Section -->
      <div class="max-w-3xl mx-auto mt-12 px-4">
        <h2 class="text-2xl md:text-3xl font-extrabold text-center mb-6">Questions fréquentes</h2>
        <div class="divide-y divide-gray-200 rounded-2xl bg-white shadow-lg">
          <div v-for="(faq, i) in faqs" :key="i" class="py-4 px-6 cursor-pointer select-none" @click="toggleFaq(i)">
            <div class="flex items-center justify-between">
              <span class="font-semibold text-gray-900">{{ faq.question }}</span>
              <Icon :icon="openFaq === i ? 'mdi:chevron-up' : 'mdi:chevron-down'" class="text-primary text-2xl transition-transform duration-200" />
            </div>
            <transition name="fade">
              <div v-show="openFaq === i" class="mt-3 text-gray-600 text-sm">
                {{ faq.answer }}
              </div>
            </transition>
          </div>
        </div>
      </div>
    </div>
  </GlobalLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>