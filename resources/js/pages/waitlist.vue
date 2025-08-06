<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Icon } from '@iconify/vue';
import { ref } from 'vue';

const email = ref('');
const role = ref('parent');
const submitted = ref(false);
const isLoading = ref(false);
const error = ref('');

// URL de votre Google Apps Script Web App (à remplacer par la vraie URL)
const GOOGLE_SCRIPT_URL = 'https://script.google.com/macros/s/AKfycbyo2SCYpMzPsTnXb_qgIMFwddJa37Yfn8HIaDR5etp-5Ob-xV7uwDfQoe2HrfmHFYVsoA/exec';

async function submitForm() {
    if (!email.value || !email.value.includes('@')) {
        error.value = 'Veuillez entrer une adresse email valide';
        return;
    }

    isLoading.value = true;
    error.value = '';

    try {
        // Données à envoyer vers Google Sheets
        const formData = {
            email: email.value,
            role: role.value,
            timestamp: new Date().toISOString(),
            source: 'waitlist',
        };

        // Envoi vers Google Sheets
        const response = await fetch(GOOGLE_SCRIPT_URL, {
            method: 'POST',
            mode: 'no-cors', // Important pour les requêtes vers Google Apps Script
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData),
        });

        // Avec no-cors, on ne peut pas vérifier le statut de la réponse
        // On assume que ça a fonctionné si aucune erreur n'est lancée
        submitted.value = true;

        // Analytics optionnel (si vous utilisez Google Analytics)
        if (typeof (window as any).gtag !== 'undefined') {
            (window as any).gtag('event', 'waitlist_signup', {
                event_category: 'engagement',
                event_label: role.value,
            });
        }
    } catch (err) {
        console.error("Erreur lors de l'envoi:", err);
        error.value = 'Une erreur est survenue. Veuillez réessayer.';
    } finally {
        isLoading.value = false;
    }
}

// Fonction pour définir le rôle
function setRole(newRole: 'parent' | 'babysitter') {
    role.value = newRole;
}

function openEmailClient() {
    window.open("mailto:contact@trouvebabysitter.com?subject=Inscription%20liste%20d'attente", '_blank');
}

// Réinitialiser les erreurs quand l'utilisateur tape
function clearError() {
    if (error.value) {
        error.value = '';
    }
}
</script>

