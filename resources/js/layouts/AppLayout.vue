<script setup lang="ts">
import { useCapacitor } from '@/composables/useCapacitor';
import { usePushNotifications } from '@/composables/usePushNotifications';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Initialiser les notifications push
const { isRegistered, permissionStatus } = usePushNotifications();

// Initialiser Capacitor pour gérer les deep links
const { isNative, platform } = useCapacitor();

// Debug logs pour vérifier l'initialisation
console.log('🏗️ AppLayout initialisé', {
    isNative: isNative.value,
    platform: platform.value,
    pushPermission: permissionStatus.value,
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
</template>
