import type { ComponentProps } from "react";

import { cn } from "@/lib/utils";

function Card({ className, ...props }: ComponentProps<"section">) {
    return (
        <section
            className={cn(
                "overflow-hidden rounded-xl border border-panel-border border-t-panel-top bg-card text-sm text-card-foreground shadow-panel",
                className,
            )}
            {...props}
        />
    );
}

function CardHeader({ className, ...props }: ComponentProps<"header">) {
    return (
        <header
            className={cn(
                "flex min-h-[54px] items-center justify-between gap-3 border-b border-separator px-6 py-4",
                className,
            )}
            {...props}
        />
    );
}

function CardTitle({ className, ...props }: ComponentProps<"h2">) {
    return (
        <h2 className={cn("text-[15px] font-semibold tracking-[-0.01em]", className)} {...props} />
    );
}

function CardContent({ className, ...props }: ComponentProps<"div">) {
    return <div className={cn(className)} {...props} />;
}

export { Card, CardContent, CardHeader, CardTitle };
