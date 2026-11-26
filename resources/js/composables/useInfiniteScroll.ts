import { ref, watch, onUnmounted, type Ref } from 'vue';

interface UseInfiniteScrollOptions {
    enabled?: Ref<boolean>;
    root?: Ref<HTMLElement | null>;
    rootMargin?: string;
}

export function useInfiniteScroll(
    target: Ref<HTMLElement | null>,
    callback: () => void | Promise<void>,
    options: UseInfiniteScrollOptions = {},
) {
    const { enabled = ref(true), root = ref(null), rootMargin = '80px' } = options;
    let observer: IntersectionObserver | null = null;

    const cleanup = () => {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
    };

    const setup = () => {
        cleanup();

        if (!enabled.value || !target.value || typeof IntersectionObserver === 'undefined') return;

        observer = new IntersectionObserver(
            (entries, source) => {
                if (source === observer && enabled.value && entries[0]?.isIntersecting) {
                    void callback();
                }
            },
            {
                root: root.value,
                rootMargin,
            },
        );

        observer.observe(target.value);
    };

    watch([target, root, enabled], setup, { immediate: true, flush: 'post' });

    onUnmounted(cleanup);

    return { stop: cleanup, start: setup };
}
