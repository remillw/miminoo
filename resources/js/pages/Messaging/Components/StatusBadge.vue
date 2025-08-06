<template>
    <span :class="badgeClasses" class="rounded-full px-2 py-1 text-xs font-medium whitespace-nowrap">
        {{ badgeText }}
    </span>
</template>

<script setup>
import { useStatusColors } from '@/composables/useStatusColors';
import { computed } from 'vue';

const props = defineProps({
    status: String,
    isExpired: Boolean,
});

const { getApplicationStatusColor, getStatusText } = useStatusColors();

const badgeClasses = computed(() => {
    if (props.isExpired) {
        return 'bg-gray-100 text-gray-600';
    }

    return getApplicationStatusColor(props.status || '').badge;
});

const badgeText = computed(() => {
    if (props.isExpired) {
        return 'Expirée';
    }

    return getStatusText('application', props.status || '');
});
</script>
