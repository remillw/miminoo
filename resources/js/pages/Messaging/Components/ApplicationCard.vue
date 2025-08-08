<template>
    <div class="p-6 transition-colors hover:bg-gray-50">
        <div class="flex items-start justify-between">
            <div class="flex flex-1 items-start gap-4">
                <!-- Avatar -->
                <div class="flex-shrink-0">
                    <img
                        :src="otherUser.avatar || '/images/default-avatar.png'"
                        :alt="otherUser.name"
                        class="h-12 w-12 rounded-full border-2 border-gray-200 object-cover"
                    />
                </div>

                <!-- Contenu principal -->
                <div class="min-w-0 flex-1">
                    <div class="mb-2 flex items-center gap-3">
                        <h3 class="truncate font-semibold text-gray-900">{{ otherUser.name }}</h3>

                        <!-- Badge statut -->
                        <StatusBadge :status="application.status" :is-expired="application.is_expired" />

                        <!-- Badge non-lu (parent uniquement) -->
                        <span
                            v-if="userRole === 'parent' && !application.viewed_at && application.status === 'pending'"
                            class="animate-pulse rounded-full bg-red-500 px-2 py-1 text-xs text-white"
                        >
                            Nouveau
                        </span>
                    </div>

                    <!-- Infos annonce -->
                    <div class="mb-3 text-sm text-gray-600">
                        <p class="font-medium">{{ application.ad_title }}</p>
                        <p class="mt-1 flex items-center gap-1">
                            <Calendar class="h-4 w-4" />
                            {{ application.ad_date }}
                        </p>
                    </div>

                    <!-- Message motivation -->
                    <div v-if="application.motivation_note" class="mb-3 rounded-lg bg-gray-50 p-3">
                        <p class="text-sm text-gray-700">{{ application.motivation_note }}</p>
                    </div>

                    <!-- Tarif proposé -->
                    <div class="mb-3 flex items-center gap-4">
                        <span class="text-sm font-medium text-gray-900"> Tarif proposé : {{ application.proposed_rate }}€/h </span>
                        <span v-if="userRole === 'parent' && application.babysitter.experience" class="text-xs text-gray-500">
                            {{ application.babysitter.experience }} {{ application.babysitter.experience === 1 ? 'an' : 'ans' }} d'expérience
                        </span>
                    </div>

                    <!-- Plus de contre-offres -->

                    <!-- Timer expiration -->
                    <div v-if="application.time_remaining && !application.is_expired" class="text-primary mb-3 flex items-center gap-2 text-xs">
                        <Clock class="h-4 w-4" />
                        <span>Expire dans {{ application.time_remaining }}</span>
                    </div>

                    <!-- Date -->
                    <p class="text-xs text-gray-500">{{ application.created_at }}</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="ml-4 flex-shrink-0">
                <ApplicationActions
                    :application="application"
                    :user-role="userRole"
                    @viewed="$emit('viewed', application.id)"
                    @accept="handleAccept"
                    @decline="$emit('decline', application.id)"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { Calendar, Clock } from 'lucide-vue-next';
import { computed } from 'vue';
import ApplicationActions from './ApplicationActions.vue';
import StatusBadge from './StatusBadge.vue';

const props = defineProps({
    application: Object,
    userRole: String,
});

const emit = defineEmits(['viewed', 'accept', 'decline']);

const otherUser = computed(() => {
    return props.userRole === 'parent' ? props.application.babysitter : props.application.parent;
});

function handleAccept() {
    // Accepter au tarif proposé par la babysitter
    emit('accept', props.application);
}
</script>
