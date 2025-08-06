<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

interface Pagination {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

interface Props {
    pagination: Pagination;
    route: string;
    parameters?: Record<string, any>;
    loadingMessage?: string;
    endMessage?: string;
    threshold?: number;
    resetTrigger?: any;
}

const props = withDefaults(defineProps<Props>(), {
    loadingMessage: 'Chargement...',
    endMessage: 'Fin des résultats',
    threshold: 0.1,
    parameters: () => ({}),
});

// Émissions
const emit = defineEmits<{
    'load-more': [data: any];
    error: [error: string];
}>();

// Refs
const observer = ref<HTMLElement>();
const loadingIndicator = ref<HTMLElement>();
const infiniteScrollContainer = ref<HTMLElement>();

// États
const loading = ref(false);
const intersectionObserver = ref<IntersectionObserver>();
const currentPage = ref(props.pagination.current_page);

// Computed
const isEnd = computed(() => {
    return currentPage.value >= props.pagination.last_page;
});

const hasNextPage = computed(() => {
    return currentPage.value < props.pagination.last_page;
});

// Méthodes
const loadMore = async () => {
    if (loading.value || isEnd.value) return;

    loading.value = true;

    try {
        const nextPage = currentPage.value + 1;
        const params = new URLSearchParams({
            ...props.parameters,
            page: nextPage.toString(),
        });

        // Construire l'URL avec Laravel's route helper côté client
        const baseUrl = window.location.origin;
        let url = baseUrl;

        // Mapping des routes pour construire l'URL
        if (props.route === 'announcements.index') {
            url += '/annonces';
        } else if (props.route === 'parent.announcements-reservations') {
            url += '/parent/annonces-et-reservations';
        } else if (props.route === 'payments.index') {
            url += '/paiements';
        }

        url += '?' + params.toString();

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        // Mettre à jour la page courante
        currentPage.value = nextPage;

        // Émettre les nouvelles données
        emit('load-more', data);
    } catch (error) {
        console.error('Erreur lors du chargement des pages suivantes:', error);
        emit('error', 'Erreur lors du chargement des données');
    } finally {
        loading.value = false;
    }
};

const setupIntersectionObserver = () => {
    if (!observer.value) return;

    intersectionObserver.value = new IntersectionObserver(
        (entries) => {
            const entry = entries[0];
            if (entry.isIntersecting && hasNextPage.value && !loading.value) {
                loadMore();
            }
        },
        {
            root: null,
            rootMargin: '100px',
            threshold: props.threshold,
        },
    );

    intersectionObserver.value.observe(observer.value);
};

const reset = () => {
    loading.value = false;
    currentPage.value = props.pagination.current_page;
    if (intersectionObserver.value) {
        intersectionObserver.value.disconnect();
        setupIntersectionObserver();
    }
};

// Watchers
watch(
    () => props.resetTrigger,
    () => {
        reset();
    },
);

watch(
    () => props.pagination,
    (newPagination) => {
        currentPage.value = newPagination.current_page;
    },
    { deep: true },
);

// Lifecycle
onMounted(() => {
    setupIntersectionObserver();
});

onUnmounted(() => {
    if (intersectionObserver.value) {
        intersectionObserver.value.disconnect();
    }
});

// Exposer les méthodes pour utilisation externe
defineExpose({
    loadMore,
    reset,
});
</script>

<template>
    <div ref="infiniteScrollContainer">
        <!-- Contenu scrollable -->
        <slot />

        <!-- Indicateur de chargement -->
        <div v-if="loading && !isEnd" ref="loadingIndicator" class="flex items-center justify-center py-8">
            <div class="flex items-center space-x-2 text-gray-600">
                <div class="h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-orange-500"></div>
                <span>{{ loadingMessage }}</span>
            </div>
        </div>

        <!-- Indicateur de fin -->
        <div v-if="isEnd && pagination.total > 0" class="py-8 text-center text-gray-500">
            <div class="flex items-center justify-center space-x-2">
                <div class="h-1 w-8 rounded bg-gray-300"></div>
                <span>{{ endMessage }}</span>
                <div class="h-1 w-8 rounded bg-gray-300"></div>
            </div>
        </div>

        <!-- Observateur pour l'intersection -->
        <div ref="observer" class="h-4 w-full" style="margin-top: -1px"></div>
    </div>
</template>
