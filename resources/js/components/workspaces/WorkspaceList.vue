<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import FormFieldError from '@/components/shared/FormFieldError.vue';
import { normalizeApiError } from '@/http/http';
import { workspaceService } from '@/services/workspaces/WorkspaceService';
import WorkspaceCard from '@/components/workspaces/WorkspaceCard.vue';

import { graphql } from '@/graphql/client';
import {
    DeleteWorkspaceDocument,
    RestoreWorkspaceDocument,
    LeaveWorkspaceDocument,
} from '@/graphql/generated/graphql';
import { useWorkspaceRequest } from '@/modules/workspaces/useWorkspaceRequest';
import WorkspaceResources from './WorkspaceResources.vue';
import CreateWorkspaceForm from './CreateWorkspaceForm.vue';
import { useInfiniteScroll } from '@/composables/useInfiniteScroll';

type Workspace = { id: string; name: string };

const selectedId = ref('');
const selected = computed(() => workspaces.value.find((item) => item.id === selectedId.value));
const sections = ['projects', 'servers', 'members', 'invitations', 'settings'] as const;
const section = ref<(typeof sections)[number]>('projects');

function selectWorkspace(id: string) {
    selectedId.value = id;
    creating.value = false;
    restoring.value = false;
    failure.value = '';
    status.value = '';
}

const restoring = ref(false);
const restoreId = ref('');
const { run, processing, failure } = useWorkspaceRequest();

const AUTO_LOAD_STORAGE_KEY = 'workspaces:auto-load';
const autoLoad = ref<boolean>(localStorage.getItem(AUTO_LOAD_STORAGE_KEY) !== 'false');

const scrollContainerRef = ref<HTMLElement | null>(null);
const sentinelRef = ref<HTMLElement | null>(null);

function restore() {
    return run(async (signal) => {
        await graphql(
            RestoreWorkspaceDocument,
            { input: { id: restoreId.value.trim() } },
            { signal, csrf: true },
        );
        restoring.value = false;
        restoreId.value = '';
        await loadWorkspaces();
        status.value = 'Workspace restored';
    });
}

function exitWorkspace(remove: boolean) {
    const workspace = selected.value;
    if (!workspace || !window.confirm(`${remove ? 'Delete' : 'Leave'} ${workspace.name}?`)) return;
    return run(async (signal) => {
        const input = { id: workspace.id };
        const options = { signal, csrf: true };
        if (remove) {
            await graphql(DeleteWorkspaceDocument, { input }, options);
            restoreId.value = workspace.id;
        } else await graphql(LeaveWorkspaceDocument, { input }, options);
        await loadWorkspaces();
        status.value = remove
            ? `Deleted ${workspace.name}. Restore ID: ${workspace.id}`
            : `Left ${workspace.name}`;
    });
}

const creating = ref(false);
const newButton = ref<HTMLButtonElement>();
const moreError = ref<string | null>(null);
const status = ref('');

async function closeCreate() {
    creating.value = false;
    await nextTick();
    newButton.value?.focus();
}

function created(workspace: Workspace) {
    workspaces.value.unshift(workspace);
    selectWorkspace(workspace.id);
    status.value = `Created ${workspace.name}`;
    void closeCreate();
}

function navigate(event: KeyboardEvent) {
    if (!(event.target instanceof HTMLButtonElement) || !event.target.matches('[data-workspace]'))
        return;
    if (!(event.currentTarget instanceof HTMLElement)) return;
    const buttons = Array.from(
        event.currentTarget.querySelectorAll<HTMLButtonElement>('[data-workspace]'),
    );
    const index = buttons.indexOf(event.target);
    const next = event.key === 'ArrowDown' ? index + 1 : index - 1;
    event.preventDefault();
    buttons[next]?.focus();
}
const isLoading = ref(true);
const errorMessage = ref<string | null>(null);
const workspaces = ref<Workspace[]>([]);
const hasNextPage = ref(false);
let requestId = 0;

const endCursor = ref<string | null>(null);
const isLoadingMore = ref(false);

const canAutoLoad = computed(
    () =>
        autoLoad.value &&
        !isLoading.value &&
        hasNextPage.value &&
        !isLoadingMore.value &&
        !moreError.value,
);

