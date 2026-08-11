export type HorizonStatus = "running" | "paused" | "partially_paused" | "inactive";

export type NavigationCounts = {
    monitoring: number;
    metrics: number;
    batches: number | null;
    pending: number;
    completed: number;
    silenced: number;
    failed: number;
};

export type DashboardStats = {
    activeBatches: number | null;
    batchPreviews: Array<{
        id: string;
        name: string;
        progress: number;
    }>;
    failedJobs: number;
    failedJobsPastDay: number | null;
    failedJobsPastHour: number | null;
    hourlyPressure: number | null;
    jobsPerMinute: number;
    maxRuntime: number | null;
    maxThroughput: number | null;
    pendingDelayed: number | null;
    pendingJobs: number | null;
    pendingReadyNow: number | null;
    pendingReserved: number | null;
    pausedMasters: number;
    periods: {
        failedJobs: number;
        recentJobs: number;
        completedJobs: number;
    };
    processes: number;
    queueWithMaxRuntime: string | null;
    queueWithMaxThroughput: string | null;
    recentJobs: number;
    status: HorizonStatus;
    throughput: number;
    wait: Record<string, number>;
};

export type WorkloadSplitQueue = {
    name: string;
    length: number;
    wait: number;
    throughput: number | null;
    paused: boolean;
    pausedUntil: number | null;
};

export type WorkloadItem = {
    name: string;
    connection: string | null;
    length: number;
    processes: number;
    wait: number;
    throughput: number | null;
    paused: boolean;
    pausedUntil: number | null;
    split_queues?: WorkloadSplitQueue[] | null;
};

export type Supervisor = {
    name: string;
    master: string;
    status: string;
    processes: Record<string, number>;
    options: {
        connection?: string;
        queue?: string;
        balance?: string | null;
    };
};

export type Master = {
    name: string;
    status: string;
    supervisors: Supervisor[];
};

export type HorizonPageProps = {
    [key: string]: unknown;
    horizon: {
        baseUrl: string;
        name: string | null;
        pollInterval: number;
        maintenanceMode: boolean;
        status: HorizonStatus;
        processing: boolean;
        capabilities: {
            queuePausing: boolean;
            queuePauseFor: boolean;
        };
    };
    navigationCounts: NavigationCounts;
};

export type DashboardPageProps = HorizonPageProps & {
    stats: DashboardStats;
    workload: WorkloadItem[];
    masters: Master[];
};
