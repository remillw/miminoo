<template>
    <!-- Logo seul pour mobile et desktop -->
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-white">
        <img src="/storage/trouve-ta-babysitter-logo.svg" alt="Trouve ta Babysitter" class="h-24 w-auto" />
    </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';

interface Props {
    duration?: number;
}

const props = withDefaults(defineProps<Props>(), {
    duration: 1500,
});

const emit = defineEmits<{
    loaded: [];
}>();

onMounted(() => {
    // Simuler un temps de chargement minimum
    setTimeout(() => {
        emit('loaded');
    }, props.duration);
});
</script>

<style scoped>
/* Animation personnalisée pour le logo */
@keyframes logoFloat {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

.animate-float {
    animation: logoFloat 2s ease-in-out infinite;
}
</style>
