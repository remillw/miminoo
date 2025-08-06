<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, FileText, ShieldAlert, Star, TrendingUp, UserCheck, Users } from 'lucide-vue-next';

const form = useForm({
    firstname: '',
    lastname: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    roles: [] as string[],
    address: '',
    postal_code: '',
    country: 'France',
});

const submit = () => {
    form.post('/admin/utilisateurs');
};

const toggleRole = (role: string) => {
    const index = form.roles.indexOf(role);
    if (index === -1) {
        form.roles.push(role);
    } else {
        form.roles.splice(index, 1);
    }
};
</script>

<template>
    <Head title="Créer un utilisateur - Admin" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="border-b bg-white shadow-sm">
            <div class="flex h-16 items-center px-6">
                <div class="flex items-center space-x-4">
                    <Button as-child variant="ghost" size="sm">
                        <Link href="/admin/parents">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Retour
                        </Link>
                    </Button>
                    <h1 class="text-xl font-semibold text-gray-900">Créer un utilisateur</h1>
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
                        <Link href="/admin/parents" class="flex items-center space-x-2 rounded-lg bg-blue-50 px-3 py-2 text-blue-700">
                            <Users class="h-4 w-4" />
                            <span>Parents</span>
                        </Link>
                        <Link href="/admin/babysitters" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <UserCheck class="h-4 w-4" />
                            <span>Babysitters</span>
                        </Link>
                        <Link
                            href="/admin/moderation-babysitters"
                            class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100"
                        >
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

            <!-- Contenu principal -->
            <main class="flex-1 p-6">
                <Card class="max-w-2xl">
                    <CardHeader>
                        <CardTitle>Créer un nouvel utilisateur</CardTitle>
                        <CardDescription> Remplissez les informations ci-dessous pour créer un nouveau compte utilisateur. </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Informations personnelles -->
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
                                <Input id="email" v-model="form.email" type="email" required :class="{ 'border-red-500': form.errors.email }" />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div>
                                <Label for="phone">Téléphone</Label>
                                <Input id="phone" v-model="form.phone" type="tel" :class="{ 'border-red-500': form.errors.phone }" />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.phone }}
                                </p>
                            </div>

                            <!-- Mot de passe -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label for="password">Mot de passe *</Label>
                                    <Input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        required
                                        :class="{ 'border-red-500': form.errors.password }"
                                    />
                                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-500">
                                        {{ form.errors.password }}
                                    </p>
                                </div>
                                <div>
                                    <Label for="password_confirmation">Confirmer le mot de passe *</Label>
                                    <Input id="password_confirmation" v-model="form.password_confirmation" type="password" required />
                                </div>
                            </div>

                            <!-- Rôles -->
                            <div>
                                <Label>Rôles *</Label>
                                <div class="mt-2 space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <Checkbox id="parent" :checked="form.roles.includes('parent')" @update:checked="() => toggleRole('parent')" />
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
                                </div>
                                <p v-if="form.errors.roles" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.roles }}
                                </p>
                            </div>

                            <!-- Adresse -->
                            <div class="border-t pt-6">
                                <h3 class="mb-4 text-lg font-medium">Adresse (optionnel)</h3>

                                <div>
                                    <Label for="address">Adresse</Label>
                                    <Input id="address" v-model="form.address" type="text" :class="{ 'border-red-500': form.errors.address }" />
                                    <p v-if="form.errors.address" class="mt-1 text-sm text-red-500">
                                        {{ form.errors.address }}
                                    </p>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-4">
                                    <div>
                                        <Label for="postal_code">Code postal</Label>
                                        <Input
                                            id="postal_code"
                                            v-model="form.postal_code"
                                            type="text"
                                            :class="{ 'border-red-500': form.errors.postal_code }"
                                        />
                                        <p v-if="form.errors.postal_code" class="mt-1 text-sm text-red-500">
                                            {{ form.errors.postal_code }}
                                        </p>
                                    </div>
                                    <div>
                                        <Label for="country">Pays</Label>
                                        <Input id="country" v-model="form.country" type="text" :class="{ 'border-red-500': form.errors.country }" />
                                        <p v-if="form.errors.country" class="mt-1 text-sm text-red-500">
                                            {{ form.errors.country }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Boutons -->
                            <div class="flex items-center justify-end space-x-4 border-t pt-6">
                                <Button as-child variant="outline">
                                    <Link href="/admin/parents">Annuler</Link>
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Création...' : "Créer l'utilisateur" }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </main>
        </div>
    </div>
</template>
