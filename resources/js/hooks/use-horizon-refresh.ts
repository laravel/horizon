import { router, usePage } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";

import {
    cancelActiveListPoll,
    INFINITE_SCROLL_MERGE_INTENT_HEADER,
    isListBrowsingHistory,
    releaseListPollCancel,
    setActiveListPollCancel,
    syncListBrowsingHistory,
} from "@/lib/list-refresh";

const hiddenInterval = 60 * 60 * 1000;
const sharedProps = ["horizon", "navigationCounts"];

const pageProps: Record<string, string[]> = {
    dashboard: ["stats", "workload", "masters"],
    monitoring: ["tags"],
    metrics: ["metrics"],
    "batches/show": ["batch", "failedJobs"],
    "jobs/show": ["job"],
};

const jobListComponents = new Set([
    "jobs/pending",
    "jobs/completed",
    "jobs/failed",
    "jobs/silenced",
    "monitoring/tag-jobs",
]);

type InfiniteList = {
    prop: "jobs" | "batches";
    cursor: "starting_at" | "before_id";
    /** Always refreshed on job lists (new-entry indicator); empty for batches. */
    additionalProps: string[];
};

type EntryReloadMode = "replace" | "prepend";

function infiniteList(component: string): InfiniteList | null {
    if (component === "batches") {
        return {
            prop: "batches",
            cursor: "before_id",
            additionalProps: [],
        };
    }

    if (jobListComponents.has(component)) {
        return {
            prop: "jobs",
            cursor: "starting_at",
            additionalProps: ["listRevision", "total"],
        };
    }

    return null;
}

/**
 * Props for a single consolidated poll.
 * - Job lists: always horizon + navigationCounts + listRevision + total;
 *   when autoLoad, also jobs (replace/prepend).
 * - Batches: shared only when autoLoad off; +batches when on (unchanged).
 * - Other pages: shared + page-specific props.
 */
function refreshProps(component: string, autoLoad: boolean, list: InfiniteList | null): string[] {
    if (list?.prop === "jobs") {
        return autoLoad
            ? [...sharedProps, list.prop, ...list.additionalProps]
            : [...sharedProps, ...list.additionalProps];
    }

    if (list) {
        return autoLoad ? [...sharedProps, list.prop, ...list.additionalProps] : sharedProps;
    }

    return [...sharedProps, ...(pageProps[component] ?? [])];
}

function listVisitOptions(
    list: InfiniteList,
    component: string,
    entryReloadMode?: EntryReloadMode,
) {
    const includeEntries = entryReloadMode !== undefined;

    return {
        data: { [list.cursor]: undefined },
        only: refreshProps(component, includeEntries, list),
        reset: entryReloadMode === "replace" ? [list.prop] : [],
        headers:
            entryReloadMode === "prepend"
                ? { [INFINITE_SCROLL_MERGE_INTENT_HEADER]: "prepend" }
                : ({} as Record<string, string>),
        preserveUrl: true,
        showProgress: false,
    };
}

export function useHorizonRefresh(interval: number, autoLoad: boolean) {
    const { component, props, url } = usePage();
    const [visibility, setVisibility] = useState(document.visibilityState);
    const [refreshing, setRefreshing] = useState(false);
    const [connectionFailed, setConnectionFailed] = useState(false);
    const lastExecutionAt = useRef(Date.now());
    const wasAutoLoadRef = useRef(autoLoad);
    const list = infiniteList(component);
    const listItems = list
        ? (props[list.prop] as { data?: unknown[] } | undefined)?.data
        : undefined;
    const loadedItemCount = listItems?.length ?? 0;
    const scopeUrl = new URL(url, window.location.origin);

    if (list) {
        scopeUrl.searchParams.delete(list.cursor);
    }

    const listScope = `${component}:${scopeUrl.pathname}${scopeUrl.search}`;

    if (list) {
        syncListBrowsingHistory(listScope, loadedItemCount);
    }

    useEffect(() => {
        const handleVisibility = () => setVisibility(document.visibilityState);

        document.addEventListener("visibilitychange", handleVisibility);

        return () => document.removeEventListener("visibilitychange", handleVisibility);
    }, []);

    useEffect(() => {
        const visible = visibility === "visible";
        const autoLoadJustEnabled = autoLoad && !wasAutoLoadRef.current;

        wasAutoLoadRef.current = autoLoad;

        const withLifecycle = <T extends Record<string, unknown>>(options: T) => {
            let cancelRequest: (() => void) | null = null;
            let cancelled = false;

            return {
                ...options,
                onStart: () => {
                    cancelled = false;
                    setRefreshing(true);
                },
                onCancelToken: (token: { cancel: () => void }) => {
                    cancelRequest = token.cancel;
                    setActiveListPollCancel(cancelRequest);
                },
                onCancel: () => {
                    // Superseded/cancelled polls must not mark the connection failed.
                    cancelled = true;
                },
                onSuccess: () => {
                    setConnectionFailed(false);
                },
                onFinish: () => {
                    releaseListPollCancel(cancelRequest);
                    lastExecutionAt.current = Date.now();
                    setRefreshing(false);
                },
                onHttpException: () => {
                    if (!cancelled) {
                        setConnectionFailed(true);
                    }

                    return false as const;
                },
                onNetworkError: () => {
                    if (!cancelled) {
                        setConnectionFailed(true);
                    }

                    return false as const;
                },
            };
        };

        const buildRequestOptions = () => {
            if (list === null) {
                return withLifecycle({
                    data: {},
                    only: refreshProps(component, autoLoad, null),
                    reset: [] as string[],
                    headers: {} as Record<string, string>,
                    preserveUrl: true,
                    showProgress: false,
                });
            }

            const entryReloadMode: EntryReloadMode | undefined = autoLoad
                ? isListBrowsingHistory()
                    ? "prepend"
                    : "replace"
                : undefined;

            return withLifecycle(listVisitOptions(list, component, entryReloadMode));
        };

        // Immediate jobs refresh when auto-load is turned on (prior useAutoLoad behavior).
        // Batches keep interval-only refresh to preserve existing behavior exactly.
        if (visible && autoLoadJustEnabled && list?.prop === "jobs") {
            router.reload(
                withLifecycle(
                    listVisitOptions(
                        list,
                        component,
                        isListBrowsingHistory() ? "prepend" : "replace",
                    ),
                ),
            );
        } else if (visible && Date.now() - lastExecutionAt.current >= interval) {
            router.reload(buildRequestOptions());
        }

        const poll = router.poll(visible ? interval : hiddenInterval, () => buildRequestOptions(), {
            autoStart: true,
            keepAlive: true,
            mode: "cancel",
        });

        return () => {
            cancelActiveListPoll();
            poll.destroy();
        };
    }, [autoLoad, component, interval, listScope, visibility]);

    return { refreshing, connectionFailed };
}
