import { CirclePauseIcon, PowerIcon } from "lucide-react";

import { cn } from "@/lib/utils";
import type { HorizonStatus as HorizonStatusValue } from "@/types/dashboard";

const radius = 6.5;
const circumference = 2 * Math.PI * radius;
const spinnerProgress = 25;

const statusLabels: Record<HorizonStatusValue, string> = {
    running: "Idle, no jobs to process",
    paused: "Horizon is paused",
    partially_paused: "Horizon is partially paused",
    inactive: "Horizon is inactive",
};

export function HorizonStatus({
    status,
    processing = false,
}: {
    status: HorizonStatusValue;
    processing?: boolean;
}) {
    const isProcessing = status === "running" && processing;
    const label = isProcessing ? "Processing jobs" : statusLabels[status];

    return (
        <div
            className={cn(
                "flex size-4 shrink-0 items-center justify-center text-muted-foreground",
                "data-[status=inactive]:opacity-60",
                "data-[status=paused]:text-status-warning-icon",
                "data-[status=partially_paused]:text-status-warning-icon",
            )}
            data-status={status}
            data-activity={status === "running" ? (isProcessing ? "working" : "idle") : undefined}
            role="status"
            aria-label={label}
            title={label}
        >
            {status === "running" ? (
                <svg
                    viewBox="0 0 16 16"
                    fill="none"
                    className={cn(
                        "size-4",
                        isProcessing
                            ? "animate-horizon-status-working text-status-working"
                            : "animate-horizon-status text-status-idle",
                    )}
                    aria-hidden="true"
                >
                    <circle
                        cx="8"
                        cy="8"
                        r={radius}
                        fill="none"
                        stroke={
                            isProcessing
                                ? "var(--status-working-track)"
                                : "var(--status-idle-track)"
                        }
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
                        strokeDashoffset={circumference * (1 - spinnerProgress / 100)}
                        transform="rotate(-90 8 8)"
                    />
                </svg>
            ) : status === "partially_paused" ? (
                <svg viewBox="0 0 16 16" fill="none" className="size-4" aria-hidden="true">
                    <circle
                        cx="8"
                        cy="8"
                        r={radius}
                        fill="none"
                        stroke="var(--status-idle-track)"
                        strokeWidth="2.5"
                    />
                    <circle
                        cx="8"
                        cy="8"
                        r={radius}
                        fill="none"
                        className="text-status-idle"
                        stroke="currentColor"
                        strokeWidth="2.5"
                        strokeLinecap="round"
                        strokeDasharray={circumference}
                        strokeDashoffset={circumference * 0.55}
                        transform="rotate(-90 8 8)"
                    />
                    <path
                        className="fill-current text-status-warning-icon"
                        d="M6.25 5.25a.75.75 0 0 0-.75.75v4a.75.75 0 0 0 1.5 0v-4a.75.75 0 0 0-.75-.75Zm3.5 0a.75.75 0 0 0-.75.75v4a.75.75 0 0 0 1.5 0v-4a.75.75 0 0 0-.75-.75Z"
                    />
                </svg>
            ) : status === "paused" ? (
                <CirclePauseIcon className="size-4" aria-hidden="true" />
            ) : (
                <PowerIcon className="size-4" aria-hidden="true" />
            )}
        </div>
    );
}
