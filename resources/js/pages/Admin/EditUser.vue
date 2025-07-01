<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Toast from '@/components/ui/Toast.vue';
import { toast } from '@/components/ui/toast';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Users, TrendingUp, ShieldAlert, FileText, Star, CreditCard, UserCheck, ArrowLeft } from 'lucide-vue-next';

interface Address {
    id: number;
    address: string;
    postal_code: string;
    country: string;
}

interface Role {
    id: number;
    name: string;
    label: string;
}

interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    phone?: string;
    status: string;
    roles: Role[];
    address?: Address;
}

interface Props {
    user: User;
}

const props = defineProps<Props>();

const form = useForm({
    firstname: props.user.firstname,
    lastname: props.user.lastname,
    email: props.user.email,
    phone: props.user.phone || '',
    status: props.user.status,
    roles: props.user.roles.map(role => role.name),
    address: props.user.address?.address || '',
    postal_code: props.user.address?.postal_code || '',
    country: props.user.address?.country || 'France',
});

const submit = () => {
    form.put(`/admin/utilisateurs/${props.user.id}`, {
        onSuccess: () => {
            toast.success(
                'Utilisateur modifié',
                'Les informations de l\'utilisateur ont été mises à jour avec succès.'
            );
        },
        onError: () => {
            toast.error(
                'Erreur',
                'Une erreur est survenue lors de la modification de l\'utilisateur.'
            );
        }
    });
};

const toggleRole = (role: string) => {
    const index = form.roles.indexOf(role);
    if (index === -1) {
        form.roles.push(role);
    } else {
        form.roles.splice(index, 1);
    }
};

const statusOptions = [
    { value: 'pending', label: 'En attente' },
    { value: 'approved', label: 'Approuvé' },
    { value: 'suspended', label: 'Suspendu' },
];
</script>

<template>
    <Head title="Modifier un utilisateur - Admin" />

    <div class="min-h-screen bg-gray-50">
        <header class="border-b bg-white shadow-sm">
            <div class="flex h-16 items-center px-6">
                <div class="flex items-center space-x-4">
                    <Button as-child variant="ghost" size="sm">
                        <Link href="/admin/parents">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Retour
                        </Link>
                    </Button>
                    <h1 class="text-xl font-semibold text-gray-900">Modifier l'utilisateur</h1>
                </div>
            </div>
        </header>

        <div class="flex">
            <nav class="w-64 border-r bg-white">
                <div class="p-6">
                    <div class="space-y-2">
                        <Link href="/admin" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <TrendingUp class="h-4 w-4" />
                            <span>Dashboard</span>
                        </Link>
                        <Link href="/admin/parents" class="flex items-center space-x-2 rounded-lg bg-blue-50 px-3 py-2 text-blue-700">
                            <Users class="h-4 w-4" />
                            <span>Parents</span>
                        </Link>
                        <Link href="/admin/babysitters" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <UserCheck class="h-4 w-4" />
                            <span>Babysitters</span>
                        </Link>
                        <Link href="/admin/moderation-babysitters" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <ShieldAlert class="h-4 w-4" />
                            <span>Modération</span>
                        </Link>
                        <Link href="/admin/annonces" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <FileText class="h-4 w-4" />
                            <span>Annonces</span>
                        </Link>
                        <Link href="/admin/avis" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <Star class="h-4 w-4" />
                            <span>Avis</span>
                        </Link>
                        <Link href="/admin/comptes-stripe" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <CreditCard class="h-4 w-4" />
                            <span>Comptes Stripe</span>
                        </Link>
                    </div>
                </div>
            </nav>

            <main class="flex-1 p-6">
                <Card class="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Modifier l'utilisateur</CardTitle>
                        <CardDescription>
                            Modifiez les informations de {{ user.firstname }} {{ user.lastname }}.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label for="firstname">Prénom *</Label>
                                    <Input
                                        id="firstname"
                                        v-model="form.firstname"
                                        type="text"
                                        required
                                        :class="{ 'border-red-500': form.errors.firstname }"
                                    />
                                    <p v-if="form.errors.firstname" class="mt-1 text-sm text-red-500">
                                        {{ form.errors.firstname }}
                                    </p>
                                </div>
                                <div>
                                    <Label for="lastname">Nom *</Label>
                                    <Input
                                        id="lastname"
                                        v-model="form.lastname"
                                        type="text"
                                        required
                                        :class="{ 'border-red-500': form.errors.lastname }"
                                    />
                                    <p v-if="form.errors.lastname" class="mt-1 text-sm text-red-500">
                                        {{ form.errors.lastname }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <Label for="email">Email *</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div>
                                <Label for="phone">Téléphone</Label>
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    :class="{ 'border-red-500': form.errors.phone }"
                                />
                            </div>

                            <div>
                                <Label for="status">Statut *</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Sélectionner un statut" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem 
                                            v-for="option in statusOptions" 
                                            :key="option.value" 
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <div>
                                <Label>Rôles *</Label>
                                <div class="mt-2 space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            id="parent"
                                            :checked="form.roles.includes('parent')"
                                            @update:checked="() => toggleRole('parent')"
                                        />
                                        <Label for="parent" class="cursor-pointer">Parent</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            id="babysitter"
                                            :checked="form.roles.includes('babysitter')"
                                            @update:checked="() => toggleRole('babysitter')"
                                        />
                                        <Label for="babysitter" class="cursor-pointer">Babysitter</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            id="admin"
                                            :checked="form.roles.includes('admin')"
                                            @update:checked="() => toggleRole('admin')"
                                        />
                                        <Label for="admin" class="cursor-pointer">Administrateur</Label>
                                    </div>
                                </div>
                                <p v-if="form.errors.roles" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.roles }}
                                </p>
                            </div>

                            <div class="border-t pt-6">
                                <h3 class="text-lg font-medium mb-4">Adresse</h3>
                                
                                <div>
                                    <Label for="address">Adresse</Label>
                                    <Input
                                        id="address"
                                        v-model="form.address"
                                        type="text"
                                    />
                                </div>

                                <div class="grid grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <Label for="postal_code">Code postal</Label>
                                        <Input
                                            id="postal_code"
                                            v-model="form.postal_code"
                                            type="text"
                                        />
                                    </div>
                                    <div>
                                        <Label for="country">Pays</Label>
                                        <Input
                                            id="country"
                                            v-model="form.country"
                                            type="text"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-4 border-t pt-6">
                                <Button as-child variant="outline">
                                    <Link href="/admin/parents">Annuler</Link>
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Modification...' : 'Modifier l\'utilisateur' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </main>
        </div>
        
        <!-- Toast notifications -->
        <Toast />
    </div>
</template> 