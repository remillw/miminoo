<script setup lang="ts">
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { computed, ref } from 'vue';

const categories = [
    { name: 'Toutes les questions', key: 'all' },
    { name: 'Service & Fonctionnement', key: 'service' },
    { name: 'Babysitters', key: 'babysitters' },
    { name: 'Parents', key: 'parents' },
    { name: 'Paiement & Tarifs', key: 'tarifs' },
    { name: 'Problèmes techniques', key: 'problemes' },
    { name: 'Sécurité & Fiabilité', key: 'securite' },
];

const faqs = [
    {
        question: 'Comment fonctionne la plateforme ?',
        answer: 'Notre plateforme met en relation des parents et des babysitters de confiance, vérifiés et notés par la communauté.',
        category: 'service',
    },
    {
        question: 'Comment réserver une babysitter ?',
        answer: 'Vous postez votre annonce en précisant vos besoins (date, horaires, localisation, etc.). Les babysitters disponibles et proches de chez vous peuvent ensuite y postuler. Vous pouvez alors consulter leur profil, échanger avec elles via la messagerie sécurisée, et choisir celle qui vous convient le mieux.',
        category: 'parents',
    },
    {
        question: 'Puis-je annuler une réservation ?',
        answer: 'Oui, vous pouvez annuler une réservation depuis votre espace personnel. Des frais peuvent s’appliquer selon le délai d’annulation.',
        category: 'parents',
    },
    {
        question: 'Comment laisser un avis après une garde ?',
        answer: 'Après chaque prestation, vous recevez une notification pour évaluer la babysitter et laisser un commentaire visible par la communauté.',
        category: 'parents',
    },
    {
        question: 'Puis-je discuter avec la babysitter avant de réserver ?',
        answer: 'Oui, vous pouvez échanger via notre messagerie sécurisée avant de valider votre réservation.',
        category: 'parents',
    },
    {
        question: 'Que faire si aucune babysitter ne répond ?',
        answer: 'Si vous n’avez pas de réponse, vous pouvez modifier votre annonce ou la supprimer pour en créer une nouvelle. Cela permet de relancer une notification auprès des babysitters disponibles autour de vous.',
        category: 'parents',
    },
    {
        question: "Combien de temps à l'avance puis-je réserver une babysitter?",
        answer: 'Vous pouvez publier une annonce à tout moment, même en dernière minute : notre plateforme est pensée pour répondre aussi aux urgences. Mais ne tardez pas trop à valider une candidature, car la babysitter pourrait être réservée par une autre famille entre-temps.',
        category: 'parents',
    },
    {
        question: 'Puis-je réserver la même babysitter plusieurs fois ?',
        answer: 'Bien sûr ! Vous pouvez la retrouver dans vos favoris ou vos anciennes réservations pour gagner du temps.',
        category: 'parents',
    },
    {
        question: 'Est-ce que le site est disponible partout en France ?',
        answer: 'Oui, nous sommes disponible dans toute la France. La disponibilité des babysitters peut varier selon les villes, mais notre communauté s’agrandit chaque jour.',
        category: 'service',
    },
    {
        question: 'Puis-je utiliser le site sur mobile ?',
        answer: 'Oui, la plateforme est pensée pour un usage mobile. Vous pouvez tout faire depuis votre téléphone : publier une annonce, postuler, discuter et réserver.',
        category: 'service',
    },
    {
        question: 'Les profils des babysitters sont-ils vérifiés ?',
        answer: 'Oui. Chaque profil est vérifié manuellement par notre équipe dans un délai de 24 à 48h, pour garantir la fiabilité des informations et la sécurité de tous.',
        category: 'parents',
    },
    {
        question: 'Comment devenir babysitter sur Trouve ta Babysitter ?',
        answer: 'Il suffit de créer un compte babysitter, remplir soigneusement votre profil, et attendre sa validation par notre équipe (sous 24 à 48h). Une fois vérifié, vous pourrez postuler aux annonces proches de chez vous.',
        category: 'babysitters',
    },
    {
        question: 'Comment publier une annonce en tant que parent ?',
        answer: "Connectez-vous à votre compte parent, cliquez sur 'Créer une annonce', puis renseignez vos besoins : date, horaires, localisation, etc. Une fois publiée, les babysitters disponibles autour de vous pourront y postuler.",
        category: 'parents',
    },
    {
        question: 'Quels sont les frais à prévoir pour une réservation ?',
        answer: 'La plateforme est gratuite à l’inscription. Lorsqu’une réservation est confirmée, des frais de service fixe sont appliqués côté parent. Le tarif horaire de la babysitter est affiché clairement dès la candidature.',
        category: 'tarifs',
    },
    {
        question: 'Je n’arrive pas à finaliser ma réservation, que faire ?',
        answer: 'Vérifiez que tous les champs du formulaire sont bien remplis et que votre moyen de paiement est valide. Si le problème persiste, essayez un autre navigateur ou contactez notre support.',
        category: 'problemes',
    },
    {
        question: 'Quels services sont inclus sur Trouve ta Babysitter ?',
        answer: "Nous facilitons la mise en relation, la messagerie sécurisée, le suivi des réservations et le paiement en ligne. Vous vous concentrez sur l'essentiel : la confiance.",
        category: 'service',
    },
    {
        question: 'Comment fonctionne la messagerie intégrée ?',
        answer: "Dès qu'une candidature est envoyée, le parent peut échanger avec la babysitter via notre messagerie privée et sécurisée, accessible dans l’espace personnel.",
        category: 'service',
    },
    {
        question: 'Comment signaler un problème ou un bug ?',
        answer: 'Utilisez le formulaire de contact en bas de page ou écrivez-nous à hello@trouvetababysitter.fr. Nous traitons chaque demande sous 24h/48h.',
        category: 'service',
    },
    {
        question: 'Quand mon profil sera-t-il visible ?',
        answer: "Votre profil est visible dès qu'il est validé par notre équipe. Cela prend généralement entre 24 et 48h après inscription.",
        category: 'babysitters',
    },
    {
        question: 'Comment fixer mon tarif horaire ?',
        answer: "Vous pouvez fixer librement votre tarif depuis votre profil. Pas d'inquiétude, vous pouvez le modifier à tout moment. Ce tarif est à titre indicatif, vous pouvez tout de même postuler à des annonces avec un tarif différent.",
        category: 'babysitters',
    },
    {
        question: 'Comment suis-je payé(e) après une garde ?',
        answer: 'Vous êtes payé(e) directement par le parent en main propre à la fin de la garde, sauf l’acompte, qui est déjà réglé en ligne avant la réservation et qui correspond à 1 heure de babysitting au tarif fixé.',
        category: 'babysitters',
    },
    {
        question: 'Puis-je refuser une mission après avoir postulé ?',
        answer: 'Oui, vous êtes libre d’accepter ou refuser une réservation tant qu’elle n’est pas confirmée. En cas d’empêchement après validation, prévenez rapidement le parent.',
        category: 'babysitters',
    },
    {
        question: 'Comment choisir une babysitter ?',
        answer: 'Dès qu’une babysitter postule à votre annonce, vous pouvez consulter son profil complet, échanger via la messagerie, lire les avis, et réserver si le feeling passe.',
        category: 'parents',
    },
    {
        question: 'Puis-je réserver plusieurs babysitters en même temps ?',
        answer: 'Non, une fois une babysitter confirmée pour une annonce, elle devient indisponible pour les autres. En revanche, vous pouvez créer plusieurs annonces si vous avez des besoins différents.',
        category: 'parents',
    },
    {
        question: 'Comment voir l’historique de mes annonces de babysitting ?',
        answer: 'Vous retrouvez toutes vos anciennes réservations dans votre espace personnel, avec les profils, les avis et les détails de chaque garde.',
        category: 'parents',
    },
    {
        question: 'Puis-je modifier mon annonce une fois publiée ?',
        answer: 'Oui, vous pouvez la modifier à tout moment depuis votre tableau de bord. Une notification sera renvoyée aux babysitters après modification.',
        category: 'parents',
    },
    {
        question: 'Quels moyens de paiement sont acceptés ?',
        answer: 'Vous pouvez payer en carte bancaire, Apple Pay ou Google Pay via notre système sécurisé géré par Stripe.',
        category: 'tarifs',
    },
    {
        question: 'Quand suis-je débité(e) ?',
        answer: 'Le paiement est prélevé lors de la confirmation de la réservation, mais la babysitter n’est payée qu’après la garde effectuée.',
        category: 'tarifs',
    },
    {
        question: 'Dois-je donner de l’argent en main propre à la babysitter ?',
        answer: 'Oui, une partie du paiement se fait directement entre vous. Lors de la réservation, vous payez un acompte en ligne correspondant à 1 heure de babysitting au tarif fixé. Le reste du montant devra être réglé en main propre à la babysitter à la fin de la garde.',
        category: 'tarifs',
    },
    {
        question: 'Est-ce que la plateforme prend une commission sur les babysitters ?',
        answer: 'Non, les babysitters reçoivent 100% du tarif qu’elles ont fixé. Seul le parent paie un frais fixe lors de la réservation.',
        category: 'tarifs',
    },
    {
        question: 'Je n’arrive pas à me connecter à mon compte',
        answer: "Vérifiez votre adresse e-mail et votre mot de passe. Si besoin, cliquez sur 'Mot de passe oublié'. Si ça ne fonctionne toujours pas, contactez notre support.",
        category: 'problemes',
    },
    {
        question: 'Je ne reçois pas les emails de confirmation',
        answer: 'Pensez à vérifier votre dossier spam ou courrier indésirable. Ajoutez aussi notre adresse email à vos contacts pour éviter ce souci.',
        category: 'problemes',
    },
    {
        question: 'Je n’arrive pas à finaliser une réservation',
        answer: 'Assurez-vous d’avoir bien rempli tous les champs et que votre moyen de paiement est valide. Essayez aussi de recharger la page ou d’utiliser un autre navigateur. Si cela ne fonctionne pas, contactez notre support par mail à hello@trouvetababysitter.fr',
        category: 'problemes',
    },
    {
        question: 'Le site ne s’affiche pas correctement sur mon téléphone',
        answer: 'Essayez de vider le cache de votre navigateur ou d’utiliser un autre. Si le bug persiste, envoyez-nous une capture d’écran via le formulaire de contact.',
        category: 'problemes',
    },
    {
        question: 'Une erreur s’affiche quand je clique sur un profil',
        answer: 'Cela peut arriver si le profil a été désactivé. Essayez de recharger la page. Si le problème continue, notre support est là pour vous aider.',
        category: 'problemes',
    },
    {
        question: 'Mes données personnelles sont-elles sécurisées ?',
        answer: 'Absolument. Nous respectons les normes RGPD et ne partageons jamais vos données sans votre consentement.',
        category: 'securite',
    },
    {
        question: 'Comment garantir la fiabilité d’une babysitter ?',
        answer: 'Outre la vérification initiale, vous pouvez consulter les avis laissés par d’autres parents. La messagerie vous permet aussi de discuter en amont.',
        category: 'securite',
    },
    {
        question: 'Y a-t-il une assurance en cas de problème ?',
        answer: 'Nous ne proposons pas d’assurance spécifique pour le moment. En cas de souci, notre équipe intervient pour trouver une solution rapide et humaine.',
        category: 'securite',
    },
    {
        question: 'Est-ce que mes messages sont privés ?',
        answer: 'Oui, tous les échanges via notre messagerie sont strictement privés. Seules les personnes concernées peuvent accéder à la conversation. Nous ne lisons jamais vos messages sans raison légale ou signalement grave.',
        category: 'securite',
    },
    {
        question: 'Mes informations personnelles sont-elles partagées ?',
        answer: 'Non, nous ne partageons jamais vos données avec des tiers sans votre consentement. Vos coordonnées ne sont visibles que si vous choisissez de les transmettre dans une conversation.',
        category: 'securite',
    },
    {
        question: 'Pourquoi je ne peux pas partager mon numéro dans la messagerie ?',
        answer: "Pour protéger la sécurité des parents et des babysitters, les échanges de numéros de téléphone sont bloqués dans la messagerie avant confirmation d'une réservation. Une fois la babysitter réservée, les coordonnées téléphoniques sont automatiquement partagées afin de faciliter l’organisation de la garde.",
        category: 'securite',
    },
    {
        question: 'Quels documents sont nécessaires pour devenir babysitter ?',
        answer: 'Une pièce d’identité valide est obligatoire pour recevoir des paiements sur le site. Il faut être âgé de 16 ans minimumpour devenir babysitter.',
        category: 'babysitters',
    },
    {
        question: "Y a-t-il une limite d'âge pour les babysitters ?",
        answer: "Vous devez avoir au minimum 16 ans pour vous inscrire comme babysitter sur la plateforme. Certaines missions peuvent exiger plus d'expérience ou un âge minimum spécifique.",
        category: 'babysitters',
    },
    {
        question: 'Comment modifier mon profil babysitter ?',
        answer: "Connectez-vous à votre compte, puis allez dans la section 'Mon profil'. Vous pouvez modifier vos informations à tout moment.",
        category: 'babysitters',
    },
    {
        question: 'Pourquoi mon profil est-il en attente de vérification ?',
        answer: 'Tous les profils sont vérifiés manuellement par notre équipe. Ce processus prend en général 24 à 48 heures.',
        category: 'babysitters',
    },
    {
        question: 'Combien de temps prend la vérification de mon profil ?',
        answer: 'La vérification prend généralement entre 24 et 48 heures. Vous recevrez un email dès que votre profil est validé.',
        category: 'babysitters',
    },
    {
        question: 'Comment activer/désactiver ma disponibilité ?',
        answer: 'Dans votre tableau de bord, vous pouvez activer ou désactiver votre statut de disponibilité en un clic. Cela vous permet de ne plus recevoir de candidatures quand vous n’êtes pas disponible.',
        category: 'babysitters',
    },
    {
        question: 'Comment supprimer mon compte ?',
        answer: 'Vous pouvez supprimer votre compte à tout moment depuis les paramètres de votre profil. Toutes vos données seront alors définitivement effacées.',
        category: 'service',
    },
    {
        question: 'Puis-je avoir un profil parent ET babysitter ?',
        answer: 'Oui, vous pouvez avoir les deux types de profils avec la même adresse email. Il suffit de basculer entre vos rôles depuis votre espace personnel.',
        category: 'service',
    },
    {
        question: 'Puis-je négocier les tarifs avec les parents ?',
        answer: 'Vous fixez librement votre tarif horaire dans votre profil. Les parents voient ce tarif au moment de votre candidature. Il est donc important de le fixer de manière juste et claire.',
        category: 'babysitters',
    },
    {
        question: 'Que se passe-t-il si un parent annule à la dernière minute ?',
        answer: 'Nous encourageons les parents à prévenir au plus tôt. En cas d’annulation à moins de 24h, vous conservez votre acompte.',
        category: 'babysitters',
    },
    {
        question: "Pourquoi ma candidature n'apparaît pas ?",
        answer: 'Assurez-vous que votre profil est bien validé et que vous êtes disponible à la date indiquée dans l’annonce. Si besoin, contactez notre équipe.',
        category: 'problemes',
    },
    {
        question: 'Comment résoudre un conflit avec un parent/une babysitter ?',
        answer: "Vous pouvez signaler tout problème via le bouton 'Signaler' dans la conversation. Notre équipe de modération vous recontactera rapidement pour évaluer la situation.",
        category: 'securite',
    },
    {
        question: 'Comment configurer mon compte bancaire (Stripe) ?',
        answer: 'Dès que votre profil aura été vérifié, vous serez invité(e) à configurer votre compte Stripe Connect pour recevoir les paiements. Cela ne prend que quelques minutes.',
        category: 'babysitters',
    },
    {
        question: 'Pourquoi dois-je vérifier mon identité avec Stripe ?',
        answer: 'Stripe est un service de paiement sécurisé qui exige une vérification d’identité pour respecter les normes anti-fraude et réglementaires européennes.',
        category: 'babysitters',
    },
    {
        question: 'Comment payer mon acompte de réservation ?',
        answer: 'En tant que parent, vous payez l’acompte directement sur le site lors de la confirmation de la babysitter. Cela correspond à une heure de garde au tarif choisi.',
        category: 'tarifs',
    },
    {
        question: 'Que se passe-t-il en cas de remboursement ?',
        answer: 'Si la garde est annulée et que l’acompte doit être remboursé, celui-ci est automatiquement recrédité sur votre compte bancaire sous quelques jours.',
        category: 'tarifs',
    },
    {
        question: 'Comment télécharger mes factures ?',
        answer: "Vous pouvez télécharger toutes vos factures depuis votre espace personnel, rubrique 'Réservations' > 'Factures'.",
        category: 'tarifs',
    },
    // Ajoute d'autres questions/réponses ici
];

