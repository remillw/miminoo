<template>
    <section class="bg-white py-6 sm:py-8 md:py-10 relative overflow-hidden">
      <div class="max-w-6xl mx-auto relative z-10 px-4">
        <!-- Header Section -->
        <div class="text-center mb-8 sm:mb-12 md:mb-16">
          <!-- Badge -->
          <div ref="badge" class="inline-block mb-4 px-3 py-1.5 text-xs font-medium rounded-full bg-orange-100 text-primary border border-orange-200 opacity-0 translate-y-4 sm:mb-6 sm:px-4 sm:py-2 sm:text-sm">
            Pour les parents
          </div>
  
          <!-- Titre -->
          <h2 ref="title" class="text-2xl font-bold text-gray-900 mb-6 opacity-0 translate-y-8 sm:text-3xl md:text-4xl lg:text-5xl sm:mb-8">
            Trouvez une babysitter en <span class="text-primary">4 étapes simples</span>
          </h2>
        </div>
  
        <!-- Étapes -->
        <div class="space-y-12 sm:space-y-16 md:space-y-20">
          <!-- Étape 1 -->
          <div class="step-container opacity-0 translate-y-12" data-step="1">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 md:gap-12 items-center">
              <div class="order-2 lg:order-1">
                <div class="flex items-center gap-3 mb-4 sm:gap-4 sm:mb-6">
                  <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center sm:w-12 sm:h-12">
                    <svg class="w-5 h-5 text-primary sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </div>
                  <h3 class="text-xl font-bold text-gray-900 sm:text-2xl">1. Créez votre annonce</h3>
                </div>
                <p class="text-gray-600 text-base leading-relaxed sm:text-lg">
                  Indiquez vos besoins de garde : date, horaires, nombre d'enfants et leurs âges. Ajoutez des informations spécifiques comme les activités souhaitées ou les tâches à effectuer.
                </p>
              </div>
              <div class="order-1 lg:order-2">
                <div class="relative">
                  <div class="bg-white rounded-xl p-4 shadow-lg border border-gray-100 sm:rounded-2xl sm:p-6">
                    <!-- Formulaire simulé -->
                    <div class="space-y-3 sm:space-y-4">
                      <div class="flex items-center gap-2 p-2.5 bg-gray-50 rounded-lg sm:gap-3 sm:p-3">
                        <svg class="w-4 h-4 text-primary sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4V9a2 2 0 012-2h4a2 2 0 012 2v2m-6 4h8a2 2 0 002-2v-4a2 2 0 00-2-2h-8a2 2 0 00-2 2v4a2 2 0 002 2z" />
                        </svg>
                        <span class="text-xs text-gray-700 sm:text-sm">Mercredi 15 mars, 14h-18h</span>
                      </div>
                      <div class="flex items-center gap-2 p-2.5 bg-gray-50 rounded-lg sm:gap-3 sm:p-3">
                        <svg class="w-4 h-4 text-red-500 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.196-2.121M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.196-2.121M7 20v-2m5-8a3 3 0 11-6 0 3 3 0 016 0zm4 0a3 3 0 100-6 3 3 0 000 6z" />
                        </svg>
                        <span class="text-xs text-gray-700 sm:text-sm">2 enfants (3 et 5 ans)</span>
                      </div>
                      <div class="flex items-center gap-2 p-2.5 bg-gray-50 rounded-lg sm:gap-3 sm:p-3">
                        <svg class="w-4 h-4 text-blue-500 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="text-xs text-gray-700 sm:text-sm">Paris 16e</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
  
          <!-- Étape 2 -->
          <div class="step-container opacity-0 translate-y-12" data-step="2">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 md:gap-12 items-center">
              <div class="order-2 lg:order-2">
                <div class="flex items-center gap-3 mb-4 sm:gap-4 sm:mb-6">
                  <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center sm:w-12 sm:h-12">
                    <svg class="w-5 h-5 text-blue-600 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                  </div>
                  <h3 class="text-xl font-bold text-gray-900 sm:text-2xl">2. Recevez des candidatures</h3>
                </div>
                <p class="text-gray-600 text-base leading-relaxed sm:text-lg">
                  Les babysitters disponibles dans votre secteur reçoivent votre annonce. Consultez leurs profils détaillés, expériences et avis pour faire votre choix en toute confiance.
                </p>
              </div>
              <div class="order-1 lg:order-1">
                <div class="relative">
                  <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                    <!-- Profile simulé -->
                    <div class="flex items-start gap-3 sm:gap-4">
                      <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-pink-400 rounded-full flex items-center justify-center text-white font-semibold text-sm sm:w-12 sm:h-12">
                        CD
                      </div>
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                          <h4 class="font-semibold text-gray-900 text-sm sm:text-base">Camille D.</h4>
                          <div class="flex items-center gap-1">
                            <svg class="w-3 h-3 text-yellow-400 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <span class="text-xs text-gray-600 sm:text-sm">4.9 (56 avis)</span>
                          </div>
                        </div>
                        <ul class="text-xs text-gray-600 space-y-1 mb-3 sm:text-sm">
                          <li>• 5 ans d'expérience</li>
                          <li>• Diplôme premiers secours</li>
                          <li>• Disponible mercredi après-midi</li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
  
          <!-- Étape 3 -->
          <div class="step-container opacity-0 translate-y-12" data-step="3">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 md:gap-12 items-center">
              <div class="order-2 lg:order-1">
                <div class="flex items-center gap-3 mb-4 sm:gap-4 sm:mb-6">
                  <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center sm:w-12 sm:h-12">
                    <svg class="w-5 h-5 text-green-600 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                  </div>
                  <h3 class="text-xl font-bold text-gray-900 sm:text-2xl">3. Échangez et confirmez</h3>
                </div>
                <p class="text-gray-600 text-base leading-relaxed sm:text-lg">
                  Discutez avec la babysitter via notre messagerie sécurisée. Précisez vos attentes, répondez à ses questions et confirmez la réservation une fois que vous êtes d'accord.
                </p>
              </div>
              <div class="order-1 lg:order-2">
                <div class="relative">
                  <div class="bg-white rounded-xl p-4 shadow-lg border border-gray-100 sm:rounded-2xl sm:p-6">
                    <!-- Messages simulés -->
                    <div class="space-y-3 sm:space-y-4">
                      <div class="bg-secondary p-2.5 rounded-2xl rounded-bl-sm border-l-4 border-orange-400 sm:p-3">
                        <p class="text-xs text-gray-800 font-medium mb-1 sm:text-sm">Bonjour ! Je suis intéressée par votre annonce. Je suis disponible mercredi.</p>
                      </div>
                      <div class="bg-gray-50 p-2.5 rounded-2xl rounded-br-sm ml-6 sm:p-3 sm:ml-8">
                        <p class="text-xs text-gray-700 sm:text-sm">Super ! Pouvez-vous me parler de votre expérience avec les enfants ?</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
  
          <!-- Étape 4 -->
          <div class="step-container opacity-0 translate-y-12 mb-12 sm:mb-16 md:mb-20" data-step="4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 md:gap-12 items-center">
              <div class="order-2 lg:order-2">
                <div class="flex items-center gap-3 mb-4 sm:gap-4 sm:mb-6">
                  <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center sm:w-12 sm:h-12">
                    <svg class="w-5 h-5 sm:w-7 sm:h-7"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
	<path fill="#CF7A00" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 18.5A6.48 6.48 0 0 1 9.24 15H15l1-2H8.58c-.05-.33-.08-.66-.08-1s.03-.67.08-1H15l1-2H9.24A6.49 6.49 0 0 1 15 5.5c1.61 0 3.09.59 4.23 1.57L21 5.3A8.96 8.96 0 0 0 15 3c-3.92 0-7.24 2.51-8.48 6H3l-1 2h4.06a8.3 8.3 0 0 0 0 2H3l-1 2h4.52c1.24 3.49 4.56 6 8.48 6c2.31 0 4.41-.87 6-2.3l-1.78-1.77c-1.13.98-2.6 1.57-4.22 1.57" />
