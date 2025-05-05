<script setup lang="ts">
import HeroSection from '@/components/HeroSection.vue';
import HowItWorksSection from '@/components/HowItWorksSection.vue';
import LandingHeader from '@/components/LandingHeader.vue';
import TrustSection from '@/components/TrustSection.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Accueil" />
    <LandingHeader />
    <HeroSection />
    <HowItWorksSection />
    <TrustSection />
</template>