useInfiniteScroll(sentinelRef, loadMoreWorkspaces, {
    enabled: canAutoLoad,
    root: scrollContainerRef,
    rootMargin: '80px',
});

function updateWorkspace(updatedWorkspace: Workspace): void {
    workspaces.value = workspaces.value.map((workspace) => {
        if (workspace.id === updatedWorkspace.id) {
            return updatedWorkspace;
        }

        return workspace;
    });
}

async function loadWorkspaces(): Promise<void> {
    const currentRequest = ++requestId;
    isLoading.value = true;
    errorMessage.value = null;
    moreError.value = null;
    isLoadingMore.value = false;

    try {
        const result = await workspaceService.getWorkspaces(20);
        if (currentRequest !== requestId) return;

        workspaces.value = result.edges.map((edge) => edge.node);
        creating.value = workspaces.value.length === 0;
        selectedId.value = workspaces.value[0]?.id ?? '';
        hasNextPage.value = result.pageInfo.hasNextPage && result.pageInfo.endCursor !== null;

        endCursor.value = result.pageInfo.endCursor;
    } catch (error) {
        if (currentRequest !== requestId) return;
        errorMessage.value = normalizeApiError(error).message;
    } finally {
        if (currentRequest === requestId) isLoading.value = false;
    }
}

async function loadMoreWorkspaces(): Promise<void> {
    if (!hasNextPage.value || isLoading.value || isLoadingMore.value || endCursor.value === null)
        return;

    const currentRequest = ++requestId;
    isLoadingMore.value = true;
    moreError.value = null;

    try {
        const result = await workspaceService.getWorkspaces(20, endCursor.value);

        if (currentRequest !== requestId) return;

        const existing = new Set(workspaces.value.map((workspace) => workspace.id));
        workspaces.value.push(
            ...result.edges.map((edge) => edge.node).filter((node) => !existing.has(node.id)),
        );
        hasNextPage.value =
            result.pageInfo.hasNextPage &&
            result.pageInfo.endCursor !== null &&
            result.pageInfo.endCursor !== endCursor.value;
        endCursor.value = result.pageInfo.endCursor;
    } catch (error) {
        if (currentRequest !== requestId) return;

        moreError.value = normalizeApiError(error).message;
    } finally {
        if (currentRequest === requestId) isLoadingMore.value = false;
    }
}

watch(autoLoad, (value) => {
    localStorage.setItem(AUTO_LOAD_STORAGE_KEY, String(value));
});

onMounted(loadWorkspaces);
</script>

