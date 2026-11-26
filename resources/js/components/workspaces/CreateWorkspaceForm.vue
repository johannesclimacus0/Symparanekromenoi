<script setup lang="ts">
import { onMounted, ref } from 'vue';
import FormFieldError from '@/components/shared/FormFieldError.vue';
import { normalizeApiError } from '@/http/http';
import { workspaceService } from '@/services/workspaces/WorkspaceService';

const name = ref('');
const isSaving = ref(false);
const errorMessage = ref<string | null>(null);
const field = ref<HTMLInputElement>();
onMounted(() => field.value?.focus());
const emit = defineEmits<{ created: [workspace: { id: string; name: string }]; cancel: [] }>();

async function createWorkspace(): Promise<void> {
    if (isSaving.value) return;

    errorMessage.value = null;

    const trimmedName = name.value.trim();

    if (!trimmedName) {
        errorMessage.value = 'Enter a name for your workspace';
        return;
    }

    if ([...trimmedName].length > 100) {
        errorMessage.value = 'Name must not be over 100 chars';
        return;
    }

    isSaving.value = true;

    try {
        const workspace = await workspaceService.createWorkspace({ name: trimmedName });
        name.value = '';
        emit('created', workspace);
    } catch (error) {
        errorMessage.value = normalizeApiError(error).message;
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <section class="border-line bg-panel border-b p-4">
        <h2 class="text-lg font-semibold">Create workspace</h2>
        <form
            class="mt-4 space-y-3"
            @keydown.esc="!isSaving && emit('cancel')"
            @submit.prevent="createWorkspace"
        >
            <label for="workspace-name" class="block text-sm font-medium">Workspace name</label>
            <input
                id="workspace-name"
                v-model="name"
                type="text"
                class="rounded"
                :disabled="isSaving"
                ref="field"
            />

            <FormFieldError v-if="errorMessage" :message="errorMessage" />

            <button type="submit" class="font-semibold" :disabled="isSaving">
                {{ isSaving ? 'Creating...' : 'Create workspace' }}
            </button>
            <button type="button" :disabled="isSaving" @click="emit('cancel')">Cancel</button>
        </form>
    </section>
</template>
