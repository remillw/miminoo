<template>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Bio Section -->
      <div class="bg-white rounded-lg p-6 shadow-sm border">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center">
            <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
            </svg>
          </div>
          <h2 class="text-lg font-semibold text-gray-900">Bio</h2>
        </div>
        <div class="space-y-2">
          <Label for="bio">Votre présentation</Label>
          <Textarea
            id="bio"
            v-model="form.bio"
            :disabled="!isEditing"
            placeholder="Présentez-vous en quelques mots..."
            class="resize-none h-24"
          >
            Étudiante en éducation à l'université, j'adore passer du temps avec les enfants et leur proposer des activités créatives.
          </Textarea>
        </div>
      </div>

      <!-- Formation et expérience -->
      <div class="bg-white rounded-lg p-6 shadow-sm border">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-5 h-5 rounded-full bg-orange-100 flex items-center justify-center">
            <svg class="w-3 h-3 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
            </svg>
          </div>
          <h2 class="text-lg font-semibold text-gray-900">Formation et expérience</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <Label for="formation">Formation</Label>
            <Input
              id="formation"
              v-model="form.formation"
              :disabled="!isEditing"
              placeholder="Votre formation..."
            />
          </div>
          <div class="space-y-2">
            <Label for="experience">Années d'expérience</Label>
            <Input
              id="experience"
              v-model="form.experience"
              type="number"
              :disabled="!isEditing"
              min="0"
              placeholder="Nombre d'années..."
            />
          </div>
        </div>
      </div>

      <!-- Langues parlées -->
      <div class="bg-white rounded-lg p-6 shadow-sm border">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-5 h-5 rounded-full bg-pink-100 flex items-center justify-center">
            <svg class="w-3 h-3 text-pink-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7 2a1 1 0 011 1v1h3a1 1 0 110 2H9.578a18.87 18.87 0 01-1.724 4.78c.29.354.596.696.914 1.026a1 1 0 11-1.44 1.389c-.188-.196-.373-.396-.554-.6a19.098 19.098 0 01-3.107 3.567 1 1 0 01-1.334-1.49 17.087 17.087 0 003.13-3.733 18.992 18.992 0 01-1.487-2.494 1 1 0 111.79-.89c.234.47.489.928.764 1.372.417-.934.752-1.913.997-2.927H3a1 1 0 110-2h3V3a1 1 0 011-1zm6 6a1 1 0 01.894.553l2.991 5.982a.869.869 0 01.02.037l.99 1.98a1 1 0 11-1.79.895L15.383 16h-4.764l-.724 1.447a1 1 0 11-1.788-.894l.99-1.98.019-.038 2.99-5.982A1 1 0 0113 8zm-1.382 6h2.764L13 12.236 11.618 14z" clip-rule="evenodd"/>
            </svg>
          </div>
          <h2 class="text-lg font-semibold text-gray-900">Langues parlées</h2>
        </div>
        
        <div class="space-y-2">
          <Label for="languages">Sélectionnez vos langues</Label>
          <div class="flex flex-wrap gap-2">
            <Button
              v-for="lang in availableLanguages"
              :key="lang"
              variant="outline"
              :class="[
                form.languages.includes(lang) ? 'bg-blue-100 text-blue-800 hover:bg-blue-200' : '',
                !isEditing ? 'cursor-default' : ''
              ]"
              :disabled="!isEditing"
              @click="toggleLanguage(lang)"
            >
              {{ lang }}
            </Button>
          </div>
        </div>
      </div>

      <!-- Compétences -->
      <div class="bg-white rounded-lg p-6 shadow-sm border">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center">
            <svg class="w-3 h-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
            </svg>
          </div>
          <h2 class="text-lg font-semibold text-gray-900">Compétences</h2>
        </div>
        
        <div class="space-y-2">
          <Label for="skills">Sélectionnez vos compétences</Label>
          <div class="flex flex-wrap gap-2">
            <Button
              v-for="skill in availableSkills"
              :key="skill"
              variant="outline"
              :class="[
                form.skills.includes(skill) ? 'bg-orange-100 text-orange-800 hover:bg-orange-200' : '',
                !isEditing ? 'cursor-default' : ''
              ]"
              :disabled="!isEditing"
              @click="toggleSkill(skill)"
            >
              {{ skill }}
            </Button>
          </div>
        </div>
      </div>

      <!-- Disponibilités -->
      <div class="bg-white rounded-lg p-6 shadow-sm border">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center">
            <svg class="w-3 h-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
            </svg>
          </div>
          <h2 class="text-lg font-semibold text-gray-900">Disponibilités</h2>
        </div>

        <div class="space-y-6">
          <!-- Tableau des disponibilités -->
          <div class="space-y-2">
            <Label>Jours et créneaux</Label>
            <div class="space-y-2">
              <div v-for="day in form.days" :key="day.name" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                <span class="font-medium text-gray-900 w-20">{{ day.name }}</span>
                <div class="flex gap-6">
                  <div class="flex items-center gap-2">
                    <Checkbox
                      v-model="day.morning"
                      :disabled="!isEditing"
                      class="rounded border-gray-300 text-blue-600"
                    />
                    <span class="text-sm text-gray-600">Morning</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <Checkbox
                      v-model="day.afternoon"
                      :disabled="!isEditing"
                      class="rounded border-gray-300 text-blue-600"
                    />
                    <span class="text-sm text-gray-600">Afternoon</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <Checkbox
                      v-model="day.evening"
                      :disabled="!isEditing"
                      class="rounded border-gray-300 text-blue-600"
                    />
                    <span class="text-sm text-gray-600">Evening</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tarif horaire -->
          <div class="space-y-2">
            <Label for="hourly_rate">Tarif horaire</Label>
            <div class="flex items-center gap-2">
              <div class="relative flex-1 max-w-32">
                <span class="absolute inset-y-0 left-3 flex items-center text-gray-500">€</span>
                <Input
                  id="hourly_rate"
                  v-model="form.hourly_rate"
                  type="number"
                  :disabled="!isEditing"
                  min="0"
                  step="0.5"
                  class="pl-8"
                />
              </div>
              <span class="text-gray-600">/heure</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Âges des enfants -->
      <div class="bg-white rounded-lg p-6 shadow-sm border">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-5 h-5 rounded-full bg-orange-100 flex items-center justify-center">
            <svg class="w-3 h-3 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
            </svg>
          </div>
          <h2 class="text-lg font-semibold text-gray-900">Âges des enfants</h2>
        </div>
        
        <div class="space-y-2">
          <Label for="age_groups">Tranches d'âge</Label>
          <div class="flex flex-wrap gap-2">
            <Button
              v-for="age in availableAgeGroups"
              :key="age"
              variant="outline"
              :class="[
                form.age_groups.includes(age) ? 'bg-blue-50 text-blue-700 border-blue-200' : '',
                !isEditing ? 'cursor-default' : ''
              ]"
              :disabled="!isEditing"
              @click="toggleAgeGroup(age)"
            >
              {{ age }}
            </Button>
          </div>
        </div>
      </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';

