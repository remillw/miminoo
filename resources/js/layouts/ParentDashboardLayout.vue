<script setup lang="ts">
import ParentDashboardContent from '@/components/dashboard/parent/ParentDashboardContent.vue';
import ParentSidebar from '@/components/dashboard/parent/ParentSidebar.vue';
import DashboardFooter from '@/components/dashboard/shared/DashboardFooter.vue';
import LandingHeader from '@/components/LandingHeader.vue';
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <LandingHeader />

        <div class="flex flex-1">
            <ParentSidebar />

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
