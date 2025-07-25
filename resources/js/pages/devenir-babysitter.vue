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
    <div class="bg-secondary min-h-screen pb-8 sm:pb-12 md:pb-16">
      <!-- HERO -->
      <div class="max-w-7xl mx-auto flex flex-col lg:flex-row items-center justify-between gap-6 pt-8 pb-6 px-4 sm:gap-8 sm:pt-10 sm:pb-8 md:pt-12 md:pb-8">
        <div class="flex-1 text-center lg:text-left">
          <span class="inline-flex items-center px-3 py-1 mb-3 rounded-full bg-primary-opacity text-primary font-semibold text-xs sm:px-4 sm:mb-4 sm:text-sm">
            <span class="mr-2">👜</span> Opportunité d'emploi flexible
          </span>
          <h1 class="text-2xl font-extrabold text-gray-900 mb-3 leading-tight sm:text-3xl md:text-4xl lg:text-5xl sm:mb-4">
            Devenez babysitter et <span class="text-primary">gagnez de l'argent</span><br class="hidden sm:block" />
            <span class="sm:hidden"> </span>en gardant des enfants
          </h1>
          <p class="text-base text-gray-600 mb-6 max-w-xl mx-auto lg:mx-0 sm:text-lg sm:mb-8">
            Rejoignez des milliers de babysitters qui nous font confiance pour développer leur activité. Flexibilité, sécurité et revenus attractifs vous attendent.
          </p>
          <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:gap-4 sm:mb-6 lg:justify-start">
            <a href="/inscription" class="bg-primary hover:bg-primary/90 text-white font-semibold rounded-xl px-6 py-2.5 text-base shadow transition sm:px-7 sm:py-3 sm:text-lg">
              Commencer maintenant
            </a>
            <a href="/comment-ca-marche" class="inline-block border-primary text-primary hover:bg-primary hover:text-white rounded-lg border px-6 py-2.5 text-base font-medium transition-colors sm:px-8 sm:py-3 sm:text-lg">
              Comment ça marche
            </a>
          </div>
          <div class="flex flex-col gap-3 mt-2 sm:flex-row sm:flex-wrap sm:gap-6 lg:justify-start">
            <div class="flex items-center justify-center gap-2 text-green-600 text-xs sm:justify-start sm:text-sm"><span>✔️</span> Inscription gratuite</div>
            <div class="flex items-center justify-center gap-2 text-green-600 text-xs sm:justify-start sm:text-sm"><span>✔️</span> Paiement garanti</div>
            <div class="flex items-center justify-center gap-2 text-green-600 text-xs sm:justify-start sm:text-sm"><span>✔️</span> Support 24/7</div>
          </div>
        </div>
        <!-- Témoignage -->
        <div class="flex-1 max-w-xs w-full bg-white rounded-2xl shadow-2xl p-6 mt-6 sm:max-w-sm sm:rounded-3xl sm:p-8 lg:mt-0 lg:max-w-md">
          <div class="flex items-center mb-4 sm:mb-6">
            <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Camille" class="w-10 h-10 rounded-full object-cover mr-3 sm:w-12 sm:h-12" />
            <div>
              <div class="font-bold text-base text-gray-900 sm:text-lg">Camille, 24 ans</div>
              <div class="flex items-center text-xs text-gray-500 sm:text-sm">
                <span class="text-yellow-400 mr-1">★</span> 4.9 (56 avis)
              </div>
            </div>
          </div>
          <div class="space-y-2.5 mb-3 sm:space-y-3 sm:mb-4">
            <div class="flex justify-between text-gray-600 font-medium bg-gray-50 rounded-lg px-3 py-2 sm:px-4">
              <span class="text-sm sm:text-base">Revenus ce mois</span><span class="text-primary font-bold text-sm sm:text-base">680€</span>
            </div>
            <div class="flex justify-between text-gray-600 font-medium bg-gray-50 rounded-lg px-3 py-2 sm:px-4">
              <span class="text-sm sm:text-base">Heures travaillées</span><span class="font-bold text-sm sm:text-base">42h</span>
            </div>
            <div class="flex justify-between text-gray-600 font-medium bg-gray-50 rounded-lg px-3 py-2 sm:px-4">
              <span class="text-sm sm:text-base">Familles fidèles</span><span class="font-bold text-sm sm:text-base">8</span>
            </div>
          </div>
          <div class="bg-secondary text-primary text-xs rounded-xl px-3 py-2.5 italic sm:text-sm sm:px-4 sm:py-3">
            "Trouve ta babysitter m'a permis de concilier mes études avec un travail flexible et bien rémunéré. Je recommande !"
          </div>
        </div>
      </div>

      <!-- Pourquoi choisir -->
      <div class="bg-white py-8 sm:py-12 md:py-16">
        <div class="max-w-5xl mx-auto px-4">
          <h2 class="text-2xl font-extrabold text-center mb-3 sm:text-3xl md:text-4xl sm:mb-4">Pourquoi choisir Trouve ta babysitter ?</h2>
          <p class="text-center text-gray-500 mb-8 text-sm sm:text-base sm:mb-12">Découvrez tous les avantages de rejoindre notre communauté de babysitters</p>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:gap-8 md:grid-cols-4">
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-3 mb-2.5 sm:p-4 sm:mb-3">
                <Icon icon="mdi:clock-outline" class="text-xl text-primary sm:text-2xl" />
              </div>
              <div class="font-bold text-base mb-1 sm:text-lg">Flexibilité totale</div>
              <div class="text-gray-500 text-xs sm:text-sm">Choisissez vos horaires et votre zone de garde selon vos disponibilités</div>
            </div>
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-3 mb-2.5 sm:p-4 sm:mb-3">
                <Icon icon="mdi:currency-eur" class="text-xl text-primary sm:text-2xl" />
              </div>
              <div class="font-bold text-base mb-1 sm:text-lg">Revenus attractifs</div>
              <div class="text-gray-500 text-xs sm:text-sm">Vous choisissez votre taux horaire et pouvez-même faire une contre offre aux parents</div>
            </div>
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-3 mb-2.5 sm:p-4 sm:mb-3">
                <Icon icon="mdi:shield-check" class="text-xl text-primary sm:text-2xl" />
              </div>
              <div class="font-bold text-base mb-1 sm:text-lg">Paiement sécurisé</div>
              <div class="text-gray-500 text-xs sm:text-sm">Recevez votre paiement automatiquement après chaque garde sur votre compte personnel</div>
            </div>
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-3 mb-2.5 sm:p-4 sm:mb-3">
                <Icon icon="mdi:star-outline" class="text-xl text-primary sm:text-2xl" />
              </div>
              <div class="font-bold text-base mb-1 sm:text-lg">Construisez votre réputation</div>
              <div class="text-gray-500 text-xs sm:text-sm">Recevez des avis positifs et fidélisez vos familles préférées</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Comment ça marche -->
      <div class="bg-secondary py-8 sm:py-12 md:py-16">
        <div class="max-w-5xl mx-auto px-4">
          <h2 class="text-2xl font-extrabold text-center mb-3 sm:text-3xl md:text-4xl sm:mb-4">Comment ça marche&nbsp;?</h2>
          <p class="text-center text-gray-500 mb-8 text-sm sm:text-base sm:mb-12">4 étapes simples pour commencer à gagner de l'argent</p>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:gap-8 md:grid-cols-4">
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-3 mb-2.5 text-primary font-bold text-base sm:p-4 sm:mb-3 sm:text-lg">1</div>
              <div class="font-bold mb-1 text-sm sm:text-base">Créez votre profil</div>
              <div class="text-gray-500 text-xs sm:text-sm">Remplissez votre profil avec vos expériences et disponibilités</div>
            </div>
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-3 mb-2.5 text-primary font-bold text-base sm:p-4 sm:mb-3 sm:text-lg">2</div>
              <div class="font-bold mb-1 text-sm sm:text-base">Vérification</div>
              <div class="text-gray-500 text-xs sm:text-sm">Nous vérifions votre identité et vos références pour rassurer les parents</div>
            </div>
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-3 mb-2.5 text-primary font-bold text-base sm:p-4 sm:mb-3 sm:text-lg">3</div>
              <div class="font-bold mb-1 text-sm sm:text-base">Recevez des offres</div>
              <div class="text-gray-500 text-xs sm:text-sm">Recevez une notification dès qu'une famille recherche une babysitter près de chez vous.</div>
            </div>
            <div class="flex flex-col items-center text-center">
              <div class="bg-primary-opacity rounded-full p-3 mb-2.5 text-primary font-bold text-base sm:p-4 sm:mb-3 sm:text-lg">4</div>
              <div class="font-bold mb-1 text-sm sm:text-base">Commencez à gagner</div>
              <div class="text-gray-500 text-xs sm:text-sm">Postulez aux annonces qui vous intéressent et commencez à gagner de l'argent.</div>
            </div>
          </div>
        </div>
      </div>

       <!-- S'inscrire -->
       <section class="bg-white py-8 sm:py-12 md:py-16">
  <div class="max-w-4xl mx-auto px-4 text-center">
    <h3 class="text-xl font-bold text-gray-900 mb-3 sm:text-2xl md:text-3xl sm:mb-4">Prêt(e) à commencer l'aventure ?</h3>
    <p class="text-gray-600 text-base mb-6 sm:text-lg sm:mb-8">
      Crée ton profil gratuitement, reçois des offres près de chez toi, et choisis les gardes qui te correspondent.
    </p>
    <a
      href="/inscription"
      class="inline-block bg-primary hover:bg-primary/90 text-white text-base font-semibold px-6 py-2.5 rounded-xl shadow-md transition transform hover:scale-105 sm:text-lg sm:px-8 sm:py-3"
    >
      Je m'inscris gratuitement
    </a>
  </div>
</section>


      <!-- FAQ Section -->
      <div class="max-w-3xl mx-auto mt-8 px-4 sm:mt-12">
        <h2 class="text-xl font-extrabold text-center mb-4 sm:text-2xl md:text-3xl sm:mb-6">Questions fréquentes</h2>
        <div class="divide-y divide-gray-200 rounded-xl bg-white shadow-lg sm:rounded-2xl">
          <div v-for="(faq, i) in faqs" :key="i" class="py-3 px-4 cursor-pointer select-none sm:py-4 sm:px-6" @click="toggleFaq(i)">
            <div class="flex items-center justify-between">
              <span class="font-semibold text-gray-900 text-sm sm:text-base">{{ faq.question }}</span>
              <Icon :icon="openFaq === i ? 'mdi:chevron-up' : 'mdi:chevron-down'" class="text-primary text-xl transition-transform duration-200 sm:text-2xl" />
            </div>
            <transition name="fade">
              <div v-show="openFaq === i" class="mt-2.5 text-gray-600 text-xs sm:mt-3 sm:text-sm">
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