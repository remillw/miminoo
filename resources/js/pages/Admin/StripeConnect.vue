<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle, Clock, CreditCard, Eye, RefreshCw, ShieldAlert, Trash2, TrendingUp, Users, XCircle } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    created_at: string;
    roles: string[];
    babysitter_profile?: any;
}

interface StripeAccount {
    id: string;
    email: string;
    type: string;
    country: string;
    default_currency: string;
    charges_enabled: boolean;
    payouts_enabled: boolean;
    details_submitted: boolean;
    created: number;
    requirements: {
        currently_due: string[];
        eventually_due: string[];
        past_due: string[];
        disabled_reason: string | null;
    };
    individual?: {
        first_name: string | null;
        last_name: string | null;
        verification: {
            status: string;
        };
    };
    error?: string;
}

interface Balance {
    available: Array<{ amount: number; currency: string }>;
    pending: Array<{ amount: number; currency: string }>;
}

interface Account {
    user: User | null;
    stripe_account: StripeAccount;
    balance: Balance | null;
    status: string;
    can_be_deleted: boolean;
    is_linked_to_user: boolean;
}

interface Stats {
    total_accounts: number;
    active_accounts: number;
    pending_accounts: number;
    rejected_accounts: number;
    deletable_accounts: number;
    linked_accounts: number;
    unlinked_accounts: number;
}

interface Props {
    accounts: Account[];
    stats: Stats;
}

const props = defineProps<Props>();
const page = usePage();
const auth = page.props.auth as { user: any };
const { showSuccess, showError } = useToast();

const accounts = ref<Account[]>(props.accounts);
const stats = ref<Stats>(props.stats);
const isRefreshing = ref(false);
const selectedAccount = ref<Account | null>(null);
const showDeleteDialog = ref(false);
const showRejectDialog = ref(false);
const showDetailsDialog = ref(false);
const isDeleting = ref(false);
const isRejecting = ref(false);
const rejectReason = ref('');
const adminNote = ref('');
const searchTerm = ref('');
const statusFilter = ref('all');

// Computed
const isAdmin = computed(() => {
    return auth.user?.roles?.some((role: any) => role.name === 'admin') || false;
});

const filteredAccounts = computed(() => {
    let filtered = accounts.value;

    // Filtre par terme de recherche
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        filtered = filtered.filter(
            (account) =>
                account.user?.firstname.toLowerCase().includes(term) ||
                account.user?.lastname.toLowerCase().includes(term) ||
                account.user?.email.toLowerCase().includes(term) ||
                account.stripe_account.id.toLowerCase().includes(term),
        );
    }

    // Filtre par statut
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter((account) => account.status === statusFilter.value);
    }

    return filtered;
});

const getStatusBadge = (account: Account) => {
    if (account.stripe_account.error) {
        return { label: 'Erreur', color: 'bg-red-100 text-red-800', icon: XCircle };
    }

    if (account.stripe_account.charges_enabled && account.stripe_account.payouts_enabled) {
        return { label: 'Actif', color: 'bg-green-100 text-green-800', icon: CheckCircle };
    }

    if (account.status === 'rejected') {
        return { label: 'Rejeté', color: 'bg-red-100 text-red-800', icon: XCircle };
    }

    return { label: 'En attente', color: 'bg-orange-100 text-orange-800', icon: Clock };
};

const formatCurrency = (amount: number, currency: string) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: currency.toUpperCase(),
    }).format(amount / 100);
};

