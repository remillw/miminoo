<script setup lang="ts">
import AdminLayout from '@/components/AdminLayout.vue';
import DataTable from '@/components/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
import type { Column } from '@/types/datatable';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle, Clock, Eye, Mail, MessageSquare, Phone, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Contact {
    id: number;
    name: string;
    email: string;
    phone?: string;
    subject: string;
    subject_text: string;
    message: string;
    status: 'unread' | 'read' | 'replied';
    created_at: string;
    read_at?: string;
    admin_notes?: string;
}

interface Props {
    contacts: {
        data: Contact[];
        meta: any;
        links: any;
    };
    filters: {
        search?: string;
        status?: string;
        subject?: string;
    };
    stats: {
        total: number;
        unread: number;
        recent: number;
    };
}

const props = defineProps<Props>();

const selectedContact = ref<Contact | null>(null);
const showDetailModal = ref(false);
const adminNotes = ref('');
const contactStatus = ref<'unread' | 'read' | 'replied'>('read');
const { showSuccess, showError } = useToast();

// Configuration des colonnes pour le DataTable
const columns: Column<Contact>[] = [
    {
        key: 'name',
        label: 'Contact',
        sortable: true,
        searchable: true,
        slot: 'contact',
        width: '250px',
    },
    {
        key: 'subject_text',
        label: 'Sujet',
        sortable: true,
        searchable: true,
        slot: 'subject',
        width: '200px',
    },
    {
        key: 'message',
        label: 'Message',
        sortable: false,
        slot: 'message',
        width: '300px',
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
        label: 'Date',
        sortable: true,
        render: (value) =>
            new Date(value).toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            }),
        width: '150px',
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

const viewContact = (contact: Contact) => {
    selectedContact.value = contact;
    adminNotes.value = contact.admin_notes || '';
    contactStatus.value = contact.status;
    showDetailModal.value = true;

    // Marquer comme lu si pas encore lu
    if (contact.status === 'unread') {
        updateContactStatus(contact.id, 'read');
    }
};

const updateContactStatus = async (contactId: number, status: 'unread' | 'read' | 'replied', notes?: string) => {
    try {
        const response = await fetch(`/admin/contacts/${contactId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                status,
                admin_notes: notes,
            }),
        });

        const data = await response.json();

        if (data.success) {
            if (selectedContact.value && selectedContact.value.id === contactId) {
                selectedContact.value.status = status;
                if (notes !== undefined) selectedContact.value.admin_notes = notes;
            }

            // Mettre à jour le contact dans la liste
            const contactIndex = props.contacts.data.findIndex((c) => c.id === contactId);
            if (contactIndex !== -1) {
                props.contacts.data[contactIndex].status = status;
                if (notes !== undefined) props.contacts.data[contactIndex].admin_notes = notes;
            }

            showSuccess('✅ Contact mis à jour', data.message);
        } else {
            showError('❌ Erreur', data.message || 'Impossible de mettre à jour le contact');
        }
    } catch (error) {
        showError('❌ Erreur', 'Impossible de mettre à jour le contact');
    }
};

const saveContactNotes = () => {
    if (!selectedContact.value) return;

    updateContactStatus(selectedContact.value.id, contactStatus.value, adminNotes.value);
};

const contactToDelete = ref<Contact | null>(null);
const showDeleteModal = ref(false);

const confirmDeleteContact = (contact: Contact) => {
    contactToDelete.value = contact;
    showDeleteModal.value = true;
};

const deleteContact = async () => {
    if (!contactToDelete.value) return;

    try {
        const response = await fetch(`/admin/contacts/${contactToDelete.value.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                Accept: 'application/json',
            },
        });

        const data = await response.json();

        if (data.success) {
            showSuccess('🗑️ Contact supprimé', data.message);
            // Recharger la page ou retirer l'élément de la liste
            router.reload({ only: ['contacts'] });
        } else {
            showError('❌ Erreur', data.message || 'Impossible de supprimer le contact');
        }
    } catch (error) {
        showError('❌ Erreur', 'Impossible de supprimer le contact');
    } finally {
        showDeleteModal.value = false;
        contactToDelete.value = null;
    }
};

