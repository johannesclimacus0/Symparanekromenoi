<script setup lang="ts">
defineProps<{ title: string; description: string }>();
</script>

<template>
    <main class="flex min-h-screen items-center justify-center bg-canvas px-4 py-8 text-ink">
        <section class="w-full max-w-sm space-y-6 rounded-lg bg-panel border border-line p-6">
            <header class="space-y-2 text-center">
                <h1 class="text-xl font-semibold">{{ title }}</h1>
                <p class="text-sm text-muted">{{ description }}</p>
            </header>
            <slot />
        </section>
    </main>
</template>
