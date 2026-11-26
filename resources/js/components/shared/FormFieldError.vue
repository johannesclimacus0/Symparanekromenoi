<script setup lang="ts">
defineProps<{ message: string; id?: string }>();
</script>

<template>
    <p :id="id" class="text-danger text-sm" role="alert">{{ message }}</p>
</template>