const getStatusClass = (status: string) => {
    switch (status) {
        case 'unread':
            return 'bg-orange-100 text-orange-800';
        case 'read':
            return 'bg-blue-100 text-blue-800';
        case 'replied':
            return 'bg-green-100 text-green-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getStatusText = (status: string) => {
    switch (status) {
        case 'unread':
            return 'Non lu';
        case 'read':
            return 'Lu';
        case 'replied':
            return 'Répondu';
        default:
            return status;
    }
};

const getSubjectClass = (subject: string) => {
    switch (subject) {
        case 'technique':
            return 'bg-red-100 text-red-800';
        case 'recherche':
            return 'bg-blue-100 text-blue-800';
        case 'inscription':
            return 'bg-green-100 text-green-800';
        case 'tarifs':
            return 'bg-yellow-100 text-yellow-800';
        case 'amélioration':
            return 'bg-purple-100 text-purple-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
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
    <Head title="Gestion des Contacts - Admin" />

    <AdminLayout title="Gestion des Contacts">
        <!-- Header Section -->
        <div class="mb-6 md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl leading-7 font-bold text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">Gestion des Contacts</h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ contacts.meta?.total || 0 }} demande{{ (contacts.meta?.total || 0) !== 1 ? 's' : '' }} de contact
                </p>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <MessageSquare class="h-8 w-8 text-blue-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ stats.total }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <Clock class="h-8 w-8 text-orange-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Non lus</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ stats.unread }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <CheckCircle class="h-8 w-8 text-green-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Récents (7j)</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ stats.recent }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <DataTable
            :data="contacts.data"
            :columns="columns"
            :pagination="contacts.meta"
            :links="contacts.links"
            search-placeholder="Rechercher un contact..."
            empty-message="Aucun contact trouvé"
        >
            <!-- Colonne contact -->
            <template #contact="{ item }">
                <div class="flex items-center space-x-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200">
                        <span class="text-sm font-medium text-gray-600">
                            {{ item.name.charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">{{ item.name }}</div>
                        <div class="flex items-center gap-1 text-sm text-gray-500">
                            <Mail class="h-3 w-3" />
                            {{ item.email }}
                        </div>
                        <div v-if="item.phone" class="flex items-center gap-1 text-sm text-gray-500">
                            <Phone class="h-3 w-3" />
                            {{ item.phone }}
                        </div>
                    </div>
                </div>
            </template>

            <!-- Colonne sujet -->
            <template #subject="{ item }">
                <Badge :class="getSubjectClass(item.subject)">
                    {{ item.subject_text }}
                </Badge>
            </template>

            <!-- Colonne message -->
            <template #message="{ item }">
                <div class="max-w-xs">
                    <p class="line-clamp-2 text-sm text-gray-900">{{ item.message.substring(0, 100) }}{{ item.message.length > 100 ? '...' : '' }}</p>
                </div>
            </template>

            <!-- Colonne statut -->
            <template #status="{ item }">
                <Badge :class="getStatusClass(item.status)">
                    {{ getStatusText(item.status) }}
                </Badge>
            </template>

            <!-- Colonne actions -->
            <template #actions="{ item }">
                <div class="flex items-center space-x-2">
                    <Button size="sm" variant="outline" @click="viewContact(item)">
                        <Eye class="h-4 w-4" />
                    </Button>
                    <Button size="sm" variant="destructive" @click="confirmDeleteContact(item)">
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </template>
        </DataTable>

        <!-- Modal détail contact -->
        <Dialog v-model:open="showDetailModal">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Détail du contact</DialogTitle>
                </DialogHeader>

                <div v-if="selectedContact" class="space-y-6">
                    <!-- Informations du contact -->
                    <div class="rounded-lg bg-gray-50 p-4">
                        <h3 class="mb-3 font-semibold text-gray-900">Informations</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Nom</label>
                                <p class="text-gray-900">{{ selectedContact.name }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Email</label>
                                <p class="text-gray-900">{{ selectedContact.email }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Téléphone</label>
                                <p class="text-gray-900">{{ selectedContact.phone || 'Non renseigné' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Date</label>
                                <p class="text-gray-900">{{ formatDate(selectedContact.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sujet et message -->
                    <div>
                        <label class="text-sm font-medium text-gray-500">Sujet</label>
                        <div class="mt-1">
                            <Badge :class="getSubjectClass(selectedContact.subject)">
                                {{ selectedContact.subject_text }}
                            </Badge>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Message</label>
                        <div class="mt-1 rounded-lg bg-gray-50 p-4">
                            <p class="whitespace-pre-wrap text-gray-900">{{ selectedContact.message }}</p>
                        </div>
                    </div>

                    <!-- Statut et notes admin -->
                    <div class="border-t pt-4">
                        <h3 class="mb-3 font-semibold text-gray-900">Administration</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Statut</label>
                                <Select v-model="contactStatus">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Sélectionner un statut" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="unread">Non lu</SelectItem>
                                        <SelectItem value="read">Lu</SelectItem>
                                        <SelectItem value="replied">Répondu</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="text-sm font-medium text-gray-500">Notes admin</label>
                            <Textarea v-model="adminNotes" placeholder="Notes internes..." rows="3" class="mt-1" />
                        </div>

                        <div class="mt-4 flex justify-end space-x-2">
                            <Button variant="outline" @click="showDetailModal = false"> Fermer </Button>
                            <Button @click="saveContactNotes"> Sauvegarder </Button>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Modal de confirmation de suppression -->
        <Dialog v-model:open="showDeleteModal">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Confirmer la suppression</DialogTitle>
                </DialogHeader>

                <div v-if="contactToDelete" class="space-y-4">
                    <p class="text-gray-600">
                        Êtes-vous sûr de vouloir supprimer le contact de
                        <strong>{{ contactToDelete.name }}</strong> ?
                    </p>
                    <p class="text-sm text-gray-500">Cette action est irréversible.</p>

                    <div class="flex justify-end space-x-2">
                        <Button variant="outline" @click="showDeleteModal = false"> Annuler </Button>
                        <Button variant="destructive" @click="deleteContact"> Supprimer </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
