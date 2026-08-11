import { Head, Link, router, usePage } from "@inertiajs/react";
import {
    BracesIcon,
    CircleAlertIcon,
    DatabaseIcon,
    RotateCcwIcon,
    type LucideIcon,
} from "lucide-react";
import { useState } from "react";

import { DetailList, DetailListItem } from "@/components/detail-list";
import { FailedJobActionsMenu } from "@/components/jobs/failed-job-actions";
import { StackTrace } from "@/components/jobs/stack-trace";
import {
    CompletedJobsNavigationIcon,
    FailedJobsNavigationIcon,
    PendingJobsNavigationIcon,
    SilencedJobsNavigationIcon,
} from "@/components/navigation-icons";
import { JsonPayload } from "@/components/payload/json-payload";
import { Badge } from "@/components/ui/badge";
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { show as batchPageShow } from "@/generated/routes/horizon/batches/page";
import { show as jobPageShow } from "@/generated/routes/horizon/jobs/page";
import { isInteractiveTarget } from "@/lib/interactive-target";
import { resolveHorizonRoute } from "@/lib/horizon-route";
import type { HorizonPageProps } from "@/types/dashboard";

type JobType = "pending" | "completed" | "silenced" | "failed";
type JobDataTab = "data" | "tags" | "exception" | "context" | "retries";

type JobRetry = {
    id: string;
    status: string;
    retriedAt: number | null;
};

type JobDetail = {
    id: string;
    name: string;
    connection: string;
    queue: string;
    status: string;
    tags: string[];
    attempts: number;
    retryOf: string | null;
    batchId: string | null;
    delay: number | null;
    pushedAt: number | null;
    reservedAt: number | null;
    completedAt: number | null;
    failedAt: number | null;
    runtime: number | null;
    payload: Record<string, unknown>;
    context: Record<string, unknown>;
    exception: string;
    retriedBy: Array<Record<string, unknown>>;
};

type JobShowProps = {
    type: JobType;
    job: JobDetail | null;
};

const jobTypeIcons = {
    pending: PendingJobsNavigationIcon,
    completed: CompletedJobsNavigationIcon,
    silenced: SilencedJobsNavigationIcon,
    failed: FailedJobsNavigationIcon,
} satisfies Record<JobType, LucideIcon>;

