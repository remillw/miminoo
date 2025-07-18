<script setup lang="ts">
import Babysitterlist from '@/components/Babysitterlist.vue';
import HeroSection from '@/components/HeroSection.vue';
import HowItWorksSection from '@/components/HowItWorksSection.vue';
import ReassuranceSection from '@/components/ReassuranceSection.vue';
import SectionReview from '@/components/SectionReview.vue';
import TrustSection from '@/components/TrustSection.vue';
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useCapacitor } from '@/composables/useCapacitor';
import { usePushNotifications } from '@/composables/usePushNotifications';
import { onMounted } from 'vue';

const { isCapacitor } = useCapacitor();
const { initializePushNotifications } = usePushNotifications();
const page = usePage();

onMounted(() => {
    // Plus besoin de gérer le fragment auth-success car nous redirigeons directement
    // Les notifications push sont maintenant initialisées dans le Dashboard
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