const isEditing = ref(false);

// Formulaire
const form = ref({
  bio: "Étudiante en éducation à l'université, j'adore passer du temps avec les enfants et leur proposer des activités créatives.",
  formation: "Licence en Sciences de l'Éducation",
  experience: 5,
  languages: ['Français', 'Anglais', 'Espagnol'],
  skills: ['Aide aux devoirs', 'Activités créatives', 'Premiers secours'],
  age_groups: ['0-1 an', '1-3 ans', '3-6 ans', '6-12 ans'],
  hourly_rate: 15,
  days: [
    { name: 'Monday', morning: false, afternoon: false, evening: true },
    { name: 'Tuesday', morning: false, afternoon: false, evening: true },
    { name: 'Wednesday', morning: true, afternoon: true, evening: true },
    { name: 'Thursday', morning: false, afternoon: false, evening: false },
    { name: 'Friday', morning: false, afternoon: false, evening: true },
    { name: 'Saturday', morning: true, afternoon: true, evening: true },
    { name: 'Sunday', morning: true, afternoon: true, evening: false },
  ]
});

// Données disponibles
const availableLanguages = ['Français', 'Anglais', 'Espagnol', 'Allemand', 'Italien'];
const availableSkills = ['Aide aux devoirs', 'Activités créatives', 'Premiers secours', 'Cuisine', 'Sport'];
const availableAgeGroups = ['0-1 an', '1-3 ans', '3-6 ans', '6-12 ans', '12+ ans'];

// Fonctions de toggle pour les sélections multiples
const toggleLanguage = (lang: string) => {
  if (!isEditing.value) return;
  const index = form.value.languages.indexOf(lang);
  if (index === -1) {
    form.value.languages.push(lang);
  } else {
    form.value.languages.splice(index, 1);
  }
};

const toggleSkill = (skill: string) => {
  if (!isEditing.value) return;
  const index = form.value.skills.indexOf(skill);
  if (index === -1) {
    form.value.skills.push(skill);
  } else {
    form.value.skills.splice(index, 1);
  }
};

const toggleAgeGroup = (age: string) => {
  if (!isEditing.value) return;
  const index = form.value.age_groups.indexOf(age);
  if (index === -1) {
    form.value.age_groups.push(age);
  } else {
    form.value.age_groups.splice(index, 1);
  }
};

// Exposer isEditing pour le parent
defineExpose({
  isEditing
});
</script>