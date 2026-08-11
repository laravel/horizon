import { cva, type VariantProps } from "class-variance-authority";
import type { ComponentProps } from "react";

import { cn } from "@/lib/utils";

const badgeVariants = cva(
    "inline-flex h-5 w-fit items-center rounded-full px-2 text-xs font-medium",
    {
        variants: {
            variant: {
                running: "bg-status-running text-status-running-foreground",
                completed: "bg-status-running text-status-running-foreground",
                paused: "bg-status-paused text-status-paused-foreground",
                pending: "bg-status-paused text-status-paused-foreground",
                delayed: "bg-status-paused text-status-paused-foreground",
                failed: "bg-status-failed text-status-failed-foreground",
                retry: "bg-status-retry text-status-retry-foreground",
                silenced: "bg-muted text-muted-foreground",
                inactive: "bg-muted text-muted-foreground",
            },
        },
        defaultVariants: {
            variant: "inactive",
        },
    },
);

function Badge({
    className,
    variant,
    ...props
}: ComponentProps<"span"> & VariantProps<typeof badgeVariants>) {
    return <span className={cn(badgeVariants({ variant }), className)} {...props} />;
}

export { Badge };
