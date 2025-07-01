<script setup lang="ts">
import AdminLayout from '@/components/AdminLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';
import { useToast } from '@/composables/useToast';
import type { Column } from '@/types/datatable';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Eye, Edit, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface Address {
    id: number;
    address: string;
    postal_code: string;
    country: string;
}

interface ParentProfile {
    id: number;
    user_id: number;
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
    parent_profile?: ParentProfile;
    ads_count: number;
    given_reviews_count: number;
}

interface Props {
    parents: User[];
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const showDeleteModal = ref(false);
const parentToDelete = ref<User | null>(null);
const isDeleting = ref(false);
const { showSuccess, showError } = useToast();

// Configuration des colonnes pour le DataTable
const columns: Column<User>[] = [
    {
        key: 'firstname',
        label: 'Parent',
        sortable: true,
        searchable: true,
        slot: 'parent',
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
        key: 'ads_count',
        label: 'Annonces',
        sortable: true,
        width: '100px',
        render: (value) => `${value || 0}`,
    },
    {
        key: 'given_reviews_count',
        label: 'Avis donnés',
        sortable: true,
        width: '100px',
        render: (value) => `${value || 0}`,
    },
    {
        key: 'status',
        label: 'Statut',
        sortable: true,
        slot: 'status',
        width: '120px',
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

const viewParent = (parentId: number) => {
    router.visit(`/admin/utilisateurs/${parentId}`);
};

const editParent = (parentId: number) => {
    router.visit(`/admin/utilisateurs/${parentId}/modifier`);
};

const deleteParent = (parent: User) => {
    parentToDelete.value = parent;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!parentToDelete.value) return;
    
    isDeleting.value = true;
    router.delete(`/admin/utilisateurs/${parentToDelete.value.id}`, {
        onSuccess: () => {
            showSuccess(
                'Parent supprimé',
                `Le parent ${parentToDelete.value?.firstname} ${parentToDelete.value?.lastname} a été supprimé avec succès.`
            );
            showDeleteModal.value = false;
            parentToDelete.value = null;
        },
        onError: () => {
            showError(
                'Erreur',
                'Une erreur est survenue lors de la suppression du parent.'
            );
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
};

const getStatusClass = (status: string) => {
    switch (status) {
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'suspended':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getStatusText = (status: string) => {
    switch (status) {
        case 'approved':
            return 'Approuvé';
        case 'pending':
            return 'En attente';
        case 'suspended':
            return 'Suspendu';
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
    <Head title="Gestion des Parents" />

    <AdminLayout title="Gestion des Parents">
        <!-- Header Section -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    Gestion des Parents
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ parents.length }} parent{{ parents.length !== 1 ? 's' : '' }} enregistré{{ parents.length !== 1 ? 's' : '' }}
                </p>
            </div>
            <div class="mt-4 flex md:ml-4 md:mt-0">
                <Button as-child>
                    <Link href="/admin/utilisateurs/creer">
                        <Plus class="mr-2 h-4 w-4" />
                        Nouveau parent
                    </Link>
                </Button>
            </div>
        </div>

        <!-- DataTable -->
        <DataTable
            :data="parents"
            :columns="columns"
            search-placeholder="Rechercher un parent..."
            empty-message="Aucun parent trouvé"
        >
            <!-- Colonne parent -->
            <template #parent="{ item }">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-600">
                            {{ item.firstname.charAt(0) }}{{ item.lastname.charAt(0) }}
                        </span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">
                            {{ item.firstname }} {{ item.lastname }}
                        </div>
                        <div class="text-sm text-gray-500">
                            Membre depuis {{ formatDate(item.created_at) }}
                        </div>
                    </div>
                </div>
            </template>

            <!-- Colonne statut -->
            <template #status="{ item }">
                <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', getStatusClass(item.status)]">
                    {{ getStatusText(item.status) }}
                </span>
            </template>

            <!-- Colonne actions -->
            <template #actions="{ item }">
                <div class="flex items-center space-x-2">
                    <Button size="sm" variant="outline" @click="viewParent(item.id)">
                        <Eye class="h-4 w-4" />
                    </Button>
                    <Button size="sm" variant="outline" @click="editParent(item.id)">
                        <Edit class="h-4 w-4" />
                    </Button>
                    <Button size="sm" variant="destructive" @click="deleteParent(item)">
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </template>
        </DataTable>

        <!-- Modal de confirmation de suppression -->
        <ConfirmModal
            :open="showDeleteModal"
            type="danger"
            title="Supprimer le parent"
            :description="`Êtes-vous sûr de vouloir supprimer le parent ${parentToDelete?.firstname} ${parentToDelete?.lastname} ?`"
            details="Cette action est irréversible. Toutes les données associées seront supprimées."
            confirm-text="Supprimer"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="parentToDelete = null; showDeleteModal = false"
        />
    </AdminLayout>
</template> 