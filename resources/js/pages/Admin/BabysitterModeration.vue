<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import type { Column } from '@/types/datatable';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CheckCircle, Eye, Mail, MapPin, ShieldAlert, TrendingUp, Users, XCircle } from 'lucide-vue-next';
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
        await router.post(`/admin/babysitter-moderation/${babysitter.id}/verify`, {
            status,
            rejection_reason: status === 'rejected' ? rejectionReason.value : null,
        });

        // Reset form
        rejectionReason.value = '';
        selectedBabysitter.value = null;
    } catch (error) {
        console.error('Erreur lors de la vérification:', error);
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

    <div v-if="isAdmin" class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="border-b bg-white shadow-sm">
            <div class="flex h-16 items-center px-6">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-semibold text-gray-900">Modération des profils babysitter</h1>
                </div>
                <div class="ml-auto flex items-center space-x-4">
                    <span class="rounded-md border border-gray-200 bg-blue-50 px-2 py-1 text-sm text-blue-700">
                        {{ auth.user?.firstname }} {{ auth.user?.lastname }}
                    </span>
                </div>
            </div>
        </header>

        <div class="flex">
            <!-- Sidebar -->
            <nav class="w-64 border-r bg-white">
                <div class="p-6">
                    <div class="space-y-2">
                        <Link href="/admin" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <TrendingUp class="h-4 w-4" />
                            <span>Dashboard</span>
                        </Link>

                        <Link href="/admin/babysitter-moderation" class="flex items-center space-x-2 rounded-lg bg-blue-50 px-3 py-2 text-blue-700">
                            <ShieldAlert class="h-4 w-4" />
                            <span class="flex-1">Modération</span>
                            <span v-if="pendingBabysitters.length > 0" class="ml-auto rounded-full bg-red-500 px-2 py-1 text-xs text-white">
                                {{ pendingBabysitters.length }}
                            </span>
                        </Link>

                        <Link href="/dashboard" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <Users class="h-4 w-4" />
                            <span>Retour utilisateur</span>
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="flex-1 p-6">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Demandes de vérification</h2>
                    <p class="text-gray-600">
                        {{ pendingBabysitters.length }} profil{{ pendingBabysitters.length !== 1 ? 's' : '' }} en attente de vérification
                    </p>
                </div>

                <!-- DataTable avec slots personnalisés -->
                <DataTable
                    :data="pendingBabysitters"
                    :columns="columns"
                    search-placeholder="Rechercher par nom, email..."
                    empty-message="Aucune demande de vérification en attente"
                >
                    <!-- Slot babysitter -->
                    <template #babysitter="{ item }">
                        <div class="flex items-center space-x-3">
                            <img
                                :src="item.avatar || '/storage/default-avatar.png'"
                                :alt="`${item.firstname} ${item.lastname}`"
                                class="h-10 w-10 rounded-full object-cover"
                            />
                            <div>
                                <div class="font-medium text-gray-900">{{ item.firstname }} {{ item.lastname }}</div>
                                <div class="text-sm text-gray-500">
                                    Inscrit{{ item.firstname.endsWith('e') ? 'e' : '' }} le {{ formatDate(item.created_at) }}
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Slot email -->
                    <template #email="{ item }">
                        <div class="flex items-center space-x-2">
                            <Mail class="h-4 w-4 text-gray-400" />
                            <span class="text-sm">{{ item.email }}</span>
                        </div>
                    </template>

                    <!-- Slot location -->
                    <template #location="{ item }">
                        <div v-if="item.address" class="flex items-center space-x-2">
                            <MapPin class="h-4 w-4 text-gray-400" />
                            <span class="text-sm">{{ item.address.postal_code }}</span>
                        </div>
                        <span v-else class="text-sm text-gray-400">Non renseigné</span>
                    </template>

                    <!-- Slot actions -->
                    <template #actions="{ item }">
                        <div class="flex items-center space-x-2">
                            <!-- Bouton Voir le profil -->
                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button variant="outline" size="sm" @click="selectedBabysitter = item">
                                        <Eye class="mr-1 h-4 w-4" />
                                        Voir
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="max-w-2xl">
                                    <DialogHeader>
                                        <DialogTitle>Profil de {{ item.firstname }} {{ item.lastname }}</DialogTitle>
                                    </DialogHeader>
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <div><strong>Email :</strong> {{ item.email }}</div>
                                            <div>
                                                <strong>Expérience :</strong>
                                                {{
                                                    item.babysitter_profile.experience_years
                                                        ? `${item.babysitter_profile.experience_years} année${item.babysitter_profile.experience_years > 1 ? 's' : ''}`
                                                        : 'Non renseigné'
                                                }}
                                            </div>
                                            <div>
                                                <strong>Tarif :</strong>
                                                {{
                                                    item.babysitter_profile.hourly_rate
                                                        ? `${item.babysitter_profile.hourly_rate}€/h`
                                                        : 'Non renseigné'
                                                }}
                                            </div>
                                            <div v-if="item.address"><strong>Adresse :</strong> {{ item.address.address }}</div>
                                        </div>
                                        <div v-if="item.babysitter_profile.bio">
                                            <strong>Bio :</strong>
                                            <p class="mt-1 text-gray-700">{{ item.babysitter_profile.bio }}</p>
                                        </div>
                                    </div>
                                </DialogContent>
                            </Dialog>

                            <!-- Bouton Valider -->
                            <Button size="sm" @click="handleVerify(item, 'verified')" :disabled="isProcessing">
                                <CheckCircle class="mr-1 h-4 w-4" />
                                Valider
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
                                        <DialogTitle>Rejeter le profil de {{ item.firstname }} {{ item.lastname }}</DialogTitle>
                                    </DialogHeader>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="text-sm font-medium">Raison du rejet :</label>
                                            <Textarea
                                                v-model="rejectionReason"
                                                placeholder="Expliquez la raison du rejet (obligatoire)..."
                                                class="mt-1 min-h-[100px]"
                                            />
                                        </div>
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                @click="
                                                    selectedBabysitter = null;
                                                    rejectionReason = '';
                                                "
                                            >
                                                Annuler
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                @click="handleVerify(item, 'rejected')"
                                                :disabled="!rejectionReason.trim() || isProcessing"
                                            >
                                                Confirmer le rejet
                                            </Button>
                                        </div>
                                    </div>
                                </DialogContent>
                            </Dialog>
                        </div>
                    </template>
                </DataTable>
            </main>
        </div>
    </div>

    <!-- Loading state pendant vérification -->
    <div v-else class="flex min-h-screen items-center justify-center">
        <div class="text-center">
            <div class="mx-auto mb-4 h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-blue-600"></div>
            <p class="text-gray-600">Vérification des permissions...</p>
        </div>
    </div>
</template>
