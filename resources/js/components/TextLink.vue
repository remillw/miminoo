<script setup lang="ts">
import { Method } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    href: string;
    tabindex?: number;
    method?: Method;
    as?: string;
    external?: boolean;
}

const props = defineProps<Props>();

const isExternal = computed(() => {
    if (props.external) return true;

    try {
        const url = new URL(props.href, window.location.origin);
        return url.origin !== window.location.origin;
    } catch {
        return false;
    }
});
</script>

<template>
    <a
        v-if="isExternal"
        :href="href"
        :tabindex="tabindex"
        target="_blank"
        rel="noopener noreferrer"
        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current!"
    >
        <slot />
    </a>

    <Link
        v-else
        :href="href"
        :tabindex="tabindex"
        :method="method"
        :as="as"
        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current!"
    >
        <slot />
    </Link>
</template>