</svg>
                  </div>
                  <h3 class="text-xl font-bold text-gray-900 sm:text-2xl">4. Payez en toute sécurité</h3>
                </div>
                <p class="text-gray-600 text-base leading-relaxed sm:text-lg">
                  Le paiement s'effectue en ligne de manière sécurisée après la garde. Vous pouvez ensuite laisser un avis pour partager votre expérience avec la communauté.
                </p>
              </div>
              <div class="order-1 lg:order-1">
                <div class="relative">
                  <div class="bg-white rounded-xl p-4 shadow-lg border border-gray-100 sm:rounded-2xl sm:p-6">
                    <!-- Facture simulée -->
                    <div class="space-y-3 sm:space-y-4">
                      <div class="flex justify-between items-center py-2">
                        <span class="text-gray-700 text-sm sm:text-base">4 heures de garde</span>
                        <span class="font-semibold text-gray-900 text-sm sm:text-base">60€</span>
                      </div>
                      <div class="flex justify-between items-center py-2 border-t border-gray-100">
                        <span class="text-gray-700 text-sm sm:text-base">Frais de service</span>
                        <span class="font-semibold text-gray-900 text-sm sm:text-base">2€</span>
                      </div>
                      <div class="flex justify-between items-center py-2 border-t-2 border-gray-200">
                        <span class="font-bold text-gray-900 text-sm sm:text-base">Total</span>
                        <span class="font-bold text-primary text-base sm:text-lg">65€</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
      </template>
  
  <script setup>
  import { onMounted, ref, nextTick } from 'vue'

  const badge = ref(null)
  const title = ref(null)
  const ctaSection = ref(null)
  
  onMounted(async () => {
    await nextTick()
  
    // Animation du header
    setTimeout(() => {
      if (badge.value) {
        badge.value.style.opacity = '1'
        badge.value.style.transform = 'translateY(0)'
      }
    }, 200)
  
    setTimeout(() => {
      if (title.value) {
        title.value.style.opacity = '1'
        title.value.style.transform = 'translateY(0)'
      }
    }, 400)
  
    // Options pour l'Observer
    const observerOptions = {
      threshold: 0.2,
      rootMargin: '0px 0px -100px 0px'
    }
  
    // Observer pour les étapes
    const stepObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const step = parseInt(entry.target.dataset.step)
          const delay = step * 200
  
          setTimeout(() => {
            entry.target.style.opacity = '1'
            entry.target.style.transform = 'translateY(0)'
            entry.target.classList.add('animate-in')
          }, delay)
  
          stepObserver.unobserve(entry.target)
        }
      })
    }, observerOptions)
  
    // Observer pour le CTA
    const ctaObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.style.opacity = '1'
            entry.target.style.transform = 'translateY(0)'
          }, 300)
          ctaObserver.unobserve(entry.target)
        }
      })
    }, observerOptions)
  
    // Observer les étapes
    const stepContainers = document.querySelectorAll('.step-container')
    stepContainers.forEach(container => {
      stepObserver.observe(container)
    })
  
    // Observer le CTA
    if (ctaSection.value) {
      ctaObserver.observe(ctaSection.value)
    }
  })
  </script>
  
  <style scoped>
  .step-container {
    transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  }
  
  .step-container.animate-in {
    animation: slideInUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
  }
  
  @keyframes slideInUp {
    0% {
      opacity: 0;
      transform: translateY(60px);
    }
    100% {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  /* Transitions des éléments header */
  [ref="badge"], [ref="title"], [ref="ctaSection"] {
    transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  }
  
  /* Hover effects */
  .step-container:hover .bg-white {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  }
  
  .bg-white {
    transition: all 0.3s ease;
  }
  
  /* Responsive adjustments */
  @media (max-width: 1024px) {
    .step-container .grid {
      gap: 2rem;
    }
    
    .step-container .order-1,
    .step-container .order-2 {
      order: unset !important;
    }
  }
  </style>