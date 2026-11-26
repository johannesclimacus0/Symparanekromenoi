<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { graphql } from '@/graphql/client';
import { DeployResultDocument, type DeploymentStatus } from '@/graphql/generated/graphql';
import { normalizeApiError } from '@/http/http';

const props = defineProps<{
    deploymentId: string;
    initialStatus: DeploymentStatus;
}>();

const status = ref(props.initialStatus);
const finishedAt = ref<string | null>(null);
const failureMessage = ref<string | null>(null);
const requestError = ref('');
const unavailable = ref(false);

const controller = new AbortController();
let timer: number | undefined;

async function refresh(): Promise<void> {
    requestError.value = '';

    try {
        const { deployment } = await graphql(
            DeployResultDocument,
            { id: props.deploymentId },
            { signal: controller.signal },
        );

        if (controller.signal.aborted) return;

        if (deployment === null) {
            unavailable.value = true;
            return;
        }

        status.value = deployment.status;
        finishedAt.value = deployment.finishedAt;
        failureMessage.value = deployment.failureMessage;

        if (['Queued', 'Preparing', 'Running'].includes(deployment.status)) {
            timer = window.setTimeout(() => void refresh(), 2000);
        }
    } catch (error) {
        if (!controller.signal.aborted) {
            requestError.value = normalizeApiError(error).message;
        }
    }
}

onMounted(refresh);

onUnmounted(() => {
    window.clearTimeout(timer);
    controller.abort();
});
</script>

<template>
    <div class="text-muted break-words sm:col-span-2">
        <p>
            dry run {{ deploymentId }} / {{ unavailable ? 'unavailable' : status }}
            <time v-if="finishedAt">{{ finishedAt }}</time>
        </p>

        <p v-if="failureMessage" class="text-danger">{{ failureMessage }}</p>

        <div v-if="requestError">
            <span class="text-danger">{{ requestError }}</span>
            <button type="button" @click="refresh">retry status</button>
        </div>
    </div>
</template>