<template>
    <div
        class="grid h-full grid-cols-1 grid-rows-[fit-content(42dvh)_minmax(0,1fr)] sm:grid-cols-[clamp(14rem,23vw,21rem)_minmax(0,1fr)] sm:grid-rows-[minmax(0,1fr)]"
    >
        <aside
            class="border-line bg-panel flex max-h-[42dvh] min-h-0 flex-col border-b px-4 pt-4 pb-2 sm:max-h-none sm:border-r sm:border-b-0"
            aria-labelledby="workspaces-heading"
        >
            <header class="terminal-actions flex items-center justify-between gap-2">
                <h2 id="workspaces-heading" class="text-sm font-semibold">workspaces</h2>
                <button
                    v-if="!creating && !isLoading && !errorMessage"
                    ref="newButton"
                    aria-label="New workspace"
                    @click="
                        creating = true;
                        restoring = false;
                        failure = '';
                    "
                >
                    new
                </button>
            </header>
            <div
                ref="scrollContainerRef"
                class="min-h-0 flex-1 scrollbar-none overflow-y-auto overscroll-contain [&::-webkit-scrollbar]:hidden"
            >
                <p v-if="isLoading" class="text-muted py-4" role="status">loading...</p>
                <div v-else-if="errorMessage" class="terminal-actions space-y-2 py-4">
                    <FormFieldError :message="errorMessage" />
                    <button @click="loadWorkspaces">retry</button>
                </div>
                <template v-else>
                    <nav aria-label="Workspaces">
                        <ul class="mt-3 space-y-1" @keydown.down="navigate" @keydown.up="navigate">
                            <li v-for="workspace in workspaces" :key="workspace.id">
                                <button
                                    :disabled="creating || processing"
                                    data-workspace
                                    class="hover:bg-raised aria-[current=true]:bg-raised aria-[current=true]:text-accent flex w-full items-center gap-2 rounded-none border-0 bg-transparent px-2 py-2 text-left"
                                    :aria-current="selectedId === workspace.id ? 'true' : undefined"
                                    @click="selectWorkspace(workspace.id)"
                                >
                                    <span class="w-[1ch] shrink-0" aria-hidden="true">{{
                                        selectedId === workspace.id ? '▸' : ' '
                                    }}</span>
                                    <span class="truncate">{{ workspace.name }}</span>
                                </button>
                                <ul
                                    v-if="selectedId === workspace.id"
                                    class="border-line my-1 ml-4 border-l pl-2"
                                >
                                    <li v-for="item in sections" :key="item">
                                        <button
                                            class="hover:bg-raised aria-[current=page]:bg-raised aria-[current=page]:text-accent flex w-full items-center gap-2 rounded-none border-0 bg-transparent px-2 py-2 text-left aria-[current=page]:border-y-4 aria-[current=page]:border-transparent aria-[current=page]:bg-clip-padding aria-[current=page]:py-1"
                                            :aria-current="section === item ? 'page' : undefined"
                                            @click="section = item"
                                        >
                                            {{ item }}
                                        </button>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </nav>
                    <div v-if="hasNextPage" class="terminal-actions">
                        <div ref="sentinelRef" class="h-px"></div>
                        <FormFieldError v-if="moreError" :message="moreError" />
                        <button :disabled="isLoadingMore" @click="loadMoreWorkspaces">
                            {{ isLoadingMore ? 'loading...' : 'load more' }}
                        </button>
                    </div>
                </template>
            </div>
            <div
                class="terminal-actions border-line mt-2 shrink-0 border-t border-dashed pt-1 text-center text-sm"
            >
                <label class="text-muted flex items-center justify-center gap-2 text-xs">
                    <input v-model="autoLoad" type="checkbox" /> auto-load workspaces
                </label>
                <button
                    :disabled="processing"
                    @click="
                        restoring = true;
                        creating = false;
                        failure = '';
                    "
                >
                    restore by id
                </button>
            </div>
        </aside>
        <section
            class="terminal-actions min-h-0 min-w-0 scrollbar-none overflow-y-auto overscroll-contain p-4 sm:p-8 [&::-webkit-scrollbar]:hidden"
            aria-label="Workspace content"
        >
            <form v-if="restoring" class="max-w-xl space-y-3" @submit.prevent="restore">
                <label class="block"
                    >deleted workspace id<input v-model.trim="restoreId" required
                /></label>
                <button :disabled="processing">restore</button>
                <button
                    type="button"
                    :disabled="processing"
                    @click="
                        restoring = false;
                        failure = '';
                    "
                >
                    cancel
                </button>
            </form>
            <CreateWorkspaceForm v-else-if="creating" @created="created" @cancel="closeCreate" />
            <template v-else-if="selected">
                <WorkspaceCard
                    :key="selected.id"
                    :workspace="selected"
                    @updated="updateWorkspace"
                />
                <div v-if="section === 'settings'" class="space-y-4 py-4">
                    <h2>workspace settings</h2>
                    <p class="text-muted break-all">id {{ selected.id }}</p>
                    <button :disabled="processing" @click="exitWorkspace(false)">
                        leave workspace
                    </button>
                    <button :disabled="processing" @click="exitWorkspace(true)">
                        delete workspace
                    </button>
                </div>
                <WorkspaceResources
                    v-else
                    :workspace-id="selected.id"
                    :key="selected.id + section"
                    :owner-id="selected.id"
                    :section="section"
                />
            </template>
            <p v-else-if="!isLoading && !errorMessage" class="text-muted">No workspaces yet</p>
            <FormFieldError v-if="failure" :message="failure" />
            <p class="text-success mt-2 text-sm" role="status">{{ status }}</p>
        </section>
    </div>
</template>
