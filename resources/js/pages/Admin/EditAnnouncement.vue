<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/composables/useToast';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, FileText, MessageSquare, ShieldAlert, Star, TrendingUp, UserCheck, Users } from 'lucide-vue-next';

interface Address {
    id: number;
    address: string;
    postal_code: string;
    country: string;
}

interface Parent {
    id: number;
    firstname: string;
    lastname: string;
}

interface Announcement {
    id: number;
    title: string;
    description: string;
    status: string;
    hourly_rate: number;
    date_start: string;
    date_end: string;
    time_start: string;
    time_end: string;
    parent: Parent;
    address: Address;
}

interface Props {
    announcement: Announcement;
}

const props = defineProps<Props>();
const { showSuccess, showError } = useToast();

// Extraire la date et les heures des données
const extractDate = (dateTime: string) => {
    return dateTime ? dateTime.split('T')[0] : '';
};

const extractTime = (dateTime: string) => {
    return dateTime ? dateTime.split('T')[1]?.substring(0, 5) : '';
};

const form = useForm({
    title: props.announcement.title,
    additional_info: props.announcement.description || '', // description -> additional_info
    status: props.announcement.status,
    hourly_rate: props.announcement.hourly_rate,
    date_start: extractDate(props.announcement.date_start),
    date_end: extractDate(props.announcement.date_end),
    time_start: extractTime(props.announcement.date_start),
    time_end: extractTime(props.announcement.date_end),
    address: props.announcement.address.address,
    postal_code: props.announcement.address.postal_code,
    country: props.announcement.address.country,
});

const submit = () => {
    form.put(`/admin/annonces/${props.announcement.id}`, {
        onSuccess: () => {
            showSuccess('Annonce modifiée', "L'annonce a été mise à jour avec succès.");
        },
        onError: () => {
            showError('Erreur', "Une erreur est survenue lors de la modification de l'annonce.");
        },
    });
};

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'booked', label: 'Réservée' },
    { value: 'service_completed', label: 'Service terminé' },
    { value: 'completed', label: 'Terminée' },
    { value: 'expired', label: 'Expirée' },
    { value: 'cancelled', label: 'Annulée' },
    { value: 'paused', label: 'En pause' },
];
</script>

