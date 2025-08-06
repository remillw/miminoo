<script setup lang="ts">
import AdminLayout from '@/components/AdminLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
import type { Column } from '@/types/datatable';
import { Head, router, usePage } from '@inertiajs/vue3';
import { CheckCircle, Eye, Mail, MapPin, XCircle } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface BabysitterProfile {
    id: number;
    bio?: string;
    experience_years?: number;
    hourly_rate?: number;
    verification_status: 'pending' | 'verified' | 'rejected';
    rejection_reason?: string;
    verified_at?: string;
    verified_by?: number;
    created_at: string;
}

interface Address {
    address: string;
    postal_code: string;
    country: string;
}

interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    avatar?: string;
    created_at: string;
    roles?: { name: string; label: string }[];
    babysitter_profile: BabysitterProfile;
    address?: Address;
}

interface Props {
    pendingBabysitters: User[];
}

const props = defineProps<Props>();
const page = usePage();
const auth = page.props.auth as { user: User | null };
const { showSuccess, showError } = useToast();

// Vérification que l'utilisateur est admin côté frontend
const isAdmin = computed(() => {
    return auth.user?.roles?.some((role) => role.name === 'admin') || false;
});

// Si pas admin, rediriger vers 403
onMounted(() => {
    if (!isAdmin.value) {
        router.visit('/403', { method: 'get' });
    }
});

const selectedBabysitter = ref<User | null>(null);
const rejectionReason = ref('');
const isProcessing = ref(false);

// Configuration des colonnes pour le DataTable
const columns: Column<User>[] = [
    {
        key: 'firstname',
        label: 'Babysitter',
        sortable: true,
        searchable: true,
        slot: 'babysitter',
        width: '300px',
    },
    {
        key: 'email',
        label: 'Email',
        sortable: true,
        searchable: true,
        slot: 'email',
        width: '250px',
    },
    {
        key: 'address.postal_code',
        label: 'Code postal',
        sortable: true,
        searchable: true,
        slot: 'location',
        width: '120px',
    },
    {
        key: 'babysitter_profile.experience_years',
        label: 'Expérience',
        sortable: true,
        render: (value) => (value ? `${value} année${value > 1 ? 's' : ''}` : 'Non renseigné'),
        width: '120px',
    },
    {
        key: 'babysitter_profile.hourly_rate',
        label: 'Tarif',
        sortable: true,
        render: (value) => (value ? `${value}€/h` : 'Non renseigné'),
        width: '100px',
    },
    {
        key: 'babysitter_profile.created_at',
        label: 'Demande',
        sortable: true,
        render: (value) => new Date(value).toLocaleDateString('fr-FR'),
        width: '120px',
    },
    {
        key: 'actions',
        label: 'Actions',
        sortable: false,
        searchable: false,
        slot: 'actions',
        width: '300px',
    },
];

