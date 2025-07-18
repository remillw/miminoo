<script setup lang="ts">
import Babysitterlist from '@/components/Babysitterlist.vue';
import HeroSection from '@/components/HeroSection.vue';
import HowItWorksSection from '@/components/HowItWorksSection.vue';
import ReassuranceSection from '@/components/ReassuranceSection.vue';
import SectionReview from '@/components/SectionReview.vue';
import TrustSection from '@/components/TrustSection.vue';
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { useCapacitor } from '@/composables/useCapacitor';
import { onMounted } from 'vue';

const { isCapacitor } = useCapacitor();

onMounted(() => {
    // Gérer le fragment auth-success pour l'app mobile
    if (isCapacitor && window.location.hash === '#auth-success') {
        // Nettoyer l'URL
        window.history.replaceState({}, '', window.location.pathname);
        
        // Rediriger vers le tableau de bord
        router.visit('/tableau-de-bord');
    }
});
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
