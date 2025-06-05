<script setup>
import EtapesParents from '../components/EtapesParents.vue';
import Etapesbabysitter from '../components/Etapesbabysitter.vue';
import ReassuranceSection from '../components/ReassuranceSection.vue';
</script>

<template>
    <div class="bg-orange-50 flex items-center justify-center px-4 py-10">
      <div class="max-w-4xl w-full text-center">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
          Comment fonctionne Miminoo ?
        </h1>
  
        <p class="text-lg md:text-xl text-gray-600 mb-12 max-w-2xl mx-auto leading-relaxed">
          Découvrez comment Miminoo vous aide à trouver la babysitter idéale
          en toute confiance, que ce soit pour une garde d'urgence ou planifiée.
        </p>
  
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 relative">
          <div class="relative order-1">
            <button class="bg-primary text-white px-8 py-3 rounded-lg font-medium text-lg hover:bg-orange-600 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
              Créer une annonce
            </button>
  
            <div class="absolute -top-3 -right-3 z-10">
              <div class="relative">
                <div class="bg-gradient-to-r from-emerald-500 to-teal-500 text-white px-2.5 py-1 rounded-full text-xs font-medium shadow-md animate-float animate-shimmer border border-white">
                  Sans inscription
                </div>
              </div>
            </div>
          </div>
  
          <button class="border border-primary text-primary px-8 py-3 rounded-lg font-medium text-lg hover:bg-primary hover:text-white transition-colors order-2">
            Voir les annonces en cours
          </button>
        </div>
      </div>
    </div>
    <EtapesParents />
    <Etapesbabysitter />
    <ReassuranceSection />
</template>
  
<style scoped>
/* Animation par box-shadow au lieu de transform pour éviter le flou */
@keyframes floatShadow {
  0%, 100% { 
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  }
  50% { 
    box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.15), 0 4px 6px -2px rgba(0, 0, 0, 0.1);
  }
}

@keyframes softGlow {
  0%, 100% { 
    box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.3);
  }
  50% { 
    box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.2);
  }
}

@keyframes shimmer {
  0% { 
    background-position: -200% center;
  }
  100% { 
    background-position: 200% center;
  }
}

/* Classes d'animation sans transform */
.animate-float {
  animation: floatShadow 4s ease-in-out infinite;
}

.animate-softPulse {
  animation: softGlow 3s ease-in-out infinite;
}

.animate-shimmer {
  background: linear-gradient(90deg, 
    rgba(16, 185, 129, 1) 0%, 
    rgba(34, 197, 94, 1) 25%,
    rgba(20, 184, 166, 1) 50%,
    rgba(34, 197, 94, 1) 75%,
    rgba(16, 185, 129, 1) 100%
  );
  background-size: 200% 100%;
  animation: shimmer 3s ease-in-out infinite;
}
</style>