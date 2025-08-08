<script setup lang="ts">
import { useDeviceToken } from '@/composables/useDeviceToken';
import { useToast } from '@/composables/useToast';
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { onMounted, ref } from 'vue';

interface UserInfo {
    name: string;
    email: string;
    phone: string;
}

interface Props {
    userInfo?: UserInfo | null;
}

const props = defineProps<Props>();
const { showSuccess, showError } = useToast();
const { isMobileApp } = useDeviceToken();

// État pour la bulle d'aide (seulement dans l'app mobile)
const showChatbotBubble = ref(false);
const showChatbot = ref(false);

const name = ref('');
const email = ref('');
const message = ref('');
const submitted = ref(false);
const loading = ref(false);
const phone = ref('');
const subject = ref('');
const openFaq = ref<number | null>(null);

// Pré-remplir les champs avec les informations utilisateur
onMounted(() => {
    if (props.userInfo) {
        name.value = props.userInfo.name || '';
        email.value = props.userInfo.email || '';
        phone.value = props.userInfo.phone || '';
    }

    // Ouvrir directement le chatbot sur mobile après un court délai
    if (isMobileApp()) {
        setTimeout(() => {
            openChatbot();
        }, 1500);
    }
});

async function submitForm() {
    loading.value = true;

    try {
        const response = await fetch('/contact', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                name: name.value,
                email: email.value,
                phone: phone.value,
                subject: subject.value,
                message: message.value,
            }),
        });

        const data = await response.json();

        if (data.success) {
            // Message de succès avec useToast
            showSuccess('✉️ Message envoyé !', data.message);

            submitted.value = true;
            name.value = '';
            email.value = '';
            message.value = '';
            phone.value = '';
            subject.value = '';
        } else {
            // Message d'erreur avec useToast
            showError("❌ Erreur d'envoi", data.message || 'Une erreur est survenue');
        }
    } catch (error) {
        console.error('Erreur:', error);
        // Message d'erreur réseau avec useToast
        showError('🌐 Erreur de connexion', "Impossible d'envoyer le message. Vérifiez votre connexion internet.");
    } finally {
        loading.value = false;
    }
}

function resetForm() {
    submitted.value = false;
}

function toggleFaq(index: number) {
    openFaq.value = openFaq.value === index ? null : index;
}

function openChatbot() {
    showChatbotBubble.value = false;
    showChatbot.value = true;

    // Charger le chatbot dynamiquement
    const typebotInitScript = document.createElement('script');
    typebotInitScript.type = 'module';
    typebotInitScript.innerHTML = `import Typebot from 'https://cdn.jsdelivr.net/npm/@typebot.io/js@0/dist/web.js'

Typebot.initBubble({
  typebot: "customer-support-2lar6lt",
  theme: {
    button: {
      backgroundColor: "#ff8157",
      customIconSrc:
        "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij4KCTxnIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2ZmZiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBzdHJva2Utd2lkdGg9IjIiPgoJCTxwYXRoIGQ9Ik0xOCA0YTMgMyAwIDAgMSAzIDN2OGEzIDMgMCAwIDEtMyAzaC01bC01IDN2LTNINmEzIDMgMCAwIDEtMy0zVjdhMyAzIDAgMCAxIDMtM3pNOS41IDloLjAxbTQuOTkgMGguMDEiIC8+CgkJPHBhdGggZD0iTTkuNSAxM2EzLjUgMy41IDAgMCAwIDUgMCIgLz4KCTwvZz4KPC9zdmc+",
    },
  },
});
`;
    document.body.append(typebotInitScript);
    console.log('✅ Chatbot Typebot activé');
}

function closeChatbotBubble() {
    showChatbotBubble.value = false;
}
</script>