const handleVerify = async (babysitter: User, status: 'verified' | 'rejected') => {
    if (isProcessing.value) return;

    isProcessing.value = true;

    try {
        await router.post(
            `/admin/moderation-babysitters/${babysitter.id}/verify`,
            {
                status,
                rejection_reason: status === 'rejected' ? rejectionReason.value : null,
            },
            {
                onSuccess: () => {
                    showSuccess(
                        status === 'verified' ? 'Profil approuvé' : 'Profil rejeté',
                        `Le profil de ${babysitter.firstname} ${babysitter.lastname} a été ${status === 'verified' ? 'approuvé' : 'rejeté'}.`,
                    );
                },
                onError: () => {
                    showError('Erreur', 'Une erreur est survenue lors de la vérification du profil.');
                },
            },
        );

        // Reset form
        rejectionReason.value = '';
        selectedBabysitter.value = null;
    } catch (error) {
        console.error('Erreur lors de la vérification:', error);
        showError('Erreur', 'Une erreur inattendue est survenue.');
    } finally {
        isProcessing.value = false;
    }
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Modération des profils babysitter" />

    <AdminLayout
        v-if="isAdmin"
        title="Modération des Babysitters"
        :description="`${pendingBabysitters.length} profil${pendingBabysitters.length !== 1 ? 's' : ''} en attente de vérification`"
    >
        <!-- Header Section -->
        <div class="mb-6 md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl leading-7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Modération des Babysitters</h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ pendingBabysitters.length }} profil{{ pendingBabysitters.length !== 1 ? 's' : '' }} en attente de vérification
                </p>
            </div>
        </div>

        <!-- Statistiques -->
        <div v-if="pendingBabysitters.length === 0" class="py-12 text-center">
            <CheckCircle class="mx-auto h-12 w-12 text-green-500" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune modération en attente</h3>
            <p class="mt-1 text-sm text-gray-500">Tous les profils babysitter ont été traités.</p>
        </div>

        <!-- DataTable -->
        <DataTable
            v-else
            :data="pendingBabysitters"
            :columns="columns"
            search-placeholder="Rechercher une babysitter..."
            empty-message="Aucune babysitter en attente"
        >
            <!-- Colonne babysitter -->
            <template #babysitter="{ item }">
                <div class="flex items-center space-x-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200">
                        <span class="text-sm font-medium text-gray-600"> {{ item.firstname.charAt(0) }}{{ item.lastname.charAt(0) }} </span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">{{ item.firstname }} {{ item.lastname }}</div>
                        <div class="text-sm text-gray-500">Membre depuis {{ formatDate(item.created_at) }}</div>
                    </div>
                </div>
            </template>

            <!-- Colonne email -->
            <template #email="{ item }">
                <div class="flex items-center space-x-2">
                    <Mail class="h-4 w-4 text-gray-400" />
                    <span class="text-sm text-gray-900">{{ item.email }}</span>
                </div>
            </template>

            <!-- Colonne localisation -->
            <template #location="{ item }">
                <div class="flex items-center space-x-2">
                    <MapPin class="h-4 w-4 text-gray-400" />
                    <span class="text-sm text-gray-900">{{ item.address?.postal_code || 'Non renseigné' }}</span>
                </div>
            </template>

            <!-- Colonne actions -->
            <template #actions="{ item }">
                <div class="flex items-center space-x-2">
                    <!-- Bouton Voir le profil -->
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="outline" size="sm">
                                <Eye class="mr-1 h-4 w-4" />
                                Voir
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="max-w-2xl">
                            <DialogHeader>
                                <DialogTitle>Profil de {{ item.firstname }} {{ item.lastname }}</DialogTitle>
                            </DialogHeader>
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="font-medium text-gray-900">Informations personnelles</h4>
                                        <div class="mt-2 space-y-2">
                                            <p class="text-sm"><span class="font-medium">Email:</span> {{ item.email }}</p>
                                            <p class="text-sm">
                                                <span class="font-medium">Adresse:</span> {{ item.address?.address || 'Non renseignée' }}
                                            </p>
                                            <p class="text-sm">
                                                <span class="font-medium">Code postal:</span> {{ item.address?.postal_code || 'Non renseigné' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">Profil babysitter</h4>
                                        <div class="mt-2 space-y-2">
                                            <p class="text-sm">
                                                <span class="font-medium">Expérience:</span>
                                                {{ item.babysitter_profile.experience_years || 0 }} année(s)
                                            </p>
                                            <p class="text-sm">
                                                <span class="font-medium">Tarif:</span>
                                                {{ item.babysitter_profile.hourly_rate || 'Non renseigné' }}€/h
                                            </p>
                                            <p class="text-sm">
                                                <span class="font-medium">Demande créée:</span> {{ formatDate(item.babysitter_profile.created_at) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="item.babysitter_profile.bio">
                                    <h4 class="font-medium text-gray-900">Présentation</h4>
                                    <p class="mt-2 text-sm text-gray-600">{{ item.babysitter_profile.bio }}</p>
                                </div>
                            </div>
                        </DialogContent>
                    </Dialog>

                    <!-- Bouton Approuver -->
                    <Button
                        variant="default"
                        size="sm"
                        :loading="isProcessing"
                        @click="handleVerify(item, 'verified')"
                        class="bg-green-600 hover:bg-green-700"
                    >
                        <CheckCircle class="mr-1 h-4 w-4" />
                        Approuver
                    </Button>

                    <!-- Bouton Rejeter -->
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="destructive" size="sm" @click="selectedBabysitter = item">
                                <XCircle class="mr-1 h-4 w-4" />
                                Rejeter
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Rejeter le profil</DialogTitle>
                            </DialogHeader>
                            <div class="space-y-4">
                                <p class="text-sm text-gray-600">
                                    Vous êtes sur le point de rejeter le profil de {{ selectedBabysitter?.firstname }}
                                    {{ selectedBabysitter?.lastname }}. Veuillez indiquer la raison du refus :
                                </p>
                                <Textarea v-model="rejectionReason" placeholder="Raison du refus (optionnel)" rows="3" />
                                <div class="flex justify-end space-x-2">
                                    <Button variant="outline"> Annuler </Button>
                                    <Button variant="destructive" :loading="isProcessing" @click="handleVerify(selectedBabysitter!, 'rejected')">
                                        Confirmer le rejet
                                    </Button>
                                </div>
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>
            </template>
        </DataTable>
    </AdminLayout>

    <!-- Redirection si pas admin -->
    <div v-else>
        <Head title="Accès refusé" />
        <div class="flex min-h-screen items-center justify-center">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-gray-900">Accès refusé</h1>
                <p class="mt-2 text-gray-600">Vous n'avez pas les permissions nécessaires.</p>
            </div>
        </div>
    </div>
</template>
