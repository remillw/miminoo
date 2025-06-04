<script setup lang="ts">
import HeroSection from '@/components/HeroSection.vue';
import HowItWorksSection from '@/components/HowItWorksSection.vue';
import TrustSection from '@/components/TrustSection.vue';
import Babysitterlist from '@/components/Babysitterlist.vue';
import { Head } from '@inertiajs/vue3';
import SectionReview from '@/components/SectionReview.vue';
import ReassuranceSection from '@/components/ReassuranceSection.vue';

</script>

<template>
    <Head title="Accueil" />
    <HeroSection />
    <HowItWorksSection />
    <TrustSection />
    <Babysitterlist />
    <SectionReview />
    <ReassuranceSection />
</template>
