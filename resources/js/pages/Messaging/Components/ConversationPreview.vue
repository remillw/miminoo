<template>
    <div
        @click="$emit('click', conversation.id)"
        class="cursor-pointer border-l-4 p-4 transition-colors hover:bg-gray-50"
        :class="conversation.unread_count > 0 ? 'border-l-blue-500 bg-blue-50' : 'border-l-transparent'"
    >
        <div class="flex items-start gap-3">
            <!-- Avatar -->
            <div class="flex-shrink-0">
                <img
                    :src="conversation.other_user.avatar || '/images/default-avatar.png'"
                    :alt="conversation.other_user.name"
                    class="h-10 w-10 rounded-full border-2 border-gray-200 object-cover"
                />
            </div>

            <!-- Contenu -->
            <div class="min-w-0 flex-1">
                <div class="mb-1 flex items-center justify-between">
                    <h4 class="truncate font-medium text-gray-900">
                        {{ conversation.other_user.name }}
                    </h4>
                    <div class="flex items-center gap-1">
                        <!-- Badge non lu -->
                        <span
                            v-if="conversation.unread_count > 0"
                            class="min-w-[18px] rounded-full bg-blue-500 px-1.5 py-0.5 text-center text-xs text-white"
                        >
                            {{ conversation.unread_count }}
                        </span>
                        <!-- Indicateur accompte -->
                        <BadgeCheck v-if="conversation.deposit_paid" class="h-4 w-4 text-green-500" title="Accompte payé" />
                    </div>
                </div>

                <!-- Infos annonce -->
                <div class="mb-1 text-xs text-gray-500">{{ conversation.ad_title }} • {{ conversation.ad_date }}</div>

                <!-- Tarif -->
                <div class="mb-2 text-xs font-medium text-blue-600">{{ conversation.rate }}€/h</div>

                <!-- Dernier message -->
                <p class="mb-1 truncate text-sm text-gray-600" v-html="filterSensitiveInfo(conversation.last_message)"></p>

                <!-- Temps -->
                <p class="text-xs text-gray-400">
                    {{ conversation.last_message_at }}
                </p>
            </div>
        </div>

        <!-- Statut conversation -->
        <div v-if="conversation.status !== 'active'" class="mt-2">
            <span class="rounded-full px-2 py-1 text-xs" :class="getReservationStatusColor(conversation.status).badge">
                {{ getStatusText('reservation', conversation.status) }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { useStatusColors } from '@/composables/useStatusColors';
import { BadgeCheck } from 'lucide-vue-next';

const props = defineProps({
    conversation: Object,
});

defineEmits(['click']);

// Composable pour les couleurs de statut
const { getReservationStatusColor, getStatusText } = useStatusColors();

function filterSensitiveInfo(text) {
    if (!text) return '';

    // Vérifier si le paiement est effectué via props.conversation
    const isPaymentCompleted = props.conversation?.status === 'active' || props.conversation?.deposit_paid;

    if (isPaymentCompleted) {
        // Si le paiement est fait, pas de filtrage
        return text;
    }

    // Patterns pour détecter les numéros de téléphone
    const phonePatterns = [
        // Numéros français (06, 07, etc.)
        /(?:(?:0|\+33\s?)[1-9](?:[\s.-]?\d{2}){4})/g,
        // Numéros avec indicatifs internationaux
        /(?:\+\d{1,3}[\s.-]?)?(?:\d[\s.-]?){6,14}\d/g,
        // Patterns simples pour 10 chiffres consécutifs
        /\b\d{10}\b/g,
        // Numéros avec espaces ou tirets
        /\b\d{2}[\s.-]?\d{2}[\s.-]?\d{2}[\s.-]?\d{2}[\s.-]?\d{2}\b/g,
    ];

    let filteredText = text;

    // Remplacer les numéros de téléphone par un message de restriction
    phonePatterns.forEach((pattern) => {
        filteredText = filteredText.replace(pattern, '<span class="bg-red-100 text-red-600 px-1 py-0.5 rounded text-xs">🔒 Masqué</span>');
    });

    // Patterns pour détecter d'autres infos sensibles
    const emailPattern = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/g;
    filteredText = filteredText.replace(emailPattern, '<span class="bg-red-100 text-red-600 px-1 py-0.5 rounded text-xs">🔒 Email masqué</span>');

    return filteredText;
}

// Fonctions de statut remplacées par le composable useStatusColors
</script>
