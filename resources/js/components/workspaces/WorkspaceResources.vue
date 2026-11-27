<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { graphql } from '@/graphql/client';
import {
    BrowseProjectsDocument,
    BrowseMembersDocument,
    BrowseServersDocument,
    BrowseEnvironmentsDocument,
    BrowseInvitationsDocument,
} from '@/graphql/generated/graphql';
import { normalizeApiError } from '@/http/http';
import * as api from '@/graphql/generated/graphql';
import DeployResult from '@/modules/deployments/DeployResult.vue';
import DeploymentHistory from '@/modules/deployments/DeploymentHistory.vue';
import ResourceEditor from '@/modules/workspaces/ResourceEditor.vue';
import AccessEditor from '@/modules/workspaces/AccessEditor.vue';
import { useWorkspaceRequest } from '@/modules/workspaces/useWorkspaceRequest';
import FormFieldError from '@/components/shared/FormFieldError.vue';

const props = defineProps<{
    ownerId: string;
    workspaceId: string;
    section: 'projects' | 'servers' | 'members' | 'environments' | 'invitations';
}>();
type Row = {
    id: string;
    name: string;
    description?: string | null;
    address?: string;
    serverId?: string | null;
    detail?: string | null;
};
const rows = ref<Row[]>([]);
const project = ref<Row | null>(null);
const cursor = ref<string | null>(null);
const busy = ref(false);
const error = ref('');
const controller = new AbortController();

const editing = ref<Row | 'new' | null>(null);
const historyEnvironmentId = ref<string | null>(null);
const { processing, failure, run } = useWorkspaceRequest();

const pendingKeys = new Map<string, string>();
const receipts = ref<
    Record<string, api.RequestDeploymentDryRunMutation['requestDeploymentDryRun']>
>({});

async function requestDryRun(row: Row): Promise<void> {
    await run(async (signal) => {
        const environmentId = row.id;
        const key = pendingKeys.get(environmentId) ?? crypto.randomUUID();

        pendingKeys.set(environmentId, key);

        const data = await graphql(
            api.RequestDeploymentDryRunDocument,
            { input: { environmentId, idempotencyKey: key } },
            { csrf: true, signal },
        );

        receipts.value[environmentId] = data.requestDeploymentDryRun;
        pendingKeys.delete(environmentId);
    });
}

async function refresh() {
    editing.value = null;
    rows.value = [];
    cursor.value = null;
    await load();
}

function perform(row: Row, transfer = false) {
    const label = transfer
        ? 'Transfer ownership to'
        : props.section === 'invitations'
          ? 'Revoke invitation for'
          : 'Remove';
    if (!window.confirm(`${label} ${row.name}?`)) return;
    return run(async (signal) => {
        const options = { csrf: true, signal };
        const id = props.workspaceId;
        if (transfer)
            await graphql(
                api.TransferWorkspaceOwnershipDocument,
                { input: { id, memberId: row.id } },
                options,
            );
        else if (props.section === 'members')
            await graphql(
                api.RemoveWorkspaceMemberDocument,
                { input: { id, memberId: row.id } },
                options,
            );
        else if (props.section === 'invitations')
            await graphql(
                api.RevokeWorkspaceInvitationDocument,
                { input: { id, invitationId: row.id } },
                options,
            );
        else if (props.section === 'projects')
            await graphql(api.DeleteProjectDocument, { id: row.id }, options);
        else if (props.section === 'servers')
            await graphql(api.DeleteServerDocument, { id: row.id }, options);
        else await graphql(api.DeleteEnvironmentDocument, { id: row.id }, options);
        await refresh();
    });
}

async function load() {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    const vars = { id: props.ownerId, after: cursor.value };
    const options = { signal: controller.signal, csrf: true };
    try {
        let page;
        if (props.section === 'projects')
            page = (await graphql(BrowseProjectsDocument, vars, options)).items;
        else if (props.section === 'servers')
            page = (await graphql(BrowseServersDocument, vars, options)).items;
        else if (props.section === 'members')
            page = (await graphql(BrowseMembersDocument, vars, options)).items;
        else if (props.section === 'invitations')
            page = (await graphql(BrowseInvitationsDocument, vars, options)).items;
        else page = (await graphql(BrowseEnvironmentsDocument, vars, options)).items;

        for (const { node } of page.edges) {
            if (node.__typename === 'WorkspaceInvitation')
                rows.value.push({
                    id: node.id,
                    name: node.email,
                    detail: 'expires ' + new Date(node.expiresAt).toLocaleString(),
                });
            else if (node.__typename === 'WorkspaceMembership')
                rows.value.push({ id: node.id, name: node.user.name, detail: node.role });
            else if (node.__typename === 'Server')
                rows.value.push({ ...node, detail: node.address });
            else if (node.__typename === 'Environment')
                rows.value.push({
                    ...node,
                    serverId: node.server?.id,
                    detail: node.server?.name ?? 'no target',
                });
            else rows.value.push({ ...node, detail: node.description });
        }
        cursor.value = page.pageInfo.hasNextPage ? (page.pageInfo.endCursor ?? null) : null;
    } catch (reason) {
        if (!controller.signal.aborted) error.value = normalizeApiError(reason).message;
    } finally {
        busy.value = false;
    }
}
onMounted(load);
onUnmounted(() => controller.abort());
</script>

