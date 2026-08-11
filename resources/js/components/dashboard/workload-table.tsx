import { Fragment } from "react";
import { ChevronRightIcon } from "lucide-react";

import { DashboardNavigationIcon } from "@/components/navigation-icons";
import { QueueActionsMenu, QueuePauseBadge } from "@/components/dashboard/queue-actions-menu";
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
import { formatWaitDuration } from "@/lib/format-duration";
import type { WorkloadItem } from "@/types/dashboard";

const numberFormatter = new Intl.NumberFormat();

function workloadRowKey(connection: string | null, name: string, splitName?: string): string {
    const base = connection === null ? name : `${connection}:${name}`;

    return splitName === undefined ? base : `${base}:${splitName}`;
}

function formatThroughput(throughput: number | null | undefined): string {
    return throughput === null || throughput === undefined
        ? "—"
        : numberFormatter.format(throughput);
}

export function WorkloadTable({
    workload,
    horizonBaseUrl,
    queuePausing,
    queuePauseFor = false,
}: {
    workload: WorkloadItem[];
    horizonBaseUrl: string;
    queuePausing: boolean;
    queuePauseFor?: boolean;
}) {
    if (workload.length === 0) {
        return (
            <Empty className="min-h-48">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <DashboardNavigationIcon aria-hidden="true" />
                    </EmptyMedia>
                    <EmptyTitle>All queues are clear</EmptyTitle>
                    <EmptyDescription>Horizon has no queued workload right now.</EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    return (
        <Table>
            <TableHeader topBorder>
                <TableRow>
                    <TableHead>Queue</TableHead>
                    <TableHead className="text-right">Ready Jobs</TableHead>
                    <TableHead className="text-right">Processes</TableHead>
                    <TableHead className="text-right">Throughput</TableHead>
                    <TableHead className="text-right">Wait</TableHead>
                    {queuePausing ? (
                        <TableHead className="w-px text-right">
                            <span className="sr-only">Actions</span>
                        </TableHead>
                    ) : null}
                </TableRow>
            </TableHeader>
            <TableBody>
                {workload.map((queue) => (
                    <Fragment key={workloadRowKey(queue.connection, queue.name)}>
                        <TableRow>
                            <TableCell
                                className={queue.split_queues?.length ? "font-semibold" : undefined}
                            >
                                <span className="flex items-center gap-2">
                                    {queue.name.replaceAll(",", ", ")}
                                    <QueuePauseBadge
                                        paused={queue.paused}
                                        pausedUntil={queue.pausedUntil}
                                    />
                                </span>
                            </TableCell>
                            <TableCell className="text-right text-muted-foreground">
                                {numberFormatter.format(queue.length || 0)}
                            </TableCell>
                            <TableCell className="text-right text-muted-foreground">
                                {numberFormatter.format(queue.processes || 0)}
                            </TableCell>
                            <TableCell className="text-right text-muted-foreground">
                                {formatThroughput(queue.throughput)}
                            </TableCell>
                            <TableCell className="text-right text-muted-foreground">
                                {formatWaitDuration(queue.wait)}
                            </TableCell>
                            {queuePausing ? (
                                <TableCell className="w-px text-right">
                                    {queue.split_queues?.length ||
                                    queue.connection === null ? null : (
                                        <QueueActionsMenu
                                            connection={queue.connection}
                                            queue={queue.name}
                                            paused={queue.paused}
                                            pausedUntil={queue.pausedUntil}
                                            horizonBaseUrl={horizonBaseUrl}
                                            queuePauseFor={queuePauseFor}
                                        />
                                    )}
                                </TableCell>
                            ) : null}
                        </TableRow>
                        {queue.split_queues?.map((splitQueue) => (
                            <TableRow
                                key={workloadRowKey(queue.connection, queue.name, splitQueue.name)}
                            >
                                <TableCell className="flex items-center gap-2">
                                    <ChevronRightIcon
                                        className="size-4 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                    <span className="flex items-center gap-2">
                                        {splitQueue.name.replaceAll(",", ", ")}
                                        <QueuePauseBadge
                                            paused={splitQueue.paused}
                                            pausedUntil={splitQueue.pausedUntil}
                                        />
                                    </span>
                                </TableCell>
                                <TableCell className="text-right text-muted-foreground">
                                    {numberFormatter.format(splitQueue.length || 0)}
                                </TableCell>
                                <TableCell className="text-right text-muted-foreground">
                                    —
                                </TableCell>
                                <TableCell className="text-right text-muted-foreground">
                                    {formatThroughput(splitQueue.throughput)}
                                </TableCell>
                                <TableCell className="text-right text-muted-foreground">
                                    {formatWaitDuration(splitQueue.wait)}
                                </TableCell>
                                {queuePausing ? (
                                    <TableCell className="w-px text-right">
                                        {queue.connection === null ? null : (
                                            <QueueActionsMenu
                                                connection={queue.connection}
                                                queue={splitQueue.name}
                                                paused={splitQueue.paused}
                                                pausedUntil={splitQueue.pausedUntil}
                                                horizonBaseUrl={horizonBaseUrl}
                                                queuePauseFor={queuePauseFor}
                                            />
                                        )}
                                    </TableCell>
                                ) : null}
                            </TableRow>
                        ))}
                    </Fragment>
                ))}
            </TableBody>
        </Table>
    );
}
