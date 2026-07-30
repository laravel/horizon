import { router } from "@inertiajs/react";
import { useCallback, useEffect, useRef, useState } from "react";

import {
    cancelActiveListPoll,
    clearListBrowsingHistory,
    markListBrowsingHistory,
} from "@/lib/list-refresh";

const emptyProps: readonly string[] = [];

/**
 * List-state and InfiniteScroll coordination only.
 * Polling/timer ownership lives in useHorizonRefresh (layout).
 */
export function useAutoLoad({
    enabled,
    prop,
    cursor = "starting_at",
    listRevision,
    additionalProps = emptyProps,
    scope = "default",
}: {
    enabled: boolean;
    prop: string;
    cursor?: string;
    listRevision?: string;
    additionalProps?: readonly string[];
    scope?: string;
}) {
    const listRevisionRef = useRef(listRevision);
    const baselineRevisionRef = useRef(listRevision);
    const scopeRef = useRef(scope);
    const [hasNewEntries, setHasNewEntries] = useState(false);

    useEffect(() => {
        listRevisionRef.current = listRevision;

        if (scopeRef.current !== scope) {
            scopeRef.current = scope;
            baselineRevisionRef.current = listRevision;
            setHasNewEntries(false);

            return;
        }

        if (enabled) {
            baselineRevisionRef.current = listRevision;
            setHasNewEntries(false);

            return;
        }

        if (listRevision !== undefined && listRevision !== baselineRevisionRef.current) {
            setHasNewEntries(true);
        }
    }, [enabled, listRevision, scope]);

    const onBeforeNextPage = useCallback(() => {
        cancelActiveListPoll();
        markListBrowsingHistory();
    }, []);

    const loadNewEntries = useCallback(() => {
        cancelActiveListPoll();

        router.reload({
            data: { [cursor]: undefined },
            only: Array.from(
                new Set([
                    prop,
                    ...(listRevision === undefined ? [] : ["listRevision"]),
                    ...additionalProps,
                ]),
            ),
            reset: [prop],
            preserveUrl: true,
            showProgress: false,
            onSuccess: (page) => {
                const refreshedRevision = page.props.listRevision;
                const baselineRevision =
                    typeof refreshedRevision === "string"
                        ? refreshedRevision
                        : listRevisionRef.current;

                listRevisionRef.current = baselineRevision;
                baselineRevisionRef.current = baselineRevision;
                clearListBrowsingHistory();
                setHasNewEntries(false);
            },
        });
    }, [additionalProps, cursor, listRevision, prop]);

    return {
        hasNewEntries: !enabled && hasNewEntries,
        loadNewEntries,
        onBeforeNextPage,
    };
}
