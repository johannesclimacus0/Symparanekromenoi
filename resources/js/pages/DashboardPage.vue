<script setup lang="ts">
import { onMounted } from 'vue';
import WorkspaceList from '@/components/workspaces/WorkspaceList.vue';

onMounted(() => {
    document.title = 'Dashboard';
});
</script>

<template>
    <main class="min-h-0 w-full">
        <WorkspaceList />
    </main>
</template>
