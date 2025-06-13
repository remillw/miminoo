<script setup lang="ts" generic="T extends Record<string, any>">
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { Column, DataTableProps } from '@/types/datatable';
import { ArrowDown, ArrowUp, ArrowUpDown, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = withDefaults(defineProps<DataTableProps<T>>(), {
    searchPlaceholder: 'Rechercher...',
    emptyMessage: 'Aucune donnée disponible',
    loading: false,
});

// État de recherche et tri
const searchQuery = ref('');
const sortColumn = ref<string | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');

// Colonnes filtrables pour la recherche
const searchableColumns = computed(() => props.columns.filter((col) => col.searchable !== false));

// Données filtrées par la recherche
const filteredData = computed(() => {
    if (!searchQuery.value.trim()) return props.data;

    const query = searchQuery.value.toLowerCase().trim();

    return props.data.filter((item) => {
        return searchableColumns.value.some((column) => {
            const value = getNestedValue(item, column.key);
            return String(value || '')
                .toLowerCase()
                .includes(query);
        });
    });
});

// Données triées
const sortedData = computed(() => {
    if (!sortColumn.value) return filteredData.value;

    const column = props.columns.find((col) => col.key === sortColumn.value);
    if (!column?.sortable) return filteredData.value;

    return [...filteredData.value].sort((a, b) => {
        const aValue = getNestedValue(a, sortColumn.value!);
        const bValue = getNestedValue(b, sortColumn.value!);

        // Gestion des valeurs nulles/undefined
        if (aValue == null && bValue == null) return 0;
        if (aValue == null) return sortDirection.value === 'asc' ? 1 : -1;
        if (bValue == null) return sortDirection.value === 'asc' ? -1 : 1;

        // Conversion en string pour la comparaison
        const aStr = String(aValue).toLowerCase();
        const bStr = String(bValue).toLowerCase();

        const result = aStr.localeCompare(bStr, 'fr', { numeric: true });
        return sortDirection.value === 'asc' ? result : -result;
    });
});

// Fonction pour récupérer une valeur imbriquée (ex: "user.profile.name")
function getNestedValue(obj: any, path: string): any {
    return path.split('.').reduce((current, key) => current?.[key], obj);
}

// Fonction pour gérer le tri
function handleSort(column: Column<T>) {
    if (!column.sortable) return;

    if (sortColumn.value === column.key) {
        // Même colonne : changer la direction
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        // Nouvelle colonne : tri ascendant par défaut
        sortColumn.value = column.key;
        sortDirection.value = 'asc';
    }
}

// Fonction pour obtenir l'icône de tri
function getSortIcon(column: Column<T>) {
    if (!column.sortable) return null;
    if (sortColumn.value !== column.key) return ArrowUpDown;
    return sortDirection.value === 'asc' ? ArrowUp : ArrowDown;
}

// Fonction pour rendre une cellule
function renderCell(column: Column<T>, item: T) {
    const value = getNestedValue(item, column.key);

    if (column.render) {
        return column.render(value, item);
    }

    return value;
}

// Reset de la recherche
function clearSearch() {
    searchQuery.value = '';
}

// Statistiques
const stats = computed(() => ({
    total: props.data.length,
    filtered: sortedData.value.length,
    isFiltered: searchQuery.value.trim() !== '',
}));
</script>

<template>
    <div class="space-y-4">
        <!-- Barre de recherche -->
        <div class="flex items-center space-x-2">
            <div class="relative max-w-sm flex-1">
                <Search class="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                <Input v-model="searchQuery" :placeholder="searchPlaceholder" class="pl-9" />
            </div>
            <Button v-if="searchQuery.trim()" variant="outline" size="sm" @click="clearSearch"> Effacer </Button>
        </div>

        <!-- Statistiques -->
        <div v-if="stats.isFiltered" class="text-muted-foreground text-sm">
            {{ stats.filtered }} résultat{{ stats.filtered !== 1 ? 's' : '' }} sur {{ stats.total }}
        </div>

        <!-- Tableau -->
        <Card>
            <div class="relative overflow-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead
                                v-for="column in columns"
                                :key="column.key"
                                :style="{ width: column.width }"
                                :class="{ 'hover:bg-muted/50 cursor-pointer': column.sortable }"
                                @click="handleSort(column)"
                            >
                                <div class="flex items-center space-x-1">
                                    <span>{{ column.label }}</span>
                                    <component
                                        :is="getSortIcon(column)"
                                        v-if="getSortIcon(column)"
                                        class="h-4 w-4"
                                        :class="{
                                            'text-foreground': sortColumn === column.key,
                                            'text-muted-foreground': sortColumn !== column.key,
                                        }"
                                    />
                                </div>
                            </TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <!-- Loading state -->
                        <TableRow v-if="loading">
                            <TableCell :colspan="columns.length" class="py-8 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <div class="border-muted-foreground h-4 w-4 animate-spin rounded-full border-2 border-t-transparent"></div>
                                    <span>Chargement...</span>
                                </div>
                            </TableCell>
                        </TableRow>

                        <!-- Empty state -->
                        <TableRow v-else-if="sortedData.length === 0">
                            <TableCell :colspan="columns.length" class="py-8 text-center">
                                <TableEmpty>
                                    <div class="text-muted-foreground">
                                        {{ stats.isFiltered ? 'Aucun résultat pour cette recherche' : emptyMessage }}
                                    </div>
                                </TableEmpty>
                            </TableCell>
                        </TableRow>

                        <!-- Data rows -->
                        <TableRow v-else v-for="(item, index) in sortedData" :key="index" class="hover:bg-muted/50">
                            <TableCell v-for="column in columns" :key="column.key" :style="{ width: column.width }">
                                <!-- Slot personnalisé si défini -->
                                <slot v-if="column.slot" :name="column.slot" :item="item" :value="getNestedValue(item, column.key)" :index="index">
                                    {{ renderCell(column, item) }}
                                </slot>

                                <!-- Rendu par défaut -->
                                <span v-else>{{ renderCell(column, item) }}</span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </Card>
    </div>
</template>
