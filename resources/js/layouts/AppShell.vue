<script setup lang="ts">
import AuthSessionBridge from '@/auth/AuthSessionBridge.vue';
import { useAuthStore } from '@/stores/auth/index';
import { RouterView, useRoute, useRouter } from 'vue-router';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

async function handleLogout(): Promise<void> {
    try {
        await auth.logout();
    } finally {
        await router.replace('/login');
    }
}
</script>

<template>
    <main
        v-if="auth.isLoading"
        class="bg-panel flex min-h-screen items-center justify-center"
        role="status"
        aria-live="polite"
        aria-busy="true"
    >
        <span class="text-muted text-sm">Loading…</span>
    </main>
    <div
        v-else
        :class="{
            'grid h-dvh grid-rows-[auto_minmax(0,1fr)]': route.path.startsWith('/dashboard'),
        }"
    >
        <AuthSessionBridge />
        <header
            v-if="route.path.startsWith('/dashboard') || route.path.startsWith('/settings')"
            class="border-line bg-panel border-b"
        >
            <div class="flex flex-wrap items-center gap-4 px-4 py-3">
                <nav aria-label="Main" class="terminal-actions flex flex-wrap gap-3 text-sm">
                    <RouterLink to="/dashboard" active-class="text-accent">Dashboard</RouterLink>
                    <RouterLink
                        to="/settings/profile"
                        :class="{ 'text-accent': route.path.startsWith('/settings') }"
                        >Settings</RouterLink
                    >
                </nav>
                <div
                    v-if="auth.user"
                    class="terminal-actions ml-auto flex items-center gap-3 text-sm"
                >
                    <span>{{ auth.user.name }}</span>
                    <button type="button" @click="handleLogout">Log out</button>
                </div>
            </div>
        </header>
        <RouterView />
    </div>
</template>
