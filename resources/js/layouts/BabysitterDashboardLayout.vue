<script setup lang="ts">
import BabysitterDashboardContent from '@/components/dashboard/babysitter/BabysitterDashboardContent.vue';
import BabysitterSidebar from '@/components/dashboard/babysitter/BabysitterSidebar.vue';
import DashboardFooter from '@/components/dashboard/shared/DashboardFooter.vue';
import DashboardHeader from '@/components/dashboard/shared/DashboardHeader.vue';
</script>

<template>
    <div class="flex min-h-screen flex-col bg-[#fcf8f6]">
        <DashboardHeader />
        <div class="flex flex-1">
            <BabysitterSidebar />
            <main class="flex-1 p-6">
                <BabysitterDashboardContent />
            </main>
        </div>
        <DashboardFooter />
    </div>
</template>