<template>
    <div class="bg-secondary min-h-screen pb-16">
        <div class="mx-auto max-w-4xl px-4 pt-16 pb-10 text-center">
            <Badge variant="secondary" class="bg-primary-opacity text-primary border-primary/20 mb-6 px-4 py-2">
                <Icon icon="lucide:sparkles" class="mr-2 h-4 w-4" />
                Bientôt disponible
            </Badge>

            <h1 class="mb-4 text-4xl leading-tight font-extrabold text-gray-900 md:text-5xl">
                La révolution de la <span class="text-primary">garde d'enfants</span><br />arrive bientôt
            </h1>

            <p class="mx-auto mb-8 max-w-2xl text-lg text-gray-600">
                Trouve ta Babysitter transforme la façon dont les parents trouvent des babysitters de confiance. Soyez parmi les premiers à découvrir
                notre plateforme révolutionnaire.
            </p>

            <!-- FORMULAIRE AMÉLIORÉ -->
            <div class="mx-auto mb-8 max-w-2xl">
                <Card class="border-0 bg-white/95 shadow-2xl backdrop-blur-sm">
                    <CardContent class="p-8">
                        <form @submit.prevent="submitForm" class="space-y-6">
                            <!-- Input Email -->
                            <div class="space-y-2">
                                <Label for="email" class="text-left font-medium text-gray-700">Adresse email</Label>
                                <Input
                                    id="email"
                                    v-model="email"
                                    type="email"
                                    required
                                    placeholder="votre.email@exemple.com"
                                    class="focus:ring-primary/20 h-12 border-2 text-lg focus:ring-4"
                                    :disabled="submitted || isLoading"
                                    @input="clearError"
                                />
                                <!-- Message d'erreur -->
                                <div v-if="error" class="flex items-center text-sm text-red-500">
                                    <Icon icon="lucide:alert-circle" class="mr-1 h-4 w-4" />
                                    {{ error }}
                                </div>
                            </div>

                            <!-- Sélection de rôle avec boutons -->
                            <div class="space-y-4">
                                <Label class="font-medium text-gray-700">Je suis un(e)</Label>
                                <div class="grid grid-cols-2 gap-3">
                                    <!-- Bouton Parent -->
                                    <button
                                        type="button"
                                        @click="setRole('parent')"
                                        :disabled="submitted || isLoading"
                                        class="flex items-center justify-center space-x-3 rounded-xl border-2 p-4 transition-all duration-200 hover:shadow-md"
                                        :class="
                                            role === 'parent'
                                                ? 'border-primary bg-primary text-white shadow-lg'
                                                : 'hover:border-primary/30 border-gray-200 bg-white text-gray-700'
                                        "
                                    >
                                        <Icon icon="lucide:users" class="h-5 w-5" :class="role === 'parent' ? 'text-white' : 'text-primary'" />
                                        <span class="font-medium">Parent</span>
                                    </button>

                                    <!-- Bouton Babysitter -->
                                    <button
                                        type="button"
                                        @click="setRole('babysitter')"
                                        :disabled="submitted || isLoading"
                                        class="flex items-center justify-center space-x-3 rounded-xl border-2 p-4 transition-all duration-200 hover:shadow-md"
                                        :class="
                                            role === 'babysitter'
                                                ? 'border-primary bg-primary text-white shadow-lg'
                                                : 'hover:border-primary/30 border-gray-200 bg-white text-gray-700'
                                        "
                                    >
                                        <Icon icon="lucide:heart" class="h-5 w-5" :class="role === 'babysitter' ? 'text-white' : 'text-primary'" />
                                        <span class="font-medium">Babysitter</span>
                                    </button>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500">
                                Pas d'inquiétude, ce choix n'est pas définitif. <br />
                                Vous pourrez même avoir les 2 rôles (parent et babysitter)
                            </p>

                            <!-- Bouton Submit -->
                            <Button
                                type="submit"
                                size="lg"
                                class="h-14 w-full rounded-xl text-lg font-bold shadow-lg transition-all duration-300 hover:shadow-xl"
                                :disabled="submitted || isLoading"
                            >
                                <Icon v-if="isLoading" icon="lucide:loader-2" class="mr-2 h-5 w-5 animate-spin" />
                                <Icon v-else-if="submitted" icon="lucide:check-circle" class="mr-2 h-5 w-5" />
                                <Icon v-else icon="lucide:user-plus" class="mr-2 h-5 w-5" />
                                {{ isLoading ? 'Inscription...' : submitted ? 'Merci !' : "S'inscrire à la liste d'attente" }}
                            </Button>
                            <p class="text-sm text-gray-500">Vous recevrez un email lorsque la plateforme sera disponible.</p>
                        </form>

                        <div v-if="submitted" class="mt-4 rounded-xl border border-green-200 bg-green-50 p-4">
                            <div class="flex items-center text-green-700">
                                <Icon icon="lucide:check-circle" class="mr-2 h-5 w-5" />
                                <span class="font-medium">Parfait ! Tu es bien inscrit(e) à la liste d'attente.</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Stats -->
            <div class="mt-8 flex flex-col justify-center gap-8 md:flex-row">
                <div class="flex flex-col items-center">
                    <Icon icon="lucide:users" class="text-primary mb-2 h-8 w-8" />
                    <span class="text-2xl font-bold text-gray-900">126</span>
                    <span class="text-sm text-gray-500">Familles en attente</span>
                </div>
                <div class="flex flex-col items-center">
                    <Icon icon="lucide:heart" class="text-primary mb-2 h-8 w-8" />
                    <span class="text-2xl font-bold text-gray-900">368</span>
                    <span class="text-sm text-gray-500">Babysitters inscrites</span>
                </div>
                <div class="flex flex-col items-center">
                    <Icon icon="lucide:clock" class="text-primary mb-2 h-8 w-8" />
                    <span class="text-2xl font-bold text-gray-900">15 min</span>
                    <span class="text-sm text-gray-500">Temps moyen de réponse</span>
                </div>
            </div>
        </div>

        <!-- Ce qui vous attend -->
        <div class="bg-white py-16">
            <div class="mx-auto max-w-6xl px-4">
                <h2 class="mb-4 text-center text-3xl font-extrabold md:text-4xl">Ce qui vous attend</h2>
                <p class="mb-12 text-center text-gray-500">
                    Découvrez les fonctionnalités qui vont révolutionner votre expérience de garde d'enfants
                </p>

                <div class="mb-16 grid grid-cols-1 gap-8 md:grid-cols-3">
                    <Card class="group overflow-hidden border-0 bg-white shadow-lg transition-all duration-500 hover:shadow-2xl">
                        <CardContent class="relative flex flex-col items-center p-8 text-center">
                            <div class="bg-primary/5 group-hover:bg-primary/10 mb-6 rounded-2xl p-6 transition-colors duration-300">
                                <Icon icon="lucide:zap" class="text-primary h-10 w-10" />
                            </div>
                            <div class="mb-3 text-xl font-bold text-gray-900">Garde en urgence</div>
                            <div class="text-base leading-relaxed text-gray-600">
                                Trouvez une babysitter en moins de 15 minutes grâce à notre système de notification instantané
                            </div>
                            <div
                                class="from-primary to-primary/50 absolute inset-x-0 bottom-0 h-1 scale-x-0 transform bg-gradient-to-r transition-transform duration-300 group-hover:scale-x-100"
                            ></div>
                        </CardContent>
                    </Card>

                    <Card class="group overflow-hidden border-0 bg-white shadow-lg transition-all duration-500 hover:shadow-2xl">
                        <CardContent class="relative flex flex-col items-center p-8 text-center">
                            <div class="mb-6 rounded-2xl bg-green-50 p-6 transition-colors duration-300 group-hover:bg-green-100">
                                <Icon icon="lucide:shield-check" class="h-10 w-10 text-green-600" />
                            </div>
                            <div class="mb-3 text-xl font-bold text-gray-900">Profils vérifiés</div>
                            <div class="text-base leading-relaxed text-gray-600">
                                Toutes nos babysitters sont vérifiées, certifiées et passent par un processus de validation rigoureux
                            </div>
                            <div
                                class="absolute inset-x-0 bottom-0 h-1 scale-x-0 transform bg-gradient-to-r from-green-500 to-green-400 transition-transform duration-300 group-hover:scale-x-100"
                            ></div>
                        </CardContent>
                    </Card>

                    <Card class="group overflow-hidden border-0 bg-white shadow-lg transition-all duration-500 hover:shadow-2xl">
                        <CardContent class="relative flex flex-col items-center p-8 text-center">
                            <div class="mb-6 rounded-2xl bg-amber-50 p-6 transition-colors duration-300 group-hover:bg-amber-100">
                                <Icon icon="lucide:star" class="h-10 w-10 text-amber-500" />
                            </div>
                            <div class="mb-3 text-xl font-bold text-gray-900">Avis authentiques</div>
                            <div class="text-base leading-relaxed text-gray-600">
                                Consultez les vrais avis des autres parents et prenez des décisions éclairées
                            </div>
                            <div
                                class="absolute inset-x-0 bottom-0 h-1 scale-x-0 transform bg-gradient-to-r from-amber-500 to-amber-400 transition-transform duration-300 group-hover:scale-x-100"
                            ></div>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                    <!-- Pour les parents -->
                    <Card class="group overflow-hidden border-0 bg-white shadow-xl transition-all duration-500 hover:shadow-2xl">
                        <CardContent class="relative p-10">
                            <div class="bg-primary/5 absolute top-0 right-0 h-32 w-32 translate-x-16 -translate-y-16 rounded-full"></div>
                            <div class="relative z-10">
                                <div class="mb-6 flex items-center">
                                    <div class="bg-primary/10 mr-4 rounded-xl p-3">
                                        <Icon icon="lucide:users" class="text-primary h-7 w-7" />
                                    </div>
                                    <span class="text-2xl font-bold text-gray-900">Pour les parents</span>
                                </div>
                                <div class="mb-6 text-lg text-gray-600">Trouvez la babysitter parfaite en quelques clics</div>
                                <ul class="space-y-4">
                                    <li class="flex items-center text-gray-700">
                                        <div class="bg-primary/10 mr-3 rounded-full p-1">
                                            <Icon icon="lucide:check" class="text-primary h-4 w-4" />
                                        </div>
                                        <span class="font-medium">Recherche géolocalisée intelligente</span>
                                    </li>
                                    <li class="flex items-center text-gray-700">
                                        <div class="bg-primary/10 mr-3 rounded-full p-1">
                                            <Icon icon="lucide:check" class="text-primary h-4 w-4" />
                                        </div>
                                        <span class="font-medium">Réservation en temps réel</span>
                                    </li>
                                    <li class="flex items-center text-gray-700">
                                        <div class="bg-primary/10 mr-3 rounded-full p-1">
                                            <Icon icon="lucide:check" class="text-primary h-4 w-4" />
                                        </div>
                                        <span class="font-medium">Paiement sécurisé intégré</span>
                                    </li>
                                    <li class="flex items-center text-gray-700">
                                        <div class="bg-primary/10 mr-3 rounded-full p-1">
                                            <Icon icon="lucide:check" class="text-primary h-4 w-4" />
                                        </div>
                                        <span class="font-medium">Système d'avis et de notation</span>
                                    </li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Pour les babysitters -->
                    <Card class="group overflow-hidden border-0 bg-white shadow-xl transition-all duration-500 hover:shadow-2xl">
                        <CardContent class="relative p-10">
                            <div class="absolute top-0 right-0 h-32 w-32 translate-x-16 -translate-y-16 rounded-full bg-pink-50"></div>
                            <div class="relative z-10">
                                <div class="mb-6 flex items-center">
                                    <div class="mr-4 rounded-xl bg-pink-100 p-3">
                                        <Icon icon="lucide:heart" class="h-7 w-7 text-pink-600" />
                                    </div>
                                    <span class="text-2xl font-bold text-gray-900">Pour les babysitters</span>
                                </div>
                                <div class="mb-6 text-lg text-gray-600">Développez votre activité et votre clientèle</div>
                                <ul class="space-y-4">
                                    <li class="flex items-center text-gray-700">
                                        <div class="mr-3 rounded-full bg-pink-100 p-1">
                                            <Icon icon="lucide:check" class="h-4 w-4 text-pink-600" />
                                        </div>
                                        <span class="font-medium">Profil professionnel détaillé</span>
                                    </li>
                                    <li class="flex items-center text-gray-700">
                                        <div class="mr-3 rounded-full bg-pink-100 p-1">
                                            <Icon icon="lucide:check" class="h-4 w-4 text-pink-600" />
                                        </div>
                                        <span class="font-medium">Gestion des disponibilités</span>
                                    </li>
                                    <li class="flex items-center text-gray-700">
                                        <div class="mr-3 rounded-full bg-pink-100 p-1">
                                            <Icon icon="lucide:check" class="h-4 w-4 text-pink-600" />
                                        </div>
                                        <span class="font-medium">Notifications de nouvelles offres</span>
                                    </li>
                                    <li class="flex items-center text-gray-700">
                                        <div class="mr-3 rounded-full bg-pink-100 p-1">
                                            <Icon icon="lucide:check" class="h-4 w-4 text-pink-600" />
                                        </div>
                                        <span class="font-medium">Suivi détaillé des revenus</span>
                                    </li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Section avantages exclusifs -->
        <div class=" ">
            <Card class="bg-primary rounded border-0 text-white">
                <CardContent class="relative px-6 py-16 text-center md:px-16">
                    <div class="absolute inset-0 opacity-50">
                        <div
                            class="absolute inset-0"
                            style="
                                background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23ffffff%22 fill-opacity=%220.05%22%3E%3Ccircle cx=%2230%22 cy=%2230%22 r=%222%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');
                            "
                        ></div>
                    </div>

                    <div class="relative z-10">
                        <Badge variant="secondary" class="mb-8 border-white/20 bg-white/10 px-6 py-2 text-white">
                            <Icon icon="lucide:crown" class="mr-2 h-5 w-5" />
                            Avantages exclusifs
                        </Badge>

                        <h2 class="mb-4 text-3xl font-bold md:text-4xl">Pourquoi rejoindre la liste d'attente ?</h2>
                        <p class="mx-auto mb-12 max-w-2xl text-lg text-white/80">
                            Rejoignez notre communauté VIP et bénéficiez d'avantages exclusifs
                        </p>

                        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                            <div class="group flex flex-col items-center p-6 text-center">
                                <div
                                    class="mb-6 rounded-2xl bg-white/10 p-6 transition-all duration-300 group-hover:scale-110 group-hover:bg-white/20"
                                >
                                    <Icon icon="lucide:bell" class="h-10 w-10" />
                                </div>
                                <div class="mb-3 text-xl font-bold">Accès prioritaire</div>
                                <div class="leading-relaxed text-white/80">Soyez parmi les premiers à utiliser notre plateforme révolutionnaire</div>
                            </div>

                            <div class="group flex flex-col items-center p-6 text-center">
                                <div
                                    class="mb-6 rounded-2xl bg-white/10 p-6 transition-all duration-300 group-hover:scale-110 group-hover:bg-white/20"
                                >
                                    <Icon icon="lucide:gift" class="h-10 w-10" />
                                </div>
                                <div class="mb-3 text-xl font-bold">Offres spéciales</div>
                                <div class="leading-relaxed text-white/80">
                                    Bénéficiez de tarifs préférentiels et d'offres exclusives au lancement
                                </div>
                            </div>

                            <div class="group flex flex-col items-center p-6 text-center">
                                <div
                                    class="mb-6 rounded-2xl bg-white/10 p-6 transition-all duration-300 group-hover:scale-110 group-hover:bg-white/20"
                                >
                                    <Icon icon="lucide:users" class="h-10 w-10" />
                                </div>
                                <div class="mb-3 text-xl font-bold">Communauté VIP</div>
                                <div class="leading-relaxed text-white/80">Participez activement à l'évolution de la plateforme</div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
