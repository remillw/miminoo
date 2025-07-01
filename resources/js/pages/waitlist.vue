<script setup lang="ts">
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';

const email = ref('');
const role = ref('parent');
const submitted = ref(false);

function submitForm() {
  submitted.value = true;
  // Ici tu peux ajouter l'appel API ou l'action d'inscription à la waitlist
}

function toggleRole() {
  role.value = role.value === 'parent' ? 'babysitter' : 'parent';
}

function handleSwitchChange(checked: boolean) {
  role.value = checked ? 'babysitter' : 'parent';
}

function openEmailClient() {
  window.open('mailto:contact@trouvebabysitter.com?subject=Inscription%20liste%20d\'attente', '_blank');
}
</script>

<template>
  <div class="bg-secondary min-h-screen pb-16">
    <div class="max-w-4xl mx-auto pt-16 pb-10 px-4 text-center">
      <Badge variant="secondary" class="mb-6 px-4 py-2 bg-primary-opacity text-primary border-primary/20">
        <Icon icon="lucide:sparkles" class="w-4 h-4 mr-2" />
        Bientôt disponible
      </Badge>
      
      <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-4 leading-tight">
        La révolution de la <span class="text-primary">garde d'enfants</span><br />arrive bientôt
      </h1>
      
      <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
        Trouve ta Babysitter transforme la façon dont les parents trouvent des babysitters de confiance. Soyez parmi les premiers à découvrir notre plateforme révolutionnaire.
      </p>

      <!-- FORMULAIRE AMÉLIORÉ -->
      <div class="max-w-2xl mx-auto mb-8">
        <Card class="border-0 shadow-2xl bg-white/95 backdrop-blur-sm">
          <CardContent class="p-8">
            <form @submit.prevent="submitForm" class="space-y-6">
              <!-- Input Email -->
              <div class="space-y-2">
                <Label for="email" class="text-left text-gray-700 font-medium">Adresse email</Label>
                <Input
                  id="email"
                  v-model="email"
                  type="email"
                  required
                  placeholder="votre.email@exemple.com"
                  class="h-12 text-lg border-2 focus:ring-4 focus:ring-primary/20"
                  :disabled="submitted"
                />
              </div>

              <!-- Switch Role -->
              <div class="space-y-4">
                <Label class="text-gray-700 font-medium">Je suis un(e)</Label>
                <div class="flex items-center justify-center space-x-4 p-4 bg-gray-50 rounded-xl">
                  <div class="flex items-center space-x-3">
                    <Icon 
                      icon="lucide:users" 
                      class="w-5 h-5 transition-colors duration-200"
                      :class="role === 'parent' ? 'text-primary' : 'text-gray-400'"
                    />
                    <span 
                      class="font-medium transition-colors duration-200"
                      :class="role === 'parent' ? 'text-primary' : 'text-gray-500'"
                    >
                      Parent
                    </span>
                  </div>
                  
                  <Switch 
                    :checked="role === 'babysitter'"
                    @update:checked="handleSwitchChange"
                    :disabled="submitted"
                    class="mx-4"
                  />
                  
                  <div class="flex items-center space-x-3">
                    <Icon 
                      icon="lucide:heart" 
                      class="w-5 h-5 transition-colors duration-200"
                      :class="role === 'babysitter' ? 'text-primary' : 'text-gray-400'"
                    />
                    <span 
                      class="font-medium transition-colors duration-200"
                      :class="role === 'babysitter' ? 'text-primary' : 'text-gray-500'"
                    >
                      Babysitter
                    </span>
                  </div>
                </div>
              </div>

              <!-- Bouton Submit -->
              <Button
                type="submit"
                size="lg"
                class="w-full h-14 text-lg font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300"
                :disabled="submitted"
                @click="openEmailClient"
              >
                <Icon 
                  v-if="!submitted" 
                  icon="lucide:user-plus" 
                  class="w-5 h-5 mr-2" 
                />
                <Icon 
                  v-else 
                  icon="lucide:check-circle" 
                  class="w-5 h-5 mr-2" 
                />
                {{ submitted ? 'Merci !' : "S'inscrire à la liste d'attente" }}
              </Button>
              <p class="text-gray-500 text-sm">Vous recevrez un email lorsque la plateforme sera disponible.</p>
            </form>

            <div v-if="submitted" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-xl">
              <div class="flex items-center text-green-700">
                <Icon icon="lucide:check-circle" class="w-5 h-5 mr-2" />
                <span class="font-medium">Parfait ! Tu es bien inscrit(e) à la liste d'attente.</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Stats -->
      <div class="flex flex-col md:flex-row justify-center gap-8 mt-8">
        <div class="flex flex-col items-center">
          <Icon icon="lucide:users" class="w-8 h-8 text-primary mb-2" />
          <span class="text-2xl font-bold text-gray-900">126</span>
          <span class="text-gray-500 text-sm">Familles en attente</span>
        </div>
        <div class="flex flex-col items-center">
          <Icon icon="lucide:heart" class="w-8 h-8 text-primary mb-2" />
          <span class="text-2xl font-bold text-gray-900">368</span>
          <span class="text-gray-500 text-sm">Babysitters inscrites</span>
        </div>
        <div class="flex flex-col items-center">
          <Icon icon="lucide:clock" class="w-8 h-8 text-primary mb-2" />
          <span class="text-2xl font-bold text-gray-900">15 min</span>
          <span class="text-gray-500 text-sm">Temps moyen de réponse</span>
        </div>
      </div>
    </div>

    <!-- Ce qui vous attend -->
    <div class="bg-white py-16">
      <div class="max-w-6xl mx-auto px-4">
        <h2 class="text-3xl md:text-4xl font-extrabold text-center mb-4">Ce qui vous attend</h2>
        <p class="text-center text-gray-500 mb-12">Découvrez les fonctionnalités qui vont révolutionner votre expérience de garde d'enfants</p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
          <Card class="group shadow-lg hover:shadow-2xl transition-all duration-500 border-0 bg-white overflow-hidden">
            <CardContent class="p-8 flex flex-col items-center text-center relative">
              <div class="bg-primary/5 rounded-2xl p-6 mb-6 group-hover:bg-primary/10 transition-colors duration-300">
                <Icon icon="lucide:zap" class="w-10 h-10 text-primary" />
              </div>
              <div class="font-bold text-xl mb-3 text-gray-900">Garde en urgence</div>
              <div class="text-gray-600 text-base leading-relaxed">Trouvez une babysitter en moins de 15 minutes grâce à notre système de notification instantané</div>
              <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-primary to-primary/50 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
            </CardContent>
          </Card>
          
          <Card class="group shadow-lg hover:shadow-2xl transition-all duration-500 border-0 bg-white overflow-hidden">
            <CardContent class="p-8 flex flex-col items-center text-center relative">
              <div class="bg-green-50 rounded-2xl p-6 mb-6 group-hover:bg-green-100 transition-colors duration-300">
                <Icon icon="lucide:shield-check" class="w-10 h-10 text-green-600" />
              </div>
              <div class="font-bold text-xl mb-3 text-gray-900">Profils vérifiés</div>
              <div class="text-gray-600 text-base leading-relaxed">Toutes nos babysitters sont vérifiées, certifiées et passent par un processus de validation rigoureux</div>
              <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-green-500 to-green-400 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
            </CardContent>
          </Card>
          
          <Card class="group shadow-lg hover:shadow-2xl transition-all duration-500 border-0 bg-white overflow-hidden">
            <CardContent class="p-8 flex flex-col items-center text-center relative">
              <div class="bg-amber-50 rounded-2xl p-6 mb-6 group-hover:bg-amber-100 transition-colors duration-300">
                <Icon icon="lucide:star" class="w-10 h-10 text-amber-500" />
              </div>
              <div class="font-bold text-xl mb-3 text-gray-900">Avis authentiques</div>
              <div class="text-gray-600 text-base leading-relaxed">Consultez les vrais avis des autres parents et prenez des décisions éclairées</div>
              <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-amber-500 to-amber-400 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
            </CardContent>
          </Card>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Pour les parents -->
          <Card class="group shadow-xl hover:shadow-2xl transition-all duration-500 border-0 bg-white overflow-hidden">
            <CardContent class="p-10 relative">
              <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full -translate-y-16 translate-x-16"></div>
              <div class="relative z-10">
                <div class="flex items-center mb-6">
                  <div class="bg-primary/10 rounded-xl p-3 mr-4">
                    <Icon icon="lucide:users" class="w-7 h-7 text-primary" />
                  </div>
                  <span class="font-bold text-2xl text-gray-900">Pour les parents</span>
                </div>
                <div class="mb-6 text-gray-600 text-lg">Trouvez la babysitter parfaite en quelques clics</div>
                <ul class="space-y-4">
                  <li class="flex items-center text-gray-700">
                    <div class="bg-primary/10 rounded-full p-1 mr-3">
                      <Icon icon="lucide:check" class="w-4 h-4 text-primary" />
                    </div>
                    <span class="font-medium">Recherche géolocalisée intelligente</span>
                  </li>
                  <li class="flex items-center text-gray-700">
                    <div class="bg-primary/10 rounded-full p-1 mr-3">
                      <Icon icon="lucide:check" class="w-4 h-4 text-primary" />
                    </div>
                    <span class="font-medium">Réservation en temps réel</span>
                  </li>
                  <li class="flex items-center text-gray-700">
                    <div class="bg-primary/10 rounded-full p-1 mr-3">
                      <Icon icon="lucide:check" class="w-4 h-4 text-primary" />
                    </div>
                    <span class="font-medium">Paiement sécurisé intégré</span>
                  </li>
                  <li class="flex items-center text-gray-700">
                    <div class="bg-primary/10 rounded-full p-1 mr-3">
                      <Icon icon="lucide:check" class="w-4 h-4 text-primary" />
                    </div>
                    <span class="font-medium">Système d'avis et de notation</span>
                  </li>
                </ul>
              </div>
            </CardContent>
          </Card>
          
          <!-- Pour les babysitters -->
          <Card class="group shadow-xl hover:shadow-2xl transition-all duration-500 border-0 bg-white overflow-hidden">
            <CardContent class="p-10 relative">
              <div class="absolute top-0 right-0 w-32 h-32 bg-pink-50 rounded-full -translate-y-16 translate-x-16"></div>
              <div class="relative z-10">
                <div class="flex items-center mb-6">
                  <div class="bg-pink-100 rounded-xl p-3 mr-4">
                    <Icon icon="lucide:heart" class="w-7 h-7 text-pink-600" />
                  </div>
                  <span class="font-bold text-2xl text-gray-900">Pour les babysitters</span>
                </div>
                <div class="mb-6 text-gray-600 text-lg">Développez votre activité et votre clientèle</div>
                <ul class="space-y-4">
                  <li class="flex items-center text-gray-700">
                    <div class="bg-pink-100 rounded-full p-1 mr-3">
                      <Icon icon="lucide:check" class="w-4 h-4 text-pink-600" />
                    </div>
                    <span class="font-medium">Profil professionnel détaillé</span>
                  </li>
                  <li class="flex items-center text-gray-700">
                    <div class="bg-pink-100 rounded-full p-1 mr-3">
                      <Icon icon="lucide:check" class="w-4 h-4 text-pink-600" />
                    </div>
                    <span class="font-medium">Gestion des disponibilités</span>
                  </li>
                  <li class="flex items-center text-gray-700">
                    <div class="bg-pink-100 rounded-full p-1 mr-3">
                      <Icon icon="lucide:check" class="w-4 h-4 text-pink-600" />
                    </div>
                    <span class="font-medium">Notifications de nouvelles offres</span>
                  </li>
                  <li class="flex items-center text-gray-700">
                    <div class="bg-pink-100 rounded-full p-1 mr-3">
                      <Icon icon="lucide:check" class="w-4 h-4 text-pink-600" />
                    </div>
                    <span class="font-medium">Suivi détaillé des revenus</span>
                  </li>
                </ul>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <!-- Section avantages exclusifs -->
    <div class=" ">
      <Card class="bg-primary text-white border-0 rounded">
        <CardContent class="py-16 px-6 md:px-16 text-center relative">
          <div class="absolute inset-0 opacity-50">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23ffffff%22 fill-opacity=%220.05%22%3E%3Ccircle cx=%2230%22 cy=%2230%22 r=%222%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
          </div>
          
          <div class="relative z-10">
            <Badge variant="secondary" class="mb-8 bg-white/10 text-white border-white/20 px-6 py-2">
              <Icon icon="lucide:crown" class="w-5 h-5 mr-2" />
              Avantages exclusifs
            </Badge>
            
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Pourquoi rejoindre la liste d'attente ?</h2>
            <p class="text-white/80 mb-12 text-lg max-w-2xl mx-auto">Rejoignez notre communauté VIP et bénéficiez d'avantages exclusifs</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
              <div class="group flex flex-col items-center text-center p-6">
                <div class="bg-white/10 rounded-2xl p-6 mb-6 group-hover:bg-white/20 transition-all duration-300 group-hover:scale-110">
                  <Icon icon="lucide:bell" class="w-10 h-10" />
                </div>
                <div class="font-bold text-xl mb-3">Accès prioritaire</div>
                <div class="text-white/80 leading-relaxed">Soyez parmi les premiers à utiliser notre plateforme révolutionnaire</div>
              </div>
              
              <div class="group flex flex-col items-center text-center p-6">
                <div class="bg-white/10 rounded-2xl p-6 mb-6 group-hover:bg-white/20 transition-all duration-300 group-hover:scale-110">
                  <Icon icon="lucide:gift" class="w-10 h-10" />
                </div>
                <div class="font-bold text-xl mb-3">Offres spéciales</div>
                <div class="text-white/80 leading-relaxed">Bénéficiez de tarifs préférentiels et d'offres exclusives au lancement</div>
              </div>
              
              <div class="group flex flex-col items-center text-center p-6">
                <div class="bg-white/10 rounded-2xl p-6 mb-6 group-hover:bg-white/20 transition-all duration-300 group-hover:scale-110">
                  <Icon icon="lucide:users" class="w-10 h-10" />
                </div>
                <div class="font-bold text-xl mb-3">Communauté VIP</div>
                <div class="text-white/80 leading-relaxed">Participez activement à l'évolution de la plateforme</div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>