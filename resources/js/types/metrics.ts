export type MetricType = "jobs" | "queues";

export type MetricSnapshot = {
    timestamp: number;
    throughput: number;
    runtime: number | null;
};

export type MetricPreview = {
    data: MetricSnapshot[];
    available: boolean;
    message: string | null;
};

export type MetricPreviewPageProps = {
    type: MetricType;
    name: string;
    preview: MetricPreview;
};
