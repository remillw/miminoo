<script setup lang="ts">
import LandingHeader from '@/components/LandingHeader.vue';
import Footer from '@/components/Footer.vue';   
</script>

<template>
    <div class="flex min-h-screen flex-col bg-orange-50">
        <LandingHeader />
        <div class="flex flex-1">
            <main class="flex-1">
                <slot />
            </main>
        </div>
        <Footer />
    </div>
</template>