<template>
    <Head title="Modifier une annonce - Admin" />

    <div class="min-h-screen bg-gray-50">
        <header class="border-b bg-white shadow-sm">
            <div class="flex h-16 items-center px-6">
                <div class="flex items-center space-x-4">
                    <Button as-child variant="ghost" size="sm">
                        <Link href="/admin/annonces">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Retour aux annonces
                        </Link>
                    </Button>
                    <h1 class="text-xl font-semibold text-gray-900">Modifier l'annonce</h1>
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
                        <Link href="/admin/parents" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
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
                        <Link href="/admin/annonces" class="flex items-center space-x-2 rounded-lg bg-blue-50 px-3 py-2 text-blue-700">
                            <FileText class="h-4 w-4" />
                            <span>Annonces</span>
                        </Link>
                        <Link href="/admin/avis" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <Star class="h-4 w-4" />
                            <span>Avis</span>
                        </Link>

                        <Link href="/admin/contacts" class="flex items-center space-x-2 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100">
                            <MessageSquare class="h-4 w-4" />
                            <span>Contacts</span>
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
                        <CardTitle>Modifier l'annonce</CardTitle>
                        <CardDescription>
                            Modifiez les informations de l'annonce "{{ announcement.title }}" créée par {{ announcement.parent.firstname }}
                            {{ announcement.parent.lastname }}.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <Label for="title">Titre *</Label>
                                <Input id="title" v-model="form.title" type="text" required :class="{ 'border-red-500': form.errors.title }" />
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <div>
                                <Label for="additional_info">Informations complémentaires</Label>
                                <Textarea
                                    id="additional_info"
                                    v-model="form.additional_info"
                                    rows="4"
                                    :class="{ 'border-red-500': form.errors.additional_info }"
                                />
                                <p v-if="form.errors.additional_info" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.additional_info }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label for="status">Statut *</Label>
                                    <Select v-model="form.status">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Sélectionner un statut" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="option in statusOptions" :key="option.value" :value="option.value">
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">
                                        {{ form.errors.status }}
                                    </p>
                                </div>
                                <div>
                                    <Label for="hourly_rate">Tarif horaire (€) *</Label>
                                    <Input
                                        id="hourly_rate"
                                        v-model="form.hourly_rate"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        required
                                        :class="{ 'border-red-500': form.errors.hourly_rate }"
                                    />
                                    <p v-if="form.errors.hourly_rate" class="mt-1 text-sm text-red-500">
                                        {{ form.errors.hourly_rate }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label for="date_start">Date de début</Label>
                                    <Input
                                        id="date_start"
                                        v-model="form.date_start"
                                        type="date"
                                        :class="{ 'border-red-500': form.errors.date_start }"
                                    />
                                    <p v-if="form.errors.date_start" class="mt-1 text-sm text-red-500">
                                        {{ form.errors.date_start }}
                                    </p>
                                </div>
                                <div>
                                    <Label for="date_end">Date de fin</Label>
                                    <Input id="date_end" v-model="form.date_end" type="date" :class="{ 'border-red-500': form.errors.date_end }" />
                                    <p v-if="form.errors.date_end" class="mt-1 text-sm text-red-500">
                                        {{ form.errors.date_end }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <Label for="time_start">Heure de début</Label>
                                    <Input
                                        id="time_start"
                                        v-model="form.time_start"
                                        type="time"
                                        :class="{ 'border-red-500': form.errors.time_start }"
                                    />
                                    <p v-if="form.errors.time_start" class="mt-1 text-sm text-red-500">
                                        {{ form.errors.time_start }}
                                    </p>
                                </div>
                                <div>
                                    <Label for="time_end">Heure de fin</Label>
                                    <Input id="time_end" v-model="form.time_end" type="time" :class="{ 'border-red-500': form.errors.time_end }" />
                                    <p v-if="form.errors.time_end" class="mt-1 text-sm text-red-500">
                                        {{ form.errors.time_end }}
                                    </p>
                                </div>
                            </div>

                            <div class="border-t pt-6">
                                <h3 class="mb-4 text-lg font-medium">Adresse</h3>

                                <div>
                                    <Label for="address">Adresse *</Label>
                                    <Input
                                        id="address"
                                        v-model="form.address"
                                        type="text"
                                        required
                                        :class="{ 'border-red-500': form.errors.address }"
                                    />
                                    <p v-if="form.errors.address" class="mt-1 text-sm text-red-500">
                                        {{ form.errors.address }}
                                    </p>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-4">
                                    <div>
                                        <Label for="postal_code">Code postal *</Label>
                                        <Input
                                            id="postal_code"
                                            v-model="form.postal_code"
                                            type="text"
                                            required
                                            :class="{ 'border-red-500': form.errors.postal_code }"
                                        />
                                        <p v-if="form.errors.postal_code" class="mt-1 text-sm text-red-500">
                                            {{ form.errors.postal_code }}
                                        </p>
                                    </div>
                                    <div>
                                        <Label for="country">Pays *</Label>
                                        <Input
                                            id="country"
                                            v-model="form.country"
                                            type="text"
                                            required
                                            :class="{ 'border-red-500': form.errors.country }"
                                        />
                                        <p v-if="form.errors.country" class="mt-1 text-sm text-red-500">
                                            {{ form.errors.country }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-4 border-t pt-6">
                                <Button as-child variant="outline">
                                    <Link href="/admin/annonces">Annuler</Link>
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Modification...' : "Modifier l'annonce" }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </main>
        </div>
    </div>
</template>
