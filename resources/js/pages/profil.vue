<script setup lang="ts">
import DashboardHeader from '@/components/dashboard/shared/DashboardHeader.vue'
import DashboardFooter from '@/components/dashboard/shared/DashboardFooter.vue'
import ParentSidebar from '@/components/dashboard/parent/ParentSidebar.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Mail, Phone, MapPin, Pencil } from 'lucide-vue-next'
import { Camera } from 'lucide-vue-next';
import { ref } from 'vue';

const removeChild = (index: number) => {
  form.value.enfants.splice(index, 1)
}

const addChild = () => {
  form.value.enfants.push({ nom: '', age: '', unite: 'ans' })
}


const form = ref({
  prenom: 'Sophie',
  nom: 'Martin',
  email: 'sophie.martin@gmail.com',
  phone: '06 12 56 43 78',
  adresse: '15 rue des Lilas, 75016 Paris',
  enfants: [
    { nom: 'Lucas', age: '5', unite: 'ans' },
    { nom: 'Emma', age: '8', unite: 'mois' }
  ]
})
const submitForm = () => {
  // TODO : Appel API ou form.post() via Inertia
  isEditing.value = false
}


const isEditing = ref(false)

const toggleEdit = () => {
  isEditing.value = !isEditing.value
}

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
<div class="relative rounded-t-[2rem] bg-gradient-to-b from-primary/10 to-orange-50 p-6 md:p-8 flex items-center justify-between mb-0">
    <!-- Avatar + Infos -->
    <div class="flex items-center gap-6">
      <div class="relative">
        <img src="/storage/babysitter-test.png" class="h-24 w-24 rounded-full object-cover border-4 border-white shadow" />
        <div class="absolute bottom-0 right-0 bg-white rounded-full p-1 shadow-md">
          <Camera class="h-4 w-4 text-gray-500" />
        </div>
      </div>
      <div>
        <h2 class="text-2xl font-semibold text-gray-900">Sophie Martin</h2>
        <p class="text-sm text-gray-500">Parent de 2 enfants</p>
      </div>
    </div>

 <!-- BOUTON MODIFIER (quand non en édition) -->
<button
  v-if="!isEditing"
  class="bg-primary text-white font-semibold px-6 py-2 rounded hover:bg-orange-500 transition"
  @click="toggleEdit"
>
  Modifier
</button>


  </div>
          <!-- Formulaire -->
<div class="bg-white rounded-b-xl p-6 space-y-6"> 
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label class="mb-3" for="prenom">Prénom</Label>
<Input id="prenom" type="text" v-model="form.prenom" :disabled="!isEditing" />              </div>
              <div>
                <Label class="mb-3" for="nom">Nom</Label>
<Input id="nom" type="text" v-model="form.nom" :disabled="!isEditing" />              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label class="mb-3" for="email">Email</Label>
                <div class="relative">
                  <Mail class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
<Input id="email" type="email" v-model="form.email" :disabled="!isEditing" class="pl-10" />
                </div>
              </div>
              <div>
                <Label class="mb-3" for="phone">Téléphone</Label>
                <div class="relative">
                  <Phone class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
<Input id="phone" type="tel" v-model="form.phone" :disabled="!isEditing" class="pl-10" />
                </div>
              </div>
            </div>

            <div>
              <Label class="mb-3" for="adresse">Adresse</Label>
              <div class="relative">
                <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
<Input id="adresse" type="text" v-model="form.adresse" :disabled="!isEditing" class="pl-10" />              </div>
            </div>

            <!-- Enfants -->
            <div>
              <h3 class="text-md font-medium text-black mb-3">Enfants</h3>
              <div class="space-y-3">
  <div
    v-for="(enfant, index) in form.enfants"
    :key="index"
    class="flex items-center gap-2"
  >
    <Input v-model="enfant.nom" :disabled="!isEditing" class="flex-1" />
    <Input v-model="enfant.age" :disabled="!isEditing" class="w-16 text-center" />
    <select
      v-model="enfant.unite"
      :disabled="!isEditing"
      class="border border-gray-300 rounded-md text-sm px-2 py-1"
    >
      <option value="ans">ans</option>
      <option value="mois">mois</option>
    </select>
    <button
      v-if="isEditing"
      @click="removeChild(index)"
      class="text-red-600 text-sm hover:underline"
    >
      Supprimer
    </button>
  </div>

  <!-- Bouton Ajouter un enfant -->
  <div
    v-if="isEditing"
    class="border border-dashed border-gray-300 rounded-md text-center text-gray-400 py-2 text-sm cursor-pointer hover:text-gray-600"
    @click="addChild"
  >
    + Ajouter un enfant
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
            <!-- BOUTONS EN BAS DU FORMULAIRE -->
<div v-if="isEditing" class="flex justify-end gap-4 pt-6">
  <button
    class="bg-gray-100 text-primary font-semibold px-6 py-2 rounded hover:bg-gray-200 transition"
    @click="toggleEdit"
  >
    Annuler
  </button>
  <button
    class="bg-primary text-white font-semibold px-6 py-2 rounded hover:bg-orange-500 transition"
    @click="submitForm"
  >
    Enregistrer les modifications
  </button>
</div>

          </div>
        </div>
      </main>
    </div>

    <DashboardFooter />
  </div>
</template>
