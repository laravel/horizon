import { Head, Link, router, usePage } from "@inertiajs/react";
import { useId } from "react";

import { BatchActions } from "@/components/batches/batch-actions";
import { ProgressRing } from "@/components/batches/progress-ring";
import { DetailList, DetailListItem } from "@/components/detail-list";
import { BatchesNavigationIcon } from "@/components/navigation-icons";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@/components/ui/empty";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";
import { show as jobPageShow } from "@/generated/routes/horizon/jobs/page";
import { isInteractiveTarget } from "@/lib/interactive-target";
import { resolveHorizonRoute } from "@/lib/horizon-route";
import type { HorizonPageProps } from "@/types/dashboard";

type BatchDetail = {
    id: string;
    name?: string | null;
    totalJobs: number;
    pendingJobs: number;
    processedJobs: number;
    failedJobs: number;
    failedJobAttempts: number;
    progress: number;
    createdAt: string;
    finishedAt?: string | null;
    cancelledAt?: string | null;
    options?: {
        queue?: string | null;
        connection?: string | null;
    } | null;
};

type FailedJob = {
    id: string;
    name?: string;
    attempts?: number;
    attemptsComplete?: boolean;
    reserved_at?: number | null;
    failed_at?: number | null;
    payload?: {
        displayName?: string | null;
    };
};

type BatchShowProps = {
    batch: BatchDetail | null;
    failedJobs: FailedJob[];
    failedJobsComplete?: boolean;
};

const dateFormatter = new Intl.DateTimeFormat("sv-SE", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false,
});

function formatDate(value?: string | null) {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? value : dateFormatter.format(date);
}

function timestamp(value?: number | null) {
    if (!value) {
        return "—";
    }

    return dateFormatter.format(value * 1000);
}

function jobBaseName(name?: string | null) {
    if (!name) {
        return "—";
    }

    const parts = name.split("\\");

    return parts[parts.length - 1];
}

