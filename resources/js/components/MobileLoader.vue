<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-white">
    <div class="flex flex-col items-center space-y-6">
      <!-- Logo animé -->
      <div class="relative">
        <img
          src="/storage/trouve-ta-babysitter-logo.svg"
          alt="Trouve ta Babysitter"
          class="h-24 w-auto animate-pulse"
        />
        <!-- Cercle de chargement autour du logo -->
        <div class="absolute -inset-4">
          <div class="animate-spin rounded-full border-4 border-gray-200 border-t-primary h-32 w-32"></div>
        </div>
      </div>
      
      <!-- Texte de chargement -->
      <div class="text-center">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">Trouve ta Babysitter</h2>
        <p class="text-gray-600 animate-pulse">Chargement...</p>
      </div>
      
      <!-- Points de chargement animés -->
      <div class="flex space-x-1">
        <div class="w-2 h-2 bg-primary rounded-full animate-bounce" style="animation-delay: 0ms"></div>
        <div class="w-2 h-2 bg-primary rounded-full animate-bounce" style="animation-delay: 150ms"></div>
        <div class="w-2 h-2 bg-primary rounded-full animate-bounce" style="animation-delay: 300ms"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';

interface Props {
  duration?: number;
}

const props = withDefaults(defineProps<Props>(), {
  duration: 2000
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
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.animate-float {
  animation: logoFloat 2s ease-in-out infinite;
}
</style>