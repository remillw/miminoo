<script setup lang="ts">
import { useDeviceToken } from '@/composables/useDeviceToken';
import Login from '@/pages/auth/Login.vue';
import LoginMobile from '@/pages/auth/LoginMobile.vue';
import { computed } from 'vue';

interface Props {
    status?: string;
    canResetPassword: boolean;
}

const props = defineProps<Props>();

// Utiliser votre système de détection mobile existant
const { isMobileApp } = useDeviceToken();

// Composant à utiliser selon le contexte
const LoginComponent = computed(() => {
    return isMobileApp() ? LoginMobile : Login;
});
</script>

<template>
    <component :is="LoginComponent" :status="props.status" :canResetPassword="props.canResetPassword" />
</template>
