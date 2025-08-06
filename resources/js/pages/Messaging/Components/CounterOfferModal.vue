<template>
    <div class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black">
        <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">Faire une contre-offre</h3>

            <div class="mb-4">
                <p class="mb-2 text-sm text-gray-600">{{ application.babysitter.name }} propose {{ application.proposed_rate }}€/h</p>

                <div class="rounded-lg bg-gray-50 p-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Tarif actuel :</span>
                        <span class="font-medium">{{ application.proposed_rate }}€/h</span>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="mb-4">
                    <label for="counter_rate" class="mb-2 block text-sm font-medium text-gray-700"> Votre contre-proposition </label>
                    <div class="relative">
                        <input
                            id="counter_rate"
                            v-model="form.counter_rate"
                            type="number"
                            step="0.5"
                            min="1"
                            max="50"
                            class="block w-full rounded-lg border-gray-300 pr-12 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="15"
                            required
                        />
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <span class="text-sm text-gray-500">€/h</span>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="counter_message" class="mb-2 block text-sm font-medium text-gray-700"> Message (optionnel) </label>
                    <textarea
                        id="counter_message"
                        v-model="form.counter_message"
                        rows="3"
                        class="block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Expliquez votre proposition..."
                    ></textarea>
                </div>

                <div class="flex gap-3">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700"
                        :disabled="!form.counter_rate"
                    >
                        Envoyer l'offre
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';

const props = defineProps({
    application: Object,
});

const emit = defineEmits(['close', 'submit']);

const form = reactive({
    counter_rate: '',
    counter_message: '',
});

function submit() {
    emit('submit', {
        counter_rate: parseFloat(form.counter_rate),
        counter_message: form.counter_message,
    });
}
</script>
