import { Head } from "@inertiajs/react";
import { useId, type ReactNode } from "react";

import { DashboardOverview } from "@/components/dashboard/dashboard-overview";
import { SupervisorsTable } from "@/components/dashboard/supervisors-table";
import { WorkloadTable } from "@/components/dashboard/workload-table";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Statistic,
    StatisticGrid,
    StatisticLabel,
    StatisticValue,
} from "@/components/ui/statistic";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";
import { page as batchesPage } from "@/generated/routes/horizon/batches";
import { page as completedJobsPage } from "@/generated/routes/horizon/completed-jobs";
import { page as failedJobsPage } from "@/generated/routes/horizon/failed-jobs";
import { page as pendingJobsPage } from "@/generated/routes/horizon/pending-jobs";
import { formatDuration } from "@/lib/format-duration";
import { resolveHorizonRoute } from "@/lib/horizon-route";
import type { DashboardPageProps } from "@/types/dashboard";

const numberFormatter = new Intl.NumberFormat();

export default function Dashboard({
    horizon,
    stats,
    workload,
    masters,
    navigationCounts,
}: DashboardPageProps) {
    const [maxWaitDescriptor, maxWaitSeconds] = Object.entries(stats.wait)[0] ?? [];
    const base = horizon.baseUrl;
    const hasMaxWait =
        maxWaitDescriptor !== undefined && maxWaitSeconds !== undefined && maxWaitSeconds > 0;
    const maxWaitTooltip = hasMaxWait
        ? `Queue with the maximum wait time: ${maxWaitDescriptor}.`
        : undefined;
    const maxRuntimeTooltip =
        stats.queueWithMaxRuntime && stats.maxRuntime !== null
            ? `Average runtime for ${stats.queueWithMaxRuntime}: ${numberFormatter.format(stats.maxRuntime)}s.`
            : undefined;
    const maxThroughputTooltip =
        stats.queueWithMaxThroughput && stats.maxThroughput !== null
            ? `Throughput for ${stats.queueWithMaxThroughput} since the last metrics snapshot: ${numberFormatter.format(stats.maxThroughput)} jobs.`
            : undefined;

    return (
        <>
            <Head title="Horizon - Dashboard" />
            <div className="flex flex-col gap-[7px] min-[1140px]:gap-3.5" aria-live="polite">
                <DashboardOverview
                    stats={stats}
                    navigationCounts={navigationCounts}
                    links={{
                        pending: resolveHorizonRoute(pendingJobsPage(), base).url,
                        failed: resolveHorizonRoute(failedJobsPage(), base).url,
                        completed: resolveHorizonRoute(completedJobsPage(), base).url,
                        batches: resolveHorizonRoute(batchesPage(), base).url,
                    }}
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Current Workload</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {workload.length > 0 ? (
                            <StatisticGrid className="grid-cols-2 min-[1140px]:grid-cols-5">
                                <WorkloadStat label="Total Processes" value={stats.processes} />
                                <WorkloadStat
                                    label="Max Wait Time"
                                    value={hasMaxWait ? formatDuration(maxWaitSeconds) : "—"}
                                    valueTooltip={maxWaitTooltip}
                                />
                                <WorkloadStat
                                    label="Max Runtime"
                                    value={stats.queueWithMaxRuntime ?? "—"}
                                    valueTooltip={maxRuntimeTooltip}
                                />
                                <WorkloadStat
                                    label="Max Throughput"
                                    value={stats.queueWithMaxThroughput ?? "—"}
                                    valueTooltip={maxThroughputTooltip}
                                />
                                <WorkloadStat
                                    label="Hourly Pressure"
                                    value={
                                        stats.hourlyPressure === null
                                            ? "—"
                                            : numberFormatter.format(stats.hourlyPressure)
                                    }
                                    valueTooltip={
                                        stats.hourlyPressure === null
                                            ? undefined
                                            : "The number of jobs received by Horizon in the past hour."
                                    }
                                />
                            </StatisticGrid>
                        ) : null}
                        <WorkloadTable
                            workload={workload}
                            horizonBaseUrl={horizon.baseUrl}
                            queuePausing={horizon.capabilities.queuePausing}
                            queuePauseFor={horizon.capabilities.queuePauseFor}
                        />
                    </CardContent>
                </Card>
                <SupervisorsTable masters={masters} />
            </div>
        </>
    );
}

function WorkloadStat({
    label,
    value,
    valueTooltip,
}: {
    label: string;
    value: ReactNode;
    valueTooltip?: string;
}) {
    const tooltipId = useId();

    return (
        <Statistic>
            <StatisticLabel>{label}</StatisticLabel>
            <StatisticValue>
                {valueTooltip ? (
                    <Tooltip>
                        <TooltipTrigger
                            render={
                                <span
                                    tabIndex={0}
                                    aria-describedby={tooltipId}
                                    className="block min-w-0 max-w-full cursor-help truncate rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                />
                            }
                        >
                            {value}
                        </TooltipTrigger>
                        <TooltipContent id={tooltipId} role="tooltip" side="top">
                            {valueTooltip}
                        </TooltipContent>
                    </Tooltip>
                ) : (
                    value
                )}
            </StatisticValue>
        </Statistic>
    );
}
