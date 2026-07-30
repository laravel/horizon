import { Head, Link, router, usePage } from "@inertiajs/react";

import { TableEmpty } from "@/components/data-table/table-empty";
import { MetricsNavigationIcon } from "@/components/navigation-icons";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { page as metricsPage } from "@/generated/routes/horizon/metrics";
import { show as metricShow } from "@/generated/routes/horizon/metrics/page";
import { isInteractiveTarget } from "@/lib/interactive-target";
import { resolveHorizonRoute } from "@/lib/horizon-route";
import { cn } from "@/lib/utils";
import type { HorizonPageProps } from "@/types/dashboard";
import type { MetricType } from "@/types/metrics";

type MetricsProps = {
    type: MetricType;
    metrics: string[];
};

const metricTypes: Array<{ label: string; value: MetricType }> = [
    { label: "Jobs", value: "jobs" },
    { label: "Queues", value: "queues" },
];

export default function Metrics({ type, metrics }: MetricsProps) {
    const { horizon } = usePage<HorizonPageProps>().props;
    const singular = type === "jobs" ? "job" : "queue";

    return (
        <>
            <Head title="Horizon - Metrics" />
            <Card>
                <CardHeader className="border-b-0 pb-2">
                    <CardTitle>Metrics</CardTitle>
                </CardHeader>
                <CardContent className="p-0">
                    <nav
                        aria-label="Metrics type"
                        className="flex h-9 items-stretch gap-2 border-b border-separator px-3"
                    >
                        {metricTypes.map((metricType) => {
                            const active = metricType.value === type;

                            return (
                                <Link
                                    aria-current={active ? "page" : undefined}
                                    className={cn(
                                        "relative flex items-center px-3 text-[13.5px] font-medium transition-colors after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:content-['']",
                                        active
                                            ? "text-foreground after:bg-primary"
                                            : "text-muted-foreground after:bg-transparent hover:text-foreground",
                                    )}
                                    href={
                                        resolveHorizonRoute(
                                            metricsPage(metricType.value),
                                            horizon.baseUrl,
                                        ).url
                                    }
                                    key={metricType.value}
                                    prefetch
                                    preserveState
                                >
                                    {metricType.label}
                                </Link>
                            );
                        })}
                    </nav>

                    <div>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{type === "jobs" ? "Job" : "Queue"}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {metrics.length === 0 ? (
                                    <TableEmpty
                                        columns={1}
                                        description={`Once Horizon records its first ${singular} snapshot, throughput and runtime history will settle in here.`}
                                        icon={MetricsNavigationIcon}
                                        title={`No ${singular} metrics yet`}
                                    />
                                ) : (
                                    metrics.map((metric) => {
                                        const detailUrl = resolveHorizonRoute(
                                            metricShow({ type, name: metric }),
                                            horizon.baseUrl,
                                        ).url;

                                        return (
                                            <TableRow
                                                className="cursor-pointer"
                                                key={metric}
                                                onClick={(event) => {
                                                    if (isInteractiveTarget(event.target)) {
                                                        return;
                                                    }

                                                    router.visit(detailUrl);
                                                }}
                                                onMouseEnter={() => router.prefetch(detailUrl)}
                                            >
                                                <TableCell>
                                                    <Link
                                                        className="text-foreground"
                                                        href={detailUrl}
                                                        prefetch
                                                    >
                                                        {metric}
                                                    </Link>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
