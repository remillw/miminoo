<script setup lang="ts">
import AdminLayout from '@/components/AdminLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';
import { useToast } from '@/composables/useToast';
import type { Column } from '@/types/datatable';
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, Eye, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface Address {
    id: number;
    address: string;
    postal_code: string;
    country: string;
}

interface BabysitterProfile {
    id: number;
    user_id: number;
    verification_status: 'pending' | 'verified' | 'rejected';
    hourly_rate?: number;
    experience_years?: number;
}

interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    phone?: string;
    status: string;
    created_at: string;
    address?: Address;
    babysitter_profile?: BabysitterProfile;
    applications_count: number;
    received_reviews_count: number;
}

interface Props {
    babysitters: {
        data: User[];
        meta: any;
        links: any;
    };
    filters: {
        search?: string;
        status?: string;
    };
}

const props = defineProps<Props>();

const showDeleteModal = ref(false);
const babysitterToDelete = ref<User | null>(null);
const isDeleting = ref(false);
const { showSuccess, showError } = useToast();

const columns: Column<User>[] = [
    {
        key: 'firstname',
        label: 'Babysitter',
        sortable: true,
        searchable: true,
        slot: 'babysitter',
        width: '250px',
    },
    {
        key: 'email',
        label: 'Email',
        sortable: true,
        searchable: true,
        width: '200px',
    },
    {
        key: 'phone',
        label: 'Téléphone',
        sortable: false,
        width: '150px',
        render: (value) => value || 'Non renseigné',
    },
    {
        key: 'address.postal_code',
        label: 'Code postal',
        sortable: true,
        width: '120px',
        render: (value, item) => item.address?.postal_code || 'Non renseigné',
    },
    {
        key: 'babysitter_profile.hourly_rate',
        label: 'Tarif',
        sortable: true,
        width: '100px',
        render: (value, item) => (item.babysitter_profile?.hourly_rate ? `${item.babysitter_profile.hourly_rate}€/h` : 'Non renseigné'),
    },
    {
        key: 'babysitter_profile.verification_status',
        label: 'Vérification',
        sortable: true,
        slot: 'verification',
        width: '120px',
    },
    {
        key: 'applications_count',
        label: 'Candidatures',
        sortable: true,
        width: '100px',
        render: (value) => `${value || 0}`,
    },
    {
        key: 'received_reviews_count',
        label: 'Avis reçus',
        sortable: true,
        width: '100px',
        render: (value) => `${value || 0}`,
    },
    {
        key: 'created_at',
        label: 'Inscription',
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
        width: '200px',
    },
];

const viewBabysitter = (babysitterId: number) => {
    router.visit(`/admin/utilisateurs/${babysitterId}`);
};

const editBabysitter = (babysitterId: number) => {
    router.visit(`/admin/utilisateurs/${babysitterId}/modifier`);
};

const deleteBabysitter = (babysitter: User) => {
    babysitterToDelete.value = babysitter;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!babysitterToDelete.value) return;

    isDeleting.value = true;
    router.delete(`/admin/utilisateurs/${babysitterToDelete.value.id}`, {
        onSuccess: () => {
            showSuccess(
                'Babysitter supprimée',
                `La babysitter ${babysitterToDelete.value?.firstname} ${babysitterToDelete.value?.lastname} a été supprimée avec succès.`,
            );
            showDeleteModal.value = false;
            babysitterToDelete.value = null;
        },
        onError: () => {
            showError('Erreur', 'Une erreur est survenue lors de la suppression de la babysitter.');
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const getVerificationClass = (status: string) => {
    switch (status) {
        case 'verified':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getVerificationText = (status: string) => {
    switch (status) {
        case 'verified':
            return 'Vérifiée';
        case 'pending':
            return 'En attente';
        case 'rejected':
            return 'Rejetée';
        default:
            return status;
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Gestion des Babysitters" />

    <AdminLayout title="Gestion des Babysitters">
        <!-- Header Section -->
        <div class="mb-6 md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl leading-7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Gestion des Babysitters</h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ babysitters.meta?.total || 0 }} babysitter{{ (babysitters.meta?.total || 0) !== 1 ? 's' : '' }} enregistrée{{
                        (babysitters.meta?.total || 0) !== 1 ? 's' : ''
                    }}
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <Button as-child>
                    <Link href="/admin/utilisateurs/creer">
                        <Plus class="mr-2 h-4 w-4" />
                        Nouvelle babysitter
                    </Link>
                </Button>
            </div>
        </div>

        <!-- DataTable -->
        <DataTable 
            :data="babysitters.data" 
            :columns="columns" 
            :pagination="babysitters.meta"
            :links="babysitters.links"
            search-placeholder="Rechercher une babysitter..." 
            empty-message="Aucune babysitter trouvée"
        >
            <!-- Colonne babysitter -->
            <template #babysitter="{ item }">
                <div class="flex items-center space-x-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200">
                        <span class="text-sm font-medium text-gray-600"> {{ item.firstname.charAt(0) }}{{ item.lastname.charAt(0) }} </span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">{{ item.firstname }} {{ item.lastname }}</div>
                        <div class="text-sm text-gray-500">
                            {{
                                item.babysitter_profile?.experience_years
                                    ? `${item.babysitter_profile.experience_years} ans d'expérience`
                                    : 'Expérience non renseignée'
                            }}
                        </div>
                    </div>
                </div>
            </template>

            <!-- Colonne vérification -->
            <template #verification="{ item }">
                <span
                    :class="[
                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                        getVerificationClass(item.babysitter_profile?.verification_status || 'pending'),
                    ]"
                >
                    {{ getVerificationText(item.babysitter_profile?.verification_status || 'pending') }}
                </span>
            </template>

            <!-- Colonne actions -->
            <template #actions="{ item }">
                <div class="flex items-center space-x-2">
                    <Button size="sm" variant="outline" @click="viewBabysitter(item.id)">
                        <Eye class="h-4 w-4" />
                    </Button>
                    <Button size="sm" variant="outline" @click="editBabysitter(item.id)">
                        <Edit class="h-4 w-4" />
                    </Button>
                    <Button size="sm" variant="destructive" @click="deleteBabysitter(item)">
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </template>
        </DataTable>

        <!-- Modal de confirmation de suppression -->
        <ConfirmModal
            :open="showDeleteModal"
            type="danger"
            title="Supprimer la babysitter"
            :description="`Êtes-vous sûr de vouloir supprimer la babysitter ${babysitterToDelete?.firstname} ${babysitterToDelete?.lastname} ?`"
            details="Cette action est irréversible. Toutes les données associées seront supprimées."
            confirm-text="Supprimer"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="
                babysitterToDelete = null;
                showDeleteModal = false;
            "
        />
    </AdminLayout>
</template>
