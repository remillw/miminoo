<template>
    <div v-if="showDebug" class="fixed top-2 left-2 z-50 max-w-xs rounded bg-black/80 p-2 text-xs text-white">
        <div>Mobile: {{ isMobile ? '✅' : '❌' }}</div>
        <div>ReactNativeWebView: {{ hasReactNative ? '✅' : '❌' }}</div>
        <div>requestDeviceToken: {{ hasRequestDeviceToken ? '✅' : '❌' }}</div>
        <div>UserAgent: {{ isMobileUserAgent ? '✅' : '❌' }}</div>
        <div>Expo Marker: {{ hasExpoMarker ? '✅' : '❌' }}</div>
        <div>Should Hide H/F: {{ shouldHide ? '✅' : '❌' }}</div>
        <button @click="showDebug = false" class="mt-1 text-red-400">Fermer</button>
    </div>
    <button v-else @click="showDebug = true" class="fixed top-2 left-2 z-50 rounded bg-blue-600 px-2 py-1 text-xs text-white">Debug</button>
</template>

<script setup lang="ts">
import { useDeviceToken } from '@/composables/useDeviceToken';
import { computed, ref } from 'vue';

const { isMobileApp } = useDeviceToken();
const showDebug = ref(false);

const isMobile = computed(() => isMobileApp());
const hasReactNative = computed(() => !!(window as any).ReactNativeWebView);
const hasRequestDeviceToken = computed(() => !!(window as any).requestDeviceToken);
const isMobileUserAgent = computed(() => window.navigator.userAgent.includes('TrouveTaBabySitter/Mobile'));
const hasExpoMarker = computed(() => !!(window as any).isExpoApp || !!(window as any).__EXPO_WEBVIEW__);
const shouldHide = computed(() => isMobile.value);
</script>