const formatDate = (timestamp: number | string) => {
    const date = typeof timestamp === 'number' ? new Date(timestamp * 1000) : new Date(timestamp);
    return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Methods
const refreshAccounts = async () => {
    isRefreshing.value = true;
    try {
        const response = await fetch(route('admin.stripe-connect.refresh'));
        const data = await response.json();

        if (data.success) {
            accounts.value = data.data;
            // Recalculer les stats
            stats.value = {
                total_accounts: data.data.length,
                active_accounts: data.data.filter((acc: Account) => acc.stripe_account.charges_enabled && acc.stripe_account.payouts_enabled).length,
                pending_accounts: data.data.filter((acc: Account) => acc.status === 'pending').length,
                rejected_accounts: data.data.filter((acc: Account) => acc.status === 'rejected').length,
                deletable_accounts: data.data.filter((acc: Account) => acc.can_be_deleted).length,
                linked_accounts: data.data.filter((acc: Account) => acc.is_linked_to_user).length,
                unlinked_accounts: data.data.filter((acc: Account) => !acc.is_linked_to_user).length,
            };

            showSuccess('🔄 Données actualisées', `${data.data.length} comptes Stripe Connect chargés`);
        } else {
            showError('❌ Erreur de chargement', 'Impossible de rafraîchir les données');
        }
    } catch (error) {
        console.error('Erreur lors du rafraîchissement:', error);
        showError('🌐 Erreur de connexion', 'Impossible de contacter le serveur pour actualiser les données');
    } finally {
        isRefreshing.value = false;
    }
};

const openDeleteDialog = (account: Account) => {
    selectedAccount.value = account;
    showDeleteDialog.value = true;
};

const openRejectDialog = (account: Account) => {
    selectedAccount.value = account;
    rejectReason.value = '';
    adminNote.value = '';
    showRejectDialog.value = true;
};

const openDetailsDialog = (account: Account) => {
    selectedAccount.value = account;
    showDetailsDialog.value = true;
};

const deleteAccount = async () => {
    if (!selectedAccount.value) return;

    isDeleting.value = true;
    try {
        const response = await fetch(route('admin.stripe-connect.delete-account', selectedAccount.value.stripe_account.id), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
        });

        const data = await response.json();

        if (data.success) {
            // Supprimer le compte de la liste locale
            accounts.value = accounts.value.filter((acc) =>
                selectedAccount.value?.user
                    ? acc.user?.id !== selectedAccount.value.user.id
                    : acc.stripe_account.id !== selectedAccount.value.stripe_account.id,
            );
            stats.value.total_accounts--;
            stats.value.deletable_accounts--;

            const displayName = selectedAccount.value.user
                ? `${selectedAccount.value.user.firstname} ${selectedAccount.value.user.lastname}`
                : selectedAccount.value.stripe_account.id;
            showDeleteDialog.value = false;
            selectedAccount.value = null;

            showSuccess('🗑️ Compte supprimé avec succès', `Le compte Stripe Connect ${displayName} a été supprimé définitivement.`);
        } else {
            showError('❌ Erreur de suppression', data.message || 'Une erreur est survenue lors de la suppression du compte');
        }
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        showError('🌐 Erreur de connexion', 'Impossible de contacter le serveur. Veuillez réessayer.');
    } finally {
        isDeleting.value = false;
    }
};

const rejectAccount = async () => {
    if (!selectedAccount.value || !rejectReason.value) return;

    isRejecting.value = true;
    try {
        const response = await fetch(route('admin.stripe-connect.reject-account', selectedAccount.value.stripe_account.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify({
                reason: rejectReason.value,
                admin_note: adminNote.value,
            }),
        });

        const data = await response.json();

        if (data.success) {
            // Mettre à jour le compte dans la liste locale
            const accountIndex = accounts.value.findIndex((acc) => acc.user.id === selectedAccount.value!.user.id);
            if (accountIndex !== -1) {
                const previousStatus = accounts.value[accountIndex].status;
                accounts.value[accountIndex].status = 'rejected';
                stats.value.rejected_accounts++;
                if (previousStatus === 'pending') {
                    stats.value.pending_accounts--;
                }
            }

            showRejectDialog.value = false;
            const userName = `${selectedAccount.value.user.firstname} ${selectedAccount.value.user.lastname}`;
            selectedAccount.value = null;

            showSuccess('🚫 Compte rejeté avec succès', `Le compte Stripe Connect de ${userName} a été rejeté et désactivé.`);
        } else {
            showError('❌ Erreur de rejet', data.message || 'Une erreur est survenue lors du rejet du compte');
        }
    } catch (error) {
        console.error('Erreur lors du rejet:', error);
        showError('🌐 Erreur de connexion', 'Impossible de contacter le serveur. Veuillez réessayer.');
    } finally {
        isRejecting.value = false;
    }
};

onMounted(() => {
    if (!isAdmin.value) {
        router.visit('/403', { method: 'get' });
    }
});
</script>

