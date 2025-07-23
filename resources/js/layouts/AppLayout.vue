<script setup lang="ts">
import { useCapacitor } from '@/composables/useCapacitor';
import { usePushNotifications } from '@/composables/usePushNotifications';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { onMounted } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Initialiser les notifications push
const { isRegistered, permissionStatus, initializePushNotifications } = usePushNotifications();

// Initialiser Capacitor pour gérer les deep links
const { isNative, platform } = useCapacitor();

// Initialisation explicite au montage
onMounted(async () => {
    console.log('🏗️ AppLayout monté, initialisation...');

    // Debug logs pour vérifier l'initialisation
    console.log('🔧 État initial:', {
        isNative: isNative.value,
        platform: platform.value,
        pushPermission: permissionStatus.value,
    });

    // Appeler explicitement l'initialisation des push notifications
    if (isNative.value) {
        console.log('📱 Plateforme native détectée, initialisation push notifications...');
        await initializePushNotifications();
    } else {
        console.log('🌐 Plateforme web, skip push notifications');
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
</template>
