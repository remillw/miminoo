<script setup lang="ts">
import AdminLayout from '@/components/AdminLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';
import { useToast } from '@/composables/useToast';
import { useStatusColors } from '@/composables/useStatusColors';
import type { Column } from '@/types/datatable';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Eye, Edit, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import type { Address, User, Announcement, PaginatedData } from '@/types';

interface Parent extends User {
    // Propriétés spécifiques au parent si nécessaire
}

interface ExtendedAnnouncement extends Announcement {
    applications_count: number;
    reservations_count: number;
    parent: Parent;
    address: Address;
}

interface Props {
    announcements: PaginatedData<ExtendedAnnouncement>;
    filters: {
        search?: string;
        status?: string;
    };
}

const props = defineProps<Props>();
const { showSuccess, showError } = useToast();
const { getAnnouncementStatusColor, getStatusText } = useStatusColors();

const searchTerm = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const showDeleteModal = ref(false);
const announcementToDelete = ref<ExtendedAnnouncement | null>(null);
const isDeleting = ref(false);

const columns: Column<ExtendedAnnouncement>[] = [
    {
        key: 'title',
        label: 'Annonce',
        sortable: true,
        searchable: true,
        slot: 'announcement',
        width: '300px',
    },
    {
        key: 'parent',
        label: 'Parent',
        sortable: false,
        searchable: false,
        slot: 'parent',
        width: '200px',
    },
    {
        key: 'hourly_rate',
        label: 'Tarif',
        sortable: true,
        width: '100px',
        render: (value) => `${value}€/h`,
    },
    {
        key: 'date_start',
        label: 'Date',
        sortable: true,
        width: '150px',
        render: (value) => new Date(value).toLocaleDateString('fr-FR'),
    },
    {
        key: 'applications_count',
        label: 'Candidatures',
        sortable: true,
        width: '100px',
        render: (value) => `${value || 0}`,
    },
    {
        key: 'reservations_count',
        label: 'Réservations',
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
        key: 'actions',
        label: 'Actions',
        sortable: false,
        searchable: false,
        slot: 'actions',
        width: '200px',
    },
];

const search = () => {
    router.get('/admin/annonces', {
        search: searchTerm.value,
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const viewAnnouncement = (announcement: ExtendedAnnouncement) => {
    // Rediriger vers la page publique de l'annonce
    window.open(`/annonce/${announcement.slug || announcement.id}`, '_blank');
};

const editAnnouncement = (announcementId: number) => {
            router.visit(`/admin/admin-annonces/${announcementId}/modifier`);
};

const deleteAnnouncement = (announcement: ExtendedAnnouncement) => {
    announcementToDelete.value = announcement;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!announcementToDelete.value) return;
    
    isDeleting.value = true;
    router.delete(`/admin/annonces/${announcementToDelete.value.id}`, {
        onSuccess: () => {
            showSuccess(
                'Annonce supprimée',
                `L'annonce "${announcementToDelete.value?.title}" a été supprimée avec succès.`
            );
            showDeleteModal.value = false;
            announcementToDelete.value = null;
        },
        onError: () => {
            showError(
                'Erreur',
                'Une erreur est survenue lors de la suppression de l\'annonce.'
            );
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Gestion des Annonces" />

    <AdminLayout title="Gestion des Annonces">
        <!-- Header Section -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    Gestion des Annonces
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ announcements.meta?.total || 0 }} annonce{{ (announcements.meta?.total || 0) > 1 ? 's' : '' }} trouvée{{ (announcements.meta?.total || 0) > 1 ? 's' : '' }}
                </p>
            </div>
            <div class="mt-4 flex md:ml-4 md:mt-0">
                <Button as-child>
                    <Link href="/admin/annonces/creer">
                        <Plus class="mr-2 h-4 w-4" />
                        Nouvelle annonce
                    </Link>
                </Button>
            </div>
        </div>

        <!-- DataTable -->
        <DataTable
            :data="announcements.data"
            :columns="columns"
            :pagination="announcements.meta"
            :links="announcements.links"
            search-placeholder="Rechercher une annonce..."
            empty-message="Aucune annonce trouvée"
        >
            <!-- Colonne annonce -->
            <template #announcement="{ item }">
                <div>
                    <div class="font-medium text-gray-900">
                        {{ item.title }}
                    </div>
                    <div class="text-sm text-gray-500 line-clamp-2">
                        {{ item.description?.substring(0, 80) }}{{ item.description && item.description.length > 80 ? '...' : '' }}
                    </div>
                </div>
            </template>

            <!-- Colonne parent -->
            <template #parent="{ item }">
                <div>
                    <div class="font-medium text-gray-900">
                        {{ item.parent.firstname }} {{ item.parent.lastname }}
                    </div>
                    <div class="text-sm text-gray-500">
                        Parent
                    </div>
                </div>
            </template>

            <!-- Colonne statut -->
            <template #status="{ item }">
                <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', getAnnouncementStatusColor(item.status).badge]">
                    {{ getStatusText('announcement', item.status) }}
                </span>
            </template>

            <!-- Colonne actions -->
            <template #actions="{ item }">
                <div class="flex items-center space-x-2">
                    <Button size="sm" variant="outline" @click="viewAnnouncement(item)">
                        <Eye class="h-4 w-4" />
                    </Button>
                    <Button size="sm" variant="outline" @click="editAnnouncement(item.id)">
                        <Edit class="h-4 w-4" />
                    </Button>
                    <Button size="sm" variant="destructive" @click="deleteAnnouncement(item)">
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </template>
        </DataTable>

        <!-- Modal de confirmation de suppression -->
        <ConfirmModal
            :open="showDeleteModal"
            type="danger"
            title="Supprimer l'annonce"
            :description="`Êtes-vous sûr de vouloir supprimer l'annonce ${announcementToDelete?.title} ?`"
            details="Cette action est irréversible. Toutes les candidatures associées seront également supprimées."
            confirm-text="Supprimer"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="announcementToDelete = null; showDeleteModal = false"
        />
    </AdminLayout>
</template> 