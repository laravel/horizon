import { Head } from "@inertiajs/react";

import { MetricChart } from "@/components/metrics/metric-chart";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import type { MetricPreviewPageProps } from "@/types/metrics";

export default function MetricShow({ name, preview }: MetricPreviewPageProps) {
    return (
        <>
            <Head title={`Horizon - Metrics for ${name}`} />
            <div className="flex flex-col gap-3.5">
                {!preview.available ? (
                    <div
                        className="rounded-xl border border-status-failed-foreground/30 bg-status-failed px-4 py-3 text-status-failed-foreground"
                        role="alert"
                    >
                        {preview.message ?? "Metrics for this item are currently unavailable."}
                    </div>
                ) : null}
                <MetricCard title={`Throughput — ${name}`}>
                    <MetricChart kind="throughput" snapshots={preview.data} />
                </MetricCard>
                <MetricCard title={`Runtime — ${name}`}>
                    <MetricChart kind="runtime" snapshots={preview.data} />
                </MetricCard>
            </div>
        </>
    );
}

function MetricCard({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="truncate" title={title}>
                    {title}
                </CardTitle>
            </CardHeader>
            <CardContent className="px-0 pt-3 pb-2">{children}</CardContent>
        </Card>
    );
}
