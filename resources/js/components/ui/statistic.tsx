import { Link } from "@inertiajs/react";
import type { ComponentProps } from "react";

import { cn } from "@/lib/utils";

const statisticClassName = "min-w-0 bg-card px-6 py-4";

function StatisticGrid({ className, ...props }: ComponentProps<"div">) {
    return (
        <div
            data-slot="statistic-grid"
            className={cn("grid gap-px bg-separator", className)}
            {...props}
        />
    );
}

function Statistic({ className, ...props }: ComponentProps<"div">) {
    return <div data-slot="statistic" className={cn(statisticClassName, className)} {...props} />;
}

function StatisticLink({ className, ...props }: ComponentProps<typeof Link>) {
    return (
        <Link
            prefetch
            data-slot="statistic"
            className={cn(
                statisticClassName,
                "block outline-none transition-colors hover:bg-table-row-hover focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring",
                className,
            )}
            {...props}
        />
    );
}

function StatisticLabel({ className, ...props }: ComponentProps<"p">) {
    return (
        <p
            data-slot="statistic-label"
            className={cn("text-[13px] font-medium text-muted-foreground", className)}
            {...props}
        />
    );
}

function StatisticValue({ className, ...props }: ComponentProps<"p">) {
    return (
        <p
            data-slot="statistic-value"
            className={cn(
                "mt-3 text-[1.375rem] font-semibold tracking-tight tabular-nums",
                className,
            )}
            {...props}
        />
    );
}

function StatisticUnit({ className, ...props }: ComponentProps<"span">) {
    return (
        <span
            data-slot="statistic-unit"
            className={cn("ml-2 text-[13px] font-medium text-muted-foreground", className)}
            {...props}
        />
    );
}

function StatisticDetails({ className, ...props }: ComponentProps<"div">) {
    return <div data-slot="statistic-details" className={cn("mt-3.5", className)} {...props} />;
}

function StatisticDetail({ className, ...props }: ComponentProps<"div">) {
    return (
        <div
            data-slot="statistic-detail"
            className={cn(
                "flex justify-between gap-3 border-t border-dashed border-separator py-[7px] text-[13px]",
                className,
            )}
            {...props}
        />
    );
}

function StatisticSupportingText({ className, ...props }: ComponentProps<"p">) {
    return (
        <p
            data-slot="statistic-supporting-text"
            className={cn("mt-0.5 text-[13px] text-muted-foreground", className)}
            {...props}
        />
    );
}

export {
    Statistic,
    StatisticDetail,
    StatisticDetails,
    StatisticGrid,
    StatisticLabel,
    StatisticLink,
    StatisticSupportingText,
    StatisticUnit,
    StatisticValue,
};