type Detail = {
    label: string;
    value: string | React.ReactNode;
    identifier?: boolean;
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

const identifierLinkClassName =
    "text-[13px] text-sm! text-foreground underline decoration-foreground/40 underline-offset-4 transition-colors hover:text-primary hover:decoration-primary";

function timestamp(value: number | null) {
    return value === null ? "—" : dateFormatter.format(value * 1000);
}

function duration(value: number | null) {
    return value === null ? "—" : `${value.toFixed(2)}s`;
}

function statusLabel(type: JobType, status: string) {
    if (type === "silenced") {
        return "Silenced";
    }

    return status.charAt(0).toUpperCase() + status.slice(1);
}

function parseJobRetry(retry: Record<string, unknown>): JobRetry | null {
    const id =
        typeof retry.id === "string" || typeof retry.id === "number" ? String(retry.id) : null;

    if (id === null || id === "") {
        return null;
    }

    const retriedAt =
        typeof retry.retried_at === "number"
            ? retry.retried_at
            : typeof retry.retriedAt === "number"
              ? retry.retriedAt
              : Number(retry.retried_at ?? retry.retriedAt) || null;

    return {
        id,
        status: typeof retry.status === "string" ? retry.status : "unknown",
        retriedAt,
    };
}

function retryDetailType(status: string): JobType | null {
    if (status === "failed") {
        return "failed";
    }

    if (status === "completed") {
        return "completed";
    }

    if (status === "pending" || status === "reserved") {
        return "pending";
    }

    return null;
}

function retryStatusBadgeVariant(status: string): "failed" | "completed" | "pending" | "inactive" {
    if (status === "failed" || status === "completed" || status === "pending") {
        return status;
    }

    if (status === "reserved") {
        return "pending";
    }

    return "inactive";
}

function retryStatusLabel(status: string) {
    if (status === "") {
        return "Unknown";
    }

    return status.charAt(0).toUpperCase() + status.slice(1);
}

function initialTab(type: JobType): JobDataTab {
    const requested = new URLSearchParams(window.location.search).get("tab");
    const available =
        type === "failed" ? ["exception", "context", "data", "tags", "retries"] : ["data", "tags"];

    return available.includes(requested ?? "")
        ? (requested as JobDataTab)
        : type === "failed"
          ? "exception"
          : "data";
}

export default function JobShow({ type, job }: JobShowProps) {
    const { horizon } = usePage<HorizonPageProps>().props;

    if (!job) {
        const Icon = jobTypeIcons[type];

        return (
            <>
                <Head title={`Horizon - ${type === "failed" ? "Failed " : ""}Job Detail`} />
                <Card>
                    <CardHeader>
                        <CardTitle>Job Preview</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Empty className="min-h-48 border-0 p-6">
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <Icon aria-hidden="true" />
                                </EmptyMedia>
                                <EmptyTitle>Job no longer available</EmptyTitle>
                                <EmptyDescription>
                                    This job may have been trimmed from Horizon.
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    </CardContent>
                </Card>
            </>
        );
    }

    const finishedAt = job.failedAt ?? job.completedAt;
    const waitTime =
        job.pushedAt !== null && job.reservedAt !== null
            ? Math.max(0, job.reservedAt - job.pushedAt)
            : null;
    const retryOfUrl = job.retryOf
        ? resolveHorizonRoute(jobPageShow({ type: "failed", id: job.retryOf }), horizon.baseUrl).url
        : null;
    const batchUrl = job.batchId
        ? resolveHorizonRoute(batchPageShow(job.batchId), horizon.baseUrl).url
        : null;

    const details: Detail[] = [
        {
            label: "Status",
            value: <Badge variant={type}>{statusLabel(type, job.status)}</Badge>,
        },
        { label: "ID", value: job.id, identifier: true },
        { label: "Connection", value: job.connection },
        { label: "Queue", value: job.queue },
        { label: type === "failed" ? "Attempts" : "Tries", value: String(job.attempts) },
        ...(retryOfUrl
            ? [
                  {
                      label: "Retry of ID",
                      identifier: true,
                      value: (
                          <Link
                              className={identifierLinkClassName}
                              href={retryOfUrl}
                              prefetch
                              title={job.retryOf ?? undefined}
                          >
                              {job.retryOf}
                          </Link>
                      ),
                  },
              ]
            : []),
        ...(job.batchId && batchUrl
            ? [
                  {
                      label: "Batch",
                      identifier: true,
                      value: (
                          <Link className={identifierLinkClassName} href={batchUrl} prefetch>
                              {job.batchId}
                          </Link>
                      ),
                  },
              ]
            : []),
        { label: "Created at", value: timestamp(job.pushedAt) },
        ...(job.delay !== null && job.delay > 0
            ? [{ label: "Delay", value: duration(job.delay) }]
            : []),
        ...(job.reservedAt !== null
            ? [{ label: "Reserved at", value: timestamp(job.reservedAt) }]
            : []),
        ...(waitTime !== null ? [{ label: "Wait time", value: duration(waitTime) }] : []),
        ...(type === "failed"
            ? [{ label: "Failed at", value: timestamp(job.failedAt) }]
            : finishedAt !== null
              ? [{ label: "Completed at", value: timestamp(job.completedAt) }]
              : []),
        ...(job.runtime !== null ? [{ label: "Runtime", value: duration(job.runtime) }] : []),
    ];

    return (
        <>
            <Head title={`Horizon - ${type === "failed" ? "Failed " : ""}Job Detail`} />
            <div className="flex flex-col gap-3.5">
                <Card>
                    <CardHeader>
                        <CardTitle className="min-w-0 truncate" title={job.name}>
                            {job.name}
                        </CardTitle>
                        {type === "failed" ? (
                            <FailedJobActionsMenu
                                horizonBaseUrl={horizon.baseUrl}
                                jobId={job.id}
                                label={job.name}
                                onSuccess={() => {
                                    router.reload({
                                        only: ["job"],
                                    });
                                }}
                            />
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

                <JobDataTabs job={job} type={type} />
            </div>
        </>
    );
}

function JobDataTabs({ job, type }: { type: JobType; job: JobDetail }) {
    const [activeTab, setActiveTab] = useState<JobDataTab>(() => initialTab(type));
    const tabs: Array<{ label: string; value: JobDataTab; count?: number }> =
        type === "failed"
            ? [
                  { label: "Exception", value: "exception" },
                  { label: "Context", value: "context" },
                  { label: "Data", value: "data" },
                  { label: "Tags", value: "tags", count: job.tags.length },
                  { label: "Retries", value: "retries", count: job.retriedBy.length },
              ]
            : [
                  { label: "Data", value: "data" },
                  { label: "Tags", value: "tags", count: job.tags.length },
              ];

    function selectTab(tab: JobDataTab) {
        setActiveTab(tab);

        const url = new URL(window.location.href);
        url.searchParams.set("tab", tab);
        router.replace({
            url: `${url.pathname}${url.search}${url.hash}`,
            preserveScroll: true,
            preserveState: true,
        });
    }

    return (
        <Card>
            <CardContent className="p-0">
                <Tabs
                    className="gap-0"
                    onValueChange={(value) => {
                        if (
                            value === "data" ||
                            value === "tags" ||
                            value === "exception" ||
                            value === "context" ||
                            value === "retries"
                        ) {
                            selectTab(value);
                        }
                    }}
                    value={activeTab}
                >
                    <div className="border-b border-separator">
                        <TabsList
                            aria-label={type === "failed" ? "Failed job data" : "Job data"}
                            className="w-full justify-start gap-2 overflow-x-auto rounded-none px-3 py-0"
                            variant="line"
                        >
                            {tabs.map((tab) => (
                                <TabsTrigger
                                    className="h-auto flex-none rounded-none px-3 py-4 text-[13.5px]"
                                    key={tab.value}
                                    value={tab.value}
                                >
                                    {tab.label}
                                    {tab.count !== undefined ? (
                                        <Badge className="h-4 min-w-4 shrink-0 px-1.5 text-[10.5px]">
                                            {tab.count}
                                        </Badge>
                                    ) : null}
                                </TabsTrigger>
                            ))}
                        </TabsList>
                    </div>

                    {tabs.map((tab) => (
                        <TabsContent className="outline-none" key={tab.value} value={tab.value}>
                            <JobTabContent activeTab={tab.value} job={job} />
                        </TabsContent>
                    ))}
                </Tabs>
            </CardContent>
        </Card>
    );
}

function JobTabContent({ activeTab, job }: { activeTab: JobDataTab; job: JobDetail }) {
    if (activeTab === "exception") {
        return job.exception.trim() ? (
            <StackTrace value={job.exception} />
        ) : (
            <DataEmptyState
                description="Horizon did not retain an exception message for this failed job."
                icon={CircleAlertIcon}
                title="No exception details"
            />
        );
    }

    if (activeTab === "context") {
        return Object.keys(job.context).length > 0 ? (
            <JsonPayload value={job.context} copyLabel="exception context" />
        ) : (
            <DataEmptyState
                description="This failure did not include any additional exception context."
                icon={BracesIcon}
                title="No exception context"
            />
        );
    }

    if (activeTab === "data") {
        return Object.keys(job.payload).length > 0 ? (
            <JsonPayload value={job.payload} />
        ) : (
            <DataEmptyState
                description="The retained job payload does not contain any data."
                icon={DatabaseIcon}
                title="No job data"
            />
        );
    }

    if (activeTab === "tags") {
        return job.tags.length > 0 ? (
            <div className="flex flex-wrap gap-2 px-6 py-5">
                {job.tags.map((tag) => (
                    <Badge key={tag}>{tag}</Badge>
                ))}
            </div>
        ) : (
            <div className="px-6 py-5">
                <span className="text-xs text-muted-foreground">—</span>
            </div>
        );
    }

    return job.retriedBy.length > 0 ? (
        <JobRetriesTable retries={job.retriedBy} />
    ) : (
        <DataEmptyState
            description="This failed job has not been retried."
            icon={RotateCcwIcon}
            title="No retry attempts"
        />
    );
}

function JobRetriesTable({ retries }: { retries: Array<Record<string, unknown>> }) {
    const { horizon } = usePage<HorizonPageProps>().props;
    const rows = retries
        .map((retry, index) => ({ retry: parseJobRetry(retry), index }))
        .filter((row): row is { retry: JobRetry; index: number } => row.retry !== null);

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Status</TableHead>
                    <TableHead>Retry job</TableHead>
                    <TableHead className="text-right">Retried</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {rows.map(({ retry, index }) => {
                    const detailType = retryDetailType(retry.status);
                    const detailUrl =
                        detailType === null
                            ? null
                            : resolveHorizonRoute(
                                  jobPageShow({ type: detailType, id: retry.id }),
                                  horizon.baseUrl,
                              ).url;

                    return (
                        <TableRow
                            className={detailUrl ? "cursor-pointer" : undefined}
                            key={retry.id || String(index)}
                            onClick={(event) => {
                                if (!detailUrl || isInteractiveTarget(event.target)) {
                                    return;
                                }

                                router.visit(detailUrl);
                            }}
                            onMouseEnter={() => {
                                if (detailUrl) {
                                    router.prefetch(detailUrl);
                                }
                            }}
                        >
                            <TableCell>
                                <Badge variant={retryStatusBadgeVariant(retry.status)}>
                                    {retryStatusLabel(retry.status)}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                {detailUrl ? (
                                    <Link
                                        className="font-medium text-left hover:text-primary"
                                        href={detailUrl}
                                        prefetch
                                        title={retry.id}
                                    >
                                        {retry.id}
                                    </Link>
                                ) : (
                                    <span title={retry.id}>{retry.id}</span>
                                )}
                            </TableCell>
                            <TableCell className="text-right text-muted-foreground">
                                {timestamp(retry.retriedAt)}
                            </TableCell>
                        </TableRow>
                    );
                })}
            </TableBody>
        </Table>
    );
}

function DataEmptyState({
    description,
    icon: Icon,
    title,
}: {
    description: string;
    icon: LucideIcon;
    title: string;
}) {
    return (
        <Empty className="min-h-48">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <Icon aria-hidden="true" />
                </EmptyMedia>
                <EmptyTitle>{title}</EmptyTitle>
                <EmptyDescription>{description}</EmptyDescription>
            </EmptyHeader>
        </Empty>
    );
}
