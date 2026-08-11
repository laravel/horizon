import { Link, usePage } from "@inertiajs/react";
import { RefreshCwIcon } from "lucide-react";

import {
    BatchesNavigationIcon,
    CompletedJobsNavigationIcon,
    DashboardNavigationIcon,
    FailedJobsNavigationIcon,
    MetricsNavigationIcon,
    MonitoringNavigationIcon,
    PendingJobsNavigationIcon,
    SilencedJobsNavigationIcon,
} from "@/components/navigation-icons";
import { HorizonStatus } from "@/components/shell/horizon-status";
import { ThemeToggle } from "@/components/shell/theme-toggle";
import { Switch } from "@/components/ui/switch";
import { dashboard } from "@/generated/routes/horizon";
import { page as batchesPage } from "@/generated/routes/horizon/batches";
import { page as completedJobsPage } from "@/generated/routes/horizon/completed-jobs";
import { page as failedJobsPage } from "@/generated/routes/horizon/failed-jobs";
import { page as metricsPage } from "@/generated/routes/horizon/metrics";
import { page as monitoringPage } from "@/generated/routes/horizon/monitoring";
import { page as pendingJobsPage } from "@/generated/routes/horizon/pending-jobs";
import { page as silencedJobsPage } from "@/generated/routes/horizon/silenced-jobs";
import { formatCount } from "@/lib/format-count";
import { resolveHorizonRoute } from "@/lib/horizon-route";
import { cn } from "@/lib/utils";
import type { HorizonStatus as HorizonStatusValue, NavigationCounts } from "@/types/dashboard";

type NavigationEntry = {
    label: string;
    route: () => { url: string };
    activePaths: string[];
    icon: typeof DashboardNavigationIcon;
    count: keyof NavigationCounts | null;
};

const navigation: NavigationEntry[] = [
    {
        label: "Dashboard",
        route: () => dashboard(),
        activePaths: ["/", "/dashboard"],
        icon: DashboardNavigationIcon,
        count: null,
    },
    {
        label: "Monitoring",
        route: () => monitoringPage(),
        activePaths: ["/monitoring"],
        icon: MonitoringNavigationIcon,
        count: "monitoring",
    },
    {
        label: "Metrics",
        route: () => metricsPage("jobs"),
        activePaths: ["/metrics"],
        icon: MetricsNavigationIcon,
        count: "metrics",
    },
    {
        label: "Batches",
        route: () => batchesPage(),
        activePaths: ["/batches"],
        icon: BatchesNavigationIcon,
        count: "batches",
    },
    {
        label: "Pending Jobs",
        route: () => pendingJobsPage(),
        activePaths: ["/jobs/pending"],
        icon: PendingJobsNavigationIcon,
        count: "pending",
    },
    {
        label: "Completed Jobs",
        route: () => completedJobsPage(),
        activePaths: ["/jobs/completed"],
        icon: CompletedJobsNavigationIcon,
        count: "completed",
    },
    {
        label: "Silenced Jobs",
        route: () => silencedJobsPage(),
        activePaths: ["/jobs/silenced"],
        icon: SilencedJobsNavigationIcon,
        count: "silenced",
    },
    {
        label: "Failed Jobs",
        route: () => failedJobsPage(),
        activePaths: ["/jobs/failed"],
        icon: FailedJobsNavigationIcon,
        count: "failed",
    },
];

