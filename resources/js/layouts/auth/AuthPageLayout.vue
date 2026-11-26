<script setup lang="ts">
defineProps<{ title: string; description: string }>();
</script>

<template>
    <main class="bg-canvas text-ink flex min-h-screen items-center justify-center px-4 py-8">
        <section class="bg-panel border-line w-full max-w-sm space-y-6 rounded-lg border p-6">
            <header class="space-y-2 text-center">
                <h1 class="text-xl font-semibold">{{ title }}</h1>
                <p class="text-muted text-sm">{{ description }}</p>
            </header>
            <slot />
        </section>
    </main>
</template>
