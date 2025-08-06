<script setup lang="ts">
import AdminLayout from '@/components/AdminLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import ConfirmModal from '@/components/ui/ConfirmModal.vue';
import { useToast } from '@/composables/useToast';
import type { Column } from '@/types/datatable';
import { Head, router } from '@inertiajs/vue3';
import { Eye, Star, StarHalf, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
}

interface Review {
    id: number;
    rating: number;
    comment: string;
    created_at: string;
    reviewer: User; // Celui qui donne l'avis
    reviewee: User; // Celui qui reçoit l'avis
    reservation_id: number;
}

interface Props {
    reviews: Review[];
}

const props = defineProps<Props>();

const showDeleteModal = ref(false);
const reviewToDelete = ref<Review | null>(null);
const isDeleting = ref(false);
const { showSuccess, showError } = useToast();

const columns: Column<Review>[] = [
    {
        key: 'reviewer',
        label: 'Évaluateur',
        sortable: true,
        searchable: true,
        slot: 'reviewer',
        width: '200px',
    },
    {
        key: 'reviewee',
        label: 'Évalué',
        sortable: true,
        searchable: true,
        slot: 'reviewee',
        width: '200px',
    },
    {
        key: 'rating',
        label: 'Note',
        sortable: true,
        slot: 'rating',
        width: '120px',
    },
    {
        key: 'comment',
        label: 'Commentaire',
        sortable: false,
        searchable: true,
        slot: 'comment',
        width: '300px',
    },
    {
        key: 'created_at',
        label: 'Date',
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
        width: '150px',
    },
];

const viewReview = (reviewId: number) => {
    router.visit(`/admin/avis/${reviewId}`);
};

const deleteReview = (review: Review) => {
    reviewToDelete.value = review;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!reviewToDelete.value) return;

    isDeleting.value = true;
    router.delete(`/admin/avis/${reviewToDelete.value.id}`, {
        onSuccess: () => {
            showSuccess('Avis supprimé', `L'avis a été supprimé avec succès.`);
            showDeleteModal.value = false;
            reviewToDelete.value = null;
        },
        onError: () => {
            showError('Erreur', "Une erreur est survenue lors de la suppression de l'avis.");
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const renderStars = (rating: number) => {
    const stars = [];
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;

    for (let i = 0; i < fullStars; i++) {
        stars.push('full');
    }

    if (hasHalfStar) {
        stars.push('half');
    }

    return stars;
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
    <Head title="Gestion des Avis" />

    <AdminLayout title="Gestion des Avis">
        <!-- Header Section -->
        <div class="mb-6 md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl leading-7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Gestion des Avis</h2>
                <p class="mt-1 text-sm text-gray-500">{{ reviews.length }} avis {{ reviews.length !== 1 ? 'enregistrés' : 'enregistré' }}</p>
            </div>
        </div>

        <!-- DataTable -->
        <DataTable :data="reviews" :columns="columns" search-placeholder="Rechercher un avis..." empty-message="Aucun avis trouvé">
            <!-- Colonne évaluateur -->
            <template #reviewer="{ item }">
                <div class="flex items-center space-x-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200">
                        <span class="text-xs font-medium text-gray-600">
                            {{ item.reviewer.firstname.charAt(0) }}{{ item.reviewer.lastname.charAt(0) }}
                        </span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">{{ item.reviewer.firstname }} {{ item.reviewer.lastname }}</div>
                        <div class="text-sm text-gray-500">
                            {{ item.reviewer.email }}
                        </div>
                    </div>
                </div>
            </template>

            <!-- Colonne évalué -->
            <template #reviewee="{ item }">
                <div class="flex items-center space-x-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200">
                        <span class="text-xs font-medium text-gray-600">
                            {{ item.reviewee.firstname.charAt(0) }}{{ item.reviewee.lastname.charAt(0) }}
                        </span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">{{ item.reviewee.firstname }} {{ item.reviewee.lastname }}</div>
                        <div class="text-sm text-gray-500">
                            {{ item.reviewee.email }}
                        </div>
                    </div>
                </div>
            </template>

            <!-- Colonne note -->
            <template #rating="{ item }">
                <div class="flex items-center space-x-1">
                    <template v-for="(star, index) in renderStars(item.rating)" :key="index">
                        <Star v-if="star === 'full'" class="h-4 w-4 fill-yellow-400 text-yellow-400" />
                        <StarHalf v-else class="h-4 w-4 fill-yellow-400 text-yellow-400" />
                    </template>
                    <span class="ml-1 text-sm text-gray-600">{{ item.rating }}/5</span>
                </div>
            </template>

            <!-- Colonne commentaire -->
            <template #comment="{ item }">
                <div class="text-sm text-gray-900">
                    <p class="line-clamp-3">
                        {{ item.comment || 'Aucun commentaire' }}
                    </p>
                </div>
            </template>

            <!-- Colonne actions -->
            <template #actions="{ item }">
                <div class="flex items-center space-x-2">
                    <Button size="sm" variant="outline" @click="viewReview(item.id)">
                        <Eye class="h-4 w-4" />
                    </Button>
                    <Button size="sm" variant="destructive" @click="deleteReview(item)">
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </template>
        </DataTable>

        <!-- Modal de confirmation de suppression -->
        <ConfirmModal
            :open="showDeleteModal"
            type="danger"
            title="Supprimer l'avis"
            description="Êtes-vous sûr de vouloir supprimer cet avis ?"
            details="Cette action est irréversible. L'avis sera définitivement supprimé."
            confirm-text="Supprimer"
            :loading="isDeleting"
            @confirm="confirmDelete"
            @cancel="
                reviewToDelete = null;
                showDeleteModal = false;
            "
        />
    </AdminLayout>
</template>
