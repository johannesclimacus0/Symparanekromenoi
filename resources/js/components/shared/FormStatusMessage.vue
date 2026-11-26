<script setup lang="ts">
defineProps<{ message: string }>();
</script>

<template>
    <p class="text-sm text-muted" role="status" aria-live="polite">
        {{ message }}
    </p>
</template>
