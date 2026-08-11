import { RefreshCwIcon } from "lucide-react";
import type { ReactNode } from "react";

import { TableCell, TableRow } from "@/components/ui/table";

export function NewEntriesTableRow({ columns, onLoad }: { columns: number; onLoad: () => void }) {
    return (
        <TableNoticeRow columns={columns}>
            <p className="inline-flex items-center gap-2">
                <RefreshCwIcon aria-hidden="true" className="size-4 shrink-0" />
                <span>
                    Results have changed.{" "}
                    <button
                        type="button"
                        className="cursor-pointer font-medium text-new-entries-foreground underline decoration-new-entries-foreground/50 underline-offset-4 transition-colors outline-none hover:decoration-new-entries-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                        onClick={onLoad}
                    >
                        Reload
                    </button>
                </span>
            </p>
        </TableNoticeRow>
    );
}

export function TableNoticeRow({ columns, children }: { columns: number; children: ReactNode }) {
    return (
        <TableRow>
            <TableCell
                colSpan={columns}
                className="relative overflow-hidden px-6 py-3 text-center whitespace-normal text-new-entries-foreground"
            >
                <span className="pointer-events-none absolute inset-0 block bg-primary opacity-[var(--new-entries-layer-opacity)]">
                    <svg
                        aria-hidden="true"
                        className="absolute inset-0 size-full text-[var(--new-entries-stripe)]"
                        shapeRendering="crispEdges"
                    >
                        <defs>
                            <pattern
                                id="table-notice-pinstripe"
                                width="4"
                                height="4"
                                patternUnits="userSpaceOnUse"
                                patternTransform="rotate(45)"
                            >
                                <rect width="1" height="4" fill="currentColor" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#table-notice-pinstripe)" />
                    </svg>
                </span>
                <div className="relative text-sm">{children}</div>
            </TableCell>
        </TableRow>
    );
}
