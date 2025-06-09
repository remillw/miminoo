<script setup lang="ts">
import BabysitterSidebar from '@/components/dashboard/babysitter/BabysitterSidebar.vue';
import ParentSidebar from '@/components/dashboard/parent/ParentSidebar.vue';
import DashboardFooter from '@/components/dashboard/shared/DashboardFooter.vue';
import DashboardHeader from '@/components/dashboard/shared/DashboardHeader.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    role?: string;
}

const props = defineProps<Props>();
const page = usePage();

// Sidebar dynamique selon le rôle
const SidebarComponent = computed(() => {
    const userRole = props.role || page.props.auth?.user?.role?.name;
    return userRole === 'parent' ? ParentSidebar : BabysitterSidebar;
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-[#fcf8f6]">
        <DashboardHeader />

        <div class="flex flex-1">
            <component :is="SidebarComponent" />

            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>

        <DashboardFooter />
    </div>
</template>
