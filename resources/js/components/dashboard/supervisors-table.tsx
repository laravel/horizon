import { CpuIcon } from "lucide-react";
import { useId } from "react";

import { TableEmpty } from "@/components/data-table/table-empty";
import { DashboardNavigationIcon } from "@/components/navigation-icons";
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
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";
import type { Master, Supervisor } from "@/types/dashboard";

const numberFormatter = new Intl.NumberFormat();

function processCount(supervisor: Supervisor) {
    return Object.values(supervisor.processes).reduce((total, count) => total + count, 0);
}

function supervisorName(supervisor: Supervisor, master: Master) {
    return supervisor.name.replace(`${master.name}:`, "");
}

function statusVariant(status: string): "running" | "paused" | "inactive" {
    return status === "running" || status === "paused" ? status : "inactive";
}

function supervisorStatusLabel(status: string) {
    if (status === "running") {
        return "Running";
    }

    if (status === "paused") {
        return "Paused";
    }

    return "Inactive";
}

function formatQueueList(queue: string | null | undefined) {
    return (queue ?? "").replaceAll(",", ", ");
}

function QueueCell({ queue }: { queue: string | null | undefined }) {
    const tooltipId = useId();
    const value = formatQueueList(queue);

    if (value === "") {
        return <TableCell className="min-w-0 text-muted-foreground">—</TableCell>;
    }

    return (
        <TableCell className="min-w-0 text-muted-foreground">
            <Tooltip>
                <TooltipTrigger
                    render={
                        <span
                            tabIndex={0}
                            aria-describedby={tooltipId}
                            className="block min-w-0 cursor-help truncate rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    }
                >
                    {value}
                </TooltipTrigger>
                <TooltipContent id={tooltipId} role="tooltip" side="top" className="max-w-sm">
                    {value}
                </TooltipContent>
            </Tooltip>
        </TableCell>
    );
}

export function SupervisorsTable({ masters }: { masters: Master[] }) {
    if (masters.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Instances</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <Empty className="min-h-40">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <CpuIcon aria-hidden="true" />
                            </EmptyMedia>
                            <EmptyTitle>No Horizon instances</EmptyTitle>
                            <EmptyDescription>
                                Run <code className="font-sans">php artisan horizon</code> to start
                                an instance and start processing queues.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                </CardContent>
            </Card>
        );
    }

    return (
        <>
            {masters.map((master) => (
                <Card key={master.name}>
                    <CardHeader>
                        <CardTitle className="min-w-0 truncate" title={master.name}>
                            {master.name}
                        </CardTitle>
                        <Badge variant={statusVariant(master.status)}>
                            {master.status === "running"
                                ? "Active"
                                : master.status === "paused"
                                  ? "Paused"
                                  : "Inactive"}
                        </Badge>
                    </CardHeader>
                    <CardContent>
                        <Table className="table-fixed">
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[20%]">Supervisor</TableHead>
                                    <TableHead className="w-[12%]">Status</TableHead>
                                    <TableHead className="w-[14%]">Connection</TableHead>
                                    <TableHead className="min-w-0 w-[30%]">Queues</TableHead>
                                    <TableHead className="w-[12%] text-right">Processes</TableHead>
                                    <TableHead className="w-[12%] text-right">Balancing</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {master.supervisors.length === 0 ? (
                                    <TableEmpty
                                        columns={6}
                                        icon={DashboardNavigationIcon}
                                        title="No supervisors"
                                    />
                                ) : null}
                                {master.supervisors.map((supervisor) => (
                                    <TableRow key={supervisor.name}>
                                        <TableCell className="min-w-0 truncate">
                                            {supervisorName(supervisor, master)}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={statusVariant(supervisor.status)}>
                                                {supervisorStatusLabel(supervisor.status)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="min-w-0 truncate text-muted-foreground">
                                            {supervisor.options.connection ?? "—"}
                                        </TableCell>
                                        <QueueCell queue={supervisor.options.queue} />
                                        <TableCell className="text-right tabular-nums text-muted-foreground">
                                            {numberFormatter.format(processCount(supervisor))}
                                        </TableCell>
                                        <TableCell className="text-right text-muted-foreground">
                                            {supervisor.options.balance
                                                ? `${supervisor.options.balance.charAt(0).toUpperCase()}${supervisor.options.balance.slice(1)}`
                                                : "Disabled"}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            ))}
        </>
    );
}
