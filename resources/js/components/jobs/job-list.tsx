import { Head, InfiniteScroll, Link, router, usePage } from "@inertiajs/react";
import { SearchIcon } from "lucide-react";
import { useEffect, useRef, useState, type Ref } from "react";

import { NewEntriesTableRow } from "@/components/data-table/new-entries-alert";
import { TableEmpty } from "@/components/data-table/table-empty";
import { RetryJobButton } from "@/components/jobs/retry-job-button";
import {
    CompletedJobsNavigationIcon,
    FailedJobsNavigationIcon,
    PendingJobsNavigationIcon,
    SilencedJobsNavigationIcon,
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
import { page as failedJobsPage } from "@/generated/routes/horizon/failed-jobs";
import { show as jobPageShow } from "@/generated/routes/horizon/jobs/page";
import { useAutoLoad } from "@/hooks/use-auto-load";
import { useAutoLoadPreference } from "@/layouts/horizon-layout";
import { isInteractiveTarget } from "@/lib/interactive-target";
import { resolveHorizonRoute } from "@/lib/horizon-route";
import type { HorizonPageProps } from "@/types/dashboard";

type JobType = "pending" | "completed" | "silenced" | "failed";

const emptyStateIcons = {
    pending: PendingJobsNavigationIcon,
    completed: CompletedJobsNavigationIcon,
    silenced: SilencedJobsNavigationIcon,
    failed: FailedJobsNavigationIcon,
} satisfies Record<JobType, typeof PendingJobsNavigationIcon>;

type Job = {
    id: string;
    name?: string;
    queue?: string;
    status?: string;
    delay?: number;
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

export type JobListProps = {
    title: string;
    type: JobType;
    jobs: {
        data: Job[];
    };
    total: number;
    listRevision: string;
    tag?: string;
};

function jobName(job: Job) {
    const name = job.payload?.displayName ?? job.name ?? job.id;
    const parts = name.split("\\");

    return parts[parts.length - 1];
}

function timestamp(value?: number) {
    if (!value) {
        return "-";
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
        return "-";
    }

    return `${(endedAt - job.reserved_at).toFixed(2)}s`;
}

function JobDetails({
    job,
    detailUrl,
    failed = false,
}: {
    job: Job;
    detailUrl: string;
    failed?: boolean;
}) {
    const tags = job.payload?.tags ?? [];

    return (
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
                    <Badge className="shrink-0" variant="retry">
                        Retry
                    </Badge>
                ) : null}
                {job.delay && (job.status === "reserved" || job.status === "pending") ? (
                    <Badge variant="delayed">Delayed</Badge>
                ) : null}
            </div>
            <p className="mt-0.5 max-w-[44rem] truncate text-xs text-muted-foreground">
                Queue: {job.queue ?? "-"}
                {failed ? ` | Attempts: ${job.payload?.attempts ?? "-"}` : ""}
                {tags.length > 0 ? ` | Tags: ${tags.slice(0, 3).join(", ")}` : ""}
                {tags.length > 3 ? ` +${tags.length - 3} more` : ""}
            </p>
        </div>
    );
}

function RecentJobsTable({
    jobs,
    type,
    baseUrl,
    bodyRef,
    hasNewEntries,
    onLoadNewEntries,
}: {
    jobs: Job[];
    type: Exclude<JobType, "failed">;
    baseUrl: string;
    bodyRef: Ref<HTMLTableSectionElement>;
    hasNewEntries: boolean;
    onLoadNewEntries: () => void;
}) {
    const completed = type === "completed" || type === "silenced";
    const columns = completed ? 4 : 2;

    return (
        <div className="overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Job</TableHead>
                        <TableHead className={completed ? undefined : "text-right"}>
                            Queued
                        </TableHead>
                        {completed ? <TableHead>Completed</TableHead> : null}
                        {completed ? <TableHead className="text-right">Runtime</TableHead> : null}
                    </TableRow>
                </TableHeader>
                <TableBody ref={bodyRef}>
                    {hasNewEntries ? (
                        <NewEntriesTableRow columns={columns} onLoad={onLoadNewEntries} />
                    ) : null}
                    {jobs.length === 0 ? (
                        <TableEmpty
                            columns={columns}
                            description={`Horizon is not reporting any ${type} jobs.`}
                            icon={emptyStateIcons[type]}
                            title={`No ${type} jobs`}
                        />
                    ) : null}
                    {jobs.map((job) => {
                        const detailUrl = resolveHorizonRoute(
                            jobPageShow({ type, id: job.id }),
                            baseUrl,
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
                                <TableCell className="min-w-[22rem]">
                                    <JobDetails detailUrl={detailUrl} job={job} />
                                </TableCell>
                                <TableCell
                                    className={
                                        completed
                                            ? "w-px text-muted-foreground"
                                            : "w-px text-right text-muted-foreground"
                                    }
                                >
                                    {timestamp(job.payload?.pushedAt)}
                                </TableCell>
                                {completed ? (
                                    <TableCell className="w-px text-muted-foreground">
                                        {timestamp(job.completed_at)}
                                    </TableCell>
                                ) : null}
                                {completed ? (
                                    <TableCell className="w-px text-right text-muted-foreground">
                                        {runtime(job)}
                                    </TableCell>
                                ) : null}
                            </TableRow>
                        );
                    })}
                </TableBody>
            </Table>
        </div>
    );
}

