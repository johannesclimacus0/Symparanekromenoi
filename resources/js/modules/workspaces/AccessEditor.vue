<script setup lang="ts">
import { ref } from 'vue';
import { graphql } from '@/graphql/client';
import * as api from '@/graphql/generated/graphql';
import { useWorkspaceRequest } from './useWorkspaceRequest';
const props = defineProps<{
    workspaceId: string;
    member?: { id: string; name: string; detail?: string | null };
}>();
const emit = defineEmits<{ saved: []; cancel: [] }>();
const roles = ['VIEWER', 'DEVELOPER', 'ADMIN'] as const;
const role = ref<api.WorkspaceMemberRole>(
    roles.find((r) => r.toLowerCase() === props.member?.detail) ?? 'VIEWER',
);
const email = ref('');
const { processing, failure, run } = useWorkspaceRequest();
function save() {
    return run(async (signal) => {
        const scope = { id: props.workspaceId, role: role.value };
        if (props.member)
            await graphql(
                api.ChangeWorkspaceMemberRoleDocument,
                { input: { ...scope, memberId: props.member.id } },
                { csrf: true, signal },
            );
        else
            await graphql(
                api.InviteWorkspaceMemberDocument,
                { input: { ...scope, email: email.value.trim() } },
                { csrf: true, signal },
            );
        emit('saved');
    });
}
</script>
<template>
    <form class="max-w-xl" @submit.prevent="save">
        <fieldset class="border-line space-y-3 border-l pl-4" :disabled="processing">
            <legend>{{ member ? member.name : 'invite member' }}</legend>
            <label v-if="!member" class="block"
                >email<input v-model="email" required type="email"
            /></label>
            <label class="block"
                >role<select v-model="role">
                    <option v-for="item in roles" :key="item" :value="item">
                        {{ item.toLowerCase() }}
                    </option>
                </select></label
            >
            <p v-if="failure" class="text-danger" role="alert">{{ failure }}</p>
            <button>{{ member ? 'save role' : 'send invitation' }}</button>
            <button type="button" @click="emit('cancel')">cancel</button>
        </fieldset>
    </form>
</template>
