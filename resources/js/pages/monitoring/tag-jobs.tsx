import { Head, InfiniteScroll, Link, router, usePage } from "@inertiajs/react";
import { useRef } from "react";

import { NewEntriesTableRow } from "@/components/data-table/new-entries-alert";
import { TableEmpty } from "@/components/data-table/table-empty";
import { RetryJobButton } from "@/components/jobs/retry-job-button";
import {
    CompletedJobsNavigationIcon,
    FailedJobsNavigationIcon,
} from "@/components/navigation-icons";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { useAutoLoad } from "@/hooks/use-auto-load";
import { useAutoLoadPreference } from "@/layouts/horizon-layout";
import { show as jobPageShow } from "@/generated/routes/horizon/jobs/page";
import { page as monitoringFailedPage } from "@/generated/routes/horizon/monitoring-failed";
import { page as monitoringJobsPage } from "@/generated/routes/horizon/monitoring-jobs";
import { isInteractiveTarget } from "@/lib/interactive-target";
import { resolveHorizonRoute } from "@/lib/horizon-route";
import { cn } from "@/lib/utils";
import type { HorizonPageProps } from "@/types/dashboard";

type Job = {
    id: string;
    name?: string;
    queue?: string;
    status?: string;
    reserved_at?: number;
    completed_at?: number;
    failed_at?: number;
    payload?: {
        displayName?: string;
        pushedAt?: number;
        attempts?: number;
        tags?: string[];
        retry_of?: string;
    };
};

type MonitoringTagJobsProps = {
    tag: string;
    failed: boolean;
    jobs: { data: Job[] };
    total: number;
    listRevision: string;
};

function jobName(job: Job) {
    const name = job.payload?.displayName ?? job.name ?? job.id;
    const parts = name.split("\\");

    return parts[parts.length - 1];
}

function timestamp(value?: number) {
    if (!value) {
        return "—";
    }

    const date = new Date(value * 1000);
    const parts = [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, "0"),
        String(date.getDate()).padStart(2, "0"),
    ];
    const time = [
        String(date.getHours()).padStart(2, "0"),
        String(date.getMinutes()).padStart(2, "0"),
        String(date.getSeconds()).padStart(2, "0"),
    ];

    return `${parts.join("-")} ${time.join(":")}`;
}

function runtime(job: Job) {
    const endedAt = job.failed_at ?? job.completed_at;

    if (!endedAt || !job.reserved_at) {
        return "—";
    }

    return `${(endedAt - job.reserved_at).toFixed(2)}s`;
}

