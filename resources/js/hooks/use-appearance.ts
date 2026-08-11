import { useSyncExternalStore } from "react";

export type ResolvedAppearance = "light" | "dark";
export type Appearance = ResolvedAppearance | "system";

export type UseAppearanceReturn = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
    readonly updateAppearance: (mode: Appearance) => void;
};

type AppearanceSnapshot = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
};

const storageKey = "horizonColorScheme";
const darkModeQuery = "(prefers-color-scheme: dark)";

const listeners = new Set<() => void>();
const serverSnapshot: AppearanceSnapshot = {
    appearance: "system",
    resolvedAppearance: "light",
};

let snapshot: AppearanceSnapshot = serverSnapshot;
let systemListenerInstalled = false;

function prefersDark(): boolean {
    if (typeof window === "undefined") {
        return false;
    }

    return window.matchMedia(darkModeQuery).matches;
}

function isDarkMode(appearance: Appearance): boolean {
    return appearance === "dark" || (appearance === "system" && prefersDark());
}

function resolvedFor(appearance: Appearance): ResolvedAppearance {
    return isDarkMode(appearance) ? "dark" : "light";
}

function applyTheme(appearance: Appearance): void {
    if (typeof document === "undefined") {
        return;
    }

    const isDark = isDarkMode(appearance);

    document.documentElement.classList.toggle("dark", isDark);
    document.documentElement.style.colorScheme = isDark ? "dark" : "light";
}

function getStoredAppearance(): Appearance {
    if (typeof window === "undefined") {
        return "system";
    }

    try {
        const value = window.localStorage.getItem(storageKey);

        return value === "dark" || value === "light" || value === "system" ? value : "system";
    } catch {
        return "system";
    }
}

function persistAppearance(appearance: Appearance): void {
    if (typeof window === "undefined") {
        return;
    }

    try {
        window.localStorage.setItem(storageKey, appearance);
    } catch {
        // Session can still use the selected appearance without storage.
    }
}

function commitSnapshot(appearance: Appearance): void {
    const next: AppearanceSnapshot = {
        appearance,
        resolvedAppearance: resolvedFor(appearance),
    };

    if (
        next.appearance === snapshot.appearance &&
        next.resolvedAppearance === snapshot.resolvedAppearance
    ) {
        return;
    }

    snapshot = next;
}

function notify(): void {
    listeners.forEach((listener) => listener());
}

function subscribe(callback: () => void): () => void {
    listeners.add(callback);

    return () => {
        listeners.delete(callback);
    };
}

function handleSystemThemeChange(): void {
    applyTheme(snapshot.appearance);
    commitSnapshot(snapshot.appearance);
    notify();
}

export function initializeTheme(): void {
    if (typeof window === "undefined") {
        return;
    }

    const appearance = getStoredAppearance();

    persistAppearance(appearance);
    commitSnapshot(appearance);
    applyTheme(appearance);

    if (!systemListenerInstalled) {
        window.matchMedia(darkModeQuery).addEventListener("change", handleSystemThemeChange);
        systemListenerInstalled = true;
    }
}

export function updateAppearance(mode: Appearance): void {
    persistAppearance(mode);
    commitSnapshot(mode);
    applyTheme(mode);
    notify();
}

export function useAppearance(): UseAppearanceReturn {
    const { appearance, resolvedAppearance } = useSyncExternalStore(
        subscribe,
        () => snapshot,
        () => serverSnapshot,
    );

    return { appearance, resolvedAppearance, updateAppearance } as const;
}
