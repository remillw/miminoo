<script setup lang="ts">
import { useUserRole } from '@/composables/useUserRole';
import BabysitterDashboardLayout from './BabysitterDashboardLayout.vue';
import ParentDashboardLayout from './ParentDashboardLayout.vue';

const { isParent, isBabysitter } = useUserRole();
</script>

<template>
    <ParentDashboardLayout v-if="isParent">
        <slot />
    </ParentDashboardLayout>

    <BabysitterDashboardLayout v-else-if="isBabysitter">
        <slot />
    </BabysitterDashboardLayout>

    <div v-else class="p-10 text-center text-red-500">Rôle inconnu ou non connecté</div>
</template>
