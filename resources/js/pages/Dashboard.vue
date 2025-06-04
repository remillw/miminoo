<script setup lang="ts">
import ParentDashboardContent from '@/components/dashboard/parent/ParentDashboardContent.vue';
import ParentSidebar from '@/components/dashboard/parent/ParentSidebar.vue';
import DashboardFooter from '@/components/dashboard/shared/DashboardFooter.vue';
import DashboardHeader from '@/components/dashboard/shared/DashboardHeader.vue';
</script>

<template>
    <div class="flex min-h-screen flex-col bg-[#fcf8f6]">
        <DashboardHeader />
        <div class="flex flex-1">
            <ParentSidebar />
            <main class="flex-1 p-6">
                <ParentDashboardContent />
            </main>
        </div>
        <DashboardFooter />
    </div>
</template>
