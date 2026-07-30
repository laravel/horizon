import { usePage } from "@inertiajs/react";
import { TriangleAlertIcon, WifiOffIcon } from "lucide-react";
import {
    createContext,
    type CSSProperties,
    type ReactNode,
    useCallback,
    useContext,
    useMemo,
    useState,
} from "react";

import { AppSidebar } from "@/components/app-sidebar";
import { useHorizonRefresh } from "@/hooks/use-horizon-refresh";
import type { DashboardStats, HorizonPageProps } from "@/types/dashboard";

const autoLoadStorageKey = "horizonAutoLoadsNewEntries";
const AutoLoadContext = createContext({ autoLoad: false });

function storedAutoLoadPreference() {
    try {
        return localStorage.getItem(autoLoadStorageKey) === "1";
    } catch {
        return false;
    }
}

export function useAutoLoadPreference() {
    return useContext(AutoLoadContext);
}

export function HorizonLayout({ children }: { children: ReactNode }) {
    const { horizon, stats, navigationCounts } = usePage<
        HorizonPageProps & { stats?: DashboardStats }
    >().props;
    const [autoLoad, setAutoLoad] = useState(storedAutoLoadPreference);
    const autoLoadValue = useMemo(() => ({ autoLoad }), [autoLoad]);
    const { refreshing, connectionFailed } = useHorizonRefresh(horizon.pollInterval, autoLoad);
    const updateAutoLoad = useCallback((enabled: boolean) => {
        setAutoLoad(enabled);

        try {
            localStorage.setItem(autoLoadStorageKey, enabled ? "1" : "0");
        } catch {
            // The current session can still use the preference.
        }
    }, []);

    return (
        <AutoLoadContext.Provider value={autoLoadValue}>
            <div
                className="relative mx-auto flex min-h-svh min-w-[1140px] max-w-[1400px]"
                style={
                    {
                        "--horizon-sidebar-offset": "max(0px, calc((100vw - 1400px) / 2))",
                    } as CSSProperties
                }
            >
                <AppSidebar
                    baseUrl={horizon.baseUrl}
                    status={stats?.status ?? horizon.status}
                    processing={horizon.processing}
                    autoLoad={autoLoad}
                    refreshing={refreshing}
                    onAutoLoadChange={updateAutoLoad}
                    navigationCounts={navigationCounts}
                />
                <main className="ml-64 flex min-h-svh min-w-0 flex-1 flex-col gap-3.5 overflow-clip pt-3.5 pr-3.5 pb-3.5 pl-6">
                    {horizon.maintenanceMode ? (
                        <div
                            className="flex items-start gap-3 rounded-xl border border-status-paused-foreground/30 bg-status-paused px-4 py-3 text-sm text-status-paused-foreground"
                            role="alert"
                        >
                            <TriangleAlertIcon
                                className="mt-0.5 size-4 shrink-0"
                                aria-hidden="true"
                            />
                            <div>
                                <p className="font-medium">Application maintenance mode</p>
                                <p className="mt-0.5 opacity-90">
                                    Queued jobs may not be processed unless the worker is using the
                                    force flag.
                                </p>
                            </div>
                        </div>
                    ) : null}
                    {connectionFailed ? (
                        <div
                            className="flex items-start gap-3 rounded-xl border border-status-failed-foreground/30 bg-status-failed px-4 py-3 text-sm text-status-failed-foreground"
                            role="alert"
                        >
                            <WifiOffIcon className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                            <div>
                                <p className="font-medium">Connection interrupted</p>
                                <p className="mt-0.5 opacity-90">
                                    Horizon could not refresh. Displayed data may be out of date.
                                </p>
                            </div>
                        </div>
                    ) : null}
                    {children}
                </main>
            </div>
        </AutoLoadContext.Provider>
    );
}
