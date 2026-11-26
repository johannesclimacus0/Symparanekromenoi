<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { graphql } from '@/graphql/client';
import * as api from '@/graphql/generated/graphql';
import { useWorkspaceRequest } from './useWorkspaceRequest';

const props = defineProps<{
    section: 'projects' | 'servers' | 'environments';
    workspaceId: string;
    ownerId: string;
    record?: {
        id: string;
        name: string;
        address?: string;
        description?: string | null;
        serverId?: string | null;
    };
}>();
const emit = defineEmits<{ saved: []; cancel: [] }>();
const name = ref(props.record?.name ?? '');
const description = ref(props.record?.description ?? '');
const address = ref(props.record?.address ?? '');
const serverId = ref(props.record?.serverId ?? '');
const targets = ref<{ id: string; name: string }[]>([]);
const after = ref<string | null>(null);
const { processing, failure, run } = useWorkspaceRequest();
const lookup = useWorkspaceRequest();

function loadTargets() {
    return lookup.run(async (signal) => {
        const { items } = await graphql(
            api.BrowseServersDocument,
            { id: props.workspaceId, after: after.value },
            { signal, csrf: true },
        );
        targets.value.push(...items.edges.map((edge) => edge.node));
        after.value = items.pageInfo.hasNextPage ? items.pageInfo.endCursor : null;
    });
}
onMounted(() => {
    if (props.section === 'environments') void loadTargets();
});

function save() {
    return run(async (signal) => {
        const options = { signal, csrf: true };
        const fields = { name: name.value.trim(), description: description.value.trim() || null };
        const id = props.record?.id;
        const workspaceId = props.workspaceId;
        if (props.section === 'projects') {
            if (id) await graphql(api.UpdateProjectDocument, { input: { ...fields, id } }, options);
            else
                await graphql(
                    api.CreateProjectDocument,
                    { input: { ...fields, workspaceId } },
                    options,
                );
        } else if (props.section === 'servers') {
            const server = { ...fields, address: address.value.trim() };
            if (id) await graphql(api.UpdateServerDocument, { input: { ...server, id } }, options);
            else
                await graphql(
                    api.CreateServerDocument,
                    { input: { ...server, workspaceId } },
                    options,
                );
        } else {
            const environment = { ...fields, serverId: serverId.value || null };
            if (id)
                await graphql(
                    api.UpdateEnvironmentDocument,
                    { input: { ...environment, id } },
                    options,
                );
            else
                await graphql(
                    api.CreateEnvironmentDocument,
                    { input: { ...environment, projectId: props.ownerId } },
                    options,
                );
        }
        emit('saved');
    });
}
</script>
<template>
    <form class="border-line max-w-xl space-y-3 border-l pl-4" @submit.prevent="save">
        <fieldset class="space-y-3" :disabled="processing">
            <legend>{{ record ? 'edit' : 'new' }} {{ section.slice(0, -1) }}</legend>
            <label class="block">name<input required v-model.trim="name" /></label>
            <label v-if="section === 'servers'" class="block"
                >address<input required v-model.trim="address"
            /></label>
            <label class="block">description<input v-model="description" /></label>
            <template v-if="section === 'environments'">
                <label class="block"
                    >target server<select v-model="serverId">
                        <option value="">no target</option>
                        <option
                            v-if="serverId && !targets.some((t) => t.id === serverId)"
                            :value="serverId"
                        >
                            {{ serverId }}
                        </option>
                        <option v-for="target in targets" :key="target.id" :value="target.id">
                            {{ target.name }}
                        </option>
                    </select></label
                >
                <p v-if="lookup.failure.value" role="alert">{{ lookup.failure.value }}</p>
                <button
                    v-if="after || lookup.failure.value"
                    type="button"
                    :disabled="lookup.processing.value"
                    @click="loadTargets"
                >
                    load servers
                </button>
            </template>
            <p v-if="failure" role="alert" class="text-danger">{{ failure }}</p>
            <button type="submit">{{ processing ? 'saving...' : 'save' }}</button>
            <button type="button" @click="emit('cancel')">cancel</button>
        </fieldset>
    </form>
</template>