const search = ref('');
const selectedCategory = ref('all');
const openIndex = ref<number | null>(null);

const filteredFaqs = computed(() => {
    return faqs.filter(
        (faq) =>
            (selectedCategory.value === 'all' || selectedCategory.value === faq.category) &&
            (faq.question.toLowerCase().includes(search.value.toLowerCase()) || faq.answer.toLowerCase().includes(search.value.toLowerCase())),
    );
});

function selectCategory(key: string) {
    selectedCategory.value = key;
    openIndex.value = null;
}
function toggleFaq(index: number) {
    openIndex.value = openIndex.value === index ? null : index;
}
</script>

<template>
    <GlobalLayout>
        <section class="bg-secondary min-h-[70vh] px-4 py-16">
            <div class="mx-auto mb-10 max-w-3xl text-center">
                <h1 class="mb-4 text-4xl font-bold text-gray-900 md:text-5xl">Foire aux questions</h1>
                <p class="mb-6 text-gray-600">Retrouvez ici les réponses aux questions les plus fréquentes sur Trouve ta Babysitter.</p>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher une question..."
                    class="focus:ring-primary mx-auto mb-6 w-full rounded-xl border border-gray-200 bg-white px-5 py-3 text-base shadow-sm transition focus:ring-2 focus:outline-none md:w-2/3"
                />
                <div class="mb-8 flex flex-wrap justify-center gap-3">
                    <button
                        v-for="cat in categories"
                        :key="cat.key"
                        @click="selectCategory(cat.key)"
                        :class="[
                            'rounded-full border px-5 py-2 font-semibold transition',
                            selectedCategory === cat.key
                                ? 'bg-primary border-primary text-white shadow'
                                : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50',
                        ]"
                    >
                        {{ cat.name }}
                    </button>
                </div>
            </div>
            <div class="mx-auto max-w-2xl">
                <div v-if="filteredFaqs.length === 0" class="py-12 text-center text-gray-400">
                    <span>Aucune question trouvée pour cette catégorie.</span>
                </div>
                <div v-for="(faq, idx) in filteredFaqs" :key="faq.question" class="mb-4">
                    <div
                        class="flex cursor-pointer items-center justify-between rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm transition hover:shadow-md"
                        @click="toggleFaq(idx)"
                    >
                        <span class="text-left text-lg font-semibold text-gray-900">{{ faq.question }}</span>
                        <svg
                            :class="['h-6 w-6 transition-transform', openIndex === idx ? 'text-primary rotate-180' : 'text-gray-400']"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    <div v-show="openIndex === idx" class="mt-2 rounded-xl bg-white px-6 pt-2 pb-5 text-left leading-relaxed text-gray-700">
                        {{ faq.answer }}
                    </div>
                </div>
            </div>
        </section>
    </GlobalLayout>
</template>
