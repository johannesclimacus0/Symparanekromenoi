<script setup lang="ts">
defineProps<{ message: string }>();
</script>

<template>
    <p class="text-muted text-sm" role="status" aria-live="polite">
        {{ message }}
    </p>
</template>
