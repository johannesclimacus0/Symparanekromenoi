<script setup lang="ts">
defineProps<{ message: string; id?: string }>();
</script>

<template>
    <p :id="id" class="text-sm text-danger" role="alert">{{ message }}</p>
</template>
