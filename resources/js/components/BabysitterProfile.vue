<template>
    <div class="mx-auto max-w-4xl space-y-6">
        <!-- Barre de progression du profil -->
        <div
            :class="[
                'rounded-lg border p-6 shadow-sm transition-all duration-300',
                profileCompletion >= 80
                    ? 'border-green-200 bg-gradient-to-r from-green-50 to-emerald-50'
                    : profileCompletion >= 50
                      ? 'border-yellow-200 bg-gradient-to-r from-yellow-50 to-orange-50'
                      : 'border-red-200 bg-gradient-to-r from-red-50 to-pink-50',
            ]"
        >
            <div class="mb-4 flex items-center gap-3">
                <div
                    :class="[
                        'flex h-8 w-8 items-center justify-center rounded-full transition-all duration-300',
                        profileCompletion >= 80 ? 'bg-green-100' : profileCompletion >= 50 ? 'bg-yellow-100' : 'bg-red-100',
                    ]"
                >
                    <svg v-if="profileCompletion >= 80" class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <svg v-else-if="profileCompletion >= 50" class="h-5 w-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <svg v-else class="h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-gray-900">Completion du profil</h2>
                    <p
                        :class="[
                            'text-sm font-medium',
                            profileCompletion >= 80 ? 'text-green-700' : profileCompletion >= 50 ? 'text-yellow-700' : 'text-red-700',
                        ]"
                    >
                        <span v-if="profileCompletion < 50">🚀 Complétez votre profil pour attirer plus de parents !</span>
                        <span v-else-if="profileCompletion < 80">⭐ Votre profil est bien avancé, continuez !</span>
                        <span v-else>🎉 Excellent ! Votre profil est très complet.</span>
                    </p>
                </div>
                <div
                    :class="[
                        'rounded-full px-4 py-2 text-lg font-bold shadow-sm',
                        profileCompletion >= 80
                            ? 'bg-green-500 text-white'
                            : profileCompletion >= 50
                              ? 'bg-yellow-500 text-white'
                              : 'bg-red-500 text-white',
                    ]"
                >
                    {{ profileCompletion }}%
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-700">Progression de votre profil babysitter</span>
                    <span class="font-bold text-gray-900">{{ profileCompletion }}% complété</span>
                </div>
                <div class="relative h-3 w-full rounded-full bg-white/50 shadow-inner">
                    <div
                        :class="[
                            'h-3 rounded-full shadow-sm transition-all duration-500 ease-out',
                            profileCompletion >= 80
                                ? 'bg-gradient-to-r from-green-400 to-green-600'
                                : profileCompletion >= 50
                                  ? 'bg-gradient-to-r from-yellow-400 to-orange-500'
                                  : 'bg-gradient-to-r from-red-400 to-red-600',
                        ]"
                        :style="{ width: profileCompletion + '%' }"
                    >
                        <div class="h-full w-full rounded-full bg-white/20"></div>
                    </div>
                    <!-- Indicateurs de seuils -->
                    <div class="absolute top-0 left-1/2 h-3 w-0.5 -translate-x-0.5 transform rounded-full bg-white/60"></div>
                    <div class="absolute top-0 left-4/5 h-3 w-0.5 -translate-x-0.5 transform rounded-full bg-white/60"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                    <span>0%</span>
                    <span>50%</span>
                    <span>80%</span>
                    <span>100%</span>
                </div>
            </div>
        </div>

        <!-- Bio Section -->
        <div class="rounded-lg border bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-100">
                    <svg class="h-3 w-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"
                        />
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
                    placeholder="Présentez-vous en quelques mots... (facultatif)"
                    class="h-24 resize-none"
                />
            </div>
        </div>

        <!-- Expérience -->
        <div class="rounded-lg border bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-orange-100">
                    <svg class="h-3 w-3 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"
                        />
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Expérience</h2>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-2">
                    <Label for="experience_years">Années d'expérience dans le babysitting</Label>
                    <Input
                        id="experience_years"
                        v-model="form.experience_years"
                        type="number"
                        :disabled="!isEditing"
                        min="0"
                        placeholder="Nombre d'années... (facultatif)"
                    />
                </div>
                <div class="space-y-2">
                    <Label for="hourly_rate">Tarif horaire (€)</Label>
                    <Input
                        id="hourly_rate"
                        v-model="form.hourly_rate"
                        type="number"
                        :disabled="!isEditing"
                        min="0"
                        step="0.5"
                        placeholder="Tarif par heure... (facultatif)"
                    />
                </div>
            </div>
        </div>

        <!-- Formations et expériences détaillées -->
        <div class="rounded-lg border bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-purple-100">
                        <svg class="h-3 w-3 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"
                            />
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">Formations et expériences</h2>
                </div>
                <div v-if="isEditing" class="flex gap-2">
                    <Button
                        type="button"
                        @click="addExperience('formation')"
                        variant="outline"
                        size="sm"
                        class="flex items-center gap-2 border-blue-300 text-blue-700 hover:bg-blue-50"
                    >
                        <Plus class="h-4 w-4" />
                        Ajouter formation
                    </Button>
                    <Button
                        type="button"
                        @click="addExperience('experience')"
                        variant="outline"
                        size="sm"
                        class="flex items-center gap-2 border-green-300 text-green-700 hover:bg-green-50"
                    >
                        <Plus class="h-4 w-4" />
                        Ajouter expérience
                    </Button>
                </div>
            </div>

            <div class="space-y-4">
                <div
                    v-for="(experience, index) in form.experiences"
                    :key="index"
                    :class="[
                        'rounded-lg border p-4',
                        experience.type === 'formation' ? 'border-blue-200 bg-blue-50' : 'border-green-200 bg-green-50',
                    ]"
                >
                    <div class="mb-3 flex items-center justify-between">
                        <div
                            :class="[
                                'rounded-full px-2 py-1 text-xs font-medium',
                                experience.type === 'formation' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800',
                            ]"
                        >
                            {{ experience.type === 'formation' ? 'Formation' : 'Expérience' }}
                        </div>
                        <Button v-if="isEditing" type="button" @click="removeExperience(index)" variant="destructive" size="sm">
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label>Titre</Label>
                            <Input v-model="experience.title" :disabled="!isEditing" placeholder="Titre de la formation/expérience" required />
                        </div>
                        <div class="space-y-2">
                            <Label>Institution/Entreprise</Label>
                            <Input v-model="experience.institution" :disabled="!isEditing" placeholder="Nom de l'institution" />
                        </div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <Label>Description</Label>
                        <Textarea
                            v-model="experience.description"
                            :disabled="!isEditing"
                            placeholder="Description détaillée..."
                            class="h-20 resize-none"
                        />
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label>Date de début</Label>
                            <Input v-model="experience.start_date" :disabled="!isEditing" type="date" />
                        </div>
                        <div class="space-y-2">
                            <Label>Date de fin</Label>
                            <Input v-model="experience.end_date" :disabled="!isEditing || experience.is_current" type="date" />
                        </div>
                        <div class="flex items-center space-x-2 pt-6">
                            <Checkbox v-model="experience.is_current" :disabled="!isEditing" />
                            <Label class="text-sm">En cours</Label>
                        </div>
                    </div>
                </div>

                <div v-if="form.experiences.length === 0" class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-gray-500">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <p class="text-sm">Aucune formation ou expérience ajoutée</p>
                    <p class="mt-1 text-xs text-gray-400">Utilisez les boutons ci-dessus pour ajouter vos formations et expériences</p>
                </div>
            </div>
        </div>

        <!-- Langues parlées -->
        <div class="rounded-lg border bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-pink-100">
                    <svg class="h-3 w-3 text-pink-600" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M7 2a1 1 0 011 1v1h3a1 1 0 110 2H9.578a18.87 18.87 0 01-1.724 4.78c.29.354.596.696.914 1.026a1 1 0 11-1.44 1.389c-.188-.196-.373-.396-.554-.6a19.098 19.098 0 01-3.107 3.567 1 1 0 01-1.334-1.49 17.087 17.087 0 003.13-3.733 18.992 18.992 0 01-1.487-2.494 1 1 0 111.79-.89c.234.47.489.928.764 1.372.417-.934.752-1.913.997-2.927H3a1 1 0 110-2h3V3a1 1 0 011-1zm6 6a1 1 0 01.894.553l2.991 5.982a.869.869 0 01.02.037l.99 1.98a1 1 0 11-1.79.895L15.383 16h-4.764l-.724 1.447a1 1 0 11-1.788-.894l.99-1.98.019-.038 2.99-5.982A1 1 0 0113 8zm-1.382 6h2.764L13 12.236 11.618 14z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Langues parlées</h2>
            </div>

            <div class="space-y-2">
                <Label for="languages">Sélectionnez vos langues</Label>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="lang in availableLanguages"
                        :key="lang.id"
                        type="button"
                        variant="outline"
                        :class="[
                            form.language_ids.includes(lang.id) ? 'bg-blue-100 text-blue-800 hover:bg-blue-200' : '',
                            !isEditing ? 'cursor-default' : '',
                        ]"
                        :disabled="!isEditing"
                        @click="toggleLanguage(lang.id)"
                    >
                        {{ lang.name }}
                    </Button>
                </div>
            </div>
        </div>

        <!-- Compétences -->
        <div class="rounded-lg border bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-3 w-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Compétences</h2>
            </div>

            <div class="space-y-2">
                <Label for="skills">Sélectionnez vos compétences</Label>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="skill in availableSkills"
                        :key="skill.id"
                        type="button"
                        variant="outline"
                        :class="[
                            form.skill_ids.includes(skill.id) ? 'bg-orange-100 text-orange-800 hover:bg-orange-200' : '',
                            !isEditing ? 'cursor-default' : '',
                        ]"
                        :disabled="!isEditing"
                        @click="toggleSkill(skill.id)"
                        :title="skill.description"
                    >
                        {{ skill.name }}
                    </Button>
                </div>
            </div>
        </div>

        <!-- Tranches d'âge -->
        <div class="rounded-lg border bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-orange-100">
                    <svg class="h-3 w-3 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Préférences d'âge</h2>
            </div>

            <div class="space-y-4">
                <!-- Option "À l'aise avec tous les âges" -->
                <div class="flex items-center space-x-3 rounded-lg border border-green-200 bg-green-50 p-3">
                    <Checkbox
                        v-model="form.comfortable_with_all_ages"
                        :disabled="!isEditing"
                        class="rounded border-green-300 text-green-600"
                        @change="onComfortableWithAllAgesChange"
                    />
                    <div class="flex-1">
                        <Label class="text-sm font-medium text-green-800">Je suis à l'aise avec toutes les tranches d'âge</Label>
                        <p class="text-xs text-green-600">Cochez cette case si vous acceptez de garder des enfants de tous âges</p>
                    </div>
                </div>

                <!-- Tranches d'âge à exclure -->
                <div v-if="!form.comfortable_with_all_ages" class="space-y-2">
                    <Label for="excluded_age_ranges">Tranches d'âge que je ne souhaite PAS garder</Label>
                    <p class="mb-3 text-xs text-gray-500">Sélectionnez les tranches d'âge avec lesquelles vous n'êtes pas à l'aise</p>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="ageRange in availableAgeRanges"
                            :key="ageRange.id"
                            type="button"
                            variant="outline"
                            :class="[
                                form.excluded_age_range_ids.includes(ageRange.id)
                                    ? 'border-red-300 bg-red-100 text-red-800 hover:bg-red-200'
                                    : 'hover:bg-gray-50',
                                !isEditing ? 'cursor-default' : '',
                            ]"
                            :disabled="!isEditing"
                            @click="toggleExcludedAgeRange(ageRange.id)"
                        >
                            {{ ageRange.name }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disponibilité et transport -->
        <div class="rounded-lg border bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-100">
                    <svg class="h-3 w-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">Disponibilité et transport</h2>
            </div>

            <div class="space-y-6">
                <!-- Statut de disponibilité -->
                <div class="space-y-3">
                    <Label>Statut actuel</Label>
                    <div
                        class="flex items-center space-x-3 rounded-lg border p-3"
                        :class="[form.is_available ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50']"
                    >
                        <Checkbox
                            v-model="form.is_available"
                            :disabled="!isEditing"
                            :class="['rounded', form.is_available ? 'border-green-300 text-green-600' : 'border-red-300 text-red-600']"
                        />
                        <div class="flex-1">
                            <Label :class="['text-sm font-medium', form.is_available ? 'text-green-800' : 'text-red-800']">
                                {{
                                    form.is_available
                                        ? 'Je suis actuellement disponible pour du babysitting'
                                        : 'Je ne suis pas disponible actuellement'
                                }}
                            </Label>
                            <p :class="['text-xs', form.is_available ? 'text-green-600' : 'text-red-600']">
                                {{
                                    form.is_available
                                        ? 'Les parents peuvent vous contacter pour des gardes'
                                        : 'Votre profil ne sera pas visible dans les recherches'
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Rayon de déplacement -->
                <div class="space-y-2">
                    <Label for="available_radius_km">Rayon de déplacement (km)</Label>
                    <div class="flex items-center gap-2">
                        <div class="relative max-w-32 flex-1">
                            <Input
                                id="available_radius_km"
                                v-model="form.available_radius_km"
                                type="number"
                                :disabled="!isEditing"
                                min="1"
                                max="50"
                                placeholder="Distance... (facultatif)"
                            />
                        </div>
                        <span class="text-gray-600">km</span>
                    </div>
                </div>

                <!-- Permis et véhicule -->
                <div class="space-y-4">
                    <Label>Transport</Label>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- Permis de conduire -->
                        <div class="flex items-center space-x-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <Checkbox v-model="form.has_driving_license" :disabled="!isEditing" class="rounded border-gray-300 text-blue-600" />
                            <div class="flex-1">
                                <Label class="text-sm font-medium text-gray-800">J'ai le permis de conduire</Label>
                                <p class="text-xs text-gray-600">Permis B valide</p>
                            </div>
                        </div>

                        <!-- Véhicule -->
                        <div class="flex items-center space-x-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <Checkbox
                                v-model="form.has_vehicle"
                                :disabled="!isEditing || !form.has_driving_license"
                                class="rounded border-gray-300 text-blue-600"
                            />
                            <div class="flex-1">
                                <Label :class="['text-sm font-medium', !form.has_driving_license ? 'text-gray-400' : 'text-gray-800']">
                                    J'ai un véhicule
                                </Label>
                                <p :class="['text-xs', !form.has_driving_license ? 'text-gray-400' : 'text-gray-600']">
                                    Voiture personnelle disponible
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Language {
    id: number;
    name: string;
    code: string;
}

interface Skill {
    id: number;
    name: string;
    description?: string;
    category?: string;
}

interface AgeRange {
    id: number;
    name: string;
    min_age_months: number;
    max_age_months?: number;
    display_order: number;
}

interface Experience {
    type: 'formation' | 'experience';
    title: string;
    description?: string;
    institution?: string;
    start_date?: string;
    end_date?: string;
    is_current?: boolean;
}

interface BabysitterProfileData {
    id?: number;
    bio?: string;
    experience_years?: number;
    available_radius_km?: number;
    is_available?: boolean;
    hourly_rate?: number;
    documents_verified?: boolean;
    has_driving_license?: boolean;
    has_vehicle?: boolean;
    comfortable_with_all_ages?: boolean;
    languages?: Language[];
    skills?: Skill[];
    age_ranges?: AgeRange[];
    excluded_age_ranges?: AgeRange[];
    experiences?: Experience[];
}

interface Props {
    babysitterProfile?: BabysitterProfileData;
    availableLanguages?: Language[];
    availableSkills?: Skill[];
    availableAgeRanges?: AgeRange[];
}

const props = defineProps<Props>();

const isEditing = ref(false);

// Initialiser les IDs des relations existantes
const getExistingLanguageIds = () => {
    return props.babysitterProfile?.languages?.map((lang) => lang.id) || [];
};

const getExistingSkillIds = () => {
    return props.babysitterProfile?.skills?.map((skill) => skill.id) || [];
};

const getExistingAgeRangeIds = () => {
    return props.babysitterProfile?.age_ranges?.map((range) => range.id) || [];
};

const getExistingExcludedAgeRangeIds = () => {
    return props.babysitterProfile?.excluded_age_ranges?.map((range) => range.id) || [];
};

const getExistingExperiences = () => {
    return (
        props.babysitterProfile?.experiences?.map((exp) => ({
            type: exp.type,
            title: exp.title,
            description: exp.description || '',
            institution: exp.institution || '',
            start_date: exp.start_date || '',
            end_date: exp.end_date || '',
            is_current: exp.is_current || false,
        })) || []
    );
};

// Formulaire avec les données du backend ou valeurs par défaut
const form = ref({
    bio: props.babysitterProfile?.bio || '',
    experience_years: props.babysitterProfile?.experience_years || 0,
    language_ids: getExistingLanguageIds(),
    skill_ids: getExistingSkillIds(),
    age_range_ids: getExistingAgeRangeIds(),
    excluded_age_range_ids: getExistingExcludedAgeRangeIds(),
    comfortable_with_all_ages: props.babysitterProfile?.comfortable_with_all_ages || false,
    experiences: getExistingExperiences(),
    hourly_rate: props.babysitterProfile?.hourly_rate || 15,
    available_radius_km: props.babysitterProfile?.available_radius_km || 10,
    is_available: props.babysitterProfile?.is_available !== undefined ? props.babysitterProfile.is_available : true,
    has_driving_license: props.babysitterProfile?.has_driving_license || false,
    has_vehicle: props.babysitterProfile?.has_vehicle || false,
});

// Watcher pour mettre à jour le formulaire si les props changent
watch(
    () => props.babysitterProfile,
    (newProfile) => {
        if (newProfile) {
            form.value.bio = newProfile.bio || '';
            form.value.experience_years = newProfile.experience_years || 0;
            form.value.language_ids = newProfile.languages?.map((lang) => lang.id) || [];
            form.value.skill_ids = newProfile.skills?.map((skill) => skill.id) || [];
            form.value.age_range_ids = newProfile.age_ranges?.map((range) => range.id) || [];
            form.value.excluded_age_range_ids = newProfile.excluded_age_ranges?.map((range) => range.id) || [];
            form.value.comfortable_with_all_ages = newProfile.comfortable_with_all_ages || false;
            form.value.experiences =
                newProfile.experiences?.map((exp) => ({
                    type: exp.type,
                    title: exp.title,
                    description: exp.description || '',
                    institution: exp.institution || '',
                    start_date: exp.start_date || '',
                    end_date: exp.end_date || '',
                    is_current: exp.is_current || false,
                })) || [];
            form.value.hourly_rate = newProfile.hourly_rate || 15;
            form.value.available_radius_km = newProfile.available_radius_km || 10;
            form.value.is_available = newProfile.is_available !== undefined ? newProfile.is_available : true;
            form.value.has_driving_license = newProfile.has_driving_license || false;
            form.value.has_vehicle = newProfile.has_vehicle || false;
        }
    },
    { deep: true },
);

// Computed pour le pourcentage de completion du profil
const profileCompletion = computed(() => {
    return calculateProfileCompletion();
});

// Fonction pour gérer le changement de "à l'aise avec tous les âges"
const onComfortableWithAllAgesChange = () => {
    if (form.value.comfortable_with_all_ages) {
        // Si on coche "à l'aise avec tous", on vide les exclusions
        form.value.excluded_age_range_ids = [];
    }
};

// Fonctions de toggle pour les sélections multiples
const toggleLanguage = (langId: number) => {
    if (!isEditing.value) return;
    const index = form.value.language_ids.indexOf(langId);
    if (index === -1) {
        form.value.language_ids.push(langId);
    } else {
        form.value.language_ids.splice(index, 1);
    }
};

const toggleSkill = (skillId: number) => {
    if (!isEditing.value) return;
    const index = form.value.skill_ids.indexOf(skillId);
    if (index === -1) {
        form.value.skill_ids.push(skillId);
    } else {
        form.value.skill_ids.splice(index, 1);
    }
};

const toggleExcludedAgeRange = (ageRangeId: number) => {
    if (!isEditing.value) return;
    const index = form.value.excluded_age_range_ids.indexOf(ageRangeId);
    if (index === -1) {
        form.value.excluded_age_range_ids.push(ageRangeId);
    } else {
        form.value.excluded_age_range_ids.splice(index, 1);
    }
};

// Fonctions pour gérer les expériences
const addExperience = (type: 'formation' | 'experience') => {
    if (!isEditing.value) return;
    form.value.experiences.push({
        type,
        title: '',
        description: '',
        institution: '',
        start_date: '',
        end_date: '',
        is_current: false,
    });
};

const removeExperience = (index: number) => {
    if (!isEditing.value) return;
    form.value.experiences.splice(index, 1);
};

// Fonction pour calculer le pourcentage de completion du profil
const calculateProfileCompletion = () => {
    let totalFields = 0;
    let completedFields = 0;

    // Bio (10%)
    totalFields += 10;
    if (form.value.bio && form.value.bio.trim().length > 0) {
        completedFields += 10;
    }

    // Années d'expérience (10%)
    totalFields += 10;
    if (form.value.experience_years && form.value.experience_years > 0) {
        completedFields += 10;
    }

    // Tarif horaire (15%)
    totalFields += 15;
    if (form.value.hourly_rate && form.value.hourly_rate > 0) {
        completedFields += 15;
    }

    // Langues (15%)
    totalFields += 15;
    if (form.value.language_ids.length > 0) {
        completedFields += 15;
    }

    // Compétences (15%)
    totalFields += 15;
    if (form.value.skill_ids.length > 0) {
        completedFields += 15;
    }

    // Préférences d'âge (15%) - soit "à l'aise avec tous" soit au moins une exclusion
    totalFields += 15;
    if (form.value.comfortable_with_all_ages || form.value.excluded_age_range_ids.length > 0) {
        completedFields += 15;
    }

    // Rayon de déplacement (10%)
    totalFields += 10;
    if (form.value.available_radius_km && form.value.available_radius_km > 0) {
        completedFields += 10;
    }

    // Statut de disponibilité (10%) - toujours compté car par défaut à true
    totalFields += 10;
    completedFields += 10; // Toujours complété car il y a une valeur par défaut

    return Math.round((completedFields / totalFields) * 100);
};

// Fonction pour obtenir les données du formulaire
const getFormData = () => {
    return {
        bio: form.value.bio,
        experience_years: form.value.experience_years,
        language_ids: form.value.language_ids,
        skill_ids: form.value.skill_ids,
        excluded_age_range_ids: form.value.excluded_age_range_ids,
        comfortable_with_all_ages: form.value.comfortable_with_all_ages,
        experiences: form.value.experiences.filter((exp) => exp.title.trim() !== ''), // Filtrer les expériences vides
        hourly_rate: form.value.hourly_rate,
        available_radius_km: form.value.available_radius_km,
        is_available: form.value.is_available,
        has_driving_license: form.value.has_driving_license,
        has_vehicle: form.value.has_vehicle,
        completion_percentage: calculateProfileCompletion(),
    };
};

// Exposer isEditing, getFormData et profileCompletion pour le parent
defineExpose({
    isEditing,
    getFormData,
    profileCompletion,
});
</script>
