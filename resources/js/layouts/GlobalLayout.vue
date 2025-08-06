<script setup lang="ts">
import Footer from '@/components/Footer.vue';
import LandingHeader from '@/components/LandingHeader.vue';
</script>

<template>
    <div class="bg-secondary flex min-h-screen flex-col">
        <LandingHeader />
        <div class="flex flex-1">
            <main class="flex-1">
                <slot />
            </main>
        </div>
        <Footer />
    </div>
</template>
