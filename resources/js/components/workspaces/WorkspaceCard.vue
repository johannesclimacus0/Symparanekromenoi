<script setup lang="ts">
import { nextTick, ref } from 'vue';
import { workspaceService } from '@/services/workspaces/WorkspaceService';
import FormFieldError from '@/components/shared/FormFieldError.vue';
import { normalizeApiError } from '@/http/http';

type Workspace = {
    id: string;
    name: string;
};

const isEditing = ref(false);
const isSaving = ref(false);
const errorMessage = ref<string | null>(null);

const props = defineProps<{ workspace: Workspace }>();
const emit = defineEmits<{ updated: [workspace: Workspace] }>();

const field = ref<HTMLHeadingElement>();
const renameButton = ref<HTMLButtonElement>();

async function closeEditing() {
    if (field.value) field.value.textContent = props.workspace.name;
    isEditing.value = false;
    errorMessage.value = null;
    await nextTick();
    renameButton.value?.focus();
}

async function onEditing() {
    errorMessage.value = null;
    isEditing.value = true;
    await nextTick();
    field.value?.focus();
    if (field.value) {
        const range = document.createRange();
        range.selectNodeContents(field.value);
        window.getSelection()?.removeAllRanges();
        window.getSelection()?.addRange(range);
    }
}

async function rename(): Promise<void> {
    if (isSaving.value) return;

    errorMessage.value = null;

    const trimmedName = field.value?.textContent?.trim() ?? '';

    if (!trimmedName) {
        errorMessage.value = 'Enter a workspace name';
        return;
    }

    if ([...trimmedName].length > 100) {
        errorMessage.value = 'Name must not be over 100 chars';
        return;
    }

    isSaving.value = true;

    try {
        const updatedWorkspace = await workspaceService.updateWorkspace({
            id: props.workspace.id,
            name: trimmedName,
        });

        emit('updated', updatedWorkspace);
        await nextTick();
        await closeEditing();
    } catch (error) {
        errorMessage.value = normalizeApiError(error).message;
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <article class="border-line border-b pb-3">
        <div class="flex items-start justify-between gap-2">
            <h1
                ref="field"
                class="min-w-0 flex-1 text-2xl break-all"
                :contenteditable="isEditing && !isSaving ? 'plaintext-only' : 'false'"
                :aria-label="isEditing ? 'Workspace name' : undefined"
                @keydown.enter.prevent="isEditing && rename()"
                @keydown.esc.prevent="isEditing && !isSaving && closeEditing()"
            >
                {{ workspace.name }}
            </h1>
            <button
                v-if="!isEditing"
                ref="renameButton"
                :aria-label="`Rename ${workspace.name}`"
                @click="onEditing"
            >
                rename
            </button>
            <template v-else>
                <button :disabled="isSaving" @click="rename">
                    {{ isSaving ? 'saving...' : 'save' }}
                </button>
                <button :disabled="isSaving" @click="closeEditing">cancel</button>
            </template>
        </div>
        <FormFieldError v-if="errorMessage" :message="errorMessage" />
    </article>
</template>