<template>
    <section class="py-5">
        <template v-if="project">
            <button @click="project = null">back to projects</button>
            <h2 class="mt-3 text-lg break-words">{{ project.name }}</h2>
            <WorkspaceResources
                :workspace-id="workspaceId"
                :owner-id="project.id"
                section="environments"
            />
        </template>
        <template v-else>
            <div
                class="border-line mb-4 flex items-center justify-between border-b border-dashed pb-2"
            >
                <h2 class="text-lg">{{ section }}</h2>
                <button
                    v-if="!editing && section !== 'members'"
                    :disabled="busy || processing"
                    @click="editing = 'new'"
                >
                    {{ section === 'invitations' ? 'invite' : 'new' }}
                </button>
            </div>
            <p v-if="failure" class="text-danger" role="alert">{{ failure }}</p>
            <template v-if="editing">
                <AccessEditor
                    v-if="section === 'members' || section === 'invitations'"
                    :workspace-id="workspaceId"
                    :member="editing === 'new' ? undefined : editing"
                    @saved="refresh"
                    @cancel="editing = null"
                />
                <ResourceEditor
                    v-else
                    :section="section"
                    :workspace-id="workspaceId"
                    :owner-id="ownerId"
                    :record="editing === 'new' ? undefined : editing"
                    @saved="refresh"
                    @cancel="editing = null"
                />
            </template>
            <ul v-else class="divide-line divide-y">
                <li
                    v-for="row in rows"
                    :key="row.id"
                    class="grid gap-1 py-3 text-sm sm:grid-cols-2"
                >
                    <button
                        v-if="section === 'projects'"
                        class="justify-self-start text-left break-all"
                        @click="project = row"
                    >
                        <span class="normal-case">{{ row.name }}</span>
                    </button>
                    <span v-else class="break-all">{{ row.name }}</span>
                    <span class="text-muted break-words">{{ row.detail }}</span>
                    <div
                        v-if="section !== 'members' || row.detail !== 'owner'"
                        class="flex flex-wrap gap-2 sm:col-span-2"
                    >
                        <button
                            v-if="section === 'environments'"
                            type="button"
                            :disabled="busy || processing || !row.serverId"
                            @click="requestDryRun(row)"
                        >
                            dry run
                        </button>
                        <button
                            v-if="section === 'environments'"
                            type="button"
                            @click="
                                historyEnvironmentId =
                                    historyEnvironmentId === row.id ? null : row.id
                            "
                        >
                            {{ historyEnvironmentId === row.id ? 'hide history' : 'history' }}
                        </button>
                        <button
                            v-if="section !== 'invitations'"
                            :disabled="busy || processing"
                            @click="editing = row"
                        >
                            {{ section === 'members' ? 'change role' : 'edit' }}
                        </button>
                        <button
                            v-if="section === 'members'"
                            :disabled="busy || processing"
                            @click="perform(row, true)"
                        >
                            transfer ownership
                        </button>
                        <button :disabled="busy || processing" @click="perform(row)">
                            {{ section === 'invitations' ? 'revoke' : 'remove' }}
                        </button>
                    </div>
                    <template v-if="section === 'environments'">
                        <DeployResult
                            v-if="receipts[row.id]"
                            :key="receipts[row.id].id"
                            :deployment-id="receipts[row.id].id"
                            :initial-status="receipts[row.id].status"
                        />
                        <DeploymentHistory
                            v-if="historyEnvironmentId === row.id"
                            :key="receipts[row.id]?.id ?? row.id"
                            :environment-id="row.id"
                        />
                    </template>
                </li>
            </ul>
            <FormFieldError v-if="error" :message="error" />
            <p v-if="busy" role="status" class="text-muted">loading...</p>
            <button v-else-if="!editing && (error || cursor)" @click="load">
                {{ error ? 'retry' : 'load more' }}
            </button>
            <p v-else-if="!editing && !rows.length" class="text-muted">No {{ section }} yet</p>
        </template>
    </section>
</template>