<template>
    <Head title="Gestion des comptes Stripe Connect" />

    <div v-if="isAdmin" class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="border-b bg-white shadow-sm">
            <div class="flex h-16 items-center px-6">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-semibold text-gray-900">Gestion des comptes Stripe Connect</h1>
                </div>
                <div class="ml-auto flex items-center space-x-4">
                    <Button variant="outline" size="sm" @click="refreshAccounts" :disabled="isRefreshing">
                        <RefreshCw :class="['mr-2 h-4 w-4', isRefreshing && 'animate-spin']" />
                        Actualiser
                    </Button>
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

                        <Link
                            href="/admin/babysitter-moderation"
                            class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100"
                        >
                            <ShieldAlert class="h-4 w-4" />
                            <span>Modération</span>
                        </Link>

                        <Link href="/admin/stripe-connect" class="flex items-center space-x-2 rounded-lg bg-blue-50 px-3 py-2 text-blue-700">
                            <CreditCard class="h-4 w-4" />
                            <span>Stripe Connect</span>
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
                <!-- Stats Cards -->
                <div class="mb-8 grid gap-6 md:grid-cols-2 lg:grid-cols-5">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total</CardTitle>
                            <CreditCard class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats.total_accounts }}</div>
                            <p class="text-muted-foreground text-xs">Comptes Stripe</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Actifs</CardTitle>
                            <CheckCircle class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-green-600">{{ stats.active_accounts }}</div>
                            <p class="text-muted-foreground text-xs">Comptes opérationnels</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">En attente</CardTitle>
                            <Clock class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-orange-600">{{ stats.pending_accounts }}</div>
                            <p class="text-muted-foreground text-xs">Configuration requise</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Rejetés</CardTitle>
                            <XCircle class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-red-600">{{ stats.rejected_accounts }}</div>
                            <p class="text-muted-foreground text-xs">Comptes rejetés</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Supprimables</CardTitle>
                            <Trash2 class="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-blue-600">{{ stats.deletable_accounts }}</div>
                            <p class="text-muted-foreground text-xs">Peuvent être supprimés</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Filtres -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle>Filtres</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <Label for="search">Rechercher</Label>
                                <Input id="search" v-model="searchTerm" placeholder="Nom, email ou ID Stripe..." class="mt-1" />
                            </div>
                            <div class="w-48">
                                <Label for="status">Statut</Label>
                                <Select v-model="statusFilter">
                                    <SelectTrigger class="mt-1">
                                        <SelectValue placeholder="Tous les statuts" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Tous les statuts</SelectItem>
                                        <SelectItem value="pending">En attente</SelectItem>
                                        <SelectItem value="active">Actif</SelectItem>
                                        <SelectItem value="rejected">Rejeté</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Tableau des comptes -->
                <Card>
                    <CardHeader>
                        <CardTitle>Comptes Stripe Connect</CardTitle>
                        <CardDescription> Gestion des comptes de paiement des babysitters </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Utilisateur</TableHead>
                                    <TableHead>Compte Stripe</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead>Solde</TableHead>
                                    <TableHead>Créé le</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="account in filteredAccounts" :key="account.stripe_account.id">
                                    <TableCell>
                                        <div v-if="account.user">
                                            <div class="font-medium">{{ account.user.firstname }} {{ account.user.lastname }}</div>
                                            <div class="text-sm text-gray-500">{{ account.user.email }}</div>
                                        </div>
                                        <div v-else>
                                            <div class="font-medium text-gray-500">Compte non lié</div>
                                            <div class="text-sm text-gray-400">Aucun utilisateur associé</div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div>
                                            <div class="font-mono text-sm">{{ account.stripe_account.id }}</div>
                                            <div class="text-xs text-gray-500">
                                                {{ account.stripe_account.type }} - {{ account.stripe_account.country }}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getStatusBadge(account).color">
                                            <component :is="getStatusBadge(account).icon" class="mr-1 h-3 w-3" />
                                            {{ getStatusBadge(account).label }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div v-if="account.balance && !account.stripe_account.error">
                                            <div v-for="balance in account.balance.available" :key="balance.currency" class="text-sm">
                                                {{ formatCurrency(balance.amount, balance.currency) }}
                                            </div>
                                            <div v-if="account.balance.pending.length > 0" class="text-xs text-orange-600">
                                                + {{ account.balance.pending.map((p) => formatCurrency(p.amount, p.currency)).join(', ') }} en attente
                                            </div>
                                        </div>
                                        <div v-else class="text-sm text-gray-400">-</div>
                                    </TableCell>
                                    <TableCell>
                                        {{ account.user ? formatDate(account.user.created_at) : formatDate(account.stripe_account.created) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button variant="outline" size="sm" @click="openDetailsDialog(account)">
                                                <Eye class="h-4 w-4" />
                                            </Button>

                                            <Button
                                                v-if="account.status !== 'rejected'"
                                                variant="outline"
                                                size="sm"
                                                @click="openRejectDialog(account)"
                                            >
                                                <XCircle class="h-4 w-4" />
                                            </Button>

                                            <Button v-if="account.can_be_deleted" variant="outline" size="sm" @click="openDeleteDialog(account)">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <div v-if="filteredAccounts.length === 0" class="py-8 text-center text-gray-500">
                            Aucun compte trouvé avec les filtres appliqués.
                        </div>
                    </CardContent>
                </Card>
            </main>
        </div>

        <!-- Dialog de suppression -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Supprimer le compte Stripe Connect</DialogTitle>
                    <DialogDescription>
                        Vous êtes sur le point de supprimer définitivement le compte Stripe Connect
                        <span v-if="selectedAccount?.user">
                            de <strong>{{ selectedAccount.user.firstname }} {{ selectedAccount.user.lastname }}</strong>
                        </span>
                        <span v-else>
                            <strong>{{ selectedAccount?.stripe_account.id }}</strong> (compte non lié) </span
                        >.
                    </DialogDescription>
                </DialogHeader>

                <Alert>
                    <AlertCircle class="h-4 w-4" />
                    <AlertDescription>
                        Cette action est irréversible. Le compte sera supprimé de Stripe et l'utilisateur devra recréer un nouveau compte pour
                        recevoir des paiements.
                    </AlertDescription>
                </Alert>

                <div v-if="selectedAccount?.balance" class="space-y-2">
                    <p class="text-sm font-medium">Soldes actuels :</p>
                    <div v-for="balance in selectedAccount.balance.available" :key="balance.currency" class="text-sm">
                        Disponible : {{ formatCurrency(balance.amount, balance.currency) }}
                    </div>
                    <div v-for="balance in selectedAccount.balance.pending" :key="balance.currency" class="text-sm text-orange-600">
                        En attente : {{ formatCurrency(balance.amount, balance.currency) }}
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">Annuler</Button>
                    <Button variant="destructive" @click="deleteAccount" :disabled="isDeleting">
                        <Trash2 v-if="!isDeleting" class="mr-2 h-4 w-4" />
                        <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                        {{ isDeleting ? 'Suppression...' : 'Supprimer définitivement' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Dialog de rejet -->
        <Dialog v-model:open="showRejectDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Rejeter le compte Stripe Connect</DialogTitle>
                    <DialogDescription>
                        Rejeter le compte
                        <span v-if="selectedAccount?.user">
                            de <strong>{{ selectedAccount.user.firstname }} {{ selectedAccount.user.lastname }}</strong>
                        </span>
                        <span v-else>
                            <strong>{{ selectedAccount?.stripe_account.id }}</strong> (compte non lié) </span
                        >. Le compte sera désactivé et ne pourra plus recevoir de paiements.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div>
                        <Label for="reason">Raison du rejet *</Label>
                        <Select v-model="rejectReason">
                            <SelectTrigger class="mt-1">
                                <SelectValue placeholder="Sélectionner une raison" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="fraud">Fraude suspectée</SelectItem>
                                <SelectItem value="terms_of_service">Violation des conditions d'utilisation</SelectItem>
                                <SelectItem value="other">Autre raison</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div>
                        <Label for="admin_note">Note administrative (optionnel)</Label>
                        <Textarea id="admin_note" v-model="adminNote" placeholder="Détails supplémentaires sur la raison du rejet..." class="mt-1" />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showRejectDialog = false">Annuler</Button>
                    <Button variant="destructive" @click="rejectAccount" :disabled="isRejecting || !rejectReason">
                        <XCircle v-if="!isRejecting" class="mr-2 h-4 w-4" />
                        <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                        {{ isRejecting ? 'Rejet...' : 'Rejeter le compte' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Dialog de détails -->
        <Dialog v-model:open="showDetailsDialog">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Détails du compte Stripe Connect</DialogTitle>
                    <DialogDescription>
                        Informations détaillées pour
                        <span v-if="selectedAccount?.user"> {{ selectedAccount.user.firstname }} {{ selectedAccount.user.lastname }} </span>
                        <span v-else> le compte {{ selectedAccount?.stripe_account.id }} </span>
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedAccount" class="space-y-4">
                    <!-- Informations utilisateur -->
                    <div>
                        <h4 class="mb-2 text-sm font-medium">Utilisateur</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>ID : {{ selectedAccount.user.id }}</div>
                            <div>Email : {{ selectedAccount.user.email }}</div>
                            <div>Inscrit le : {{ formatDate(selectedAccount.user.created_at) }}</div>
                            <div>Rôles : {{ selectedAccount.user.roles.join(', ') }}</div>
                        </div>
                    </div>

                    <!-- Informations Stripe -->
                    <div v-if="!selectedAccount.stripe_account.error">
                        <h4 class="mb-2 text-sm font-medium">Compte Stripe</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>ID : {{ selectedAccount.stripe_account.id }}</div>
                            <div>Type : {{ selectedAccount.stripe_account.type }}</div>
                            <div>Pays : {{ selectedAccount.stripe_account.country }}</div>
                            <div>Devise : {{ selectedAccount.stripe_account.default_currency }}</div>
                            <div>Paiements : {{ selectedAccount.stripe_account.charges_enabled ? 'Activés' : 'Désactivés' }}</div>
                            <div>Virements : {{ selectedAccount.stripe_account.payouts_enabled ? 'Activés' : 'Désactivés' }}</div>
                            <div>Créé le : {{ formatDate(selectedAccount.stripe_account.created) }}</div>
                            <div>Détails soumis : {{ selectedAccount.stripe_account.details_submitted ? 'Oui' : 'Non' }}</div>
                        </div>
                    </div>

                    <!-- Erreur Stripe -->
                    <div v-else>
                        <Alert>
                            <AlertCircle class="h-4 w-4" />
                            <AlertDescription>
                                {{ selectedAccount.stripe_account.error }}
                            </AlertDescription>
                        </Alert>
                    </div>

                    <!-- Requirements -->
                    <div v-if="selectedAccount.stripe_account.requirements && !selectedAccount.stripe_account.error">
                        <h4 class="mb-2 text-sm font-medium">Requirements</h4>
                        <div class="space-y-2 text-sm">
                            <div v-if="selectedAccount.stripe_account.requirements.currently_due.length > 0">
                                <span class="font-medium text-red-600">Actuellement requis :</span>
                                <ul class="ml-4 list-inside list-disc">
                                    <li v-for="req in selectedAccount.stripe_account.requirements.currently_due" :key="req">
                                        {{ req }}
                                    </li>
                                </ul>
                            </div>
                            <div v-if="selectedAccount.stripe_account.requirements.eventually_due.length > 0">
                                <span class="font-medium text-orange-600">Éventuellement requis :</span>
                                <ul class="ml-4 list-inside list-disc">
                                    <li v-for="req in selectedAccount.stripe_account.requirements.eventually_due" :key="req">
                                        {{ req }}
                                    </li>
                                </ul>
                            </div>
                            <div v-if="selectedAccount.stripe_account.requirements.disabled_reason">
                                <span class="font-medium text-red-600">Raison de désactivation :</span>
                                {{ selectedAccount.stripe_account.requirements.disabled_reason }}
                            </div>
                        </div>
                    </div>

                    <!-- Soldes -->
                    <div v-if="selectedAccount.balance">
                        <h4 class="mb-2 text-sm font-medium">Soldes</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-medium">Disponible :</span>
                                <div v-for="balance in selectedAccount.balance.available" :key="balance.currency">
                                    {{ formatCurrency(balance.amount, balance.currency) }}
                                </div>
                            </div>
                            <div>
                                <span class="font-medium">En attente :</span>
                                <div v-for="balance in selectedAccount.balance.pending" :key="balance.currency">
                                    {{ formatCurrency(balance.amount, balance.currency) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showDetailsDialog = false">Fermer</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>

    <!-- Loading state pendant vérification -->
    <div v-else class="flex min-h-screen items-center justify-center">
        <div class="text-center">
            <div class="mx-auto mb-4 h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-blue-600"></div>
            <p class="text-gray-600">Vérification des permissions...</p>
        </div>
    </div>
</template>
