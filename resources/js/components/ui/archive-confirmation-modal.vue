<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Archive, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    open: boolean;
    title?: string;
    description?: string;
    confirmText?: string;
    cancelText?: string;
    isLoading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Archiver cette conversation ?',
    description: 'Elle ne sera plus visible dans votre messagerie mais restera accessible dans vos archives.',
    confirmText: 'Oui, archiver',
    cancelText: 'Annuler',
    isLoading: false
});

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
    cancel: [];
}>();

const close = () => {
    emit('update:open', false);
    emit('cancel');
};

const confirm = () => {
    emit('confirm');
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => emit('update:open', value)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <div class="flex items-start gap-4">
                    <!-- Icône d'archivage -->
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                        <Archive class="h-6 w-6 text-amber-600" />
                    </div>
                    
                    <div class="flex-1">
                        <DialogTitle class="text-left text-lg font-semibold text-gray-900">
                            {{ title }}
                        </DialogTitle>
                        <DialogDescription class="mt-2 text-left text-sm text-gray-600">
                            {{ description }}
                        </DialogDescription>
                    </div>
                    
                    <!-- Bouton fermer -->
                    <button 
                        @click="close"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </DialogHeader>

            <!-- Actions -->
            <div class="flex flex-col-reverse gap-3 pt-6 sm:flex-row sm:justify-end">
                <Button 
                    @click="close"
                    variant="outline" 
                    class="w-full sm:w-auto"
                    :disabled="isLoading"
                >
                    {{ cancelText }}
                </Button>
                <Button 
                    @click="confirm"
                    class="w-full bg-amber-600 hover:bg-amber-700 sm:w-auto"
                    :disabled="isLoading"
                >
                    <Archive v-if="!isLoading" class="mr-2 h-4 w-4" />
                    <div v-if="isLoading" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                    {{ isLoading ? 'Archivage...' : confirmText }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template> 