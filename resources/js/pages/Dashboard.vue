<script setup lang="ts">
import { useUserRole } from '@/composables/useUserRole';
import BabysitterDashboardLayout from '@/layouts/BabysitterDashboardLayout.vue';
import ParentDashboardLayout from '@/layouts/ParentDashboardLayout.vue';

const { isParent, isBabysitter } = useUserRole();
</script>

<template>
    <ParentDashboardLayout v-if="isParent" />
    <BabysitterDashboardLayout v-else-if="isBabysitter" />
    <div v-else>Rôle inconnu</div>
</template>