export function AppSidebar({
    baseUrl,
    status,
    processing,
    autoLoad,
    refreshing,
    onAutoLoadChange,
    navigationCounts,
}: {
    baseUrl: string;
    status: HorizonStatusValue;
    processing: boolean;
    autoLoad: boolean;
    refreshing: boolean;
    onAutoLoadChange: (enabled: boolean) => void;
    navigationCounts: NavigationCounts;
}) {
    const { url: pageUrl } = usePage();
    const dashboardUrl = resolveHorizonRoute(dashboard(), baseUrl).url;
    const pagePathname = new URL(pageUrl, window.location.origin).pathname;
    const basePrefix = baseUrl === "/" ? "" : baseUrl.replace(/\/+$/, "");
    const relativePath = (() => {
        const stripped =
            basePrefix !== "" && pagePathname.startsWith(basePrefix)
                ? pagePathname.slice(basePrefix.length)
                : pagePathname;

        if (stripped === "" || stripped === "/") {
            return "/";
        }

        return stripped.startsWith("/") ? stripped : `/${stripped}`;
    })();

    return (
        <aside
            className="fixed inset-y-0 left-(--horizon-sidebar-offset) z-50 flex w-64 flex-col bg-sidebar px-3"
            aria-label="Horizon sidebar"
        >
            <header className="flex h-[4.8rem] items-center justify-between gap-3 pt-[5px] pr-[7px] pl-2">
                <Link
                    href={dashboardUrl}
                    prefetch={["mount", "hover"]}
                    cacheFor={["5s", "5m"]}
                    className="flex min-w-0 items-center gap-2 text-sm tracking-[-0.01em] outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring"
                    aria-label="Laravel Horizon"
                >
                    <svg
                        viewBox="0 0 30 30"
                        className="size-5 shrink-0 fill-[#7746ec]"
                        aria-hidden="true"
                    >
                        <path d="M5.26176342 26.4094389C2.04147988 23.6582233 0 19.5675182 0 15c0-4.1421356 1.67893219-7.89213562 4.39339828-10.60660172C7.10786438 1.67893219 10.8578644 0 15 0c8.2842712 0 15 6.71572875 15 15 0 8.2842712-6.7157288 15-15 15-3.716753 0-7.11777662-1.3517984-9.73823658-3.5905611zM4.03811305 15.9222506C5.70084247 14.4569342 6.87195416 12.5 10 12.5c5 0 5 5 10 5 3.1280454 0 4.2991572-1.9569336 5.961887-3.4222502C25.4934253 8.43417206 20.7645408 4 15 4 8.92486775 4 4 8.92486775 4 15c0 .3105915.01287248.6181765.03811305.9222506z" />
                    </svg>
                    <span>
                        <strong className="font-bold">Laravel</strong> Horizon
                    </span>
                </Link>
                <HorizonStatus status={status} processing={processing} />
            </header>

            <nav className="flex-1" aria-label="Horizon">
                <p className="flex h-8 items-center px-2 text-xs font-medium text-muted-foreground">
                    Pages
                </p>
                <ul className="flex flex-col gap-1.5">
                    {navigation.map((item) => {
                        const Icon = item.icon;
                        const href = resolveHorizonRoute(item.route(), baseUrl).url;
                        const count = navigationCount(item.count, navigationCounts);
                        const active = item.activePaths.some(
                            (path) =>
                                relativePath === path ||
                                (path !== "/" && relativePath.startsWith(`${path}/`)),
                        );
                        const className = cn(
                            "flex h-9 items-center gap-2.5 rounded-lg border-t border-transparent px-2.5 text-sm font-normal text-sidebar-foreground transition-colors outline-none hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 focus-visible:ring-sidebar-ring",
                            active &&
                                "border-nav-top bg-sidebar-accent font-medium text-sidebar-accent-foreground",
                        );

                        return (
                            <li key={item.label} className="relative">
                                <Link
                                    href={href}
                                    className={className}
                                    aria-current={active ? "page" : undefined}
                                    prefetch
                                >
                                    <Icon
                                        className={cn(
                                            "size-3.5",
                                            active ? "text-primary" : "text-muted-foreground",
                                        )}
                                        strokeWidth={1.75}
                                        aria-hidden="true"
                                    />
                                    <span>{item.label}</span>
                                    {count !== null ? (
                                        <NavigationCount value={count} label={item.label} />
                                    ) : null}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            <footer className="pb-3">
                <p className="flex h-8 items-center px-2 text-xs font-medium text-muted-foreground">
                    Settings
                </p>
                <div className="flex flex-col gap-1.5">
                    <ThemeToggle />
                    <label className="relative flex h-9 w-full cursor-pointer items-center gap-2.5 rounded-lg px-2.5 pr-14 text-sm text-sidebar-foreground transition-colors outline-none hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-within:ring-2 focus-within:ring-sidebar-ring">
                        <RefreshCwIcon
                            className={cn(
                                "size-4 text-muted-foreground",
                                refreshing && autoLoad && "animate-spin motion-reduce:animate-none",
                            )}
                            style={{ rotate: "0turn" }}
                            aria-hidden="true"
                        />
                        <span>Auto refresh</span>
                        <Switch
                            aria-busy={refreshing}
                            checked={autoLoad}
                            className="absolute top-1/2 right-2.5 -translate-y-1/2"
                            onCheckedChange={onAutoLoadChange}
                        />
                    </label>
                </div>
            </footer>
        </aside>
    );
}

function NavigationCount({ value, label }: { value: number; label: string }) {
    return (
        <span
            className="pointer-events-none absolute top-1/2 right-1 flex h-5 min-w-5 -translate-x-[2px] -translate-y-1/2 items-center justify-center rounded-md px-1 text-xs font-normal tabular-nums text-sidebar-muted-foreground"
            aria-label={`${value} ${label.toLowerCase()}`}
        >
            {formatCount(value)}
        </span>
    );
}

function navigationCount(
    key: keyof NavigationCounts | null,
    counts: NavigationCounts,
): number | null {
    if (key === null) {
        return null;
    }

    const value = counts[key];

    // Batches may be null when only a lower bound is known.
    return typeof value === "number" ? value : null;
}
