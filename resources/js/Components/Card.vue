<!-- Card.vue -->
<template>
    <div class="bg-white rounded-xl shadow-lg p-6 hover:scale-105 transform transition duration-300 text-left">
        <div class="text-4xl mb-4">{{ icon }}</div>
        <h3 class="text-xl font-semibold mb-2">{{ title }}</h3>
        <p class="text-gray-600">{{ description }}</p>
    </div>
</template>

<script setup>
defineProps({
    title: String,
    description: String,
    icon: String,
})
</script>
