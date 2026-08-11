import type { ComponentProps } from "react";

import { cn } from "@/lib/utils";

function Table({ className, ...props }: ComponentProps<"table">) {
    return <table className={cn("w-full border-collapse text-sm", className)} {...props} />;
}

function TableHeader({
    className,
    topBorder = false,
    ...props
}: ComponentProps<"thead"> & { topBorder?: boolean }) {
    return (
        <thead
            className={cn(
                "sticky top-0 z-10 [&_tr]:border-0",
                topBorder
                    ? "[&_th]:shadow-[inset_0_1px_0_var(--separator),inset_0_-1px_0_var(--separator)]"
                    : "[&_th]:shadow-[inset_0_-1px_0_var(--separator)]",
                className,
            )}
            {...props}
        />
    );
}

function TableBody({ className, ...props }: ComponentProps<"tbody">) {
    return <tbody className={cn("[&_tr:last-child]:border-0", className)} {...props} />;
}

function TableRow({ className, ...props }: ComponentProps<"tr">) {
    return (
        <tr
            className={cn(
                "border-b border-separator transition-colors hover:bg-table-row-hover",
                className,
            )}
            {...props}
        />
    );
}

function TableHead({ className, ...props }: ComponentProps<"th">) {
    return (
        <th
            className={cn(
                "h-10 bg-th px-6 text-left align-middle text-[13px] font-medium whitespace-nowrap text-muted-foreground",
                className,
            )}
            {...props}
        />
    );
}

function TableCell({ className, ...props }: ComponentProps<"td">) {
    return <td className={cn("px-6 py-3 align-middle whitespace-nowrap", className)} {...props} />;
}

export { Table, TableBody, TableCell, TableHead, TableHeader, TableRow };
