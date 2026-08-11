import { Head, InfiniteScroll, Link, router, usePage } from "@inertiajs/react";
import { useRef } from "react";

import { ProgressRing } from "@/components/batches/progress-ring";
import { TableEmpty } from "@/components/data-table/table-empty";
import { BatchesNavigationIcon } from "@/components/navigation-icons";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { show as batchShow } from "@/generated/routes/horizon/batches/page";
import { isInteractiveTarget } from "@/lib/interactive-target";
import { resolveHorizonRoute } from "@/lib/horizon-route";
import type { HorizonPageProps } from "@/types/dashboard";

type Batch = {
    id: string;
    name?: string | null;
    totalJobs?: number;
    pendingJobs?: number;
    failedJobs?: number;
    failedJobAttempts?: number;
    progress?: number;
    cancelledAt?: string | null;
};

export default function Batches({ batches }: { batches: { data: Batch[] } }) {
    const { horizon } = usePage<HorizonPageProps>().props;
    const batchItemsRef = useRef<HTMLTableSectionElement>(null);

    return (
        <>
            <Head title="Horizon - Batches" />
            <Card>
                <CardHeader>
                    <CardTitle>Batches</CardTitle>
                </CardHeader>
                <CardContent>
                    <InfiniteScroll
                        data="batches"
                        itemsElement={batchItemsRef}
                        onlyNext
                        preserveUrl
                        buffer={600}
                    >
                        <Table className="table-fixed">
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-1/2">Batch</TableHead>
                                    <TableHead className="w-[13%] text-right">Total</TableHead>
                                    <TableHead className="w-[15%] text-right">Failed</TableHead>
                                    <TableHead className="w-[22%] px-4">Progress</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody ref={batchItemsRef}>
                                {batches.data.length === 0 ? (
                                    <TableEmpty
                                        columns={4}
                                        description="There aren't any batches."
                                        icon={BatchesNavigationIcon}
                                        title="No batches"
                                    />
                                ) : null}
                                {batches.data.map((batch) => {
                                    const totalJobs = batch.totalJobs ?? 0;
                                    const pendingJobs = batch.pendingJobs ?? 0;
                                    const failedJobs = batch.failedJobs ?? 0;
                                    const detailUrl = resolveHorizonRoute(
                                        batchShow(batch.id),
                                        horizon.baseUrl,
                                    ).url;

                                    return (
                                        <TableRow
                                            className="cursor-pointer"
                                            key={batch.id}
                                            onClick={(event) => {
                                                if (!isInteractiveTarget(event.target)) {
                                                    router.visit(detailUrl);
                                                }
                                            }}
                                            onMouseEnter={() => router.prefetch(detailUrl)}
                                        >
                                            <TableCell className="max-w-0">
                                                <Link
                                                    href={detailUrl}
                                                    className="block max-w-full truncate font-medium text-left hover:text-primary"
                                                    title={batch.name || batch.id}
                                                    prefetch
                                                >
                                                    {batch.name || batch.id}
                                                </Link>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums text-muted-foreground">
                                                {totalJobs.toLocaleString()}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums text-muted-foreground">
                                                {failedJobs.toLocaleString()}
                                            </TableCell>
                                            <TableCell className="px-4 text-muted-foreground">
                                                <ProgressRing
                                                    value={batch.progress ?? 0}
                                                    cancelled={Boolean(batch.cancelledAt)}
                                                    pendingJobs={pendingJobs}
                                                    failedJobs={failedJobs}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </InfiniteScroll>
                </CardContent>
            </Card>
        </>
    );
}
