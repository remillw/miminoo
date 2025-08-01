<template>
    <DashboardLayout :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole">
        <div class="min-h-screen bg-secondary py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <!-- En-tête -->
                <div class="mb-8">
                    <div class="mb-4 flex items-center gap-4">
                        <button
                            @click="() => router.visit('/parent/annonces-et-reservations')"
                            class="flex items-center gap-2 text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeft class="h-5 w-5" />
                            Retour
                        </button>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900">Modifier mon annonce</h1>
                    <p class="mt-2 text-gray-600">Mettez à jour les informations de votre annonce</p>
                </div>

                <!-- Formulaire -->
                <div class="rounded-lg bg-white p-8 shadow">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Titre (généré automatiquement) -->
                        <div>
                            <label for="title" class="mb-2 block text-sm font-medium text-gray-700"> 
                                Titre de l'annonce 
                                <span class="text-sm font-normal text-gray-500">(généré automatiquement)</span>
                            </label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                disabled
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 bg-gray-100 text-gray-600 cursor-not-allowed"
                                placeholder="Le titre sera généré automatiquement en fonction des dates et enfants"
                            />
                            <p class="mt-1 text-sm text-gray-500">
                                Le titre est généré automatiquement à partir des dates et prénoms des enfants
                            </p>
                        </div>

                        <!-- Informations complémentaires -->
                        <div>
                            <label for="additional_info" class="mb-2 block text-sm font-medium text-gray-700"> Informations complémentaires </label>
                            <textarea
                                id="additional_info"
                                v-model="form.additional_info"
                                rows="4"
                                class="focus:border-primary focus:ring-primary w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-1 focus:outline-none"
                                :class="{ 'border-red-500': form.errors.additional_info }"
                                placeholder="Décrivez vos attentes, les activités à prévoir, informations importantes..."
                            ></textarea>
                            <p v-if="form.errors.additional_info" class="mt-1 text-sm text-red-500">
                                {{ form.errors.additional_info }}
                            </p>
                        </div>

                        <!-- Date et heures -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div>
                                <label for="date_start" class="mb-2 block text-sm font-medium text-gray-700"> Date * </label>
                                <input
                                    id="date_start"
                                    v-model="dateOnly"
                                    type="date"
                                    required
                                    class="focus:border-primary focus:ring-primary w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-1 focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.date_start }"
                                />
                                <p v-if="form.errors.date_start" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_start }}
                                </p>
                            </div>

                            <div>
                                <label for="time_start" class="mb-2 block text-sm font-medium text-gray-700"> Heure de début * </label>
                                <input
                                    id="time_start"
                                    v-model="timeStart"
                                    type="time"
                                    required
                                    class="focus:border-primary focus:ring-primary w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-1 focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.date_start }"
                                />
                                <p v-if="form.errors.date_start" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_start }}
                                </p>
                            </div>

                            <div>
                                <label for="time_end" class="mb-2 block text-sm font-medium text-gray-700"> Heure de fin * </label>
                                <input
                                    id="time_end"
                                    v-model="timeEnd"
                                    type="time"
                                    required
                                    class="focus:border-primary focus:ring-primary w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-1 focus:outline-none"
                                    :class="{ 'border-red-500': form.errors.date_end }"
                                />
                                <p v-if="form.errors.date_end" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_end }}
                                </p>
                            </div>
                        </div>

                        <!-- Tarif horaire -->
                        <div>
                            <label for="hourly_rate" class="mb-2 block text-sm font-medium text-gray-700"> Tarif horaire proposé (€) * </label>
                            <input
                                id="hourly_rate"
                                v-model.number="form.hourly_rate"
                                type="number"
                                step="0.5"
                                min="10"
                                required
                                class="focus:border-primary focus:ring-primary w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-1 focus:outline-none"
                                :class="{ 'border-red-500': form.errors.hourly_rate }"
                                placeholder="15"
                            />
                            <p v-if="form.errors.hourly_rate" class="mt-1 text-sm text-red-500">
                                {{ form.errors.hourly_rate }}
                            </p>
                            <p class="mt-1 text-sm text-gray-500">Le tarif doit être d'au moins 10€ par heure</p>
                        </div>

                        <!-- Enfants -->
                        <div>
                            <label class="mb-4 block text-sm font-medium text-gray-700"> Enfants à garder * </label>
                            <div class="space-y-3">
                                <div
                                    v-for="(child, index) in form.children"
                                    :key="index"
                                    class="flex items-center gap-4 rounded-lg border border-gray-200 p-4"
                                >
                                    <div class="flex-1">
                                        <input
                                            v-model="child.nom"
                                            type="text"
                                            placeholder="Prénom de l'enfant"
                                            required
                                            class="focus:border-primary focus:ring-primary w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-1 focus:outline-none"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <select
                                            v-model="child.age_range"
                                            required
                                            class="focus:border-primary focus:ring-primary w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-1 focus:outline-none"
                                        >
                                            <option value="">Sélectionner l'âge</option>
                                            <option value="0-6-mois">0-6 mois</option>
                                            <option value="6-12-mois">6-12 mois</option>
                                            <option value="1-2-ans">1-2 ans</option>
                                            <option value="2-3-ans">2-3 ans</option>
                                            <option value="3-6-ans">3-6 ans</option>
                                            <option value="6-10-ans">6-10 ans</option>
                                            <option value="10-ans-plus">10 ans et +</option>
                                        </select>
                                    </div>
                                    <button
                                        v-if="form.children.length > 1"
                                        @click.prevent="removeChild(index)"
                                        type="button"
                                        class="text-red-600 hover:text-red-800"
                                    >
                                        <X class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                            <button @click.prevent="addChild" type="button" class="text-primary hover:text-primary/80 mt-3 flex items-center gap-2">
                                <Plus class="h-4 w-4" />
                                Ajouter un enfant
                            </button>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-4 border-t pt-6">
                            <button
                                @click.prevent="() => router.visit('/parent/annonces-et-reservations')"
                                type="button"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-primary hover:bg-primary/90 flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                            >
                                <Save v-if="!form.processing" class="h-4 w-4" />
                                <div v-else class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                {{ form.processing ? 'Modification...' : "Modifier l'annonce" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Save, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Child {
    nom: string;
    age_range: string;
}

interface Address {
    id: number;
    address: string;
    postal_code: string;
    country: string;
}

interface Announcement {
    id: number;
    title: string;
    additional_info?: string;
    date_start: string;
    date_end: string;
    hourly_rate: number;
    children: Child[];
    address: Address;
}

interface Props {
    announcement: Announcement;
}

const props = defineProps<Props>();
const page = usePage();

// Récupérer les informations utilisateur
const user = computed(() => (page.props.auth as any)?.user);
const userRoles = computed(() => user.value?.roles?.map((role: any) => role.name) || []);
const hasParentRole = computed(() => userRoles.value.includes('parent'));
const hasBabysitterRole = computed(() => userRoles.value.includes('babysitter'));

// Séparer la date et les heures pour les inputs
const dateOnly = ref(props.announcement.date_start.split('T')[0]);
const timeStart = ref(props.announcement.date_start.split('T')[1]?.substring(0, 5) || '');
const timeEnd = ref(props.announcement.date_end.split('T')[1]?.substring(0, 5) || '');

// Formulaire
const form = useForm({
    title: props.announcement.title,
    additional_info: props.announcement.additional_info || '',
    date_start: props.announcement.date_start,
    date_end: props.announcement.date_end,
    hourly_rate: props.announcement.hourly_rate,
    children: [...props.announcement.children],
});

// Fonction pour générer automatiquement le titre
const generateTitle = () => {
    if (dateOnly.value && timeStart.value && timeEnd.value && form.children.length > 0) {
        const childrenNames = form.children
            .filter(child => child.nom.trim() !== '')
            .map(child => child.nom.trim())
            .join(', ');
        
        if (childrenNames) {
            const date = new Date(dateOnly.value).toLocaleDateString('fr-FR');
            form.title = `Garde de ${childrenNames} le ${date} de ${timeStart.value} à ${timeEnd.value}`;
        }
    }
};

// Watchers pour reconstruire les dates complètes et régénérer le titre
watch([dateOnly, timeStart], () => {
    if (dateOnly.value && timeStart.value) {
        form.date_start = `${dateOnly.value}T${timeStart.value}:00.000Z`;
        generateTitle();
    }
});

watch([dateOnly, timeEnd], () => {
    if (dateOnly.value && timeEnd.value) {
        form.date_end = `${dateOnly.value}T${timeEnd.value}:00.000Z`;
        generateTitle();
    }
});

// Watcher pour les noms d'enfants
watch(() => form.children, () => {
    generateTitle();
}, { deep: true });

// Gestion des enfants
const addChild = () => {
    form.children.push({ nom: '', age_range: '' });
};

const removeChild = (index: number) => {
    form.children.splice(index, 1);
};

// Soumission du formulaire
const submit = () => {
    form.put(`/parent/annonces/${props.announcement.id}`, {
        onSuccess: () => {
            router.visit('/parent/annonces-et-reservations');
        },
    });
};
</script>