function FailedAttemptsValue({ attempts, complete }: { attempts: number; complete: boolean }) {
    const tooltipId = useId();

    if (complete) {
        return attempts;
    }

    return (
        <Tooltip>
            <TooltipTrigger
                render={
                    <span
                        tabIndex={0}
                        aria-describedby={tooltipId}
                        className="cursor-help rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                }
            >
                {attempts}+
            </TooltipTrigger>
            <TooltipContent id={tooltipId} role="tooltip" side="top">
                Earlier attempts were trimmed by Horizon. At least {attempts} attempts are known.
            </TooltipContent>
        </Tooltip>
    );
}

export default function BatchShow({
    batch,
    failedJobs,
    failedJobsComplete = true,
}: BatchShowProps) {
    const { horizon } = usePage<HorizonPageProps>().props;

    if (!batch) {
        return (
            <>
                <Head title="Horizon - Batch Detail" />
                <Card>
                    <CardHeader>
                        <CardTitle>Batch Preview</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Empty className="min-h-48 border-0 p-6">
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <BatchesNavigationIcon aria-hidden="true" />
                                </EmptyMedia>
                                <EmptyTitle>Batch no longer available</EmptyTitle>
                                <EmptyDescription>
                                    This batch may have been pruned from the application database.
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    </CardContent>
                </Card>
            </>
        );
    }

    const details: Array<{ label: string; value: React.ReactNode; identifier?: boolean }> = [
        {
            label: "Progress",
            value: (
                <ProgressRing
                    value={batch.progress ?? 0}
                    cancelled={Boolean(batch.cancelledAt)}
                    pendingJobs={batch.pendingJobs}
                    failedJobs={batch.failedJobs}
                />
            ),
        },
        { label: "ID", value: batch.id, identifier: true },
        ...(batch.name ? [{ label: "Name", value: batch.name }] : []),
        ...(batch.options?.queue ? [{ label: "Queue", value: String(batch.options.queue) }] : []),
        ...(batch.options?.connection
            ? [{ label: "Connection", value: String(batch.options.connection) }]
            : []),
        { label: "Created", value: formatDate(batch.createdAt) },
        ...(batch.finishedAt ? [{ label: "Finished", value: formatDate(batch.finishedAt) }] : []),
        ...(batch.cancelledAt
            ? [{ label: "Cancelled", value: formatDate(batch.cancelledAt) }]
            : []),
        { label: "Total jobs", value: String(batch.totalJobs) },
        { label: "Pending jobs", value: String(batch.pendingJobs) },
        {
            label: "Failed job attempts",
            value: String(batch.failedJobAttempts ?? batch.failedJobs),
        },
    ];

    const showFailedSection = failedJobs.length > 0 || !failedJobsComplete;
    const canRetry = batch.failedJobs > 0;

    return (
        <>
            <Head title="Horizon - Batch Detail" />
            <div className="flex flex-col gap-3.5">
                <Card>
                    <CardHeader>
                        <CardTitle className="min-w-0 truncate" title={batch.name || batch.id}>
                            {batch.name || batch.id}
                        </CardTitle>
                        {canRetry ? (
                            <BatchActions batchId={batch.id} horizonBaseUrl={horizon.baseUrl} />
                        ) : null}
                    </CardHeader>
                    <CardContent className="p-0">
                        <DetailList>
                            {details.map((detail, index) => {
                                const title =
                                    typeof detail.value === "string" ? detail.value : undefined;

                                return (
                                    <DetailListItem
                                        key={detail.label}
                                        label={detail.label}
                                        bordered={index > 0}
                                        scrollable={detail.identifier}
                                        valueClassName={
                                            detail.identifier ? "text-[13px] text-sm!" : "break-all"
                                        }
                                        valueTitle={title}
                                    >
                                        {detail.value}
                                    </DetailListItem>
                                );
                            })}
                        </DetailList>
                    </CardContent>
                </Card>

                {showFailedSection ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Failed Jobs</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {!failedJobsComplete ? (
                                <p className="border-b border-separator px-6 py-3 text-sm text-muted-foreground">
                                    Some failed jobs are no longer retained by Horizon.
                                </p>
                            ) : null}
                            {failedJobs.length > 0 ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Job</TableHead>
                                            <TableHead className="text-right">Attempts</TableHead>
                                            <TableHead className="text-right">Failed</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {failedJobs.map((job) => {
                                            const detailUrl = resolveHorizonRoute(
                                                jobPageShow({ type: "failed", id: job.id }),
                                                horizon.baseUrl,
                                            ).url;

                                            return (
                                                <TableRow
                                                    className="cursor-pointer"
                                                    key={job.id}
                                                    onClick={(event) => {
                                                        if (!isInteractiveTarget(event.target)) {
                                                            router.visit(detailUrl);
                                                        }
                                                    }}
                                                    onMouseEnter={() => router.prefetch(detailUrl)}
                                                >
                                                    <TableCell className="min-w-0">
                                                        <Link
                                                            href={detailUrl}
                                                            className="block max-w-full truncate font-medium text-left hover:text-primary"
                                                            prefetch
                                                            title={
                                                                job.payload?.displayName ?? job.name
                                                            }
                                                        >
                                                            {jobBaseName(
                                                                job.payload?.displayName ??
                                                                    job.name,
                                                            )}
                                                        </Link>
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums text-muted-foreground">
                                                        <FailedAttemptsValue
                                                            attempts={job.attempts ?? 1}
                                                            complete={
                                                                job.attemptsComplete !== false
                                                            }
                                                        />
                                                    </TableCell>
                                                    <TableCell className="text-right text-muted-foreground">
                                                        {timestamp(job.failed_at)}
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            ) : (
                                <Empty className="min-h-40 border-0">
                                    <EmptyHeader>
                                        <EmptyMedia variant="icon">
                                            <BatchesNavigationIcon aria-hidden="true" />
                                        </EmptyMedia>
                                        <EmptyTitle>No retained failed jobs</EmptyTitle>
                                        <EmptyDescription>
                                            Failed job records for this batch are no longer retained
                                            by Horizon.
                                        </EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
                            )}
                        </CardContent>
                    </Card>
                ) : null}
            </div>
        </>
    );
}
