import { CircleAlertIcon, CircleCheckIcon, CircleXIcon } from "lucide-react";

import { cn } from "@/lib/utils";

const radius = 6.5;
const circumference = 2 * Math.PI * radius;

export function ProgressRing({
    value,
    cancelled = false,
    pendingJobs,
    failedJobs = 0,
    className,
}: {
    value: number;
    cancelled?: boolean;
    pendingJobs?: number;
    failedJobs?: number;
    className?: string;
}) {
    const progress = Math.min(100, Math.max(0, value));
    const doneWithFailures =
        !cancelled &&
        failedJobs > 0 &&
        pendingJobs !== undefined &&
        Math.max(0, pendingJobs - failedJobs) === 0;

    if (cancelled) {
        return (
            <div
                className={cn("inline-flex items-center gap-2", className)}
                role="status"
                aria-label="Batch outcome: Cancelled"
            >
                <CircleXIcon aria-hidden="true" className="size-4 text-status-destructive-icon" />
                <span>Cancelled</span>
            </div>
        );
    }

    if (doneWithFailures) {
        return (
            <div
                className={cn("inline-flex items-center gap-2", className)}
                role="status"
                aria-label={`Batch outcome: Incomplete · ${failedJobs.toLocaleString()} failed`}
            >
                <CircleAlertIcon aria-hidden="true" className="size-4 text-status-warning-icon" />
                <span>Incomplete</span>
            </div>
        );
    }

    const label = progress === 100 ? "Complete" : `${Math.round(progress)}%`;

    return (
        <div
            className={cn("inline-flex items-center gap-2", className)}
            role="progressbar"
            aria-label="Batch progress"
            aria-valuemin={0}
            aria-valuemax={100}
            aria-valuenow={progress}
            aria-valuetext={label}
        >
            {progress === 100 ? (
                <CircleCheckIcon aria-hidden="true" className="size-4 text-status-success-icon" />
            ) : (
                <svg className="size-4 -rotate-90" viewBox="0 0 16 16" aria-hidden="true">
                    <circle
                        cx="8"
                        cy="8"
                        r={radius}
                        fill="none"
                        stroke="var(--muted)"
                        strokeWidth="2.5"
                    />
                    <circle
                        cx="8"
                        cy="8"
                        r={radius}
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2.5"
                        strokeLinecap="round"
                        strokeDasharray={circumference}
                        strokeDashoffset={circumference * (1 - progress / 100)}
                        className="text-primary transition-[stroke-dashoffset] duration-1000 ease-in-out motion-reduce:transition-none"
                    />
                </svg>
            )}
            <span className="min-w-[3ch] text-right tabular-nums">{label}</span>
        </div>
    );
}
