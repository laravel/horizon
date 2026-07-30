import { MetricsNavigationIcon } from "@/components/navigation-icons";
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@/components/ui/empty";
import { formatDuration } from "@/lib/format-duration";
import type { MetricSnapshot } from "@/types/metrics";

type MetricKind = "throughput" | "runtime";

const chartWidth = 960;
const chartHeight = 256;
const chartTop = 12;
const chartRight = 16;
const chartBottom = 32;
const chartLeft = 64;
const plotWidth = chartWidth - chartLeft - chartRight;
const plotHeight = chartHeight - chartTop - chartBottom;
const gridSteps = [0, 0.25, 0.5, 0.75, 1];

const timeFormatter = new Intl.DateTimeFormat(undefined, {
    hour: "2-digit",
    minute: "2-digit",
});

function formatTime(timestamp: number) {
    return timeFormatter.format(new Date(timestamp * 1000));
}

function formatValue(kind: MetricKind, value: number) {
    return kind === "throughput"
        ? `${Math.round(value).toLocaleString()} jobs per minute`
        : formatDuration(value, true);
}

export function MetricChart({
    kind,
    snapshots,
}: {
    kind: MetricKind;
    snapshots: MetricSnapshot[];
}) {
    const title = kind === "throughput" ? "Throughput" : "Runtime";

    if (snapshots.length === 0) {
        return (
            <Empty className="min-h-64">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <MetricsNavigationIcon aria-hidden="true" />
                    </EmptyMedia>
                    <EmptyTitle>Not Enough Data</EmptyTitle>
                    <EmptyDescription>
                        Horizon needs at least one metrics snapshot to draw this chart.
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    const values = snapshots.map((snapshot) => snapshot[kind] ?? 0);
    const maximum = Math.max(1, ...values);
    const slotWidth = plotWidth / snapshots.length;
    const barWidth = Math.min(28, slotWidth * 0.7);
    const labelInterval = Math.max(1, Math.ceil(snapshots.length / 6));
    const latest = snapshots.at(-1);
    const latestDescription =
        kind === "throughput"
            ? `Latest throughput: ${Math.round(latest?.throughput ?? 0).toLocaleString()} jobs per minute.`
            : latest?.runtime === null || latest?.runtime === undefined
              ? "Latest runtime: no observation."
              : `Latest runtime: ${formatDuration(latest.runtime, true)}.`;

    return (
        <div aria-label={`${title} metric chart`} className="h-64 w-full px-3 text-xs" role="img">
            <span className="sr-only">{latestDescription}</span>
            <svg
                aria-hidden="true"
                className="h-full w-full overflow-visible"
                preserveAspectRatio="none"
                viewBox={`0 0 ${chartWidth} ${chartHeight}`}
            >
                {gridSteps.map((step) => {
                    const value = maximum * step;
                    const y = chartTop + plotHeight * (1 - step);

                    return (
                        <g key={step}>
                            <line
                                stroke="var(--chart-grid)"
                                strokeDasharray="3 3"
                                vectorEffect="non-scaling-stroke"
                                x1={chartLeft}
                                x2={chartWidth - chartRight}
                                y1={y}
                                y2={y}
                            />
                            <text
                                fill="var(--muted-foreground)"
                                fontSize="11"
                                textAnchor="end"
                                x={chartLeft - 10}
                                y={y + 4}
                            >
                                {kind === "throughput"
                                    ? Math.round(value).toLocaleString()
                                    : formatDuration(value, true)}
                            </text>
                        </g>
                    );
                })}

                {snapshots.map((snapshot, index) => {
                    const value = values[index] ?? 0;
                    const height = (value / maximum) * plotHeight;
                    const x = chartLeft + slotWidth * index + (slotWidth - barWidth) / 2;
                    const y = chartTop + plotHeight - height;
                    const showLabel = index % labelInterval === 0 || index === snapshots.length - 1;

                    return (
                        <g key={`${snapshot.timestamp}-${index}`}>
                            <rect
                                fill="var(--primary)"
                                fillOpacity="0.82"
                                height={Math.max(height, value > 0 ? 1 : 0)}
                                rx="3"
                                width={barWidth}
                                x={x}
                                y={y}
                            >
                                <title>
                                    {formatTime(snapshot.timestamp)}: {formatValue(kind, value)}
                                </title>
                            </rect>
                            {showLabel ? (
                                <text
                                    fill="var(--muted-foreground)"
                                    fontSize="11"
                                    textAnchor="middle"
                                    x={x + barWidth / 2}
                                    y={chartHeight - 8}
                                >
                                    {formatTime(snapshot.timestamp)}
                                </text>
                            ) : null}
                        </g>
                    );
                })}
            </svg>
        </div>
    );
}
