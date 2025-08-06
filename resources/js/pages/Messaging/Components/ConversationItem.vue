<template>
    <div
        @click="$emit('click', item)"
        class="cursor-pointer border-l-4 p-3 transition-colors hover:bg-gray-50"
        :class="[active ? 'border-l-blue-500 bg-blue-50' : 'border-l-transparent', hasUnreadBadge ? 'bg-secondary' : '']"
    >
        <div class="flex items-start gap-3">
            <!-- Avatar -->
            <div class="relative flex-shrink-0">
                <img
                    :src="otherUser.avatar || '/images/default-avatar.png'"
                    :alt="otherUser.name"
                    class="h-10 w-10 rounded-full border-2 border-gray-200 object-cover"
                />
                <!-- Badge status pour candidatures -->
                <div v-if="type === 'application'" class="absolute -top-1 -right-1">
                    <div
                        class="flex h-4 w-4 items-center justify-center rounded-full text-xs font-bold text-white"
                        :class="getStatusBadgeClass(item.status)"
                    >
                        {{ getStatusIcon(item.status) }}
                    </div>
                </div>
            </div>

            <!-- Contenu -->
            <div class="min-w-0 flex-1">
                <div class="mb-1 flex items-center justify-between">
                    <h4 class="truncate font-medium text-gray-900">
                        {{ otherUser.name }}
                    </h4>
                    <div class="flex items-center gap-1">
                        <!-- Badge non lu -->
                        <span v-if="unreadCount > 0" class="min-w-[18px] rounded-full bg-red-500 px-1.5 py-0.5 text-center text-xs text-white">
                            {{ unreadCount }}
                        </span>
                        <!-- Badge nouveau (candidature) -->
                        <span v-if="type === 'application' && isNew" class="bg-primary animate-pulse rounded-full px-2 py-0.5 text-xs text-white">
                            Nouveau
                        </span>
                    </div>
                </div>

                <!-- Titre annonce -->
                <div class="mb-1 truncate text-xs text-gray-500">
                    {{ item.ad_title }}
                </div>

                <!-- Info spécifique au type -->
                <div v-if="type === 'application'" class="mb-2">
                    <!-- Tarif proposé -->
                    <div class="mb-1 text-sm font-medium text-blue-600">
                        {{ item.proposed_rate }}€/h
                        <span v-if="item.counter_rate" class="text-primary"> → {{ item.counter_rate }}€/h </span>
                    </div>

                    <!-- Dernier message s'il existe, sinon note de motivation -->
                    <p v-if="item.last_message" class="truncate text-sm text-gray-600" v-html="filterSensitiveInfo(item.last_message)"></p>
                    <p v-else-if="item.motivation_note" class="truncate text-xs text-gray-600">
                        {{ item.motivation_note }}
                    </p>
                    <p v-else class="truncate text-xs text-gray-500 italic">La conversation a commencé !</p>
                </div>

                <div v-else class="mb-2">
                    <!-- Dernier message -->
                    <p class="truncate text-sm text-gray-600" v-html="filterSensitiveInfo(item.last_message)"></p>
                </div>

                <!-- Temps et actions rapides -->
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-400">
                        {{ getTimeDisplay() }}
                    </p>

                    <!-- Actions rapides pour candidatures (parent) -->
                    <div v-if="type === 'application' && userRole === 'parent' && item.status === 'pending'" class="flex gap-1">
                        <button
                            @click.stop="$emit('quick-action', 'accept', item)"
                            class="rounded bg-green-600 px-2 py-1 text-xs text-white hover:bg-green-700"
                        >
                            ✓
                        </button>
                        <button
                            @click.stop="$emit('quick-action', 'decline', item)"
                            class="rounded bg-red-600 px-2 py-1 text-xs text-white hover:bg-red-700"
                        >
                            ✗
                        </button>
                    </div>

                    <!-- Actions rapides pour contre-offres (babysitter) -->
                    <div v-if="type === 'application' && userRole === 'babysitter' && item.status === 'counter_offered'" class="flex gap-1">
                        <button
                            @click.stop="$emit('quick-action', 'accept-counter', item)"
                            class="rounded bg-green-600 px-2 py-1 text-xs text-white hover:bg-green-700"
                        >
                            ✓
                        </button>
                        <button
                            @click.stop="$emit('quick-action', 'decline-counter', item)"
                            class="rounded bg-red-600 px-2 py-1 text-xs text-white hover:bg-red-700"
                        >
                            ✗
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    item: Object,
    type: String, // 'application' ou 'conversation'
    userRole: String,
    active: Boolean,
});

const emit = defineEmits(['click', 'quick-action']);

const otherUser = computed(() => {
    if (props.type === 'application') {
        return props.userRole === 'parent' ? props.item.babysitter : props.item.parent;
    } else {
        return props.item.other_user;
    }
});

const unreadCount = computed(() => {
    if (props.type === 'application') {
        return props.userRole === 'parent' && !props.item.viewed_at ? 1 : 0;
    } else {
        return props.item.unread_count || 0;
    }
});

const isNew = computed(() => {
    return props.type === 'application' && props.userRole === 'parent' && props.item.status === 'pending' && !props.item.viewed_at;
});

const hasUnreadBadge = computed(() => {
    return props.type === 'application' && isNew.value;
});

function filterSensitiveInfo(text) {
    if (!text) return '';

    // Vérifier si le paiement est effectué
    const isPaymentCompleted = props.item?.status === 'active' || props.item?.deposit_paid;

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

function getStatusBadgeClass(status) {
    switch (status) {
        case 'pending':
            return 'bg-yellow-500';
        case 'counter_offered':
            return 'bg-blue-500';
        case 'accepted':
            return 'bg-green-500';
        case 'declined':
            return 'bg-red-500';
        default:
            return 'bg-gray-500';
    }
}

function getStatusIcon(status) {
    switch (status) {
        case 'pending':
            return '⏳';
        case 'counter_offered':
            return '↩';
        case 'accepted':
            return '✓';
        case 'declined':
            return '✗';
        default:
            return '?';
    }
}

function getTimeDisplay() {
    if (props.type === 'application') {
        return props.item.created_at;
    } else {
        return props.item.last_message_at;
    }
}
</script>
