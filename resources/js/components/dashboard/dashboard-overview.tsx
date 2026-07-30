import { Link } from "@inertiajs/react";
import { useId, type ReactNode } from "react";

import { ProgressRing } from "@/components/batches/progress-ring";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Statistic,
    StatisticDetail,
    StatisticDetails,
    StatisticGrid,
    StatisticLabel,
    StatisticLink,
    StatisticValue,
} from "@/components/ui/statistic";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";
import type { DashboardStats, NavigationCounts } from "@/types/dashboard";

const numberFormatter = new Intl.NumberFormat(undefined, {
    maximumFractionDigits: 2,
});

export function determinePeriod(minutes: number): string {
    if (!Number.isFinite(minutes) || minutes <= 0) {
        return "Hour";
    }

    if (minutes < 60) {
        return `${Math.round(minutes)} Minutes`;
    }

    if (minutes === 60) {
        return "Hour";
    }

    if (minutes < 1440) {
        const hours = Math.round(minutes / 60);

        return hours === 1 ? "Hour" : `${hours} Hours`;
    }

    if (minutes === 1440) {
        return "Day";
    }

    const days = Math.round(minutes / 1440);

    return days === 1 ? "Day" : `${days} Days`;
}

/**
 * Human-readable retention window for completed-job tooltips.
 * Prefer natural articles for singular units (a minute / an hour / a day).
 * Returns null when retention is disabled or unconfigured so the caller can
 * use truthful "not retained" copy instead of inventing a default period.
 */
function formatRetentionPeriod(minutes: number): string | null {
    if (!Number.isFinite(minutes) || minutes <= 0) {
        return null;
    }

    if (minutes < 60) {
        const rounded = Math.round(minutes);

        return rounded === 1 ? "a minute" : `${rounded} minutes`;
    }

    if (minutes < 1440) {
        const hours = Math.round(minutes / 60);

        return hours === 1 ? "an hour" : `${hours} hours`;
    }

    const days = Math.round(minutes / 1440);

    return days === 1 ? "a day" : `${days} days`;
}

function completedRetentionTooltip(minutes: number): string {
    const period = formatRetentionPeriod(minutes);

    return period === null
        ? "Completed jobs are not retained."
        : `Completed jobs are retained for ${period}.`;
}

function FocusableTooltip({
    content,
    children,
    className,
}: {
    content: string;
    children: ReactNode;
    className?: string;
}) {
    const tooltipId = useId();

    return (
        <Tooltip>
            <TooltipTrigger
                render={
                    <span
                        tabIndex={0}
                        aria-describedby={tooltipId}
                        className={
                            className ??
                            "cursor-help rounded-sm text-muted-foreground outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        }
                    />
                }
            >
                {children}
            </TooltipTrigger>
            <TooltipContent id={tooltipId} role="tooltip" side="top">
                {content}
            </TooltipContent>
        </Tooltip>
    );
}

function OverviewDetail({
    label,
    value,
    tooltip,
}: {
    label: string;
    value: number | string | null;
    tooltip?: string;
}) {
    const formatted =
        value === null ? "—" : typeof value === "number" ? numberFormatter.format(value) : value;

    return (
        <StatisticDetail>
            {tooltip ? (
                <FocusableTooltip content={tooltip}>{label}</FocusableTooltip>
            ) : (
                <span className="text-muted-foreground">{label}</span>
            )}
            <span className="font-normal text-muted-foreground">{formatted}</span>
        </StatisticDetail>
    );
}

function OverviewStatLink({
    href,
    title,
    value,
    valueTooltip,
    children,
}: {
    href: string;
    title: string;
    value: number;
    valueTooltip?: string;
    children: React.ReactNode;
}) {
    return (
        <StatisticLink href={href} prefetch>
            <StatisticLabel>{title}</StatisticLabel>
            <StatisticValue>
                {valueTooltip ? (
                    <FocusableTooltip
                        content={valueTooltip}
                        className="cursor-help rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        {numberFormatter.format(value)}
                    </FocusableTooltip>
                ) : (
                    numberFormatter.format(value)
                )}
            </StatisticValue>
            <StatisticDetails>{children}</StatisticDetails>
        </StatisticLink>
    );
}

export function DashboardOverview({
    stats,
    navigationCounts,
    links,
}: {
    stats: DashboardStats;
    navigationCounts: NavigationCounts;
    links: {
        pending: string;
        failed: string;
        completed: string;
        batches: string;
    };
}) {
    const pendingTotal = stats.pendingJobs ?? navigationCounts.pending;
    const failedPeriodLabel = `Past ${determinePeriod(stats.periods.failedJobs)}`;
    const completedRetentionMinutes = stats.periods.completedJobs ?? stats.periods.recentJobs;
    const completedValueTooltip = completedRetentionTooltip(completedRetentionMinutes);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Overview</CardTitle>
            </CardHeader>
            <CardContent>
                <StatisticGrid className="grid-cols-4">
                    <OverviewStatLink
                        href={links.pending}
                        title="Pending Jobs"
                        value={pendingTotal}
                    >
                        <OverviewDetail
                            label="Reserved"
                            value={stats.pendingReserved}
                            tooltip="Jobs currently being worked on."
                        />
                        <OverviewDetail
                            label="Ready"
                            value={stats.pendingReadyNow}
                            tooltip="Jobs waiting for an available worker."
                        />
                        <OverviewDetail
                            label="Delayed"
                            value={stats.pendingDelayed}
                            tooltip="Jobs scheduled to run later."
                        />
                    </OverviewStatLink>

                    <OverviewStatLink
                        href={links.failed}
                        title="Failed Jobs"
                        value={navigationCounts.failed}
                    >
                        <OverviewDetail label="Past hour" value={stats.failedJobsPastHour} />
                        <OverviewDetail label="Past 24 hours" value={stats.failedJobsPastDay} />
                        <OverviewDetail label={failedPeriodLabel} value={stats.failedJobs} />
                    </OverviewStatLink>

                    <OverviewStatLink
                        href={links.completed}
                        title="Completed Jobs"
                        value={navigationCounts.completed}
                        valueTooltip={completedValueTooltip}
                    >
                        <OverviewDetail label="Jobs per minute" value={stats.jobsPerMinute} />
                        <OverviewDetail label="Throughput" value={stats.throughput} />
                        <OverviewDetail label="Silenced Jobs" value={navigationCounts.silenced} />
                    </OverviewStatLink>

                    <Statistic className="outline-none transition-colors hover:bg-table-row-hover">
                        <Link
                            href={links.batches}
                            prefetch
                            className="block outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <StatisticLabel>Batches in progress</StatisticLabel>
                            <StatisticValue>
                                {stats.activeBatches === null
                                    ? "—"
                                    : numberFormatter.format(stats.activeBatches)}
                            </StatisticValue>
                        </Link>
                        <StatisticDetails>
                            {stats.batchPreviews.map((batch) => (
                                <div
                                    className="flex items-center justify-between gap-2 border-t border-dashed border-separator py-[7px] text-[13px]"
                                    key={batch.id}
                                >
                                    <span className="truncate text-muted-foreground">
                                        {batch.name}
                                    </span>
                                    <ProgressRing
                                        className="shrink-0 gap-1.5 [&_span]:text-[13px]"
                                        value={batch.progress}
                                    />
                                </div>
                            ))}
                        </StatisticDetails>
                    </Statistic>
                </StatisticGrid>
            </CardContent>
        </Card>
    );
}
