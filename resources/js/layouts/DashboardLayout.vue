<script setup lang="ts">
import DashboardFooter from '@/components/dashboard/shared/DashboardFooter.vue';
import LandingHeader from '@/components/LandingHeader.vue';
import UnifiedSidebar from '@/components/sidebar/UnifiedSidebar.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    role?: string;
    currentMode?: 'parent' | 'babysitter';
    hasParentRole?: boolean;
    hasBabysitterRole?: boolean;
}

const props = defineProps<Props>();
const page = usePage();

// Récupérer les informations utilisateur depuis les props globales
const user = computed(() => (page.props.auth as any)?.user);
const userRoles = computed(() => user.value?.roles?.map((role: any) => role.name) || []);

const hasParentRole = computed(() => props.hasParentRole ?? userRoles.value.includes('parent'));
const hasBabysitterRole = computed(() => props.hasBabysitterRole ?? userRoles.value.includes('babysitter'));
</script>

<template>
    <div class="bg-secondary flex min-h-screen flex-col">
        <LandingHeader />

        <div class="flex flex-1">
            <UnifiedSidebar :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole" :requestedMode="props.currentMode" />

            <!-- Main content avec padding pour mobile -->
            <main class="flex-1 pb-20 lg:pb-0">
                <div class="px-4 py-6 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-7xl">
                        <slot />
                    </div>
                </div>
            </main>
        </div>

        <DashboardFooter />
    </div>
</template>
