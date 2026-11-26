<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';
import { AcceptWorkspaceInvitationDocument } from '@/graphql/generated/graphql';
import { graphql } from '@/graphql/client';
import { useWorkspaceRequest } from './useWorkspaceRequest';
const route = useRoute();
const router = useRouter();
const token = new URLSearchParams(route.hash.slice(1)).get('token');
const { processing, failure, run } = useWorkspaceRequest();
function accept() {
    if (!token) return;
    return run(async (signal) => {
        await graphql(AcceptWorkspaceInvitationDocument, { token }, { signal, csrf: true });
        await router.replace('/dashboard');
    });
}
</script>
<template>
    <main class="terminal-actions mx-auto max-w-lg space-y-4 p-6">
        <h1 class="text-xl">workspace invitation</h1>
        <p v-if="!token" role="alert">Open the original link in your invitation email.</p>
        <button v-else :disabled="processing" @click="accept">
            {{ processing ? 'accepting...' : 'accept invitation' }}
        </button>
        <p v-if="failure" role="alert" class="text-danger">{{ failure }}</p>
        <RouterLink to="/dashboard">dashboard</RouterLink>
    </main>
</template>
