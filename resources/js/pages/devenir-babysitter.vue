<script setup lang="ts">
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Icon } from '@iconify/vue';
import { ref } from 'vue';

const faqs = [
    {
        question: 'Faut-il une expérience préalable pour devenir babysitter ?',
        answer: "Non, il n'est pas obligatoire d'avoir déjà gardé des enfants, mais toute expérience est un plus pour rassurer les parents.",
    },
    {
        question: 'Comment suis-je payé(e) ?',
        answer: 'Au moment de la réservation, les parents vous versent un acompte correspondant à 1h de garde. Vous recevez cet argent directement sur votre compte, que vous pouvez retirer à tout moment. Le reste du paiement est effectué directement sur place par les parents à la fin du babysitting.',
    },
    {
        question: 'Dois-je déclarer mes revenus ?',
        answer: 'Oui, comme toute activité rémunérée, il est nécessaire de déclarer vos revenus selon la législation en vigueur.',
    },
    {
        question: 'Comment fonctionne la vérification du profil ?',
        answer: 'Nous vérifions votre identité sous 24 à 48h et vos références pour garantir la sécurité des familles et des babysitters.',
    },
];
const openFaq = ref<number | null>(null);
function toggleFaq(index: number) {
    openFaq.value = openFaq.value === index ? null : index;
}
</script>

