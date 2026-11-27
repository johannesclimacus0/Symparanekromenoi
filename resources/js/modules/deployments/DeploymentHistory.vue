<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { graphql } from '@/graphql/client';
import {
    DeploymentHistoryDocument,
    type DeploymentHistoryQuery,
} from '@/graphql/generated/graphql';
import { normalizeApiError } from '@/http/http';

import DeploymentJournal from './DeploymentJournal.vue';

const props = defineProps<{ environmentId: string }>();

type DeploymentRow = DeploymentHistoryQuery['deployments']['edges'][number]['node'];

const openDeploymentId = ref<string | null>(null);
const deployments = ref<DeploymentRow[]>([]);
const after = ref<string | null>(null);
const hasNextPage = ref(true);
const loading = ref(false);
const errorMessage = ref('');
const controller = new AbortController();

async function loadPage(): Promise<void> {
    if (loading.value || !hasNextPage.value) return;

    loading.value = true;
    errorMessage.value = '';

    try {
        const { deployments: page } = await graphql(
            DeploymentHistoryDocument,
            { environmentId: props.environmentId, after: after.value },
            { csrf: true, signal: controller.signal },
        );

        if (controller.signal.aborted) return;

        const loadedIds = new Set(deployments.value.map((deployment) => deployment.id));
        deployments.value.push(
            ...page.edges
                .map((edge) => edge.node)
                .filter((deployment) => !loadedIds.has(deployment.id)),
        );
        after.value = page.pageInfo.endCursor;
        hasNextPage.value = page.pageInfo.hasNextPage && after.value !== null;
    } catch (error) {
        if (!controller.signal.aborted) {
            errorMessage.value = normalizeApiError(error).message;
        }
    } finally {
        loading.value = false;
    }
}

onMounted(loadPage);
onUnmounted(() => controller.abort());
</script>

<template>
    <section class="space-y-2 text-sm sm:col-span-2">
        <h3>deployment history</h3>

        <ul v-if="deployments.length" class="divide-line divide-y">
            <li
                v-for="deployment in deployments"
                :key="deployment.id"
                class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 py-2 sm:grid-cols-[auto_10ch_minmax(0,1fr)_auto]"
            >
                <time class="text-muted col-span-2 sm:col-span-1">{{ deployment.createdAt }}</time>
                <span class="col-start-1 sm:col-start-2">{{ deployment.status }}</span>
                <span class="col-start-1 min-w-0 break-words sm:col-start-3">{{
                    deployment.snapshot?.server.name ?? 'unknown target'
                }}</span>
                <button
                    class="col-start-2 row-span-2 row-start-2 justify-self-end whitespace-nowrap sm:col-start-4 sm:row-span-1 sm:row-start-1"
                    type="button"
                    @click="
                        openDeploymentId = openDeploymentId === deployment.id ? null : deployment.id
                    "
                >
                    {{ openDeploymentId === deployment.id ? 'hide logs' : 'logs' }}
                </button>
                <DeploymentJournal
                    v-if="openDeploymentId === deployment.id"
                    :deployment-id="deployment.id"
                    class="col-span-full min-w-0"
                />
            </li>
        </ul>

        <p v-if="loading" class="text-muted">loading...</p>
        <div v-else-if="errorMessage">
            <p class="text-danger">{{ errorMessage }}</p>
            <button type="button" @click="loadPage">retry</button>
        </div>
        <p v-else-if="!deployments.length" class="text-muted">no deployments yet</p>
        <button v-else-if="hasNextPage" type="button" @click="loadPage">load more</button>
    </section>
</template>
