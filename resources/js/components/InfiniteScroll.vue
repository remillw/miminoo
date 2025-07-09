<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { onMounted, onUnmounted, ref, computed, watch } from 'vue'
import { route } from 'ziggy-js'

interface Pagination {
    current_page: number
    last_page: number
    total: number
    per_page: number
}

interface Props {
    pagination: Pagination
    route: string
    parameters?: Record<string, any>
    loadingMessage?: string
    endMessage?: string
    threshold?: number
    resetTrigger?: any
}

const props = withDefaults(defineProps<Props>(), {
    loadingMessage: 'Chargement...',
    endMessage: 'Fin des résultats',
    threshold: 0.1,
    parameters: () => ({})
})

// Refs
const observer = ref<HTMLElement>()
const loadingIndicator = ref<HTMLElement>()
const infiniteScrollContainer = ref<HTMLElement>()

// États
const loading = ref(false)
const intersectionObserver = ref<IntersectionObserver>()

// Computed
const isEnd = computed(() => {
    return props.pagination.current_page >= props.pagination.last_page
})

const hasNextPage = computed(() => {
    return props.pagination.current_page < props.pagination.last_page
})

// Méthodes
const loadMore = async () => {
    if (loading.value || isEnd.value) return
    
    loading.value = true
    
    try {
        const nextPage = props.pagination.current_page + 1
        const params = {
            ...props.parameters,
            page: nextPage
        }
        
        // Utiliser router.get avec preserveScroll pour conserver la position
        router.get(route(props.route), params, {
            preserveState: true,
            preserveScroll: true,
            only: ['announcements'] // Seulement recharger les annonces
        })
    } catch (error) {
        console.error('Erreur lors du chargement des pages suivantes:', error)
    } finally {
        loading.value = false
    }
}

const setupIntersectionObserver = () => {
    if (!observer.value) return
    
    intersectionObserver.value = new IntersectionObserver(
        (entries) => {
            const entry = entries[0]
            if (entry.isIntersecting && hasNextPage.value && !loading.value) {
                loadMore()
            }
        },
        {
            root: null,
            rootMargin: '100px',
            threshold: props.threshold
        }
    )
    
    intersectionObserver.value.observe(observer.value)
}

const reset = () => {
    loading.value = false
    if (intersectionObserver.value) {
        intersectionObserver.value.disconnect()
        setupIntersectionObserver()
    }
}

// Watchers
watch(() => props.resetTrigger, () => {
    reset()
})

// Lifecycle
onMounted(() => {
    setupIntersectionObserver()
})

onUnmounted(() => {
    if (intersectionObserver.value) {
        intersectionObserver.value.disconnect()
    }
})

// Exposer les méthodes pour utilisation externe
defineExpose({
    loadMore,
    reset
})
</script>

<template>
    <div ref="infiniteScrollContainer">
        <!-- Contenu scrollable -->
        <slot />
        
        <!-- Indicateur de chargement -->
        <div
            v-if="loading && !isEnd"
            ref="loadingIndicator"
            class="flex items-center justify-center py-8"
        >
            <div class="flex items-center space-x-2 text-gray-600">
                <div class="h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-blue-500"></div>
                <span>{{ loadingMessage }}</span>
            </div>
        </div>
        
        <!-- Indicateur de fin -->
        <div v-if="isEnd && pagination.total > 0" class="py-8 text-center text-gray-500">
            <div class="flex items-center justify-center space-x-2">
                <div class="h-1 w-8 bg-gray-300 rounded"></div>
                <span>{{ endMessage }}</span>
                <div class="h-1 w-8 bg-gray-300 rounded"></div>
            </div>
        </div>
        
        <!-- Observateur pour l'intersection -->
        <div 
            ref="observer" 
            class="h-4 w-full"
            style="margin-top: -1px;"
        ></div>
    </div>
</template> 