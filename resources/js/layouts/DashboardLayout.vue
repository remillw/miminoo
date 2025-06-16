<script setup lang="ts">
import BabysitterSidebar from '@/components/dashboard/babysitter/BabysitterSidebar.vue';
import ParentSidebar from '@/components/dashboard/parent/ParentSidebar.vue';
import DashboardFooter from '@/components/dashboard/shared/DashboardFooter.vue';
import DashboardHeader from '@/components/dashboard/shared/DashboardHeader.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    role?: string;
    currentMode?: 'parent' | 'babysitter';
}

const props = defineProps<Props>();
const page = usePage();

// Sidebar dynamique selon le mode ou le rôle
const SidebarComponent = computed(() => {
    // Priorité au currentMode si fourni
    if (props.currentMode) {
        return props.currentMode === 'parent' ? ParentSidebar : BabysitterSidebar;
    }
    
    // Fallback sur le rôle simple (pour la rétrocompatibilité)
    const userRole = props.role || (page.props.auth as any)?.user?.role?.name;
    return userRole === 'parent' ? ParentSidebar : BabysitterSidebar;
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <DashboardHeader />

        <div class="flex flex-1">
            <component :is="SidebarComponent" />

            <!-- Main content avec padding pour mobile -->
            <main class="flex-1 pb-20 lg:pb-0">
                <div class="py-6 px-4 sm:px-6 lg:px-8">
                    <div class="max-w-7xl mx-auto">
                        <slot />
                    </div>
                </div>
            </main>
        </div>

        <DashboardFooter />
    </div>
</template>
