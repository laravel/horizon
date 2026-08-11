import {
    ChartNoAxesCombinedIcon,
    CircleAlertIcon,
    CircleCheckIcon,
    CirclePauseIcon,
    createLucideIcon,
    LayoutDashboardIcon,
} from "lucide-react";

export const DashboardNavigationIcon = LayoutDashboardIcon;

export const MonitoringNavigationIcon = createLucideIcon("Search", [
    ["circle", { cx: "11", cy: "11", r: "8", key: "monitoring-circle" }],
    ["path", { d: "m21 21-4.3-4.3", key: "monitoring-handle" }],
]);

export const MetricsNavigationIcon = ChartNoAxesCombinedIcon;

export const BatchesNavigationIcon = createLucideIcon("Layers3", [
    [
        "path",
        {
            d: "M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z",
            key: "batches-top",
        },
    ],
    ["path", { d: "m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65", key: "batches-bottom" }],
    ["path", { d: "m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65", key: "batches-middle" }],
]);

export const PendingJobsNavigationIcon = CirclePauseIcon;
export const CompletedJobsNavigationIcon = CircleCheckIcon;

export const SilencedJobsNavigationIcon = createLucideIcon("BellOff", [
    ["path", { d: "M8.7 3A6 6 0 0 1 18 8a21.3 21.3 0 0 0 .6 5", key: "silenced-top" }],
    ["path", { d: "M17 17H3s3-2 3-9a4.67 4.67 0 0 1 .3-1.7", key: "silenced-body" }],
    ["path", { d: "M10.3 21a1.94 1.94 0 0 0 3.4 0", key: "silenced-clapper" }],
    ["path", { d: "m2 2 20 20", key: "silenced-slash" }],
]);

export const FailedJobsNavigationIcon = CircleAlertIcon;
