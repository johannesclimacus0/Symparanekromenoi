import { graphql } from '@/graphql/client';
import {
    CreateWorkspaceDocument,
    type CreateWorkspaceInput,
    MyWorkspaceDocument,
    UpdateWorkspaceDocument,
    UpdateWorkspaceInput,
} from '@/graphql/generated/graphql';

export class WorkspaceService {
    async createWorkspace(input: CreateWorkspaceInput) {
        const data = await graphql(CreateWorkspaceDocument, { input }, { csrf: true });

        return data.createWorkspace;
    }

    async getWorkspaces(first: number = 20, after?: string) {
        const data = await graphql(MyWorkspaceDocument, { first, after }, { csrf: true });

        return data.myWorkspaces;
    }

    async updateWorkspace(input: UpdateWorkspaceInput) {
        const data = await graphql(UpdateWorkspaceDocument, { input }, { csrf: true });

        return data.updateWorkspace;
    }
}

export const workspaceService = new WorkspaceService();
