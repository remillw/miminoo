<script setup lang="ts">

</script>

<template>
    <div>
        <!-- Widgets, stats, notifications, annonces, etc. -->
        <div class="mb-6">
            <h2 class="mb-2 text-xl font-bold">Bonjour, Sophie</h2>
            <p class="text-gray-500">Bienvenue sur votre tableau de bord</p>
        </div>
        <!-- À compléter avec les widgets et sections du dashboard parent -->
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl bg-white p-4 shadow-sm">Prochaine garde</div>
            <div class="rounded-xl bg-white p-4 shadow-sm">Notifications</div>
            <div class="rounded-xl bg-white p-4 shadow-sm">Stats</div>
        </div>
        <div class="mb-6 rounded-xl bg-white p-4 shadow-sm">Vos dernières annonces</div>
    </div>
</template>
