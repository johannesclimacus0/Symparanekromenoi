<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { graphql } from '@/graphql/client';
import {
    DeploymentJournalDocument,
    type DeploymentJournalQuery,
} from '@/graphql/generated/graphql';
import { normalizeApiError } from '@/http/http';

const props = defineProps<{ deploymentId: string }>();

type Deployment = NonNullable<DeploymentJournalQuery['deployment']>;
type LogEntry = Deployment['logs']['edges'][number]['node'];

const entries = ref<LogEntry[]>([]);
const failureMessage = ref<string | null>(null);
const unavailable = ref(false);

const after = ref<string | null>(null);
const hasNextPage = ref(true);
const loading = ref(false);
const errorMessage = ref('');
const controller = new AbortController();

async function loadJournal(): Promise<void> {
    if (loading.value || !hasNextPage.value) return;

    loading.value = true;
    errorMessage.value = '';

    try {
        const { deployment } = await graphql(
            DeploymentJournalDocument,
            { id: props.deploymentId, after: after.value },
            { csrf: true, signal: controller.signal },
        );

        if (controller.signal.aborted) return;

        if (deployment === null) {
            unavailable.value = true;
            hasNextPage.value = false;
            entries.value = [];
            failureMessage.value = null;
            return;
        }

        failureMessage.value = deployment.failureMessage;

        const page = deployment.logs;
        entries.value.push(...page.edges.map((edge) => edge.node));
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

onMounted(loadJournal);
onUnmounted(() => controller.abort());
</script>

<template>
    <section class="border-line my-2 space-y-2 border-l pl-3 text-sm">
        <p v-if="failureMessage" class="text-danger">{{ failureMessage }}</p>
        <ol v-if="entries.length" class="space-y-1">
            <li v-for="entry in entries" :key="entry.id" class="flex flex-wrap gap-x-2">
                <span class="text-muted">{{ entry.sequence }}</span>
                <time class="text-muted">{{ entry.recordedAt }}</time>
                <span class="text-muted">{{ entry.stream }}</span>
                <span class="min-w-0 break-words whitespace-pre-wrap">{{ entry.message }}</span>
            </li>
        </ol>
        <p v-if="loading" class="text-muted">loading logs...</p>
        <div v-else-if="errorMessage">
            <p class="text-danger">{{ errorMessage }}</p>
            <button type="button" @click="loadJournal">retry</button>
        </div>
        <p v-else-if="unavailable" class="text-muted">deployment unavailable</p>
        <p v-else-if="!entries.length" class="text-muted">no log entries yet</p>
        <button v-else-if="hasNextPage" type="button" @click="loadJournal">load more</button>
    </section>
</template>
