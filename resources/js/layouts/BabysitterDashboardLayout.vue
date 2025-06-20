<script setup lang="ts">
import BabysitterDashboardContent from '@/components/dashboard/babysitter/BabysitterDashboardContent.vue';
import BabysitterSidebar from '@/components/dashboard/babysitter/BabysitterSidebar.vue';
import DashboardFooter from '@/components/dashboard/shared/DashboardFooter.vue';
import LandingHeader from '@/components/LandingHeader.vue';
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gray-50">
        <LandingHeader />

        <div class="flex flex-1">
            <BabysitterSidebar />

            <!-- Main content avec padding pour mobile -->
            <main class="flex-1 pb-20 lg:pb-0">
                <div class="py-6 px-4 sm:px-6 lg:px-8">
                    <div class="max-w-7xl mx-auto">
                        <BabysitterDashboardContent />
                    </div>
                </div>
            </main>
        </div>

        <DashboardFooter />
    </div>
</template>
