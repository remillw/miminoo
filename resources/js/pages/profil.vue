<script setup lang="ts">
import DashboardHeader from '@/components/dashboard/shared/DashboardHeader.vue'
import DashboardFooter from '@/components/dashboard/shared/DashboardFooter.vue'
import ParentSidebar from '@/components/dashboard/parent/ParentSidebar.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Mail, Phone, MapPin, Pencil } from 'lucide-vue-next'
</script>

<template>
  <div class="flex min-h-screen flex-col bg-[#fcf8f6]">
    <DashboardHeader />

    <div class="flex flex-1">
      <ParentSidebar />

      <main class="flex-1 p-6">
        <div class="max-w-4xl mx-auto">
          <!-- Titre -->
          <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Mon profil</h1>
            <p class="text-gray-500">Gérez vos informations personnelles</p>
          </div>

          <!-- Header avatar -->
          <div class="relative rounded-xl bg-gradient-to-r from-orange-100 to-orange-200 p-6 mb-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div class="relative">
                <img src="/storage/babysitter-test.png" class="h-20 w-20 rounded-full object-cover border-4 border-white" />
                <div class="absolute bottom-0 right-0 bg-white rounded-full p-1 shadow">
                  <Pencil class="h-4 w-4 text-gray-500" />
                </div>
              </div>
              <div>
                <h2 class="text-xl font-semibold text-gray-800">Sophie Martin</h2>
                <p class="text-gray-600 text-sm">Parent de 2 enfants</p>
              </div>
            </div>
            <button class="bg-primary text-white font-semibold px-4 py-2 rounded-md hover:bg-orange-500">
              Modifier
            </button>
          </div>

          <!-- Formulaire -->
          <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label for="prenom">Prénom</Label>
                <Input id="prenom" type="text" model-value="Sophie" disabled />
              </div>
              <div>
                <Label for="nom">Nom</Label>
                <Input id="nom" type="text" model-value="Martin" disabled />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label for="email">Email</Label>
                <div class="relative">
                  <Mail class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
                  <Input id="email" type="email" model-value="sophie.martin@gmail.com" class="pl-10" disabled />
                </div>
              </div>
              <div>
                <Label for="phone">Téléphone</Label>
                <div class="relative">
                  <Phone class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
                  <Input id="phone" type="tel" model-value="06 12 56 43 78" class="pl-10" disabled />
                </div>
              </div>
            </div>

            <div>
              <Label for="adresse">Adresse</Label>
              <div class="relative">
                <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
                <Input id="adresse" type="text" model-value="15 rue des Lilas, 75016 Paris" class="pl-10" disabled />
              </div>
            </div>

            <!-- Enfants -->
            <div>
              <h3 class="text-md font-medium text-gray-700 mb-2">Enfants</h3>
              <div class="space-y-3">
                <div class="flex items-center gap-2">
                  <Input type="text" model-value="Lucas" disabled />
                  <Input type="text" model-value="5" class="w-16 text-center" disabled />
                  <span class="text-sm text-gray-500">ans</span>
                </div>
                <div class="flex items-center gap-2">
                  <Input type="text" model-value="Emma" disabled />
                  <Input type="text" model-value="8" class="w-16 text-center" disabled />
                  <span class="text-sm text-gray-500">mois</span>
                </div>
              </div>
            </div>

            <!-- Vérification -->
            <div class="rounded-xl bg-green-50 border border-green-200 text-green-800 p-4 text-sm flex items-center gap-2">
              <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5 13l4 4L19 7" />
              </svg>
              <div>
                <p class="font-semibold">Vérification d'identité</p>
                <p>Votre identité a été vérifiée le <strong>10 mars 2024</strong></p>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <DashboardFooter />
  </div>
</template>
