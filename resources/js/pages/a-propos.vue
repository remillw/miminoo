<script setup lang="ts">
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Icon } from '@iconify/vue';
import { onMounted, ref } from 'vue';

// Animation de la timeline
const timelineRef = ref<HTMLElement>();
const timelineProgress = ref(0);

onMounted(() => {
  const handleScroll = () => {
    if (!timelineRef.value) return;
    
    const timelineElement = timelineRef.value;
    const rect = timelineElement.getBoundingClientRect();
    const windowHeight = window.innerHeight;
    
    // Calculer le pourcentage de progression basé sur le scroll
    const elementTop = rect.top;
    const elementHeight = rect.height;
    
    // Commencer l'animation quand l'élément entre dans la vue
    if (elementTop < windowHeight && elementTop + elementHeight > 0) {
      const visibleHeight = Math.min(windowHeight - elementTop, elementHeight);
      const progress = Math.max(0, Math.min(100, (visibleHeight / elementHeight) * 100));
      timelineProgress.value = progress;
    }
  };

  window.addEventListener('scroll', handleScroll);
  handleScroll(); // Appel initial
  
  return () => {
    window.removeEventListener('scroll', handleScroll);
  };
});
</script>

<template>
  <GlobalLayout>
    <!-- Hero Section -->
    <section class="bg-secondary py-20">
      <div class="max-w-6xl mx-auto px-4 text-center">
        <div class="inline-block bg-primary-opacity text-primary px-4 py-2 rounded-full text-sm font-medium mb-6">
          Notre histoire
        </div>
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6 leading-tight">
          Derrière la plateforme, une mission simple : <br>
          <span class="text-primary">faciliter la garde d'enfants</span> partout en France
        </h1>
        <p class="text-lg text-gray-600 max-w-4xl mx-auto leading-relaxed">
          Chez Trouve ta Babysitter, on croit qu'il ne devrait pas être compliqué de trouver une 
          personne de confiance pour garder ses enfants. C'est pourquoi on a imaginé une 
          plateforme simple, rapide, et surtout humaine.
        </p>
      </div>
    </section>

    <!-- Section Histoire -->
    <section class="py-20 bg-white">
      <div class="max-w-6xl mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-16 items-center">
          <div>
            <h2 class="text-3xl font-bold text-gray-900 mb-6">
              Une idée née autour d'un dîner... et d'une galère
            </h2>
            <div class="space-y-6 text-gray-600">
              <p>
                C'était un soir d'été, autour d'un plat de pâtes, 
                que tout a commencé. Julie, maman solo de 
                deux enfants, venait de se faire planter par sa 
                babysitter pour la troisième fois en un mois. 
                Elle avait une soirée importante au travail... et 
                aucune solution de repli.
              </p>
              <p>
                Face à elle, son frère Lucas, développeur web, 
                l'écoute râler – encore. Il lui lance en souriant :
              </p>
              <blockquote class="border-l-4 border-primary pl-4 italic text-primary">
                « Et si je te faisais une appli pour trouver 
                une babysitter fiable en 15 minutes chrono ? »
              </blockquote>
              <p>
                Julie rit, mais l'idée fait son chemin...
              </p>
            </div>
          </div>
          <div class="relative">
            <img 
              src="/storage/trouve-ta-babysitter-equipe.png" 
              alt="Equipe Trouve ta Babysitter" 
              class="rounded-2xl w-full"
            >
          </div>
        </div>

        <!-- Le déclic -->
        <div class="mt-20 bg-secondary rounded-2xl p-8 md:p-12">
          <h3 class="text-2xl font-bold text-gray-900 mb-6">Le déclic</h3>
          <div class="text-gray-600 leading-relaxed">
            <p>
              Quelques jours plus tard, ils commencent à en 
              parler autour d'eux. Et là, surprise : les parents ET 
              les babysitters galèrent. Entre les groupes 
              Facebook pas toujours rassurants, les 
              plateformes floues avec un abonnement, ou les appels de dernière 
              minute... personne n'est vraiment satisfait.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Équipe -->
    <section class="py-20 bg-secondary">
      <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-16">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Le début de Trouve ta Babysitter</h2>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8 mb-16">
          <!-- Julie -->
          <div class="text-center">
            <div class="w-24 h-24 mx-auto mb-4 rounded-full overflow-hidden">
              <img src="/storage/julie-trouve-ta-babysitter-ceo.png" alt="Julie" class="w-full h-full object-cover">
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Julie</h3>
            <p class="text-primary font-medium mb-2">Co-fondatrice</p>
            <p class="text-gray-600 text-sm">
              Maman et experte UX, elle comprend 
              les besoins des familles
            </p>
          </div>

          <!-- Lucas -->
          <div class="text-center">
            <div class="w-24 h-24 mx-auto mb-4 rounded-full overflow-hidden">
              <img src="/storage/lucas-trouve-ta-babysitter-cto.png" alt="Lucas" class="w-full h-full object-cover">
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Lucas</h3>
            <p class="text-primary font-medium mb-2">Co-fondateur & CTO</p>
            <p class="text-gray-600 text-sm">
              Développeur passionné, il donne vie à 
              la plateforme
            </p>
          </div>

          <!-- Sarah -->
          <div class="text-center">
            <div class="w-24 h-24 mx-auto mb-4 rounded-full overflow-hidden">
              <img src="/storage/sarah-trouve-ta-babysitter-commu.png" alt="Sarah" class="w-full h-full object-cover">
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Sarah</h3>
            <p class="text-primary font-medium mb-2">Responsable Communauté</p>
            <p class="text-gray-600 text-sm">
              Éducatrice de formation, elle veille à la 
              qualité des profils
            </p>
          </div>
        </div>

        <div class="text-center max-w-4xl mx-auto">
          <p class="text-gray-600 leading-relaxed">
            Avec Sarah, une amie éducatrice passionnée par la petite enfance, ils se lancent : créer une plateforme 
            simple, humaine et efficace pour mettre en relation les familles et des babysitters de confiance. Ils 
            bossent le soir, les week-ends, entre deux couches ou deux réunions. Et après plusieurs mois de travail 
            acharné, la plateforme voit le jour.
          </p>
        </div>
      </div>
    </section>

    <!-- Section Valeurs -->
    <section class="py-20 bg-white">
      <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-16">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Nos valeurs</h2>
          <p class="text-gray-600">Ce qui nous guide au quotidien pour créer la meilleure expérience possible</p>
        </div>

        <div class="grid md:grid-cols-4 gap-8">
          <!-- Confiance -->
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-orange-100 rounded-full flex items-center justify-center">
              <Icon icon="lucide:shield-check" class="w-8 h-8 text-primary" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Confiance</h3>
            <p class="text-gray-600 text-sm">
              Profils vérifiés, avis 
              authentiques et processus 
              de sélection rigoureux
            </p>
          </div>

          <!-- Rapidité -->
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-orange-100 rounded-full flex items-center justify-center">
              <Icon icon="lucide:clock" class="w-8 h-8 text-primary" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Rapidité</h3>
            <p class="text-gray-600 text-sm">
              Trouvez une babysitter en 
              quelques minutes, même en 
              urgence
            </p>
          </div>

          <!-- Liberté -->
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-orange-100 rounded-full flex items-center justify-center">
              <Icon icon="lucide:user" class="w-8 h-8 text-primary" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Liberté</h3>
            <p class="text-gray-600 text-sm">
              Chaque babysitter choisit 
              ses disponibilités et ses 
              conditions
            </p>
          </div>

          <!-- Bienveillance -->
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-orange-100 rounded-full flex items-center justify-center">
              <Icon icon="lucide:heart" class="w-8 h-8 text-primary" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Bienveillance</h3>
            <p class="text-gray-600 text-sm">
              Une communauté 
              respectueuse qui met 
              l'humain au centre
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Timeline -->
    <section class="py-20 bg-secondary" ref="timelineRef">
      <div class="max-w-4xl mx-auto px-4">
        <div class="text-center mb-16">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Notre parcours</h2>
          <p class="text-gray-600">De l'idée à la réalité, retour sur les étapes clés de Trouve ta Babysitter</p>
        </div>

        <div class="relative">
          <!-- Ligne de timeline animée -->
          <div class="absolute left-1/2 transform -translate-x-1/2 w-1 bg-gray-200 h-full">
            <div 
              class="w-full bg-primary transition-all duration-500 ease-out"
              :style="{ height: `${timelineProgress}%` }"
            ></div>
          </div>

          <!-- Événements timeline -->
          <div class="space-y-16">
            <!-- 2023 - L'idée naît -->
            <div class="relative flex items-center">
              <div class="flex-1 pr-8 text-right">
                <Card class="bg-white shadow-lg">
                  <CardContent class="p-6">
                    <div class="text-primary font-bold text-sm mb-2">2023</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">L'idée naît</h3>
                    <p class="text-gray-600">
                      Autour d'un dîner, face à une énième galère de 
                      babysitting
                    </p>
                  </CardContent>
                </Card>
              </div>
              <div class="absolute left-1/2 transform -translate-x-1/2 w-4 h-4 bg-primary rounded-full border-4 border-white shadow-lg z-10"></div>
              <div class="flex-1 pl-8"></div>
            </div>

            <!-- 2024 - Développement -->
            <div class="relative flex items-center">
              <div class="flex-1 pr-8"></div>
              <div class="absolute left-1/2 transform -translate-x-1/2 w-4 h-4 bg-primary rounded-full border-4 border-white shadow-lg z-10"></div>
              <div class="flex-1 pl-8">
                <Card class="bg-white shadow-lg">
                  <CardContent class="p-6">
                    <div class="text-primary font-bold text-sm mb-2">2024</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Développement</h3>
                    <p class="text-gray-600">
                      Création de la plateforme avec une équipe 
                      passionnée
                    </p>
                  </CardContent>
                </Card>
              </div>
            </div>

            <!-- Aujourd'hui - Communauté grandissante -->
            <div class="relative flex items-center">
              <div class="flex-1 pr-8 text-right">
                <Card class="bg-white shadow-lg">
                  <CardContent class="p-6">
                    <div class="text-primary font-bold text-sm mb-2">Aujourd'hui</div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Communauté grandissante</h3>
                    <p class="text-gray-600">
                      Des centaines de familles et babysitters nous 
                      font confiance
                    </p>
                  </CardContent>
                </Card>
              </div>
              <div class="absolute left-1/2 transform -translate-x-1/2 w-4 h-4 bg-primary rounded-full border-4 border-white shadow-lg z-10"></div>
              <div class="flex-1 pl-8"></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Statistiques -->
    <section class="py-20 bg-primary text-white">
      <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold mb-4">Aujourd'hui, trouve ta Babysitter c'est...</h2>
          <p class="text-orange-100">Une communauté bienveillante qui grandit chaque jour</p>
        </div>

        <div class="grid md:grid-cols-4 gap-8 text-center">
          <div>
            <div class="text-4xl font-bold mb-2">200+</div>
            <div class="text-orange-100">Familles satisfaites</div>
          </div>
          <div>
            <div class="text-4xl font-bold mb-2">700+</div>
            <div class="text-orange-100">Babysitters vérifiées</div>
          </div>
          <div>
            <div class="text-4xl font-bold mb-2">900+</div>
            <div class="text-orange-100">Gardes réalisées</div>
          </div>
          <div>
            <div class="text-4xl font-bold mb-2">4.9/5</div>
            <div class="text-orange-100">Note moyenne</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Mission -->
    <section class="py-20 bg-white">
      <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-16">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Notre mission</h2>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <!-- Créer une plateforme de confiance -->
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-orange-100 rounded-full flex items-center justify-center">
              <Icon icon="lucide:shield" class="w-8 h-8 text-primary" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-4">Créer une plateforme de confiance</h3>
            <p class="text-gray-600">
              Qui respecte les familles 
              comme les babysitters, avec 
              des profils vérifiés et des avis 
              authentiques.
            </p>
          </div>

          <!-- Simplifier la vie de tous -->
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
              <Icon icon="lucide:lightbulb" class="w-8 h-8 text-blue-600" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-4">Simplifier la vie de tous</h3>
            <p class="text-gray-600">
              Avec un outil clair, mobile-
              first et sans prise de tête, 
              accessible à tous.
            </p>
          </div>

          <!-- Mettre l'humain au centre -->
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
              <Icon icon="lucide:heart" class="w-8 h-8 text-green-600" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-4">Mettre l'humain au centre</h3>
            <p class="text-gray-600">
              Dans un monde qui va 
              souvent trop vite, nous 
              privilégions les relations 
              humaines.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Section CTA Final -->
    <section class="py-20 bg-secondary">
      <div class="max-w-4xl mx-auto px-4 text-center">
        <div class="inline-flex items-center bg-primary-opacity px-4 py-2 rounded-full text-primary mb-6">
          <Icon icon="lucide:heart" class="w-5 h-5 mr-2" />
          <span class="text-sm font-medium">Rejoignez l'aventure</span>
        </div>
        
        <h2 class="text-4xl font-bold text-gray-900 mb-6">
          Bienvenue chez Trouvez ta Babysitter ❤️
        </h2>
        
        <div class="space-y-2 mb-8">
          <p class="text-lg text-gray-600">
            Si vous êtes babysitter, il est temps de valoriser votre expérience.
          </p>
          <p class="text-lg text-gray-600">
            Si vous êtes parent, on est là pour vous aider à souffler un peu.
          </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
  <a href="/inscription" class="bg-primary text-white px-8 py-3 rounded-lg font-medium hover:bg-orange-600 transition-colors">
    Devenir babysitter →
  </a>
  <a href="/annonces" class="border-2 border-primary text-primary px-8 py-3 rounded-lg font-medium hover:bg-orange-50 transition-colors">
    Trouver une babysitter
  </a>
</div>

      </div>
    </section>
  </GlobalLayout>
</template>
