<script setup lang="ts">
import HeroSection from '@/components/HeroSection.vue';
import HowItWorksSection from '@/components/HowItWorksSection.vue';
import LandingHeader from '@/components/LandingHeader.vue';
import TrustSection from '@/components/TrustSection.vue';
import Babysitterlist from '@/components/Babysitterlist.vue';
import { Head } from '@inertiajs/vue3';
import SectionReview from '@/components/SectionReview.vue';
</script>

<template>
    <Head title="Accueil" />
    <LandingHeader />
    <HeroSection />
    <HowItWorksSection />
    <TrustSection />
    <Babysitterlist />
    <SectionReview />
</template>
