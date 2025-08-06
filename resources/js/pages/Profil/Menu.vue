<template>
    <DashboardLayout :hasParentRole="hasParentRole" :hasBabysitterRole="hasBabysitterRole">
        <div class="min-h-screen bg-gray-50">
            <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                <!-- En-tête avec avatar et nom -->
                <div class="mb-8 rounded-2xl bg-white p-6 shadow-lg">
                    <div class="flex items-center space-x-4">
                        <img
                            :src="user.avatar || '/default-avatar.png'"
                            :alt="user.firstname"
                            class="h-20 w-20 rounded-full border-2 border-orange-200 object-cover"
                        />
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ user.firstname }} {{ user.lastname }}</h1>
                            <p class="text-gray-600">{{ user.email }}</p>
                            <div class="mt-2 flex space-x-2">
                                <span v-if="hasParentRole" class="rounded-full bg-pink-100 px-3 py-1 text-xs font-medium text-pink-800">
                                    Parent
                                </span>
                                <span v-if="hasBabysitterRole" class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                                    Babysitter
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Menu de navigation -->
                <div class="space-y-4">
                    <!-- Section Profil -->
                    <div class="rounded-2xl bg-white p-6 shadow-lg">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">Mon compte</h2>
                        <div class="space-y-3">
                            <a href="/profil" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <Settings class="mr-3 h-5 w-5 text-orange-500" />
                                    <span class="font-medium">Modifier mon profil</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                            <a href="/settings" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <Shield class="mr-3 h-5 w-5 text-orange-500" />
                                    <span class="font-medium">Paramètres</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                        </div>
                    </div>

                    <!-- Section Parent -->
                    <div v-if="hasParentRole" class="rounded-2xl bg-white p-6 shadow-lg">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">Espace Parent</h2>
                        <div class="space-y-3">
                            <a href="/parent/announcements-reservations" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <Calendar class="mr-3 h-5 w-5 text-pink-500" />
                                    <span class="font-medium">Mes annonces & réservations</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                            <a href="/create-announcement" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <Plus class="mr-3 h-5 w-5 text-pink-500" />
                                    <span class="font-medium">Créer une annonce</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                            <a href="/messaging" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <MessageCircle class="mr-3 h-5 w-5 text-pink-500" />
                                    <span class="font-medium">Messagerie</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                        </div>
                    </div>

                    <!-- Section Babysitter -->
                    <div v-if="hasBabysitterRole" class="rounded-2xl bg-white p-6 shadow-lg">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">Espace Babysitter</h2>
                        <div class="space-y-3">
                            <a href="/babysitting" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <Briefcase class="mr-3 h-5 w-5 text-blue-500" />
                                    <span class="font-medium">Mes candidatures</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                            <a href="/annonces" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <Search class="mr-3 h-5 w-5 text-blue-500" />
                                    <span class="font-medium">Chercher des gardes</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                            <a href="/babysitter/payments" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <CreditCard class="mr-3 h-5 w-5 text-blue-500" />
                                    <span class="font-medium">Mes paiements</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                        </div>
                    </div>

                    <!-- Section Aide -->
                    <div class="rounded-2xl bg-white p-6 shadow-lg">
                        <h2 class="mb-4 text-xl font-semibold text-gray-900">Aide & Support</h2>
                        <div class="space-y-3">
                            <a href="/comment-ca-marche" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <BookOpen class="mr-3 h-5 w-5 text-green-500" />
                                    <span class="font-medium">Comment ça marche</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                            <a href="/contact" class="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <Phone class="mr-3 h-5 w-5 text-green-500" />
                                    <span class="font-medium">Support client</span>
                                </div>
                                <ChevronRight class="h-4 w-4 text-gray-400" />
                            </a>
                        </div>
                    </div>

                    <!-- Section Déconnexion -->
                    <div class="rounded-2xl bg-white p-6 shadow-lg">
                        <button 
                            @click="logout" 
                            class="flex w-full items-center justify-between rounded-lg p-3 text-red-600 hover:bg-red-50"
                        >
                            <div class="flex items-center">
                                <LogOut class="mr-3 h-5 w-5" />
                                <span class="font-medium">Se déconnecter</span>
                            </div>
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Briefcase,
    Calendar,
    ChevronRight,
    CreditCard,
    LogOut,
    MessageCircle,
    Phone,
    Plus,
    Search,
    Settings,
    Shield,
} from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();

// Récupérer les informations utilisateur
const user = computed(() => (page.props.auth as any)?.user);
const userRoles = computed(() => user.value?.roles?.map((role: any) => role.name) || []);
const hasParentRole = computed(() => userRoles.value.includes('parent'));
const hasBabysitterRole = computed(() => userRoles.value.includes('babysitter'));

const logout = () => {
    router.post('/logout');
};
</script>