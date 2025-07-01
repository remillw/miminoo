<script setup lang="ts">
import { ref } from 'vue'
import { Mail } from 'lucide-vue-next'
import GlobalLayout from '@/layouts/GlobalLayout.vue';

const name = ref('')
const email = ref('')
const message = ref('')
const submitted = ref(false)
const loading = ref(false)
const phone = ref('')
const subject = ref('')
const openFaq = ref<number|null>(null)

function submitForm() {
  loading.value = true
  setTimeout(() => {
    submitted.value = true
    loading.value = false
    name.value = ''
    email.value = ''
    message.value = ''
    phone.value = ''
    subject.value = ''
  }, 1200)
}

function resetForm() {
  submitted.value = false
}

function toggleFaq(index: number) {
  openFaq.value = openFaq.value === index ? null : index
}
</script>

<template>
    <GlobalLayout> 
        <body class="min-h-screen bg-secondary font-sans">
    <div id="app">
        <div class="container mx-auto px-4 py-8 max-w-7xl">
            <!-- Header -->
            <header class="text-center mb-12">
                <h1 class="mb-6 text-4xl font-bold text-gray-900 md:text-5xl">
                    Contactez-nous
                </h1>
                <p class="mx-auto mb-12 max-w-2xl text-lg leading-relaxed text-gray-600 md:text-xl">
                    Une question ? Un projet ? Notre équipe est là pour vous accompagner dans votre recherche de babysitter.
                </p>
            </header>

            <!-- Contact Grid -->
            <div class="grid lg:grid-cols-2 gap-8 mb-12">
                <!-- Contact Form -->
                <div class="bg-white rounded-3xl p-8 shadow-2xl transform hover:scale-[1.02] transition-all duration-300 fade-in lg:col-span-2">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                        <div class="w-8 h-8 bg-primary rounded-full mr-3 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        Envoyez-nous un message
                    </h2>
                    
                    <form v-if="!submitted" @submit.prevent="submitForm" class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-700 mb-2 tracking-wide">
                                Nom complet
                            </label>
                            <input 
                                id="name"
                                v-model="name" 
                                type="text" 
                                required 
                                class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-primary focus:bg-white focus:outline-none transition-all duration-300 transform focus:-translate-y-1" 
                                placeholder="Votre nom et prénom"
                            />
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2  tracking-wide">
                                Adresse email
                            </label>
                            <input 
                                id="email"
                                v-model="email" 
                                type="email" 
                                required 
                                class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-primary focus:bg-white focus:outline-none  transition-all duration-300 transform focus:-translate-y-1" 
                                placeholder="email@exemple.com"
                            />
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2  tracking-wide">
                                Téléphone <span class="text-gray-400 normal-case">(optionnel)</span>
                            </label>
                            <input 
                                id="phone"
                                v-model="phone" 
                                type="tel" 
                                class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-primary focus:bg-white focus:outline-none transition-all duration-300 transform focus:-translate-y-1" 
                                placeholder="06 12 34 56 78"
                            />
                        </div>

                        <div>
                            <label for="subject" class="block text-sm font-semibold text-gray-700 mb-2  tracking-wide">
                                Sujet
                            </label>
                            <select 
                                id="subject"
                                v-model="subject" 
                                required 
                                class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-primary focus:bg-white focus:outline-none  transition-all duration-300 transform focus:-translate-y-1"
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
                            <label for="message" class="block text-sm font-semibold text-gray-700 mb-2  tracking-wide">
                                Votre message
                            </label>
                            <textarea 
                                id="message"
                                v-model="message" 
                                required 
                                rows="6" 
                                class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl focus:border-primary focus:bg-white focus:outline-none transition-all duration-300 transform focus:-translate-y-1 resize-none" 
                                placeholder="Décrivez votre demande en détail..."
                            ></textarea>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="loading" 
                            class="w-full bg-primary text-white font-bold py-4 px-8 rounded-xl hover:from-primary hover:to-primary hover:shadow-xl hover:shadow-primary/25 transform hover:-translate-y-1 transition-all duration-300 disabled:opacity-70 disabled:cursor-not-allowed disabled:transform-none relative overflow-hidden"
                        >
                            <div v-if="loading" class="flex items-center justify-center">
                                <div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent mr-3"></div>
                                Envoi en cours...
                            </div>
                            <span v-else>Envoyer le message</span>
                        </button>
                    </form>

                    <div v-else class="text-center py-12 fade-in">
                        <div class="w-20 h-20 bg-gradient-to-r from-green-400 to-green-600 rounded-full flex items-center justify-center mx-auto mb-6 bounce-animation">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Message envoyé avec succès !</h3>
                        <p class="text-gray-600 mb-6 text-lg">Merci pour votre message. Notre équipe vous répondra dans les plus brefs délais.</p>
                        <button 
                            @click="resetForm" 
                            class="bg-primary text-white font-semibold py-3 px-6 rounded-xl hover:shadow-lg transform hover:-translate-y-1 transition-all duration-300"
                        >
                            Envoyer un autre message
                        </button>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="glass-effect rounded-3xl p-8 fade-in lg:col-span-2 mt-8 max-w-none w-full">
                    <h2 class="text-2xl font-bold mb-8 flex items-center">
                        <div class="w-8 h-8 bg-white/20 rounded-full mr-3 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        Questions fréquentes
                    </h2>
                    
                    <div class="space-y-4">
                        <div class="bg-white/10 rounded-2xl overflow-hidden">
                            <div 
                                class="p-6 cursor-pointer hover:bg-white/20 transition-all duration-300 flex justify-between items-center"
                                @click="toggleFaq(0)"
                            >
                                <span class="font-semibold text-lg">Je n'arrive pas à me connecter, que faire ?</span>
                                <svg 
                                    class="w-6 h-6 transition-transform duration-300" 
                                    :class="{ 'rotate-180': openFaq === 0 }"
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                            <div v-show="openFaq === 0" class="px-6 pb-6 text-black leading-relaxed">
                                Vérifiez d’abord que votre adresse e-mail et votre mot de passe sont corrects. Si besoin, vous pouvez cliquer sur "Mot de passe oublié" pour le réinitialiser. Et si le problème persiste, contactez-nous via le formulaire ci-dessus.
                            </div>
                        </div>

                        <div class="bg-white/10 rounded-2xl overflow-hidden">
                            <div 
                                class="p-6 cursor-pointer hover:bg-white/20 transition-all duration-300 flex justify-between items-center"
                                @click="toggleFaq(1)"
                            >
                                <span class="font-semibold text-lg">Je ne trouve pas de babysitter dans ma ville. Est-ce normal ?</span>
                                <svg 
                                    class="w-6 h-6 transition-transform duration-300" 
                                    :class="{ 'rotate-180': openFaq === 1 }"
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                            <div v-show="openFaq === 1" class="px-6 pb-6 text-black leading-relaxed">
                                La disponibilité des babysitters dépend de votre zone géographique. Si vous ne voyez aucun profil, essayez d’élargir un peu votre recherche (en kilomètres). Et rassurez-vous : notre communauté grandit chaque jour !
                            </div>
                        </div>

                        <div class="bg-white/10 rounded-2xl overflow-hidden">
                            <div 
                                class="p-6 cursor-pointer hover:bg-white/20 transition-all duration-300 flex justify-between items-center"
                                @click="toggleFaq(2)"
                            >
                                <span class="font-semibold text-lg">Un message d’erreur s’affiche quand je clique sur un profil</span>
                                <svg 
                                    class="w-6 h-6 transition-transform duration-300" 
                                    :class="{ 'rotate-180': openFaq === 2 }"
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                            <div v-show="openFaq === 2" class="px-6 pb-6 text-black leading-relaxed">
                                Cela peut arriver si le profil a été désactivé ou en cours de vérification. Rechargez la page ou réessayez un peu plus tard. Si l’erreur persiste, envoyez-nous une capture d’écran via le formulaire, on s’en occupe vite.
                            </div>
                        </div>

                        <div class="bg-white/10 rounded-2xl overflow-hidden">
                            <div 
                                class="p-6 cursor-pointer hover:bg-white/20 transition-all duration-300 flex justify-between items-center"
                                @click="toggleFaq(3)"
                            >
                                <span class="font-semibold text-lg">Je n'arrive pas à finaliser mon inscription</span>
                                <svg 
                                    class="w-6 h-6 transition-transform duration-300" 
                                    :class="{ 'rotate-180': openFaq === 3 }"
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                            <div v-show="openFaq === 3" class="px-6 pb-6 text-black leading-relaxed">
                                Si vous ne recevez pas le mail de validation ou que le bouton de création de compte ne fonctionne pas, vérifiez votre connexion internet, essayez un autre navigateur ou contactez-nous directement. On vous aidera à débloquer ça rapidement.


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </body>
</GlobalLayout>
</template>

<style>
 @keyframes bounce {
            0%, 20%, 53%, 80%, 100% { transform: translate3d(0,0,0); }
            40%, 43% { transform: translate3d(0,-20px,0); }
            70% { transform: translate3d(0,-10px,0); }
            90% { transform: translate3d(0,-4px,0); }
        }
        .bounce-animation { animation: bounce 0.6s ease-out; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeIn 0.6s ease-out forwards; }
        
        .glass-effect {
            backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: 0 4px 24px 0 rgba(0,0,0,0.06);
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