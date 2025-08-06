<template>
    <div class="bg-secondary min-h-screen py-8">
        <div class="mx-auto max-w-2xl px-4">
            <!-- En-tête -->
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-gray-900">Laisser un avis</h1>
                <p class="mt-2 text-gray-600">Votre avis aide la communauté à faire de meilleurs choix</p>
            </div>

            <!-- Carte principale -->
            <div class="rounded-lg bg-white p-8 shadow-sm">
                <!-- Informations sur le service -->
                <div class="mb-8 rounded-lg bg-gray-50 p-6">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Service réalisé</h2>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <Calendar class="h-5 w-5 text-gray-400" />
                            <span class="text-gray-700">{{ formatDate(reservation.service_start_at) }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <Clock class="h-5 w-5 text-gray-400" />
                            <span class="text-gray-700"
                                >{{ formatTime(reservation.service_start_at) }} - {{ formatTime(reservation.service_end_at) }}</span
                            >
                        </div>
                        <div class="flex items-center gap-3">
                            <FileText class="h-5 w-5 text-gray-400" />
                            <span class="text-gray-700">{{ reservation.ad_title }}</span>
                        </div>
                    </div>
                </div>

                <!-- Personne évaluée -->
                <div class="mb-8 flex items-center gap-4 rounded-lg border border-gray-200 p-4">
                    <img
                        :src="reviewed_user.avatar || '/default-avatar.svg'"
                        :alt="reviewed_user.firstname"
                        class="h-16 w-16 rounded-full object-cover"
                    />
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ reviewed_user.firstname }} {{ reviewed_user.lastname }}</h3>
                        <p class="text-gray-600">
                            {{ user_role === 'parent' ? 'Babysitter' : 'Parent' }}
                        </p>
                    </div>
                </div>

                <!-- Formulaire d'avis -->
                <form @submit.prevent="submitReview" class="space-y-6">
                    <!-- Note -->
                    <div>
                        <label class="mb-3 block text-sm font-medium text-gray-700"> Note générale * </label>
                        <div class="flex items-center gap-2">
                            <button
                                v-for="star in 5"
                                :key="star"
                                type="button"
                                @click="form.rating = star"
                                class="transition-colors"
                                :class="star <= form.rating ? 'text-yellow-400' : 'text-gray-300'"
                            >
                                <Star class="h-8 w-8 fill-current" />
                            </button>
                            <span v-if="form.rating" class="ml-2 text-sm text-gray-600">
                                {{ getRatingText(form.rating) }}
                            </span>
                        </div>
                        <div v-if="errors.rating" class="mt-1 text-sm text-red-600">
                            {{ errors.rating }}
                        </div>
                    </div>

                    <!-- Commentaire -->
                    <div>
                        <label for="comment" class="mb-2 block text-sm font-medium text-gray-700"> Commentaire (optionnel) </label>
                        <textarea
                            id="comment"
                            v-model="form.comment"
                            rows="4"
                            class="focus:border-primary focus:ring-primary/20 w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:outline-none"
                            placeholder="Partagez votre expérience..."
                            maxlength="1000"
                        ></textarea>
                        <div class="mt-1 flex justify-between text-sm text-gray-500">
                            <span v-if="errors.comment" class="text-red-600">{{ errors.comment }}</span>
                            <span class="ml-auto">{{ form.comment?.length || 0 }}/1000</span>
                        </div>
                    </div>

                    <!-- Suggestions selon le rôle -->
                    <div class="rounded-lg bg-blue-50 p-4">
                        <h4 class="mb-2 font-medium text-blue-900">Suggestions pour votre avis :</h4>
                        <ul class="space-y-1 text-sm text-blue-800">
                            <li v-if="user_role === 'parent'">• Ponctualité et professionnalisme de la babysitter</li>
                            <li v-if="user_role === 'parent'">• Interaction avec les enfants</li>
                            <li v-if="user_role === 'parent'">• Respect des consignes données</li>
                            <li v-if="user_role === 'babysitter'">• Clarté des consignes du parent</li>
                            <li v-if="user_role === 'babysitter'">• Comportement des enfants</li>
                            <li v-if="user_role === 'babysitter'">• Ponctualité du parent</li>
                        </ul>
                    </div>

                    <!-- Boutons -->
                    <div class="flex gap-4 pt-4">
                        <button
                            type="button"
                            @click="$inertia.visit(route('messaging.index'))"
                            class="flex-1 rounded-lg border border-gray-300 px-4 py-3 text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="!form.rating || processing"
                            class="bg-primary hover:bg-primary/90 flex-1 rounded-lg px-4 py-3 text-white transition-colors disabled:opacity-50"
                        >
                            <span v-if="processing">Publication...</span>
                            <span v-else>Publier l'avis</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { Calendar, Clock, FileText, Star } from 'lucide-vue-next';
import { reactive, ref } from 'vue';

const props = defineProps({
    reservation: Object,
    reviewed_user: Object,
    user_role: String,
    errors: Object,
});

const processing = ref(false);

const form = reactive({
    rating: 0,
    comment: '',
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatTime = (dateString) => {
    return new Date(dateString).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getRatingText = (rating) => {
    const texts = {
        1: 'Très insatisfait',
        2: 'Insatisfait',
        3: 'Correct',
        4: 'Satisfait',
        5: 'Très satisfait',
    };
    return texts[rating] || '';
};

const submitReview = () => {
    if (!form.rating) return;

    processing.value = true;

    router.post(route('reviews.store', props.reservation.id), form, {
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>