export default function MonitoringTagJobs({
    tag,
    failed,
    jobs,
    total,
    listRevision,
}: MonitoringTagJobsProps) {
    const { horizon } = usePage<HorizonPageProps>().props;
    const { autoLoad } = useAutoLoadPreference();
    const jobItemsRef = useRef<HTMLTableSectionElement>(null);
    const refreshedJobs = useAutoLoad({
        enabled: autoLoad,
        prop: "jobs",
        listRevision,
        additionalProps: ["total"],
        scope: `monitoring:${failed ? "failed" : "jobs"}:${tag}`,
    });

    const jobsUrl = resolveHorizonRoute(monitoringJobsPage(tag), horizon.baseUrl).url;
    const failedUrl = resolveHorizonRoute(monitoringFailedPage(tag), horizon.baseUrl).url;

    return (
        <>
            <Head title={`Horizon - ${failed ? "Failed " : ""}Jobs for ${tag}`} />
            <Card>
                <CardHeader>
                    <CardTitle className="min-w-0 truncate" title={tag}>
                        {failed ? "Failed Jobs" : "Recent Jobs"} for &quot;{tag}&quot;
                    </CardTitle>
                </CardHeader>
                <nav
                    aria-label={`Monitored tag ${tag}`}
                    className="flex h-[49px] items-stretch gap-2 overflow-x-auto border-b border-separator px-3"
                >
                    {[
                        { label: "Recent Jobs", href: jobsUrl, active: !failed },
                        { label: "Failed Jobs", href: failedUrl, active: failed },
                    ].map((tab) => (
                        <Link
                            key={tab.label}
                            href={tab.href}
                            aria-current={tab.active ? "page" : undefined}
                            className={cn(
                                "relative flex shrink-0 items-center gap-2 px-3 text-[13.5px] font-medium transition-colors after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:content-['']",
                                tab.active
                                    ? "text-foreground after:bg-primary"
                                    : "text-muted-foreground after:bg-transparent hover:text-foreground",
                            )}
                            prefetch
                        >
                            {tab.label}
                        </Link>
                    ))}
                </nav>
                <CardContent className="p-0">
                    <InfiniteScroll
                        data="jobs"
                        itemsElement={jobItemsRef}
                        onlyNext
                        preserveUrl
                        buffer={600}
                        params={{ onBefore: refreshedJobs.onBeforeNextPage }}
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Job</TableHead>
                                    {failed ? (
                                        <>
                                            <TableHead>Failed</TableHead>
                                            <TableHead className="w-px text-right">
                                                <span className="sr-only">Actions</span>
                                            </TableHead>
                                        </>
                                    ) : (
                                        <>
                                            <TableHead>Completed</TableHead>
                                            <TableHead className="text-right">Runtime</TableHead>
                                        </>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody ref={jobItemsRef}>
                                {refreshedJobs.hasNewEntries ? (
                                    <NewEntriesTableRow
                                        columns={failed ? 3 : 3}
                                        onLoad={refreshedJobs.loadNewEntries}
                                    />
                                ) : null}
                                {jobs.data.length === 0 ? (
                                    <TableEmpty
                                        columns={3}
                                        description={
                                            failed
                                                ? `There aren't any failed jobs for “${tag}”.`
                                                : `There aren't any recent jobs for “${tag}”.`
                                        }
                                        icon={
                                            failed
                                                ? FailedJobsNavigationIcon
                                                : CompletedJobsNavigationIcon
                                        }
                                        title={failed ? "No failed jobs" : "No recent jobs"}
                                    />
                                ) : null}
                                {jobs.data.map((job) => {
                                    const detailUrl = resolveHorizonRoute(
                                        jobPageShow({
                                            type: failed ? "failed" : "completed",
                                            id: job.id,
                                        }),
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
                                            <TableCell>
                                                <div className="min-w-0">
                                                    <div className="flex items-center gap-2">
                                                        <Link
                                                            className="truncate font-medium text-foreground"
                                                            href={detailUrl}
                                                            prefetch
                                                            title={job.name}
                                                        >
                                                            {jobName(job)}
                                                        </Link>
                                                        {job.payload?.retry_of ? (
                                                            <Badge
                                                                className="shrink-0"
                                                                variant="retry"
                                                            >
                                                                Retry
                                                            </Badge>
                                                        ) : null}
                                                    </div>
                                                    <p className="mt-0.5 max-w-[44rem] truncate text-xs text-muted-foreground">
                                                        Queue: {job.queue ?? "—"}
                                                        {failed
                                                            ? ` | Attempts: ${job.payload?.attempts ?? "—"}`
                                                            : ""}
                                                    </p>
                                                </div>
                                            </TableCell>
                                            {failed ? (
                                                <>
                                                    <TableCell className="text-muted-foreground">
                                                        {timestamp(job.failed_at)}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <RetryJobButton
                                                            baseUrl={horizon.baseUrl}
                                                            jobId={job.id}
                                                            label={jobName(job)}
                                                        />
                                                    </TableCell>
                                                </>
                                            ) : (
                                                <>
                                                    <TableCell className="text-muted-foreground">
                                                        {timestamp(job.completed_at)}
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums text-muted-foreground">
                                                        {runtime(job)}
                                                    </TableCell>
                                                </>
                                            )}
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </InfiniteScroll>
                    <p className="sr-only">{total} total</p>
                </CardContent>
            </Card>
        </>
    );
}
