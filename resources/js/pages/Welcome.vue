<script setup lang="ts">
import Babysitterlist from '@/components/Babysitterlist.vue';
import HeroSection from '@/components/HeroSection.vue';
import HowItWorksSection from '@/components/HowItWorksSection.vue';
import ReassuranceSection from '@/components/ReassuranceSection.vue';
import SectionReview from '@/components/SectionReview.vue';
import TrustSection from '@/components/TrustSection.vue';
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <GlobalLayout>
        <Head title="Accueil" />
        <HeroSection />
        <HowItWorksSection />
        <TrustSection />
        <Babysitterlist />
        <SectionReview />
        <ReassuranceSection />
    </GlobalLayout>
</template>