function FailedJobsTable({
    jobs,
    baseUrl,
    tag,
    bodyRef,
    hasNewEntries,
    onLoadNewEntries,
}: {
    jobs: Job[];
    baseUrl: string;
    tag: string;
    bodyRef: Ref<HTMLTableSectionElement>;
    hasNewEntries: boolean;
    onLoadNewEntries: () => void;
}) {
    return (
        <div className="overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Job</TableHead>
                        <TableHead className="text-right">Runtime</TableHead>
                        <TableHead>Failed</TableHead>
                        <TableHead className="text-right">Retry</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody ref={bodyRef}>
                    {hasNewEntries ? (
                        <NewEntriesTableRow columns={4} onLoad={onLoadNewEntries} />
                    ) : null}
                    {jobs.length === 0 ? (
                        <TableEmpty
                            columns={4}
                            description={
                                tag
                                    ? `No failed jobs match the exact tag “${tag}”.`
                                    : "There aren't any failed jobs."
                            }
                            icon={FailedJobsNavigationIcon}
                            title={tag ? "No matching failed jobs" : "No failed jobs"}
                        />
                    ) : null}
                    {jobs.map((job) => {
                        const detailUrl = resolveHorizonRoute(
                            jobPageShow({ type: "failed", id: job.id }),
                            baseUrl,
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
                                <TableCell className="min-w-[22rem]">
                                    <JobDetails detailUrl={detailUrl} job={job} failed />
                                </TableCell>
                                <TableCell className="w-px text-right text-muted-foreground">
                                    {runtime(job)}
                                </TableCell>
                                <TableCell className="w-px text-muted-foreground">
                                    {timestamp(job.failed_at)}
                                </TableCell>
                                <TableCell className="w-px text-right">
                                    <RetryJobButton
                                        baseUrl={baseUrl}
                                        jobId={job.id}
                                        label={jobName(job)}
                                    />
                                </TableCell>
                            </TableRow>
                        );
                    })}
                </TableBody>
            </Table>
        </div>
    );
}

function FailedJobsSearch({ tag, baseUrl }: { tag: string; baseUrl: string }) {
    const [search, setSearch] = useState(tag);

    useEffect(() => {
        setSearch(tag);
    }, [tag]);

    useEffect(() => {
        if (search === tag) {
            return;
        }

        const timeout = window.setTimeout(() => {
            const route = resolveHorizonRoute(
                failedJobsPage(search ? { query: { tag: search } } : undefined),
                baseUrl,
            );

            router.get(
                route.url,
                {},
                {
                    only: ["jobs", "total", "tag", "listRevision", "navigationCounts"],
                    preserveScroll: true,
                    preserveState: true,
                    replace: true,
                    reset: ["jobs"],
                },
            );
        }, 500);

        return () => window.clearTimeout(timeout);
    }, [baseUrl, search, tag]);

    return (
        <label className="relative flex h-8 w-[12.5rem] min-w-44 max-w-1/2 flex-[0_1_12.5rem] items-center rounded-lg border border-input bg-card shadow-xs transition-[border-color,box-shadow] duration-150 focus-within:border-ring focus-within:ring-3 focus-within:ring-ring/25 dark:bg-input/30 dark:focus-within:ring-ring/50">
            <span className="sr-only">Search Tags</span>
            <SearchIcon
                className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true"
            />
            <input
                type="text"
                value={search}
                placeholder="Search Tags"
                className="h-8 w-full border-none bg-transparent py-1 pr-3 pl-9 text-sm outline-none placeholder:text-muted-foreground"
                onChange={(event) => setSearch(event.target.value)}
            />
        </label>
    );
}

export function JobList({ title, type, jobs, total, listRevision, tag = "" }: JobListProps) {
    const { horizon } = usePage<HorizonPageProps>().props;
    const { autoLoad } = useAutoLoadPreference();
    const jobItemsRef = useRef<HTMLTableSectionElement>(null);
    const refreshedJobs = useAutoLoad({
        enabled: autoLoad,
        prop: "jobs",
        listRevision,
        additionalProps: ["total"],
        scope: `${type}:${tag}`,
    });

    return (
        <>
            <Head title={`Horizon - ${title}`} />
            <Card>
                <CardHeader
                    className={type === "failed" ? "flex-row items-center py-2 pr-2" : undefined}
                >
                    <CardTitle>{title}</CardTitle>
                    {type === "failed" ? (
                        <FailedJobsSearch tag={tag} baseUrl={horizon.baseUrl} />
                    ) : null}
                </CardHeader>
                <CardContent className="p-0">
                    <InfiniteScroll
                        data="jobs"
                        itemsElement={jobItemsRef}
                        onlyNext
                        preserveUrl
                        buffer={600}
                        params={{ onBefore: refreshedJobs.onBeforeNextPage }}
                    >
                        {type === "failed" ? (
                            <FailedJobsTable
                                jobs={jobs.data}
                                baseUrl={horizon.baseUrl}
                                tag={tag}
                                bodyRef={jobItemsRef}
                                hasNewEntries={refreshedJobs.hasNewEntries}
                                onLoadNewEntries={refreshedJobs.loadNewEntries}
                            />
                        ) : (
                            <RecentJobsTable
                                baseUrl={horizon.baseUrl}
                                jobs={jobs.data}
                                type={type}
                                bodyRef={jobItemsRef}
                                hasNewEntries={refreshedJobs.hasNewEntries}
                                onLoadNewEntries={refreshedJobs.loadNewEntries}
                            />
                        )}
                    </InfiniteScroll>
                    <p className="sr-only">{total} total</p>
                </CardContent>
            </Card>
        </>
    );
}
