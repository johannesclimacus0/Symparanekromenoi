import { onScopeDispose, ref } from 'vue';
import { normalizeApiError } from '@/http/http';

export function useWorkspaceRequest() {
    const processing = ref(false);
    const failure = ref('');
    const controller = new AbortController();
    onScopeDispose(() => controller.abort());

    async function run(action: (signal: AbortSignal) => Promise<void>) {
        if (processing.value) return;
        processing.value = true;
        failure.value = '';
        try {
            await action(controller.signal);
        } catch (error) {
            if (!controller.signal.aborted) {
                const normalized = normalizeApiError(error);
                failure.value =
                    Object.values(normalized.errors).flat().join(' ') || normalized.message;
            }
        } finally {
            processing.value = false;
        }
    }
    return { processing, failure, run };
}