<template>
    <GlobalLayout>
        <body class="bg-secondary min-h-screen font-sans">
            <div id="app">
                <div class="container mx-auto max-w-7xl px-4 py-8">
                    <!-- Header -->
                    <header class="mb-12 text-center">
                        <h1 class="mb-6 text-4xl font-bold text-gray-900 md:text-5xl">Contactez-nous</h1>
                        <p class="mx-auto mb-12 max-w-2xl text-lg leading-relaxed text-gray-600 md:text-xl">
                            Une question ? Un projet ? Notre équipe est là pour vous accompagner dans votre recherche de babysitter.
                        </p>
                    </header>

                    <!-- Contact Grid -->
                    <div class="mb-12 grid gap-8 lg:grid-cols-2">
                        <!-- Contact Form -->
                        <div
                            class="fade-in transform rounded-3xl bg-white p-8 shadow-2xl transition-all duration-300 hover:scale-[1.02] lg:col-span-2"
                        >
                            <h2 class="mb-6 flex items-center text-2xl font-bold text-gray-800">
                                <div class="bg-primary mr-3 flex h-8 w-8 items-center justify-center rounded-full">
                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                                        ></path>
                                    </svg>
                                </div>
                                Envoyez-nous un message
                            </h2>

                            <!-- Message informatif si connecté -->
                            <div v-if="props.userInfo" class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                                <div class="flex items-center">
                                    <svg class="mr-2 h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                    <span class="text-sm text-blue-800">
                                        Nous avons pré-rempli le formulaire avec vos informations de compte. Vous pouvez les modifier si nécessaire.
                                    </span>
                                </div>
                            </div>

                            <form v-if="!submitted" @submit.prevent="submitForm" class="space-y-6">
                                <div>
                                    <label for="name" class="mb-2 block text-sm font-semibold tracking-wide text-gray-700"> Nom complet </label>
                                    <input
                                        id="name"
                                        v-model="name"
                                        type="text"
                                        required
                                        class="focus:border-primary w-full transform rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 transition-all duration-300 focus:-translate-y-1 focus:bg-white focus:outline-none"
                                        placeholder="Votre nom et prénom"
                                    />
                                </div>

                                <div>
                                    <label for="email" class="mb-2 block text-sm font-semibold tracking-wide text-gray-700"> Adresse email </label>
                                    <input
                                        id="email"
                                        v-model="email"
                                        type="email"
                                        required
                                        class="focus:border-primary w-full transform rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 transition-all duration-300 focus:-translate-y-1 focus:bg-white focus:outline-none"
                                        placeholder="email@exemple.com"
                                    />
                                </div>

                                <div>
                                    <label for="phone" class="mb-2 block text-sm font-semibold tracking-wide text-gray-700">
                                        Téléphone <span class="text-gray-400 normal-case">(optionnel)</span>
                                    </label>
                                    <input
                                        id="phone"
                                        v-model="phone"
                                        type="tel"
                                        class="focus:border-primary w-full transform rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 transition-all duration-300 focus:-translate-y-1 focus:bg-white focus:outline-none"
                                        placeholder="06 12 34 56 78"
                                    />
                                </div>

                                <div>
                                    <label for="subject" class="mb-2 block text-sm font-semibold tracking-wide text-gray-700"> Sujet </label>
                                    <select
                                        id="subject"
                                        v-model="subject"
                                        required
                                        class="focus:border-primary w-full transform rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 transition-all duration-300 focus:-translate-y-1 focus:bg-white focus:outline-none"
                                    >
                                        <option value="">Choisissez un sujet</option>
                                        <option value="recherche">Recherche de babysitter</option>
                                        <option value="inscription">Connexion/Inscription</option>
                                        <option value="tarifs">Tarifs</option>
                                        <option value="technique">Problème technique</option>
                                        <option value="amélioration">Suggestion d'amélioration</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="message" class="mb-2 block text-sm font-semibold tracking-wide text-gray-700"> Votre message </label>
                                    <textarea
                                        id="message"
                                        v-model="message"
                                        required
                                        rows="6"
                                        class="focus:border-primary w-full transform resize-none rounded-xl border-2 border-gray-200 bg-gray-50 px-4 py-3 transition-all duration-300 focus:-translate-y-1 focus:bg-white focus:outline-none"
                                        placeholder="Décrivez votre demande en détail..."
                                    ></textarea>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="loading"
                                    class="bg-primary hover:from-primary hover:to-primary hover:shadow-primary/25 relative w-full transform overflow-hidden rounded-xl px-8 py-4 font-bold text-white transition-all duration-300 hover:-translate-y-1 hover:shadow-xl disabled:transform-none disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    <div v-if="loading" class="flex items-center justify-center">
                                        <div class="mr-3 h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                        Envoi en cours...
                                    </div>
                                    <span v-else>Envoyer le message</span>
                                </button>
                            </form>

                            <div v-else class="fade-in py-12 text-center">
                                <div
                                    class="bounce-animation mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-r from-green-400 to-green-600"
                                >
                                    <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <h3 class="mb-4 text-2xl font-bold text-gray-800">Message envoyé avec succès !</h3>
                                <p class="mb-6 text-lg text-gray-600">
                                    Merci pour votre message. Notre équipe vous répondra dans les plus brefs délais.
                                </p>
                                <button
                                    @click="resetForm"
                                    class="bg-primary transform rounded-xl px-6 py-3 font-semibold text-white transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                                >
                                    Envoyer un autre message
                                </button>
                            </div>
                        </div>

                        <!-- FAQ Section -->
                        <div class="glass-effect fade-in mt-8 w-full max-w-none rounded-3xl p-8 lg:col-span-2">
                            <h2 class="mb-8 flex items-center text-2xl font-bold">
                                <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-full bg-white/20">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                </div>
                                Questions fréquentes
                            </h2>

                            <div class="space-y-4">
                                <div class="overflow-hidden rounded-2xl bg-white/10">
                                    <div
                                        class="flex cursor-pointer items-center justify-between p-6 transition-all duration-300 hover:bg-white/20"
                                        @click="toggleFaq(0)"
                                    >
                                        <span class="text-lg font-semibold">Je n'arrive pas à me connecter, que faire ?</span>
                                        <svg
                                            class="h-6 w-6 transition-transform duration-300"
                                            :class="{ 'rotate-180': openFaq === 0 }"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                    <div v-show="openFaq === 0" class="px-6 pb-6 leading-relaxed text-black">
                                        Vérifiez d’abord que votre adresse e-mail et votre mot de passe sont corrects. Si besoin, vous pouvez cliquer
                                        sur "Mot de passe oublié" pour le réinitialiser. Et si le problème persiste, contactez-nous via le formulaire
                                        ci-dessus.
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-2xl bg-white/10">
                                    <div
                                        class="flex cursor-pointer items-center justify-between p-6 transition-all duration-300 hover:bg-white/20"
                                        @click="toggleFaq(1)"
                                    >
                                        <span class="text-lg font-semibold">Je ne trouve pas de babysitter dans ma ville. Est-ce normal ?</span>
                                        <svg
                                            class="h-6 w-6 transition-transform duration-300"
                                            :class="{ 'rotate-180': openFaq === 1 }"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                    <div v-show="openFaq === 1" class="px-6 pb-6 leading-relaxed text-black">
                                        La disponibilité des babysitters dépend de votre zone géographique. Si vous ne voyez aucun profil, essayez
                                        d’élargir un peu votre recherche (en kilomètres). Et rassurez-vous : notre communauté grandit chaque jour !
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-2xl bg-white/10">
                                    <div
                                        class="flex cursor-pointer items-center justify-between p-6 transition-all duration-300 hover:bg-white/20"
                                        @click="toggleFaq(2)"
                                    >
                                        <span class="text-lg font-semibold">Un message d’erreur s’affiche quand je clique sur un profil</span>
                                        <svg
                                            class="h-6 w-6 transition-transform duration-300"
                                            :class="{ 'rotate-180': openFaq === 2 }"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                    <div v-show="openFaq === 2" class="px-6 pb-6 leading-relaxed text-black">
                                        Cela peut arriver si le profil a été désactivé ou en cours de vérification. Rechargez la page ou réessayez un
                                        peu plus tard. Si l’erreur persiste, envoyez-nous une capture d’écran via le formulaire, on s’en occupe vite.
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-2xl bg-white/10">
                                    <div
                                        class="flex cursor-pointer items-center justify-between p-6 transition-all duration-300 hover:bg-white/20"
                                        @click="toggleFaq(3)"
                                    >
                                        <span class="text-lg font-semibold">Je n'arrive pas à finaliser mon inscription</span>
                                        <svg
                                            class="h-6 w-6 transition-transform duration-300"
                                            :class="{ 'rotate-180': openFaq === 3 }"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                    <div v-show="openFaq === 3" class="px-6 pb-6 leading-relaxed text-black">
                                        Si vous ne recevez pas le mail de validation ou que le bouton de création de compte ne fonctionne pas,
                                        vérifiez votre connexion internet, essayez un autre navigateur ou contactez-nous directement. On vous aidera à
                                        débloquer ça rapidement.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulle d'aide pour l'app mobile -->
            <div v-if="showChatbotBubble && isMobileApp()" class="fixed right-6 bottom-6 z-50">
                <div class="relative">
                    <!-- Bulle d'aide -->
                    <div
                        @click="openChatbot"
                        class="animate-bounce cursor-pointer rounded-full bg-gradient-to-r from-orange-500 to-red-500 px-6 py-3 text-white shadow-lg transition-shadow duration-300 hover:shadow-xl"
                    >
                        <div class="flex items-center gap-2">
                            <div class="flex h-6 w-6 items-center justify-center">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                                    />
                                </svg>
                            </div>
                            <span class="text-sm font-medium">Vous avez besoin d'aide ?</span>
                        </div>
                    </div>

                    <!-- Bouton fermer -->
                    <button
                        @click="closeChatbotBubble"
                        class="absolute -top-2 -right-2 flex h-6 w-6 items-center justify-center rounded-full bg-gray-600 text-white transition-colors duration-200 hover:bg-gray-700"
                    >
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </body>
    </GlobalLayout>
</template>

<style>
@keyframes bounce {
    0%,
    20%,
    53%,
    80%,
    100% {
        transform: translate3d(0, 0, 0);
    }
    40%,
    43% {
        transform: translate3d(0, -20px, 0);
    }
    70% {
        transform: translate3d(0, -10px, 0);
    }
    90% {
        transform: translate3d(0, -4px, 0);
    }
}
.bounce-animation {
    animation: bounce 0.6s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.fade-in {
    animation: fadeIn 0.6s ease-out forwards;
}

.glass-effect {
    backdrop-filter: blur(8px);
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid rgba(0, 0, 0, 0.06);
    box-shadow: 0 4px 24px 0 rgba(0, 0, 0, 0.06);
    color: #222;
}
.glass-effect h2,
.glass-effect h3,
.glass-effect p,
.glass-effect span {
    color: #222 !important;
}
.glass-effect svg {
    color: #f97316;
}
</style>
