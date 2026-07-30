/**
 * Shared constants and coordination for infinite-list refresh behavior.
 * useHorizonRefresh owns the poll timer; useAutoLoad coordinates list state
 * (new-entry indicator, InfiniteScroll next-page race, manual load).
 */

export const FIRST_PAGE_SIZE = 50;

export const INFINITE_SCROLL_MERGE_INTENT_HEADER = "X-Inertia-Infinite-Scroll-Merge-Intent";

let listScope: string | null = null;
let browsingHistory = false;
let activePollCancel: (() => void) | null = null;

/**
 * Keep browsing-history (loaded past first page) coherent for the current list scope.
 * Returns whether history should be preserved (prepend vs replace).
 */
export function syncListBrowsingHistory(scope: string, loadedItemCount: number): boolean {
    if (listScope !== scope) {
        listScope = scope;
        browsingHistory = loadedItemCount > FIRST_PAGE_SIZE;
    } else if (loadedItemCount > FIRST_PAGE_SIZE) {
        browsingHistory = true;
    }

    return browsingHistory;
}

export function markListBrowsingHistory(): void {
    browsingHistory = true;
}

export function clearListBrowsingHistory(): void {
    browsingHistory = false;
}

export function isListBrowsingHistory(): boolean {
    return browsingHistory;
}

export function setActiveListPollCancel(cancel: (() => void) | null): void {
    activePollCancel = cancel;
}

/** Drop the cancel handle only if it still matches this request (avoids wiping a newer poll). */
export function releaseListPollCancel(cancel: (() => void) | null): void {
    if (cancel !== null && activePollCancel === cancel) {
        activePollCancel = null;
    }
}

export function cancelActiveListPoll(): void {
    const cancel = activePollCancel;

    activePollCancel = null;
    cancel?.();
}
