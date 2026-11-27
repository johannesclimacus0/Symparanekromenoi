<script setup lang="ts">
import { graphql } from '@/graphql/client';
import {
    CancelDeploymentDocument,
    type CancelDeploymentMutation,
} from '@/graphql/generated/graphql';
import { useWorkspaceRequest } from '@/modules/workspaces/useWorkspaceRequest';

const props = defineProps<{ deploymentId: string }>();
const emit = defineEmits<{
    cancelled: [deployment: CancelDeploymentMutation['cancelDeployment']];
}>();
const { processing, failure, run } = useWorkspaceRequest();

function cancel() {
    return run(async (signal) => {
        const data = await graphql(
            CancelDeploymentDocument,
            { id: props.deploymentId },
            { csrf: true, signal },
        );
        emit('cancelled', data.cancelDeployment);
    });
}
</script>
<template>
    <button type="button" :disabled="processing" @click="cancel">cancel</button>
    <span v-if="failure" class="text-danger">{{ failure }}</span>
</template>