<template>
    <GlobalLayout>
        <div class="bg-secondary min-h-screen pb-8 sm:pb-12 md:pb-16">
            <!-- HERO -->
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-4 pt-8 pb-6 sm:gap-8 sm:pt-10 sm:pb-8 md:pt-12 md:pb-8 lg:flex-row"
            >
                <div class="flex-1 text-center lg:text-left">
                    <span
                        class="bg-primary-opacity text-primary mb-3 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold sm:mb-4 sm:px-4 sm:text-sm"
                    >
                        <span class="mr-2">👜</span> Opportunité d'emploi flexible
                    </span>
                    <h1 class="mb-3 text-2xl leading-tight font-extrabold text-gray-900 sm:mb-4 sm:text-3xl md:text-4xl lg:text-5xl">
                        Devenez babysitter et <span class="text-primary">gagnez de l'argent</span><br class="hidden sm:block" />
                        <span class="sm:hidden"> </span>en gardant des enfants
                    </h1>
                    <p class="mx-auto mb-6 max-w-xl text-base text-gray-600 sm:mb-8 sm:text-lg lg:mx-0">
                        Rejoignez des milliers de babysitters qui nous font confiance pour développer leur activité. Flexibilité, sécurité et revenus
                        attractifs vous attendent.
                    </p>
                    <div class="mb-4 flex flex-col gap-3 sm:mb-6 sm:flex-row sm:gap-4 lg:justify-start">
                        <a
                            href="/inscription"
                            class="bg-primary hover:bg-primary/90 rounded-xl px-6 py-2.5 text-base font-semibold text-white shadow transition sm:px-7 sm:py-3 sm:text-lg"
                        >
                            Commencer maintenant
                        </a>
                        <a
                            href="/comment-ca-marche"
                            class="border-primary text-primary hover:bg-primary inline-block rounded-lg border px-6 py-2.5 text-base font-medium transition-colors hover:text-white sm:px-8 sm:py-3 sm:text-lg"
                        >
                            Comment ça marche
                        </a>
                    </div>
                    <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:gap-6 lg:justify-start">
                        <div class="flex items-center justify-center gap-2 text-xs text-green-600 sm:justify-start sm:text-sm">
                            <span>✔️</span> Inscription gratuite
                        </div>
                        <div class="flex items-center justify-center gap-2 text-xs text-green-600 sm:justify-start sm:text-sm">
                            <span>✔️</span> Paiement garanti
                        </div>
                        <div class="flex items-center justify-center gap-2 text-xs text-green-600 sm:justify-start sm:text-sm">
                            <span>✔️</span> Support 24/7
                        </div>
                    </div>
                </div>
                <!-- Témoignage -->
                <div class="mt-6 w-full max-w-xs flex-1 rounded-2xl bg-white p-6 shadow-2xl sm:max-w-sm sm:rounded-3xl sm:p-8 lg:mt-0 lg:max-w-md">
                    <div class="mb-4 flex items-center sm:mb-6">
                        <img
                            src="https://randomuser.me/api/portraits/women/44.jpg"
                            alt="Camille"
                            class="mr-3 h-10 w-10 rounded-full object-cover sm:h-12 sm:w-12"
                        />
                        <div>
                            <div class="text-base font-bold text-gray-900 sm:text-lg">Camille, 24 ans</div>
                            <div class="flex items-center text-xs text-gray-500 sm:text-sm">
                                <span class="mr-1 text-yellow-400">★</span> 4.9 (56 avis)
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 space-y-2.5 sm:mb-4 sm:space-y-3">
                        <div class="flex justify-between rounded-lg bg-gray-50 px-3 py-2 font-medium text-gray-600 sm:px-4">
                            <span class="text-sm sm:text-base">Revenus ce mois</span
                            ><span class="text-primary text-sm font-bold sm:text-base">680€</span>
                        </div>
                        <div class="flex justify-between rounded-lg bg-gray-50 px-3 py-2 font-medium text-gray-600 sm:px-4">
                            <span class="text-sm sm:text-base">Heures travaillées</span><span class="text-sm font-bold sm:text-base">42h</span>
                        </div>
                        <div class="flex justify-between rounded-lg bg-gray-50 px-3 py-2 font-medium text-gray-600 sm:px-4">
                            <span class="text-sm sm:text-base">Familles fidèles</span><span class="text-sm font-bold sm:text-base">8</span>
                        </div>
                    </div>
                    <div class="bg-secondary text-primary rounded-xl px-3 py-2.5 text-xs italic sm:px-4 sm:py-3 sm:text-sm">
                        "Trouve ta babysitter m'a permis de concilier mes études avec un travail flexible et bien rémunéré. Je recommande !"
                    </div>
                </div>
            </div>

            <!-- Pourquoi choisir -->
            <div class="bg-white py-8 sm:py-12 md:py-16">
                <div class="mx-auto max-w-5xl px-4">
                    <h2 class="mb-3 text-center text-2xl font-extrabold sm:mb-4 sm:text-3xl md:text-4xl">Pourquoi choisir Trouve ta babysitter ?</h2>
                    <p class="mb-8 text-center text-sm text-gray-500 sm:mb-12 sm:text-base">
                        Découvrez tous les avantages de rejoindre notre communauté de babysitters
                    </p>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:gap-8 md:grid-cols-4">
                        <div class="flex flex-col items-center text-center">
                            <div class="bg-primary-opacity mb-2.5 rounded-full p-3 sm:mb-3 sm:p-4">
                                <Icon icon="mdi:clock-outline" class="text-primary text-xl sm:text-2xl" />
                            </div>
                            <div class="mb-1 text-base font-bold sm:text-lg">Flexibilité totale</div>
                            <div class="text-xs text-gray-500 sm:text-sm">
                                Choisissez vos horaires et votre zone de garde selon vos disponibilités
                            </div>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="bg-primary-opacity mb-2.5 rounded-full p-3 sm:mb-3 sm:p-4">
                                <Icon icon="mdi:currency-eur" class="text-primary text-xl sm:text-2xl" />
                            </div>
                            <div class="mb-1 text-base font-bold sm:text-lg">Revenus attractifs</div>
                            <div class="text-xs text-gray-500 sm:text-sm">
                                Vous choisissez votre taux horaire et pouvez-même faire une contre offre aux parents
                            </div>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="bg-primary-opacity mb-2.5 rounded-full p-3 sm:mb-3 sm:p-4">
                                <Icon icon="mdi:shield-check" class="text-primary text-xl sm:text-2xl" />
                            </div>
                            <div class="mb-1 text-base font-bold sm:text-lg">Paiement sécurisé</div>
                            <div class="text-xs text-gray-500 sm:text-sm">
                                Recevez votre paiement automatiquement après chaque garde sur votre compte personnel
                            </div>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="bg-primary-opacity mb-2.5 rounded-full p-3 sm:mb-3 sm:p-4">
                                <Icon icon="mdi:star-outline" class="text-primary text-xl sm:text-2xl" />
                            </div>
                            <div class="mb-1 text-base font-bold sm:text-lg">Construisez votre réputation</div>
                            <div class="text-xs text-gray-500 sm:text-sm">Recevez des avis positifs et fidélisez vos familles préférées</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comment ça marche -->
            <div class="bg-secondary py-8 sm:py-12 md:py-16">
                <div class="mx-auto max-w-5xl px-4">
                    <h2 class="mb-3 text-center text-2xl font-extrabold sm:mb-4 sm:text-3xl md:text-4xl">Comment ça marche&nbsp;?</h2>
                    <p class="mb-8 text-center text-sm text-gray-500 sm:mb-12 sm:text-base">4 étapes simples pour commencer à gagner de l'argent</p>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:gap-8 md:grid-cols-4">
                        <div class="flex flex-col items-center text-center">
                            <div class="bg-primary-opacity text-primary mb-2.5 rounded-full p-3 text-base font-bold sm:mb-3 sm:p-4 sm:text-lg">1</div>
                            <div class="mb-1 text-sm font-bold sm:text-base">Créez votre profil</div>
                            <div class="text-xs text-gray-500 sm:text-sm">Remplissez votre profil avec vos expériences et disponibilités</div>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="bg-primary-opacity text-primary mb-2.5 rounded-full p-3 text-base font-bold sm:mb-3 sm:p-4 sm:text-lg">2</div>
                            <div class="mb-1 text-sm font-bold sm:text-base">Vérification</div>
                            <div class="text-xs text-gray-500 sm:text-sm">
                                Nous vérifions votre identité et vos références pour rassurer les parents
                            </div>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="bg-primary-opacity text-primary mb-2.5 rounded-full p-3 text-base font-bold sm:mb-3 sm:p-4 sm:text-lg">3</div>
                            <div class="mb-1 text-sm font-bold sm:text-base">Recevez des offres</div>
                            <div class="text-xs text-gray-500 sm:text-sm">
                                Recevez une notification dès qu'une famille recherche une babysitter près de chez vous.
                            </div>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="bg-primary-opacity text-primary mb-2.5 rounded-full p-3 text-base font-bold sm:mb-3 sm:p-4 sm:text-lg">4</div>
                            <div class="mb-1 text-sm font-bold sm:text-base">Commencez à gagner</div>
                            <div class="text-xs text-gray-500 sm:text-sm">
                                Postulez aux annonces qui vous intéressent et commencez à gagner de l'argent.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- S'inscrire -->
            <section class="bg-white py-8 sm:py-12 md:py-16">
                <div class="mx-auto max-w-4xl px-4 text-center">
                    <h3 class="mb-3 text-xl font-bold text-gray-900 sm:mb-4 sm:text-2xl md:text-3xl">Prêt(e) à commencer l'aventure ?</h3>
                    <p class="mb-6 text-base text-gray-600 sm:mb-8 sm:text-lg">
                        Crée ton profil gratuitement, reçois des offres près de chez toi, et choisis les gardes qui te correspondent.
                    </p>
                    <a
                        href="/inscription"
                        class="bg-primary hover:bg-primary/90 inline-block transform rounded-xl px-6 py-2.5 text-base font-semibold text-white shadow-md transition hover:scale-105 sm:px-8 sm:py-3 sm:text-lg"
                    >
                        Je m'inscris gratuitement
                    </a>
                </div>
            </section>

            <!-- FAQ Section -->
            <div class="mx-auto mt-8 max-w-3xl px-4 sm:mt-12">
                <h2 class="mb-4 text-center text-xl font-extrabold sm:mb-6 sm:text-2xl md:text-3xl">Questions fréquentes</h2>
                <div class="divide-y divide-gray-200 rounded-xl bg-white shadow-lg sm:rounded-2xl">
                    <div v-for="(faq, i) in faqs" :key="i" class="cursor-pointer px-4 py-3 select-none sm:px-6 sm:py-4" @click="toggleFaq(i)">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-900 sm:text-base">{{ faq.question }}</span>
                            <Icon
                                :icon="openFaq === i ? 'mdi:chevron-up' : 'mdi:chevron-down'"
                                class="text-primary text-xl transition-transform duration-200 sm:text-2xl"
                            />
                        </div>
                        <transition name="fade">
                            <div v-show="openFaq === i" class="mt-2.5 text-xs text-gray-600 sm:mt-3 sm:text-sm">
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
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
